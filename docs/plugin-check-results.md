# Plugin Check verification — 1.1.0

Checked the unpacked 1.1.0 release ZIP using official Plugin Check in an isolated WordPress 7.1.3 installation with PHP 8.5 and a SQLite test database. Low-severity errors and warnings were included.

Result for **Online Scout Manager**, slug `online-scout-manager`: **0 errors; 0 warnings.** Renaming the title and package removed the previous WordPress name/slug findings. No report-wide ignore codes or excluded checks were used.

```sh
wp plugin check online-scout-manager --format=strict-json \
  --include-low-severity-errors --include-low-severity-warnings
```

Additional verification:

- All 31 PHP source/test/tool files passed syntax checks.
- PHP suites passed: 91 core checks, 185 including admin checks, 159 including waiting-list lifecycle checks, 101 including API quota checks. Totals include the shared core suite rather than representing independent counts.
- All four Gutenberg editor tests passed; build output matches its source.
- Release ZIP metadata is version 1.1.0 and contains no Divi/Elementor adapters/assets or separate waiting-list authentication class.
- Real WordPress browser checks passed for three-tab navigation, three-column section tiles with a fourth tile wrapping, independent programme/events and Section Points switches, per-section name formats, administrator member lists, saved exclusion/unexclude behavior, visible reset controls, live previews, API quota/countdown and frontend redaction.
- Waiting-list regressions cover fresh credentials, clear/re-enable, one-use submissions, rate limits and partial failures.
- Additional regression cases cover retained exclusions during roster outages, inaccessible section saves, unidentified members, and exclusion enforcement in shortcodes/Gutenberg.
- Desktop and 390-pixel mobile screenshots inspected; no settings overflow detected.
- Administrator CSS/JS use changing file versions even while the release stays 1.1.0; removed obsolete hosted-form code and unused assets/styles.
- Original detailed authentication instructions and all four default scopes appear on Authentication.

OSM HTTP requests used synthetic local fixtures. No real applicants or credentials were sent. Real OSM account grants and API responses still require staging verification. Existing OSM Divi/Elementor widgets/modules must be replaced by shortcodes or Gutenberg blocks when updating to 1.1.0.

Waiting-list follow-up: verified the default responsive form styling, CSS-variable colour override and mobile single-column layout in real WordPress. Synthetic browser submissions validate the child-create/adult-contact sequence with the new custom-data endpoint and explicit section/member context. A simulated negative adult-update response leaves a clear partial-result message, removes the resubmission form and exposes no applicant fields. Real OSM contact persistence still needs confirmation on the user's account; no live applications were submitted during verification.

Date/notes/notifications follow-up: 227 combined checks including application-detail tests passed. Real WordPress browser checks confirmed Day/Month/Year numeric fields survive KSES, impossible dates retain values and send no mail, optional Notes/helping answers reach the discovered OSM field through fixtures, and two selected WordPress users receive individual intercepted alerts. Desktop/mobile screenshots were inspected. SMTP/inbox delivery and section-specific Notes fields still require staging checks; every test notification was intercepted by pre_wp_mail.

Rename verification: activated online-scout-manager/online-scout-manager.php in the isolated WordPress installation after deactivating the old path. Confirmed new settings routes and stylesheet/script URLs, shared saved configuration, shortcode output and form rendering. The package remains 1.1.0; GitHub repository URLs retain their existing addresses.
