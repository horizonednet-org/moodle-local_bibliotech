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
 * Administration settings for local_bibliotech.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_bibliotech', get_string('pluginname', 'local_bibliotech'));

    // Base API URL.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/base_url',
        get_string('setting_base_url', 'local_bibliotech'),
        get_string('setting_base_url_desc', 'local_bibliotech'),
        '',
        PARAM_URL
    ));

    // Client ID.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/client_id',
        get_string('setting_client_id', 'local_bibliotech'),
        get_string('setting_client_id_desc', 'local_bibliotech'),
        '',
        PARAM_RAW
    ));

    // Public Keyset Path.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/public_keyset',
        get_string('setting_public_keyset', 'local_bibliotech'),
        get_string('setting_public_keyset_desc', 'local_bibliotech'),
        '/.well-known/jwks.json',
        PARAM_RAW
    ));

    // Initiate Login Path.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/initiate_login',
        get_string('setting_initiate_login', 'local_bibliotech'),
        get_string('setting_initiate_login_desc', 'local_bibliotech'),
        '/api/v1/lti/login',
        PARAM_RAW
    ));

    // Redirection URIs Path.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/redirection_uris',
        get_string('setting_redirection_uris', 'local_bibliotech'),
        get_string('setting_redirection_uris_desc', 'local_bibliotech'),
        '/api/v1/lti/launch',
        PARAM_RAW
    ));

    // Content Selection Path.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/content_selection',
        get_string('setting_content_selection', 'local_bibliotech'),
        get_string('setting_content_selection_desc', 'local_bibliotech'),
        '/api/v1/lti/deep-link',
        PARAM_RAW
    ));

    // Custom Parameters.
    $settings->add(new admin_setting_configtextarea(
        'local_bibliotech/custom_parameters',
        get_string('setting_custom_parameters', 'local_bibliotech'),
        get_string('setting_custom_parameters_desc', 'local_bibliotech'),
        'moodle_user_id={$User.id}',
        PARAM_RAW
    ));

    // Profile Field Shortname.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/profile_field',
        get_string('setting_profile_field', 'local_bibliotech'),
        get_string('setting_profile_field_desc', 'local_bibliotech'),
        'bibliotech_subscriber',
        PARAM_ALPHANUMEXT
    ));

    // Subscribe CTA URL.
    $settings->add(new admin_setting_configtext(
        'local_bibliotech/subscribe_url',
        get_string('setting_subscribe_url', 'local_bibliotech'),
        get_string('setting_subscribe_url_desc', 'local_bibliotech'),
        'https://bibliotechsl.com/subscribe/',
        PARAM_URL
    ));

    $ADMIN->add('localplugins', $settings);

    // Sync LTI tool configuration if setting form was just submitted.
    if (defined('ADMIN_THISSAVE') && ADMIN_THISSAVE) {
        \local_bibliotech\lti_manager::sync_lti_tool();
    }
}
