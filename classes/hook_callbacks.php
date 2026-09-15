<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_bibliotech;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_http_headers;
use core\hook\after_config;

/**
 * Hook callbacks for local_bibliotech.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Intercepts direct /mod/lti/launch.php requests early to prevent unauthorized LTI launches.
     *
     * @param after_config $hook
     */
    public static function after_config(after_config $hook): void {
        global $CFG, $DB, $SESSION;

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script, '/mod/lti/launch.php') !== false) {
            $cmid = optional_param('id', 0, PARAM_INT);
            if ($cmid && isloggedin() && !isguestuser()) {
                require_once($CFG->dirroot . '/course/lib.php');
                $cm = get_coursemodule_from_id('lti', $cmid, 0, false, IGNORE_MISSING);
                if ($cm && self::is_bibliotech_lti($cm)) {
                    if (!\local_bibliotech\access_manager::has_access()) {
                        print_error('access_denied', 'local_bibliotech');
                    }
                }
            }
        } else if (strpos($script, '/mod/lti/auth.php') !== false) {
            $ltimessagehintenc = optional_param('lti_message_hint', '', PARAM_RAW);
            if (!empty($ltimessagehintenc)) {
                $ltimessagehint = json_decode($ltimessagehintenc);
                $launchid = $ltimessagehint->launchid ?? '';
                if (!empty($launchid) && !empty($SESSION->local_bibliotech_launches[$launchid])) {
                    $launchinfo = $SESSION->local_bibliotech_launches[$launchid];
                    unset($SESSION->local_bibliotech_launches[$launchid]);
                    self::handle_bibliotech_auth_launch($launchinfo);
                    exit;
                }
            }
        }
    }

    /**
     * Handles LTI 1.3 authentication request callback for Bibliotech direct publication launches.
     * Generates a signed LtiResourceLinkRequest JWT and submits it directly to Bibliotech's launch endpoint.
     *
     * @param array $launchinfo Information about the launch stored during launch.php.
     */
    public static function handle_bibliotech_auth_launch(array $launchinfo): void {
        global $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $clientid = optional_param('client_id', '', PARAM_TEXT);
        $redirecturi = optional_param('redirect_uri', '', PARAM_URL);
        $loginhint = optional_param('login_hint', '', PARAM_TEXT);
        $state = optional_param('state', '', PARAM_TEXT);
        $nonce = optional_param('nonce', '', PARAM_TEXT);

        $typeid = (int)($launchinfo['typeid'] ?? 0);
        $config = lti_get_type_type_config($typeid);
        if (!$config || $clientid !== $config->lti_clientid) {
            throw new \moodle_exception('invalidrequest', 'error');
        }

        $uris = array_map("trim", explode("\n", $config->lti_redirectionuris));
        if (!in_array($redirecturi, $uris)) {
            throw new \moodle_exception('invalidrequest', 'error');
        }

        if ((string)$loginhint !== (string)$USER->id) {
            throw new \moodle_exception('access_denied', 'local_bibliotech');
        }

        $courseid = (int)($launchinfo['courseid'] ?? SITEID);
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            $course = get_site();
        }
        $PAGE->set_course($course);

        $pubid = $launchinfo['id'] ?? '';
        $title = $launchinfo['title'] ?? 'Bibliotech';

        $instance = new \stdClass();
        $instance->id = 0;
        $instance->typeid = $typeid;
        $instance->course = $course->id;
        $instance->name = $title;
        $instance->intro = '';
        $instance->introformat = FORMAT_HTML;
        $instance->toolurl = $config->lti_toolurl ?? '';
        $instance->securetoolurl = $config->lti_toolurl ?? '';
        $instance->instructorchoicesendname = 1;
        $instance->instructorchoicesendemailaddr = 1;
        $instance->instructorchoiceacceptgrades = 0;
        $instance->instructorchoiceallowroster = 0;
        $instance->resource_link_id = !empty($pubid) ? "bibliotech_pub_{$pubid}" : "bibliotech_library";
        if (!empty($pubid)) {
            $instance->instructorcustomparameters = "publication_id={$pubid}\nid={$pubid}\nuuid={$pubid}";
        } else {
            $instance->instructorcustomparameters = '';
        }
        $instance->servicesalt = 'local_bibliotech';

        list($endpoint, $params) = lti_get_launch_data($instance, $nonce, 'basic-lti-launch-request');

        $r = '<form action="' . s($redirecturi) . "\" name=\"ltiAuthForm\" id=\"ltiAuthForm\" " .
             "method=\"post\" enctype=\"application/x-www-form-urlencoded\">\n";
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $r .= "  <input type=\"hidden\" name=\"" . s($key) . "\" value=\"" . s($value) . "\"/>\n";
            }
        }
        if (!empty($state)) {
            $r .= "  <input type=\"hidden\" name=\"state\" value=\"" . s($state) . "\"/>\n";
        }
        $r .= "</form>\n";
        $r .= "<script type=\"text/javascript\">\n";
        $r .= "  document.ltiAuthForm.submit();\n";
        $r .= "</script>\n";

        echo $r;
        exit;
    }

    /**
     * Intercepts /mod/lti/view.php before headers to block unsubscribed access with a friendly subscription notice.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE, $OUTPUT;

        if ($PAGE->pagetype === 'mod-lti-view') {
            $cm = $PAGE->cm;
            if ($cm && self::is_bibliotech_lti($cm)) {
                if (!\local_bibliotech\access_manager::has_access()) {
                    $subscribeurl = get_config('local_bibliotech', 'subscribe_url') ?: 'https://bibliotechsl.com/subscribe/';
                    $heading = get_string('unauthorized_lti_heading', 'local_bibliotech');
                    $message = get_string('unauthorized_lti_message', 'local_bibliotech');
                    $subtext = get_string('subscribe_now_button', 'local_bibliotech');
                    $returncourse = get_string('return_to_course', 'local_bibliotech');
                    $courseid = !empty($PAGE->course->id) ? $PAGE->course->id : SITEID;
                    $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);

                    $content = \html_writer::start_div('bibliotech-unauthorized-container shadow-sm');
                    $content .= \html_writer::tag('div', '🔒', ['style' => 'font-size: 3rem; margin-bottom: 1rem;']);
                    $content .= \html_writer::tag('h4', s($heading), ['class' => 'font-weight-bold text-dark']);
                    $content .= \html_writer::tag('p', s($message), ['class' => 'text-muted mb-4']);
                    $content .= \html_writer::start_div('d-flex justify-content-center gap-2');
                    $content .= \html_writer::tag('a', s($subtext) . ' <i class="fa fa-external-link ml-1"></i>', [
                        'href' => $subscribeurl,
                        'class' => 'btn btn-warning font-weight-bold mr-2',
                        'target' => '_blank'
                    ]);
                    $content .= \html_writer::tag('a', s($returncourse), [
                        'href' => $courseurl,
                        'class' => 'btn btn-outline-secondary'
                    ]);
                    $content .= \html_writer::end_div();
                    $content .= \html_writer::end_div();

                    echo $OUTPUT->header();
                    echo $content;
                    echo $OUTPUT->footer();
                    exit;
                }
            }
        }
    }

    /**
     * Callback before footer HTML generation to initialize LTI viewer sizing or course view restrictions.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if ($PAGE->pagetype === 'mod-lti-view') {
            $PAGE->requires->js_call_amd('local_bibliotech/lti_viewer', 'init');
        } else if (strpos($PAGE->pagetype, 'course-view-') === 0 && !empty($PAGE->course->id)) {
            $hasaccess = \local_bibliotech\access_manager::has_access();
            if (!$hasaccess) {
                $cmids = self::get_course_bibliotech_cmids((int)$PAGE->course->id);
                if (!empty($cmids)) {
                    $displaymode = get_config('local_bibliotech', 'unsubscribed_cm_display') ?: 'grayout';
                    $subscribeurl = get_config('local_bibliotech', 'subscribe_url') ?: 'https://bibliotechsl.com/subscribe/';
                    $strings = [
                        'subNotice' => get_string('cm_subscription_required', 'local_bibliotech'),
                        'notAvailable' => get_string('cm_not_available_online', 'local_bibliotech'),
                        'subscribeNow' => get_string('subscribe_now_button', 'local_bibliotech'),
                        'modalTitle' => get_string('cm_modal_title', 'local_bibliotech'),
                        'modalBody' => get_string('cm_modal_body', 'local_bibliotech'),
                    ];
                    $PAGE->requires->js_call_amd('local_bibliotech/course_view', 'init', [
                        $cmids,
                        $displaymode,
                        $subscribeurl,
                        $strings
                    ]);
                }
            }
        }
    }

    /**
     * Checks whether a course module represents a Bibliotech LTI tool.
     *
     * @param \cm_info|\stdClass $cm The course module.
     * @return bool
     */
    public static function is_bibliotech_lti($cm): bool {
        global $DB;

        if (!$cm || empty($cm->modname) || $cm->modname !== 'lti') {
            return false;
        }

        $lti = $DB->get_record('lti', ['id' => $cm->instance], 'id, typeid, toolurl', IGNORE_MISSING);
        if (!$lti) {
            return false;
        }

        $bibliotechtypeid = \local_bibliotech\lti_manager::get_type_id();
        if ($bibliotechtypeid && (int)$lti->typeid === (int)$bibliotechtypeid) {
            return true;
        }

        $baseurl = get_config('local_bibliotech', 'base_url');
        if (!empty($baseurl) && !empty($lti->toolurl) && strpos($lti->toolurl, $baseurl) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves all Bibliotech LTI course module IDs for a given course.
     *
     * @param int $courseid Course ID.
     * @return array Array of integer cm IDs.
     */
    public static function get_course_bibliotech_cmids(int $courseid): array {
        global $DB;

        $bibliotechtypeid = \local_bibliotech\lti_manager::get_type_id();
        $baseurl = get_config('local_bibliotech', 'base_url');

        $where = "cm.course = :courseid AND m.name = 'lti'";
        $params = ['courseid' => $courseid];

        $conditions = [];
        if ($bibliotechtypeid) {
            $conditions[] = "l.typeid = :typeid";
            $params['typeid'] = $bibliotechtypeid;
        }
        if (!empty($baseurl)) {
            $conditions[] = $DB->sql_like('l.toolurl', ':baseurl');
            $params['baseurl'] = '%' . $baseurl . '%';
        }

        if (empty($conditions)) {
            return [];
        }

        $where .= " AND (" . implode(" OR ", $conditions) . ")";

        $sql = "SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {lti} l ON l.id = cm.instance
                 WHERE $where";

        return array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
    }
}
