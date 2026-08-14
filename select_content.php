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
 * Subscriber content selection endpoint for Bibliotech LTI 1.3 Deep Linking.
 * Allows any authorized subscriber (students, teachers, admins) to search and select publications.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

require_login();

// Check if user has active Bibliotech subscription access.
if (!class_exists('\local_bibliotech\access_manager') || !\local_bibliotech\access_manager::has_access()) {
    print_error('access_denied', 'local_bibliotech');
}

$courseid = optional_param('course', SITEID, PARAM_INT);
$typeid = \local_bibliotech\lti_manager::get_type_id();

$config = lti_get_type_type_config($typeid);
if (!$config) {
    print_error('cannotfindtool', 'mod_lti');
}

$title = optional_param('title', 'Select Bibliotech Publication', PARAM_TEXT);
$text = optional_param('text', '', PARAM_RAW);

// Initiate LTI 1.3 Deep Linking request for the subscriber.
if (!isset($SESSION->lti_initiatelogin_status)) {
    echo lti_initiate_login($courseid, 0, null, $config, 'ContentItemSelectionRequest', $title, $text);
    exit;
} else {
    unset($SESSION->lti_initiatelogin_status);
}
