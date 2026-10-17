<?php
declare(strict_types=1);
/** Administrator settings content; supplied by OSM_Admin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="postbox osm-settings-tile" id="osm-shortcodes" aria-labelledby="osm-shortcodes-title">
                <h2 id="osm-shortcodes-title">Shortcode documentation</h2>
                <h3>Programme</h3><p><code>[osm_programme sectionids="12345,67890" futureonly="true"]</code></p>
                <h3>Events</h3><p><code>[osm_events sectionids="12345,67890" futureonly="true"]</code></p>
                <h3>Section Points</h3><p><code>[osm_patrols sectionids="12345,67890"]</code></p><p>Patrols and member-name formats follow the Section Points settings.</p>
                <h3>Waiting-list application</h3><p><code>[osm_waiting_list]</code></p><p>Uses the section selected in Waiting List. No section IDs are required.</p>
                <p class="description"><code>sectionid="12345"</code> remains supported. Both <code>sectionid="12345,67890"</code> and <code>sectionids="12345,67890"</code> accept multiple sections. Each section uses its own current OSM term. <code>futureonly</code> defaults to <code>false</code>; <code>true</code> includes today and future activities.</p>
                <p class="description">Use <code>layout="grid"</code> for grid tiles, <code>layout="vertical"</code> for full-width vertical tiles, or <code>layout="table"</code> for tables (the default). Existing <code>layout="tiles"</code> shortcodes still select grid tiles. In Gutenberg, choose Layout in the block settings. Add <code>combine="true"</code> to programme/events to share one table with a labelled section row before each section’s entries, or enable <strong>Combine sections into one table</strong> in Gutenberg. This option applies to table layouts; the default is separate tables.</p>
            </section>
