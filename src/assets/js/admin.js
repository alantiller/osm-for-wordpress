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
     * @returns {void} Replaces only its preview; never changes saved/public configuration.
     */
    function update_preview(form) {
        const preview = form.querySelector('.osm-points-preview');
        const first_mode = form.querySelector('[name="osm_firstname_display"]').value;
        const last_mode = form.querySelector('[name="osm_lastname_display"]').value;
        const other_ids = new Set(form.querySelector('[name="osm_other_excluded_members"]').value.split(/[\s,]+/).filter(Boolean).map(id => id.replace(/^0+/, '')));
        const groups = new Map();
        form.querySelectorAll('[data-osm-member]').forEach(row => {
            const group_name = row.dataset.patrol || 'Unassigned members';
            if (!groups.has(group_name)) groups.set(group_name, []);
            const checkbox = row.querySelector('[name="osm_excluded_members[]"]');
            if (checkbox.checked || other_ids.has(checkbox.value)) return;
            const label = [format_name(row.dataset.firstname, first_mode), format_name(row.dataset.lastname, last_mode)].filter(Boolean).join(' ');
            if (label) groups.get(group_name).push(label);
        });
        preview.replaceChildren();
        if (!groups.size) {
            preview.textContent = 'No member preview is available until OSM returns a member list.';
            return;
        }
        const list = document.createElement('ul');
        groups.forEach((members, group_name) => {
            const group_item = document.createElement('li');
            group_item.textContent = group_name;
            if (members.length) {
                const member_list = document.createElement('ul');
                members.forEach(name => {
                    const member_item = document.createElement('li');
                    member_item.textContent = name;
                    member_list.append(member_item);
                });
                group_item.append(member_list);
            }
            list.append(group_item);
        });
        preview.append(list);
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
