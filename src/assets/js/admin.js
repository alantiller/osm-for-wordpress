/** Live administrator previews and a local quota countdown; no extra API requests. */
(() => {
    'use strict';

    /**
     * Apply the same full/initial/hidden name policy as the public PHP formatter.
     * @param {string} value Sanitised name from the administrator-only roster.
     * @param {string} mode Selected display mode.
     * @returns {string} Approved label part, or empty for hidden/unknown modes.
     */
    function format_name(value, mode) {
        value = value.trim();
        if (mode === 'full') return value;
        if (mode === 'initial' && value) return Array.from(value)[0] + '.';
        return '';
    }

    /**
     * Render this section's unsaved name formats and exclusions using safe text nodes.
     * @param {HTMLFormElement} form Section Points settings form with request-local roster rows.
     * @returns {void} Updates each member row preview; never changes saved/public configuration.
     */
    function update_preview(form) {
        const first_mode = form.querySelector('[name="osm_firstname_display"]').value;
        const last_mode = form.querySelector('[name="osm_lastname_display"]').value;
        const other_ids = new Set(form.querySelector('[name="osm_other_excluded_members"]').value.split(/[\s,]+/).filter(Boolean).map(id => id.replace(/^0+/, '')));
        form.querySelectorAll('[data-osm-member]').forEach(row => {
            const checkbox = row.querySelector('[name="osm_excluded_members[]"]');
            const excluded = checkbox.checked || other_ids.has(checkbox.value);
            const label = [format_name(row.dataset.firstname, first_mode), format_name(row.dataset.lastname, last_mode)].filter(Boolean).join(' ');
            const preview = row.querySelector('.osm-points-preview');
            preview.textContent = excluded ? 'Excluded' : (label || 'Name hidden');
            preview.dataset.state = excluded || !label ? 'hidden' : 'visible';
        });
    }

    /**
     * Update an observed reset countdown without polling or assuming a fresh quota.
     * @param {HTMLElement} element Quota reset label with server-calculated seconds remaining.
     * @returns {void} Shows an awaiting-response state once the observed window ends.
     */
    function start_countdown(element) {
        const deadline = performance.now() + Number(element.dataset.osmResetSeconds) * 1000;
        const timer = setInterval(update_countdown, 1000);
        /** @returns {void} Refreshes the remaining duration and stops when the window ends. */
        function update_countdown() {
            const seconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
            element.textContent = seconds ? Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's' : 'Awaiting next OSM response';
            if (!seconds) clearInterval(timer);
        }
        update_countdown();
    }

    document.querySelectorAll('.osm-points-form').forEach(form => {
        // Delegation covers name selects, member checkboxes and retained exclusion IDs.
        form.addEventListener('input', () => update_preview(form));
        form.addEventListener('change', () => update_preview(form));
        update_preview(form);
    });
    document.querySelectorAll('[data-osm-reset-seconds]').forEach(start_countdown);
})();
