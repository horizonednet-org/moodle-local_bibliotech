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

/**
 * Launch endpoint for Bibliotech LTI 1.3 publications with subscriber verification.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

require_login();

// Verify user subscription access.
if (!class_exists('\local_bibliotech\access_manager') || !\local_bibliotech\access_manager::has_access()) {
    print_error('access_denied', 'local_bibliotech');
}

$id = optional_param('id', '', PARAM_RAW); // publication_id or UUID
$uuid = optional_param('uuid', '', PARAM_RAW);
if (empty($id) && !empty($uuid)) {
    $id = $uuid;
}

$numericid = 0;
$resolveduuid = '';
if (class_exists('\local_bibliotech\publication_resolver')) {
    $numericid = \local_bibliotech\publication_resolver::resolve_id(!empty($id) ? $id : $uuid);
    $resolveduuid = \local_bibliotech\publication_resolver::resolve_uuid(!empty($uuid) ? $uuid : $id);
} else if (is_numeric($id)) {
    $numericid = (int)$id;
}

if (empty($numericid) && empty($resolveduuid)) {
    redirect('bibliotech://bookshelf');
}

$courseid = optional_param('course', SITEID, PARAM_INT);
$defaulttitle = (!empty($numericid) || !empty($resolveduuid)) ? 'Bibliotech Publication' : get_string('bibliotech_library', 'local_bibliotech');
$title = optional_param('title', $defaulttitle, PARAM_TEXT);

$typeid = \local_bibliotech\lti_manager::get_type_id();
$config = lti_get_type_type_config($typeid);
if (!$config) {
    print_error('cannotfindtool', 'mod_lti');
}

// Generate unique launch ID for session tracking.
$launchid = 'ltilaunch_bt_' . bin2hex(random_bytes(16));

if (!isset($SESSION->local_bibliotech_launches)) {
    $SESSION->local_bibliotech_launches = [];
}
$SESSION->local_bibliotech_launches[$launchid] = [
    'id' => $numericid,
    'uuid' => $resolveduuid,
    'title' => $title,
    'courseid' => $courseid,
    'typeid' => $typeid,
    'time' => time(),
];

// Clean up stale launch entries older than 1 hour.
foreach ($SESSION->local_bibliotech_launches as $lid => $linfo) {
    if (time() - ($linfo['time'] ?? 0) > 3600) {
        unset($SESSION->local_bibliotech_launches[$lid]);
    }
}

// Prepare OIDC login initiation parameters for Bibliotech.
$endpoint = !empty($config->lti_redirectionuris)
    ? explode("\n", trim($config->lti_redirectionuris))[0]
    : ($config->lti_toolurl ?? '');
$ltihint = [
    'cmid' => 0,
    'launchid' => $launchid,
];
$params = [
    'iss' => $CFG->wwwroot,
    'target_link_uri' => $endpoint,
    'login_hint' => (string)$USER->id,
    'lti_message_hint' => json_encode($ltihint),
    'client_id' => $config->lti_clientid,
    'lti_deployment_id' => (string)$config->typeid,
];

// Output auto-submitting login initiation form to Bibliotech.
$r = "<form action=\"" . s($config->lti_initiatelogin) . "\" name=\"ltiInitiateLoginForm\" id=\"ltiInitiateLoginForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n";
foreach ($params as $key => $value) {
    $r .= "  <input type=\"hidden\" name=\"" . s($key) . "\" value=\"" . s($value) . "\"/>\n";
}
$r .= "</form>\n";
$r .= "<script type=\"text/javascript\">\n";
$r .= "  document.ltiInitiateLoginForm.submit();\n";
$r .= "</script>\n";

echo $r;
exit;
