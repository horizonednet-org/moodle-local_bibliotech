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
 * English language strings for local_bibliotech.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bibliotech Integration Core';
$string['tool_description'] = 'Expertly-curated theology and ministry resources from Baker, Eerdmans, Fortress, InterVarsity, Kairós, Langham, Puma, Regnum, SBL, and Westminster.';
$string['profile_field_name'] = 'Bibliotech Subscriber';
$string['profile_field_category'] = 'Bibliotech';
$string['open_app_button'] = 'Open Bibliotech';
$string['open_in_bibliotech'] = 'Open in Bibliotech';
$string['bibliotech_library'] = 'Bibliotech Library';
$string['open_web_reader'] = 'Open Web Reader';
$string['subscribe_cta_heading'] = 'Access Theological Library Resources';
$string['subscribe_cta_text'] = 'Subscribe to Bibliotech to access hundreds of expert-curated theological and ministry resources for a low annual fee.';
$string['subscribe_now_button'] = 'Subscribe Now';
$string['subscription_required_notice'] = 'Bibliotech Subscription Required';
$string['select_content'] = 'Select Bibliotech Resource';
$string['access_denied'] = 'You do not have active access to Bibliotech resources.';
$string['bibliotech:manage'] = 'Manage Bibliotech subscriptions and configuration';

// Admin Settings strings.
$string['setting_base_url'] = 'Base API URL';
$string['setting_base_url_desc'] = 'The primary base URL for the Bibliotech API environment.';
$string['setting_client_id'] = 'LTI Client ID';
$string['setting_client_id_desc'] = 'The LTI 1.3 Client ID registered with Bibliotech.';
$string['setting_public_keyset'] = 'Public Keyset Path / URL';
$string['setting_public_keyset_desc'] = 'Relative path or full URL to the JWKS public keyset.';
$string['setting_initiate_login'] = 'Initiate Login Path / URL';
$string['setting_initiate_login_desc'] = 'Relative path or full URL for LTI 1.3 login initiation.';
$string['setting_redirection_uris'] = 'Redirection URI(s) Path / URL';
$string['setting_redirection_uris_desc'] = 'Relative path or full URL for LTI 1.3 launch callback.';
$string['setting_content_selection'] = 'Content Selection Path / URL';
$string['setting_content_selection_desc'] = 'Relative path or full URL for LTI 1.3 Deep-Linking content selection.';
$string['setting_custom_parameters'] = 'Custom Parameters';
$string['setting_custom_parameters_desc'] = 'Key=Value pairs passed during LTI launches (one per line).';
$string['setting_profile_field'] = 'User Profile Field Shortname';
$string['setting_profile_field_desc'] = 'The shortname of the custom user profile field controlling Bibliotech access.';
$string['setting_subscribe_url'] = 'Subscribe Page URL';
$string['setting_subscribe_url_desc'] = 'The public web page URL where users can purchase a subscription to Bibliotech.';
$string['setting_unsubscribed_cm_display'] = 'Unsubscribed Course Resource Display';
$string['setting_unsubscribed_cm_display_desc'] = 'How Bibliotech LTI activities should appear on course pages for users without an active Bibliotech subscription.';
$string['unsubscribed_cm_display_grayout'] = 'Gray out with subscription notice (recommended)';
$string['unsubscribed_cm_display_hide'] = 'Hide completely from course page';

// Course module restriction strings.
$string['cm_subscription_required'] = 'Bibliotech Subscription Required';
$string['cm_not_available_online'] = 'Not available online';
$string['cm_modal_title'] = 'Bibliotech Subscription Required';
$string['cm_modal_body'] = 'Access to this resource requires an active Bibliotech subscription. Please subscribe to unlock full online reading access.';
$string['unauthorized_lti_heading'] = 'Bibliotech Subscription Required';
$string['unauthorized_lti_message'] = 'You need an active Bibliotech subscription to view this digital publication online. Subscriptions can be acquired individually or through your affiliated organization.';
$string['return_to_course'] = 'Return to Course';

