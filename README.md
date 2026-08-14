# Bibliotech Integration Core (`local_bibliotech`)

**Author**: Trevor McCready, Horizon Education Network (<https://www.horizonednet.org>)  
**License**: GNU General Public License v3 or later  
**Software Dependency**: Bibliotech (<https://bibliotechsl.com>)

---

## Overview

`local_bibliotech` is the central core integration plugin connecting Moodle LMS to the **Bibliotech** digital library platform (<https://bibliotechsl.com>). 

Bibliotech offers integrated access to hundreds of expert-curated theological and ministry resources through generous partnerships with leading theological publishers, including:
- Baker Academic
- Wm. B. Eerdmans Publishing Co.
- Fortress Press
- InterVarsity Press
- Ediciones Kairós
- Langham Publishing
- Puma
- Regnum Books
- Society of Biblical Literature (SBL)
- Westminster John Knox Press

---

## Features

- **Automated LTI 1.3 Provisioning**: Programmatically registers and configures Bibliotech as a pre-configured External Tool in Moodle.
- **Automated Access Control**: Automatically creates a site-wide user profile field (`bibliotech_subscriber`) to manage access.
- **Centralized Internationalization**: Provides shared language strings for English (`en`), Spanish (`es`), French (`fr`), Portuguese (`pt`), and Ukrainian (`uk`).
- **Activity Chooser Control**: Filters activity chooser visibility based on user subscription status.

---

## Installation Instructions

1. **Copy/Extract Plugin**:
   Extract or clone the plugin directory into Moodle's `local/` folder:
   ```bash
   moodle/local/bibliotech
   ```
2. **Run Moodle Upgrade**:
   - Log in to your Moodle site as an Administrator.
   - Navigate to **Site Administration > Notifications** (or run `php admin/cli/upgrade.php` via command line).
   - Follow the prompts to complete the installation.
3. **Configure Settings**:
   - Go to **Site Administration > Plugins > Local plugins > Bibliotech**.
   - Enter your Bibliotech Base API URL and Client ID. Saving settings automatically syncs the LTI pre-configured tool.

---

## Companion Plugins & Dependencies

The Bibliotech suite includes companion plugins that require `local_bibliotech`:

1. **`block_bibliotech`** (`blocks/bibliotech`) — Dashboard/Course widget for launching `bibliotech://` or subscribing.
   *Depends on*: `local_bibliotech`
2. **`filter_bibliotech`** (`filter/bibliotech`) — Text filter converting `[bibliotech]` shortcodes into active desktop app links or web reader links.
   *Depends on*: `local_bibliotech`
3. **`tiny_bibliotech`** (`lib/editor/tiny/plugins/bibliotech`) — TinyMCE editor button for picking and inserting Bibliotech resources.
   *Depends on*: `local_bibliotech`, `filter_bibliotech`
