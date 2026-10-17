/* Fresh form credentials avoid sharing one-use tokens through a page cache. */
(function () {
    'use strict';
    /** Prepare each public form; previews remain inert and failures are explained inline. */
    function prepare_forms() {
        document.querySelectorAll('.osm-application-form').forEach(function (form) {
            if (form.dataset.osmPreview || form.dataset.osmPrepared) { return; }
            form.dataset.osmPrepared = 'true';
            var button = form.querySelector('button[type="submit"]');
            var status = form.querySelector('.osm-form-status');
            button.disabled = true;
            status.textContent = 'Preparing application form…';
            fetch(form.dataset.prepareUrl, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=osm_waiting_list_prepare'
            }).then(function (response) {
                if (!response.ok) { throw new Error('unavailable'); }
                return response.json();
            }).then(function (response) {
                if (!response.success || !response.data.nonce || !response.data.token) { throw new Error('unavailable'); }
                form.elements._osm_nonce.value = response.data.nonce;
                form.elements._osm_token.value = response.data.token;
                button.disabled = false;
                status.textContent = '';
            }).catch(function () {
                status.textContent = 'The form is currently unavailable. Reload the page or contact the group.';
            });
            form.addEventListener('submit', function (event) {
                if (form.dataset.osmSubmitting || button.disabled) { event.preventDefault(); return; }
                form.dataset.osmSubmitting = 'true';
                button.disabled = true;
                status.textContent = 'Sending application. Please wait…';
            });
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', prepare_forms); }
    else { prepare_forms(); }
})();
