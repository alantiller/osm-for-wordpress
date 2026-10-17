# Online Scout Manager

Display Online Scout Manager programmes, events and patrols and accept waiting-list applications in WordPress shortcodes and Gutenberg blocks. Requires WordPress 6.7+ and PHP 8.0+. This plugin is not affiliated with Online Scout Manager.

The installable package is `online-scout-manager/online-scout-manager.php`. If replacing an existing installation under the old name, deactivate that copy before activating this package. Saved settings and content identifiers are retained.

## Settings

Every settings section uses native WordPress tiles with consistent spacing. Admin asset versions follow the file modification time so fixes load while the release stays at 1.1.0.

- **General:** sections and shortcode documentation in the left column; settings/help tiles in the right. Enable website displays here.
- **Configuration:** per-section member-name formats and exclusions, date/time formats, waiting-list selection and cache/reset tiles.
- **Authentication:** application credentials and detailed setup instructions in one tile.

Old General/Sections, Shortcodes, Advanced Options and Public Data bookmarks are routed to their replacement pages. There is no separate Shortcodes tab or Patrol API permission field.

## Authentication

Create an OSM application under Settings → My Account Details → Developer Tools. Enable **Client Credentials Grant** and save the Client ID/Secret under Authentication. The detailed instructions on that tab explain how to create the application and reveal the credentials.

Every token request uses Client Credentials and all four scopes by default:

- `section:programme:read`
- `section:event:read`
- `section:member:read`
- `section:member:write`

Grant the application access to the sections you select in OSM. The same server-only token supports feeds, patrols and waiting-list writes. Credential changes reset publication choices and the waiting-list destination.

## Section Points and name formats

On **General**, use each section’s **Show Programme and Events** and **Show Section Points** rows independently. There is no additional website-display switch.

In **Configuration → Section Points**, select **Hidden**, **Full name** or **Initial only** independently for first and last names for each section:

| First name | Last name | Example |
| --- | --- | --- |
| Full name | Full name | Alex Taylor |
| Full name | Hidden | Alex |
| Hidden | Full name | Taylor |
| Initial only | Initial only | A. T. |
| Full name | Initial only | Alex T. |
| Initial only | Full name | A. Taylor |
| Hidden | Hidden | Patrol names only |

Each enabled Section Points section has an administrator-only member table. Tick **Exclude** beside any member to omit them completely from public member lists. IDs absent from the current roster remain editable under **Other excluded member IDs** so an OSM outage or a member leaving cannot silently remove their exclusion. Each section also has a live Preview row that applies unsaved name formats and exclusions to the currently loaded member list. Saving publishes those choices. Only exclusion IDs and formatting choices are stored; the roster is not cached. Unknown member IDs are omitted whenever exclusions exist.

Member names are hidden by default; existing global name choices remain the fallback until a section is configured. The API adapter immediately applies these settings and returns only approved display strings. No raw patrol response or member names are cached. Leader/young-leader pseudo-patrols and inactive members are excluded. Member IDs, contact information, dates of birth, photos, ages and ranks are not published.

These controls limit what appears on the website; they do not establish a lawful basis for publication. Review the settings, programme/event content and your organisation's privacy notice before making personal data public.

## Shortcodes and Gutenberg blocks

```text
[osm_programme sectionids="12345,67890" futureonly="true"]
[osm_events sectionids="12345,67890" futureonly="false"]
[osm_patrols sectionids="12345,67890"]
[osm_waiting_list]
```

Programme/event section IDs require **Show Programme and Events**. The existing `[osm_patrols]` shortcode and `osm/patrols` block retain their identifiers but now display the **Section Points** label and require **Show Section Points**, independently of programme/events. `sectionid="12345"` remains supported; comma-separated `sectionids` takes precedence. Selections are deduplicated and multiple feeds are grouped under section headings. One failed feed does not hide another section's successful feed.

`futureonly` defaults to false and includes today in the WordPress timezone when true. Gutenberg blocks and shortcodes share rendering and member-name controls.

Version 1.1.0 removes Divi and Elementor integrations. Use the equivalent shortcodes or Gutenberg blocks for pages that used OSM builder modules/widgets.

## Waiting-list form

1. Select the receiving section in **Configuration → Waiting list** and save. This enables applications independently of feed publication and does not require a current term.
2. Add `[osm_waiting_list]` or the corresponding Gutenberg block. Content uses the saved section automatically.
3. Use **Clear waiting list** to remove the destination and disable applications, including submissions from forms already opened in a browser.

There are no hosted-form URL or delivery-mode options. Legacy hosted URLs are ignored. If a WordPress privacy-policy page is configured, the form links to it; otherwise the required acknowledgement explains that details are sent to OSM for the group to manage the application. Keep your organisation's privacy notice accurate.

The form collects only the child's name/date of birth and the adult's name/email, plus an unchecked data-use acknowledgement. Application fields remain in memory for the request and are sent directly to OSM; this plugin does not save, cache, log or email them. Hosting and other plugins may have separate logging policies. No external OSM Helper service is used.

Nonces, a honeypot, signed expiring one-use tokens and an atomic limit of five attempts per server connection/hour deter basic abuse and duplicate submissions. Opaque token hashes and hourly salted IP-derived rate keys are retained temporarily, with deletion scheduled through WordPress cron. No raw IP is stored in those keys. Browser JavaScript refreshes tokens independently of cached HTML. Editor previews disable submission.

