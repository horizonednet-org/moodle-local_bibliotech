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

require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * LTI manager for programmatically managing Bibliotech's mod_lti external tool registration.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_manager {

    /**
     * Tool name constant.
     */
    const TOOL_NAME = 'Bibliotech';

    /**
     * Synchronizes or creates the pre-configured LTI tool type in Moodle.
     *
     * @return int|bool Type ID on success or false on failure.
     */
    public static function sync_lti_tool() {
        global $DB;

        $baseurl = get_config('local_bibliotech', 'base_url');
        $clientid = get_config('local_bibliotech', 'client_id');

        if (empty($baseurl) || empty($clientid)) {
            return false;
        }

        $publickeyset = get_config('local_bibliotech', 'public_keyset') ?: '/.well-known/jwks.json';
        $initiatelogin = get_config('local_bibliotech', 'initiate_login') ?: '/api/v1/lti/login';
        $redirectionuris = get_config('local_bibliotech', 'redirection_uris') ?: '/api/v1/lti/launch';
        $contentselection = get_config('local_bibliotech', 'content_selection') ?: '/api/v1/lti/deep-link';
        $customparams = get_config('local_bibliotech', 'custom_parameters') ?: 'moodle_user_id={$User.id}';

        $fullpublickeyset = (strpos($publickeyset, 'http') === 0) ? $publickeyset : rtrim($baseurl, '/') . '/' . ltrim($publickeyset, '/');
        $fullinitiatelogin = (strpos($initiatelogin, 'http') === 0) ? $initiatelogin : rtrim($baseurl, '/') . '/' . ltrim($initiatelogin, '/');
        $fullredirectionuris = (strpos($redirectionuris, 'http') === 0) ? $redirectionuris : rtrim($baseurl, '/') . '/' . ltrim($redirectionuris, '/');
        $fullcontentselection = (strpos($contentselection, 'http') === 0) ? $contentselection : rtrim($baseurl, '/') . '/' . ltrim($contentselection, '/');

        // Look up existing type record by name.
        $existing = $DB->get_record('lti_types', ['name' => self::TOOL_NAME]);

        $type = new \stdClass();
        $type->name = self::TOOL_NAME;
        $type->baseurl = $baseurl;
        $type->description = get_string('tool_description', 'local_bibliotech');
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->ltiversion = '1.3.0';
        $type->clientid = $clientid;
        $type->coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;

        $config = new \stdClass();
        $config->lti_toolurl = $baseurl;
        $config->lti_description = get_string('tool_description', 'local_bibliotech');
        $config->lti_ltiversion = '1.3.0';
        $config->lti_clientid = $clientid;
        $config->lti_keytype = 'JWKS_KEYSET';
        $config->lti_publickeyset = $fullpublickeyset;
        $config->lti_initiatelogin = $fullinitiatelogin;
        $config->lti_redirectionuris = $fullredirectionuris;
        $config->lti_customparameters = $customparams;
        $config->lti_coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_EMBED;
        $config->lti_contentitem = 1;
        $config->lti_deeplinkingurl = $fullcontentselection;
        $config->lti_icon = 'https://storage.googleapis.com/bibliotech-thumbnail/BiblioTech.png';
        $config->lti_secureicon = 'https://storage.googleapis.com/bibliotech-thumbnail/BiblioTech.png';

        if ($existing) {
            $type->id = $existing->id;
            lti_update_type($type, $config);
            return $existing->id;
        } else {
            return lti_add_type($type, $config);
        }
    }

    /**
     * Gets the Bibliotech LTI tool type ID from the database.
     *
     * @return int Tool type ID.
     */
    public static function get_type_id(): int {
        global $DB;
        $type = $DB->get_record('lti_types', ['name' => self::TOOL_NAME], 'id');
        if ($type) {
            return (int)$type->id;
        }
        $id = self::sync_lti_tool();
        return $id ? (int)$id : 0;
    }

    /**
     * Constructs the Moodle LTI content item selection URL for Bibliotech.
     *
     * @param int|null $courseid Course ID (defaults to global $COURSE->id or SITEID).
     * @return string Deep link URL.
     */
    public static function get_deeplink_url(?int $courseid = null): string {
        global $COURSE;
        $typeid = self::get_type_id();
        if (empty($courseid)) {
            $courseid = !empty($COURSE->id) ? $COURSE->id : SITEID;
        }
        $url = new \moodle_url('/local/bibliotech/select_content.php', [
            'id' => $typeid,
            'course' => $courseid,
        ]);
        return $url->out(false);
    }
}
