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
 * Responsive LTI iframe viewer helper.
 *
 * @module     local_bibliotech/lti_viewer
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    function init() {
        var frame = document.getElementById('contentframe');
        if (!frame) {
            return;
        }

        function adjustHeight() {
            var rect = frame.getBoundingClientRect();
            var windowHeight = window.innerHeight || document.documentElement.clientHeight;
            var availableHeight = windowHeight - rect.top - 30;
            var targetHeight = Math.max(availableHeight, 850);
            frame.style.setProperty('height', targetHeight + 'px', 'important');
        }

        adjustHeight();
        window.addEventListener('resize', adjustHeight);
        window.addEventListener('orientationchange', adjustHeight);

        // Also listen for LTI 1.3 frameResize postMessage
        window.addEventListener('message', function(event) {
            if (!event.data) {
                return;
            }
            var data = event.data;
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    return;
                }
            }
            if ((data.subject === 'lti.frameResize' || data.type === 'lti.frameResize' || data.event === 'resize') && data.height) {
                var newHeight = parseInt(data.height, 10);
                if (newHeight > 0) {
                    frame.style.setProperty('height', newHeight + 'px', 'important');
                }
            }
        });
    }

    return {
        init: init
    };
});