OSM member creation and contact update are separate operations. Primary Contact 1 now uses `/ext/customdata/?action=update&section_id=…`, with `associated_id` bound to the confirmed child and only the adult first/last name and email transmitted. This replaces the older member-contact update route. The plugin never retries creation automatically. A failed contact update after confirmed creation produces a partial-result message; uncertain receipt asks the applicant to contact the group before applying again.

The member/contact endpoints follow [the fork's earlier direct implementation](https://github.com/jonbloor/osm-for-wordpress/blob/9ecd8567ea1bf6e0ec093dd5df2f21030e67ccfe/src/includes/class-osm-api.php). The patrol shape follows the maintained [observed OSM API schema](https://github.com/newcastlescouts/osm-api-docs/blob/main/openapi.yaml). These are implementation references, not an official OSM API guarantee. Account-specific permissions and live OSM responses still need staging verification; automated tests never send real applications.

### Date of birth, notes and helping

Date of birth uses separate Day, Month and Year text inputs (dd / mm / yyyy), with numeric keyboards on mobile. Server validation rejects missing parts, impossible dates, future dates and years before 1900, then converts to OSM’s ISO date format. Already-open older forms remain compatible.

Visitors can add an optional note (up to 2,000 characters) and answer whether they are willing to help: Yes, No or “I’d like to find out more”. There is no preselected volunteering answer. Both are saved together to the new member’s Notes field. The receiving OSM section needs exactly one identifiable text field named Notes; field/group IDs are resolved from OSM, never hard-coded. Existing notes are preserved. A missing or ambiguous field produces a clear partial result after child/adult creation, rather than falsely claiming the answers were saved. The form advises applicants not to enter medical details in free-text notes.

The fork’s current parent-note implementation relies on an external helper and a mapped Notes field. This plugin keeps direct OSM writes through custom-data lookup/updateColumn; no helper service is introduced.

### Notifications

In **Configuration → Notifications**, tick the WordPress users who should receive waiting-list alerts and save. Leaving every user unticked disables email. Only user IDs are stored; current addresses are resolved when sending. Each recipient gets a separate plain-text email, keeping recipients’ addresses private from one another.

An alert is sent after confirmed child creation, including partial adult/notes saves. It links to OSM and excludes applicant names, dates of birth, contact details, notes and helping answers. Duplicate form submissions do not resend alerts. Delivery uses the site’s existing `wp_mail` setup; the tile reports the latest accepted/failed counts, which do not guarantee inbox delivery. A mail failure never reverses OSM creation or asks the visitor to apply again. No email is sent by simply saving recipient settings.

### Waiting-list design

The form includes a responsive card, grouped child/adult details, a two-column name layout on wide screens, visible focus styles and clear submission/validation states. Theme typography is inherited. Use your theme’s custom CSS to adjust the scoped variables; no application fields or HTML need editing:

```css
.osm-waiting-list {
    --osm-form-accent: #2271b1;
    --osm-form-button-text: #ffffff;
    --osm-form-background: #ffffff;
    --osm-form-text: #1d2327;
    --osm-form-border: #c3c4c7;
    --osm-form-radius: 8px;
    --osm-form-max-width: 44rem;
}
```

Choose readable colour combinations. These variables affect only the waiting-list form. Frontend CSS uses a changing file version so updated styling loads while release 1.1.0 is retained.

## API usage

The top tile in General’s right column shows requests used, remaining quota and a reset countdown from the last complete OSM `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `X-RateLimit-Reset` headers. Reset is interpreted as seconds until renewal, following the [observed OSM response headers](https://opensource.newcastlescouts.org.uk/). This remains an account-specific staging check.

The dashboard reads stored numeric quota metadata without making extra API calls. Cached feeds and tokens do not increase usage. Error responses, including HTTP 429, can update the quota. Missing/malformed headers retain the last valid observation. Before the first valid observation the tile says Unavailable; after the reset deadline it waits for the next OSM response rather than inventing a fresh quota. Purging the feed cache retains quota history; credential changes/reset clear account-specific observations.

## Cache and reset

Sections, programmes and events are cached for 24 hours; current terms expire by the next WordPress-local midnight. The shared four-scope token is cached below its lifetime. Patrol responses and applications are not cached. Purge uses WordPress's option API to invalidate persistent object caches too.

**Configuration → Cache and reset** contains Purge cache and Reset configuration. Administrator capabilities and action nonces protect every mutation. Clearing the waiting-list selection disables the public form without changing the application credentials.

## Build and verification

Assets are committed. With Node 20+ and PHP available:

```sh
cd src
npm run build
npm run check
npm test
cd ..
php tests/run.php
php tests/admin.php
php tests/waiting-list.php
php tests/usage.php
php tests/application-details.php
php tools/package-plugin.php
```

The package tool lives outside the installable plugin. Runtime Gutenberg output uses a form-aware WordPress KSES allowlist. Templates block direct access. Authentication and API requests stay on the server; all settings changes verify administrator capabilities and action nonces.

See [development notes](docs/development-notes.md) and [Plugin Check results](docs/plugin-check-results.md) for verification and limitations. The renamed package has no Plugin Check findings in the isolated verification.

## License

MIT. See [LICENCE](LICENCE).
