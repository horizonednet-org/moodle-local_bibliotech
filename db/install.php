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
 * Post-installation callback for local_bibliotech.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to execute after plugin installation.
 */
function xmldb_local_bibliotech_install() {
    global $DB;

    // 1. Create Profile Field Category "Bibliotech" if it does not exist.
    $categoryid = $DB->get_field('user_info_category', 'id', ['name' => 'Bibliotech']);
    if (!$categoryid) {
        $category = new \stdClass();
        $category->name = 'Bibliotech';
        $category->sortorder = $DB->count_records('user_info_category') + 1;
        $categoryid = $DB->insert_record('user_info_category', $category);
    }

    // 2. Create Profile Field "bibliotech_subscriber" if it does not exist.
    $fieldshortname = 'bibliotech_subscriber';
    $field = $DB->get_record('user_info_field', ['shortname' => $fieldshortname]);
    if (!$field) {
        $field = new \stdClass();
        $field->shortname = $fieldshortname;
        $field->name = get_string('profile_field_name', 'local_bibliotech');
        $field->datatype = 'checkbox';
        $field->description = get_string('profile_field_name', 'local_bibliotech');
        $field->descriptionformat = FORMAT_HTML;
        $field->categoryid = $categoryid;
        $field->sortorder = 1;
        $field->required = 0;
        $field->locked = 1; // Locked: managed automatically by organization subscriptions.
        $field->visible = 0; // Not visible directly on user profile.
        $field->forceunique = 0;
        $field->signup = 0;
        $field->defaultdata = 0; // Default unchecked (0).
        $field->param1 = '';
        $field->param2 = '';
        $field->param3 = '';
        $field->param4 = '';
        $field->param5 = '';
        $DB->insert_record('user_info_field', $field);
    } else {
        if (empty($field->locked)) {
            $DB->set_field('user_info_field', 'locked', 1, ['id' => $field->id]);
        }
        if ($field->visible != 0) {
            $DB->set_field('user_info_field', 'visible', 0, ['id' => $field->id]);
        }
    }

    // 3. Provision the pre-configured LTI tool type.
    \local_bibliotech\lti_manager::sync_lti_tool();

    return true;
}
