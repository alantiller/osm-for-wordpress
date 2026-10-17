/* This dependency-free source uses the WordPress editor's registered packages. */
(function (wp) {
    'use strict';

    const metadata = /* OSM_BLOCK_METADATA */[];
    const el = wp.element.createElement;
    const __ = wp.i18n.__;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl } = wp.components;

    /**
     * Create a block-specific editor while keeping saved section aliases editable.
     *
     * @param {Object} block Parsed block.json metadata for one OSM content kind.
     * @returns {Function} WordPress editor component for this block kind.
     */
    function create_block_editor(block) {
        /**
         * Show editable controls and an explanatory placeholder for dynamic content.
         *
         * @param {Object} editor_props WordPress edit properties.
         * @param {Object} editor_props.attributes Saved block settings.
         * @param {Function} editor_props.setAttributes WordPress attribute update callback.
         * @returns {Object} WordPress element tree for the block editor.
         */
        return function Edit({ attributes, setAttributes: set_attributes }) {
                const sections = attributes.sectionids || attributes.sectionid || '';
                const has_future_filter = Object.prototype.hasOwnProperty.call(block.attributes, 'futureonly');
                const waiting_list = block.name === 'osm/waiting-list';
                const note = block.name === 'osm/patrols'
                    ? __('Enable Show Section Points on General. Each section’s name formats and member exclusions are applied before displaying members.', 'online-scout-manager')
                    : waiting_list
                        ? __('Shows the waiting-list application form configured in OSM Settings. Uses the waiting-list section selected there when no override is entered.', 'online-scout-manager')
                        : __('Current OSM data is displayed when visitors view the page.', 'online-scout-manager');

                return el(wp.element.Fragment, null,
                    el(InspectorControls, null,
                        el(PanelBody, { title: block.title, initialOpen: true },
                            el(TextControl, {
                                label: waiting_list ? __('Section IDs (optional override)', 'online-scout-manager') : __('Section IDs', 'online-scout-manager'),
                                value: sections,
                                placeholder: '123,456',
                                help: waiting_list
                                    ? __('Uses the waiting-list section selected in OSM Settings when empty. Enter section IDs separated by commas only to override that selection.', 'online-scout-manager')
                                    : __('Enter one or more enabled section IDs, separated by commas. Manage available sections in OSM Settings.', 'online-scout-manager'),
                                onChange: function (value) {
                                    // Clear the old alias so an empty selection stays empty.
                                    set_attributes({ sectionids: value, sectionid: '' });
                                },
                                __nextHasNoMarginBottom: true,
                            }),
                            has_future_filter && el(ToggleControl, {
                                label: __('Future only', 'online-scout-manager'),
                                checked: !!attributes.futureonly,
                                onChange: function (value) { set_attributes({ futureonly: value }); },
                                __nextHasNoMarginBottom: true,
                            })
                        )
                    ),
                    el('div', useBlockProps({ className: 'osm-block-editor' }),
                        el('strong', null, block.title),
                        el('p', null, __('Section IDs:', 'online-scout-manager') + ' ' + (sections || (waiting_list ? __('Uses OSM Settings', 'online-scout-manager') : __('Not set', 'online-scout-manager')))),
                        has_future_filter && el('p', null, __('Future only:', 'online-scout-manager') + ' ' + (attributes.futureonly ? __('Yes', 'online-scout-manager') : __('No', 'online-scout-manager'))),
                        el('p', { className: 'osm-block-editor-note' }, note)
                    )
                );
        };
    }

    // Each editor closes over its own metadata; saves remain dynamic and accept
    // markup from the original blocks when WordPress loads an existing document.
    metadata.forEach(function (block) {
        wp.blocks.registerBlockType(block.name, {
            ...block,
            edit: create_block_editor(block),
            // Dynamic rendering also accepts markup from the original saved blocks.
            save: function () { return null; },
        });
    });
})(window.wp);
