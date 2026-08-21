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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Access manager for Bibliotech user permission verification.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_manager {

    /**
     * Checks if the given user (or current user) has active access to Bibliotech resources.
     *
     * @param int|null $userid User ID to check, or null for current global $USER.
     * @return bool True if authorized, false otherwise.
     */
    public static function has_access(?int $userid = null): bool {
        global $USER, $DB;

        if (empty($userid) || $userid === (int)$USER->id) {
            $user = $USER;
        } else {
            $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
            if (!$user) {
                return false;
            }
        }

        if (isguestuser($user) || empty($user->id)) {
            return false;
        }

        $fieldshortname = get_config('local_bibliotech', 'profile_field') ?: 'bibliotech_subscriber';

        profile_load_custom_fields($user);

        if (isset($user->profile[$fieldshortname])) {
            return !empty($user->profile[$fieldshortname]);
        }

        // Direct DB fallback if custom fields object wasn't populated as array key.
        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE f.shortname = :shortname AND d.userid = :userid";
        $val = $DB->get_field_sql($sql, ['shortname' => $fieldshortname, 'userid' => $user->id]);

        return !empty($val);
    }

    /**
     * Checks if the given user (or current user) has capability to manage Bibliotech subscriber status.
     *
     * @param int|null $userid User ID to check, or null for current global $USER.
     * @return bool True if authorized to manage, false otherwise.
     */
    public static function can_manage(?int $userid = null): bool {
        $context = \context_system::instance();
        return has_capability('local/bibliotech:manage', $context, $userid);
    }
}
