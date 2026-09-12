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
 * Course view enhancement for Bibliotech resources when viewed by unsubscribed users.
 *
 * @module     local_bibliotech/course_view
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/modal_factory', 'core/modal_events'], function(ModalFactory, ModalEvents) {
    'use strict';

    /**
     * Finds the container element for a given course module ID.
     *
     * @param {number|string} cmid Course module ID.
     * @return {HTMLElement|null} The container element.
     */
    function findCmElement(cmid) {
        return document.getElementById('module-' + cmid) ||
            document.querySelector('[data-id="' + cmid + '"]') ||
            document.querySelector('.activity[data-activity-id="' + cmid + '"]') ||
            null;
    }

    /**
     * Shows a subscription prompt modal when an unsubscribed user clicks a restricted resource.
     *
     * @param {string} title Resource title.
     * @param {string} subscribeUrl URL to subscribe page.
     * @param {Object} strings Language strings map.
     */
    function showSubscriptionModal(title, subscribeUrl, strings) {
        const bodyHtml = '<div class="text-center p-3">' +
            '<div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔒</div>' +
            '<h5 class="font-weight-bold mb-2">' + strings.modalTitle + '</h5>' +
            '<p class="text-muted mb-3">' + strings.modalBody + '</p>' +
            '<a href="' + subscribeUrl + '" class="btn btn-warning font-weight-bold" target="_blank">' +
            strings.subscribeNow + ' <i class="fa fa-external-link ml-1"></i></a>' +
            '</div>';

        ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: title || strings.modalTitle,
            body: bodyHtml,
            large: false
        }).then(function(modal) {
            modal.show();
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
        });
    }

    /**
     * Initializes the course view handler for unsubscribed users.
     *
     * @param {Array} cmids Array of Bibliotech course module IDs.
     * @param {string} displayMode 'grayout' or 'hide'.
     * @param {string} subscribeUrl Subscription checkout URL.
     * @param {Object} strings Localized string map.
     */
    function init(cmids, displayMode, subscribeUrl, strings) {
        if (!Array.isArray(cmids) || cmids.length === 0) {
            return;
        }

        const applyRestrictions = function() {
            cmids.forEach(function(cmid) {
                const cmEl = findCmElement(cmid);
                if (!cmEl) {
                    return;
                }

                if (displayMode === 'hide') {
                    cmEl.style.display = 'none';
                    return;
                }

                // Gray out display mode.
                cmEl.classList.add('bibliotech-restricted-cm');

                // Intercept activity links.
                const links = cmEl.querySelectorAll('a[href*="/mod/lti/view.php?id=' + cmid + '"], a.aal_test');
                links.forEach(function(link) {
                    if (link.dataset.bibliotechIntercepted) {
                        return;
                    }
                    link.dataset.bibliotechIntercepted = 'true';
                    link.setAttribute('title', strings.subNotice + ' — ' + strings.notAvailable);

                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const linkText = link.textContent.trim();
                        showSubscriptionModal(linkText, subscribeUrl, strings);
                    });
                });

                // Add restriction badge if not already added.
                if (!cmEl.querySelector('.bibliotech-cm-restriction-badge')) {
                    const badgeContainer = document.createElement('div');
                    badgeContainer.className = 'bibliotech-cm-restriction-badge';

                    const badge = document.createElement('span');
                    badge.className = 'badge badge-secondary text-muted';
                    badge.textContent = '🔒 ' + strings.subNotice + ' — ' + strings.notAvailable;
                    badgeContainer.appendChild(badge);

                    const subLink = document.createElement('a');
                    subLink.href = subscribeUrl;
                    subLink.target = '_blank';
                    subLink.className = 'bibliotech-cm-subscribe-link';
                    subLink.innerHTML = strings.subscribeNow + ' <i class="fa fa-external-link"></i>';
                    badgeContainer.appendChild(subLink);

                    const textContainer = cmEl.querySelector('.activity-item, .activityinstance, .contentafterlink') || cmEl;
                    textContainer.appendChild(badgeContainer);
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyRestrictions);
        } else {
            applyRestrictions();
        }
    }

    return {
        init: init
    };
});
