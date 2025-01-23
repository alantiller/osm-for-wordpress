# Online Scout Manager for WordPress

**This WordPress plugin is not affiliated with Online Scout Manager in any way.**

The Online Scout Manager (OSM) for WordPress plugin allows you to display programmes and events directly from OSM on your website. The plugin integrates with the OSM API to fetch and display data from your sections, ensuring an up-to-date representation of your scouting activities.

---

## Features

- Display section programmes and events using shortcodes.
- Manage sections, authentication, and cache directly from the WordPress admin.
- Dynamically retrieve and cache data from OSM for optimal performance.

---

## Installation

1. Download the latest release of the plugin from [here](https://github.com/alantiller/osm-for-wordpress/releases)
2. Log in to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Select the zip file and click **Install Now**.
5. Activate the plugin.

---

## Setup

### Step 1: Generate Your Client ID and Secret

To authenticate with the OSM API, you'll need a **Client ID** and **Client Secret**. Follow these steps to generate them:

1. Log in to [Online Scout Manager (OSM)](https://www.onlinescoutmanager.co.uk).
2. Expand the **Settings** menu at the bottom of the page.
3. Select **My Account Details**.
4. Click **Developer Tools** from the menu on the left-hand side.
5. Click **Create Application**.
6. Provide a name for your application and click **Save**.
7. The **Client ID** and **Client Secret** will be displayed **once only**. Make sure to copy and save them securely.

### Step 2: Authenticate with OSM

1. Go to **OSM Settings** in the WordPress admin menu.
2. Click on the **Authentication** tab.
3. Enter your **Client ID** and **Client Secret**.
4. Click **Save & Authenticate** to validate your credentials.

### Step 3: Enable Sections

1. Navigate to the **Sections Enabled** tab.
2. Select the sections you want to enable by ticking the checkboxes.
3. Click **Save Sections**. The plugin will automatically fetch and cache the current term for each enabled section.

### Step 4: Verify Configuration

1. Go to the **General** tab.
2. Verify that your enabled sections are listed along with their current term IDs.
3. If needed, use the **Purge Cache** or **Reset Configuration** options.

---

## Usage

### Shortcodes

Use the following shortcodes to display OSM data on your website, you'll be able to find the Section ID in the general tab of the settings menu:

#### Programme Shortcode

```plaintext
[osm_programme sectionid="SECTION_ID" futureonly="true"]
```

- **sectionid** (required): The ID of the section to display.
- **futureonly** (optional): Set to `true` to show only future events. Default: `false`.

Example:

```plaintext
[osm_programme sectionid="12345" futureonly="true"]
```

#### Events Shortcode

```plaintext
[osm_events sectionid="SECTION_ID" futureonly="true"]
```

- **sectionid** (required): The ID of the section to display.
- **futureonly** (optional): Set to `true` to show only future events. Default: `false`.

Example:

```plaintext
[osm_events sectionid="67890" futureonly="false"]
```

---

## Admin Features

- **General Tab**:
  - View enabled sections and their current term IDs.
  - Purge cached data.
  - Reset all plugin configuration.

- **Sections Enabled Tab**:
  - List all available sections retrieved from OSM.
  - Enable or disable specific sections.

- **Authentication Tab**:
  - Enter and manage your OSM **Client ID** and **Client Secret**.

- **Advanced Options**:
  - **Date Format**: Customize the date format used in the plugin. Default: `d/m/Y`.
  - **Time Format**: Customize the time format used in the plugin. Default: `H:i`.

---

## Caching

The plugin caches the following data to reduce API calls:
- **Sections**: Cached for 24 hours.
- **Current Term IDs**: Cached for 24 hours per section.
- **Programmes and Events**: Cached for 24 hours per section and term.

Cached data is automatically refreshed when it expires.

---

## Reset Configuration

If you need to reset the plugin:
1. Navigate to the **General** tab.
2. Click the **Reset Configuration** button.
3. All authentication details, enabled sections, and cached data will be deleted.

---

## Frequently Asked Questions

### 1. How often is the data refreshed?
Data is cached for 24 hours and is refreshed automatically when the cache expires.

---

## License

This plugin is licensed under the MIT License. See the LICENSE file for details.

---

## Disclaimer

**This plugin is not affiliated with Online Scout Manager. Use it at your own risk.**
