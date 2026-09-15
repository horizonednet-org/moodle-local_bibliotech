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

/**
 * Resolves publication identifiers between UUIDs and numeric publication IDs.
 *
 * @package    local_bibliotech
 * @copyright  2026 Trevor McCready, Horizon Education Network <https://www.horizonednet.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class publication_resolver {

    /** @var array|null Cache of uuid => int id mappings */
    private static ?array $uuidtoid = null;

    /** @var array|null Cache of int id => uuid mappings */
    private static ?array $idtouuid = null;

    /**
     * Initializes the catalog map from bundled catalog_map.json and any cached updates.
     */
    private static function init_catalog(): void {
        if (self::$uuidtoid !== null) {
            return;
        }

        global $CFG;
        self::$uuidtoid = [];
        self::$idtouuid = [];

        // 1. Load bundled catalog JSON if present.
        $jsonfile = $CFG->dirroot . '/local/bibliotech/db/catalog_map.json';
        if (file_exists($jsonfile)) {
            $content = file_get_contents($jsonfile);
            $data = json_decode($content, true);
            if (is_array($data)) {
                foreach ($data as $uuid => $id) {
                    $u = strtolower(trim($uuid));
                    $i = (int)$id;
                    self::$uuidtoid[$u] = $i;
                    self::$idtouuid[$i] = $u;
                }
            }
        }

        // 2. Supplement from mdl_lti records for custom parameters.
        try {
            global $DB;
            $records = $DB->get_records_select('lti', "instructorcustomparameters LIKE '%publication_id%'", null, '', 'id, instructorcustomparameters');
            foreach ($records as $rec) {
                if (!empty($rec->instructorcustomparameters)) {
                    $lines = explode("\n", $rec->instructorcustomparameters);
                    $u = '';
                    $i = 0;
                    foreach ($lines as $line) {
                        $parts = explode('=', trim($line), 2);
                        if (count($parts) === 2) {
                            $k = trim($parts[0]);
                            $v = trim($parts[1]);
                            if ($k === 'uuid' && preg_match('/^[0-9a-f-]{36}$/i', $v)) {
                                $u = strtolower($v);
                            } else if ($k === 'publication_id' && is_numeric($v)) {
                                $i = (int)$v;
                            }
                        }
                    }
                    if (!empty($u) && !empty($i)) {
                        self::$uuidtoid[$u] = $i;
                        self::$idtouuid[$i] = $u;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal if DB not yet available.
        }
    }

    /**
     * Resolves an identifier (UUID or numeric ID) to an integer publication ID.
     *
     * @param string|int|null $identifier
     * @return int Numeric publication ID, or 0 if unresolvable / library launch.
     */
    public static function resolve_id($identifier): int {
        if (empty($identifier)) {
            return 0;
        }

        $idstr = trim((string)$identifier);
        if (is_numeric($idstr)) {
            return (int)$idstr;
        }

        self::init_catalog();
        $clean = strtolower($idstr);
        if (isset(self::$uuidtoid[$clean])) {
            return self::$uuidtoid[$clean];
        }

        return 0;
    }

    /**
     * Resolves an identifier (UUID or numeric ID) to its 36-character UUID string.
     *
     * @param string|int|null $identifier
     * @return string UUID string or empty string if not found.
     */
    public static function resolve_uuid($identifier): string {
        if (empty($identifier)) {
            return '';
        }

        $idstr = trim((string)$identifier);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idstr)) {
            return strtolower($idstr);
        }

        if (is_numeric($idstr)) {
            self::init_catalog();
            $numeric = (int)$idstr;
            if (isset(self::$idtouuid[$numeric])) {
                return self::$idtouuid[$numeric];
            }
        }

        return '';
    }
}
