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
 * Upgrade steps for local_bibliotech.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute upgrade steps.
 *
 * @param int $oldversion Old plugin version.
 * @return bool True on success.
 */
function xmldb_local_bibliotech_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026081302) {
        // Re-sync LTI tool configuration with correct keytype definition.
        \local_bibliotech\lti_manager::sync_lti_tool();
        upgrade_plugin_savepoint(true, 2026081302, 'local', 'bibliotech');
    }

    if ($oldversion < 2026081400) {
        // Lock bibliotech_subscriber custom profile field.
        $DB->set_field('user_info_field', 'locked', 1, ['shortname' => 'bibliotech_subscriber']);
        upgrade_plugin_savepoint(true, 2026081400, 'local', 'bibliotech');
    }

    if ($oldversion < 2026091100) {
        // Set bibliotech_subscriber custom profile field to visible = 0 (hidden from user profiles).
        $DB->set_field('user_info_field', 'visible', 0, ['shortname' => 'bibliotech_subscriber']);
        upgrade_plugin_savepoint(true, 2026091100, 'local', 'bibliotech');
    }

    return true;
}
