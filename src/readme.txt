=== Online Scout Manager ===
Contributors: alantiller
Tags: scouts, programme, events, waiting list, patrols
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.1
License: MIT
License URI: https://opensource.org/license/mit/

Display Online Scout Manager programmes, events and patrols, and accept waiting-list applications with privacy controls.

== Description ==

Online Scout Manager integration for WordPress shortcodes and Gutenberg blocks. Enable website sections using native WordPress tiles. Section Points controls each section’s first/last-name formats and excluded members. Sections has independent programme/events and Section Points switches. Waiting-list applications use one selected section and the shared Client Credentials connection.

This plugin is not affiliated with Online Scout Manager.

= External service: Online Scout Manager =

The plugin connects to https://www.onlinescoutmanager.co.uk to authenticate and retrieve the sections, current terms, programmes, events and patrol data selected by administrators. Credentials and tokens stay on the server. Patrol responses are not cached; only member-name formats approved in the Section Points settings are displayed. Other personal fields are excluded from patrol output.

When a visitor submits the waiting-list form, the plugin sends the child's first name, last name and date of birth and the adult's first name, last name and email, plus optional notes and helping answers, to OSM. The receiving section is selected on Waiting List. The visitor must acknowledge how the application details are used; a configured site privacy notice is linked. Application fields are processed for the current request and are not stored in WordPress, logged or emailed by this plugin. Opaque submission tokens and salted hourly connection identifiers are stored temporarily to deter duplicate submissions and abuse; cleanup is scheduled through WordPress cron.

Selected WordPress users can receive individual notification emails through the site’s existing mail service. These emails contain an alert and OSM link, with no applicant fields. No OSM Helper or other application-submission service is used. Your organisation must review its own privacy notice, lawful basis, hosting/logging policies and OSM account permissions.

Service information and policies: https://www.onlinescoutmanager.co.uk/

== Installation ==

1. Upload the plugin folder to wp-content/plugins, or install the release ZIP through Plugins > Add New.
2. Activate the plugin and open Settings > Online Scout Manager. Only Authentication is shown until you connect.
3. Create an application in OSM Developer Tools and enable Client Credentials Grant. The plugin requests section:programme:read, section:event:read, section:member:read and section:member:write by default.
4. Save your credentials and enable website sections on Sections.
5. Use Section Points, Waiting List, Notifications and Advanced to configure each feature. Reset Auth is on Advanced.
6. Save a waiting-list section to enable applications. Use Clear waiting list to disable them.

== Frequently Asked Questions ==

= How do I show more than one section? =

Use sectionids="12345,67890" with the programme, event or patrol shortcode. Gutenberg blocks support the same selection.

= Does the waiting-list shortcode need a section ID? =

No. Use [osm_waiting_list]. The receiving section is selected on Waiting List.

= Can I limit the member names on the website? =

Yes. Configure first and last names per section as hidden, full or initial. Both hidden displays patrol names only. The administrator-only member list has Exclude checkboxes; excluded members are omitted entirely. Missing members retain their saved excluded IDs, even when the roster cannot be loaded.

= Does the form store applications on this website? =

The plugin sends applications directly to OSM without persisting application fields in WordPress. Other plugins or your hosting environment may have separate logging policies.

== Changelog ==

= 1.1.1 =
* Restyle programme/event tables and patrol cards with consistent surfaces, Scouts Purple headers and white text.
* Display current patrol points beside the patrol name without extra API requests.
* Move Online Scout Manager into the WordPress Settings menu.
* Split settings into Overview, Sections, Section Points, Waiting List, Notifications, Advanced and Documentation with left-hand icon navigation and a row-based white settings panel.
* Replace the Sections grid with full-width bordered rows and aligned table columns.
* Compact Section Points controls and place live website-name previews beside members, including hidden/excluded states.
* Strengthen borders while retaining member exclusions and responsive table scrolling.
* Show only Authentication until connected; hide it after successful authentication and move Reset Auth to Advanced.
* Add programme/event grid tiles (layout="grid") and full-width vertical tiles (layout="vertical"), also selectable in Gutenberg. Existing layout="tiles" remains a grid alias.
* Add optional combined programme/event tables with a labelled row for each section, through combine="true" or the Gutenberg toggle.
* Remove trailing table spacing and isolate section failures so successful feeds remain visible.
* Preserve shortcodes, block identifiers, saved settings and member exclusions.


= 1.1.0 =
* Add Gutenberg blocks and multiple-section output for programmes, events and Section Points while preserving existing shortcode identifiers.
* Arrange section tiles three across on wide screens, with independent Show Programme and Events and Show Section Points rows.
* Add per-section first/last-name formats, a live preview and an administrator-only member list with member exclusions.
* Rename the options page to Configuration and display observed API quota/countdown on General.
* Keep exclusions when a roster is unavailable or a section display is disabled; omit excluded members before rendering.
* Request programme read, event read, member read and member write scopes through Client Credentials by default.
* Add separate day/month/year birth-date inputs with server validation, optional parent notes and a helping question.
* Add a Notifications tile to choose WordPress users for private, minimal application alerts.
* Resolve the receiving section’s Notes field from OSM and report partial saves honestly.
* Add waiting-list applications using the configured section and a Clear waiting list button to disable them.
* Style waiting-list forms with a responsive default layout and scoped CSS variables for theme customisation.
* Save adult contact details through the custom-data update endpoint with explicit section/member context.
* Restore detailed authentication instructions and keep configuration and maintenance buttons visible.
* Simplify the plugin to shortcodes and Gutenberg blocks, removing Divi/Elementor adapters.
* Rename the plugin/package/text domain to Online Scout Manager (online-scout-manager); resolve Plugin Check findings.
