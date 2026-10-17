/* This dependency-free source uses the WordPress editor's registered packages. */
(function (wp) {
    'use strict';

    const metadata = [{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"osm/programme","title":"OSM Programme","category":"widgets","icon":"calendar","description":"Display the OSM programme for one or more sections.","textdomain":"online-scout-manager","attributes":{"sectionid":{"type":"string","default":""},"sectionids":{"type":"string","default":""},"futureonly":{"type":"boolean","default":false},"layout":{"type":"string","enum":["table","grid","vertical","tiles"],"default":"table"},"combine":{"type":"boolean","default":false}},"supports":{"html":false},"editorScript":"osm-blocks-editor","editorStyle":"osm-blocks-editor","style":"osm-frontend-style","render":"file:./render.php"},{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"osm/events","title":"OSM Events","category":"widgets","icon":"calendar-alt","description":"Display the OSM events for one or more sections.","textdomain":"online-scout-manager","attributes":{"sectionid":{"type":"string","default":""},"sectionids":{"type":"string","default":""},"futureonly":{"type":"boolean","default":false},"layout":{"type":"string","enum":["table","grid","vertical","tiles"],"default":"table"},"combine":{"type":"boolean","default":false}},"supports":{"html":false},"editorScript":"osm-blocks-editor","editorStyle":"osm-blocks-editor","style":"osm-frontend-style","render":"file:./render.php"},{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"osm/patrols","title":"OSM Section Points","category":"widgets","icon":"list-view","description":"Display section patrols and member names using each section’s name formats and member exclusions.","textdomain":"online-scout-manager","attributes":{"sectionid":{"type":"string","default":""},"sectionids":{"type":"string","default":""}},"supports":{"html":false},"editorScript":"osm-blocks-editor","editorStyle":"osm-blocks-editor","style":"osm-frontend-style","render":"file:./render.php"},{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"osm/waiting-list","title":"OSM Waiting List","category":"widgets","icon":"forms","description":"Show the application form using the destination selected on the Waiting List settings page.","textdomain":"online-scout-manager","attributes":{"sectionid":{"type":"string","default":""},"sectionids":{"type":"string","default":""}},"supports":{"html":false},"editorScript":"osm-blocks-editor","editorStyle":"osm-blocks-editor","style":"osm-frontend-style","render":"file:./render.php"}];
    const el = wp.element.createElement;
    const __ = wp.i18n.__;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl, SelectControl } = wp.components;

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
                const display_layout = attributes.layout === 'tiles' ? 'grid' : (attributes.layout || 'table');
                const waiting_list = block.name === 'osm/waiting-list';
                const note = block.name === 'osm/patrols'
                    ? __('Enable Show Section Points on Sections. Each section’s name formats and member exclusions are applied before displaying members.', 'online-scout-manager')
                    : waiting_list
                        ? __('Shows the application form for the destination selected on the Waiting List settings page.', 'online-scout-manager')
                        : __('Current OSM data is displayed when visitors view the page.', 'online-scout-manager');

                return el(wp.element.Fragment, null,
                    el(InspectorControls, null,
                        el(PanelBody, { title: block.title, initialOpen: true },
                            el(TextControl, {
                                label: waiting_list ? __('Section ID (optional)', 'online-scout-manager') : __('Section IDs', 'online-scout-manager'),
                                value: sections,
                                placeholder: '123,456',
                                help: waiting_list
                                    ? __('Leave blank to use the saved waiting-list destination. Any supplied section ID must match that destination.', 'online-scout-manager')
                                    : __('Enter one or more enabled section IDs, separated by commas. Manage available sections in Settings → Online Scout Manager.', 'online-scout-manager'),
                                onChange: function (value) {
                                    // Clear the old alias so an empty selection stays empty.
                                    set_attributes({ sectionids: value, sectionid: '' });
                                },
                                __nextHasNoMarginBottom: true,
                            }),
                            has_future_filter && el(SelectControl, {
                                label: __('Layout', 'online-scout-manager'),
                                value: display_layout,
                                options: [ { label: __('Table', 'online-scout-manager'), value: 'table' }, { label: __('Grid tiles', 'online-scout-manager'), value: 'grid' }, { label: __('Vertical tiles', 'online-scout-manager'), value: 'vertical' } ],
                                onChange: function (value) { set_attributes({ layout: value }); },
                                __nextHasNoMarginBottom: true,
                            }),
                            has_future_filter && el(ToggleControl, {
                                label: __('Combine sections into one table', 'online-scout-manager'),
                                checked: !!attributes.combine,
                                disabled: display_layout !== 'table',
                                help: __('Table layout only. Adds a labelled section row before each section’s entries.', 'online-scout-manager'),
                                onChange: function (value) { set_attributes({ combine: value }); },
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
                        el('p', null, __('Section IDs:', 'online-scout-manager') + ' ' + (sections || (waiting_list ? __('Uses Settings → Online Scout Manager', 'online-scout-manager') : __('Not set', 'online-scout-manager')))),
                        has_future_filter && el('p', null, __('Future only:', 'online-scout-manager') + ' ' + (attributes.futureonly ? __('Yes', 'online-scout-manager') : __('No', 'online-scout-manager'))),
                        has_future_filter && el('p', null, __('Layout:', 'online-scout-manager') + ' ' + (display_layout === 'vertical' ? __('Vertical tiles', 'online-scout-manager') : display_layout === 'grid' ? __('Grid tiles', 'online-scout-manager') : __('Table', 'online-scout-manager'))),
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
