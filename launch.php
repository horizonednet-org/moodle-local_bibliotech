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
$courseid = optional_param('course', SITEID, PARAM_INT);
$defaulttitle = !empty($id) ? 'Bibliotech Publication' : get_string('bibliotech_library', 'local_bibliotech');
$title = optional_param('title', $defaulttitle, PARAM_TEXT);

$typeid = \local_bibliotech\lti_manager::get_type_id();
$config = lti_get_type_type_config($typeid);
if (!$config) {
    print_error('cannotfindtool', 'mod_lti');
}

// Construct LTI instance for authenticated LTI 1.3 launch request.
$instance = new stdClass();
$instance->id = 0;
$instance->typeid = $typeid;
$instance->course = $courseid;
$instance->name = $title;
$instance->intro = '';
$instance->introformat = FORMAT_HTML;
$instance->toolurl = $config->lti_toolurl ?? '';
$instance->securetoolurl = $config->lti_toolurl ?? '';
if (!empty($id)) {
    $instance->instructorcustomparameters = "publication_id={$id}";
} else {
    $instance->instructorcustomparameters = '';
}
$instance->servicesalt = 'local_bibliotech';

// Initiate authenticated LTI 1.3 launch request.
if (!isset($SESSION->lti_initiatelogin_status)) {
    echo lti_initiate_login($courseid, 0, $instance, $config, 'basic-lti-launch-request', $title, '', 0);
    exit;
} else {
    unset($SESSION->lti_initiatelogin_status);
}
