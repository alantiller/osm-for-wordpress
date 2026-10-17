import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import vm from 'node:vm';

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const registered = new Map();
const controls = { PanelBody: 'PanelBody', TextControl: 'TextControl', ToggleControl: 'ToggleControl', SelectControl: 'SelectControl' };
const wp = {
    blocks: { registerBlockType: (name, block) => registered.set(name, block) },
    element: {
        Fragment: 'Fragment',
        createElement: (type, props, ...children) => ({ type, props, children }),
    },
    i18n: { __: (value) => value },
    blockEditor: { InspectorControls: 'InspectorControls', useBlockProps: (props) => props },
    components: controls,
};
vm.runInNewContext(readFileSync(join(root, 'build/osm-blocks-editor.js'), 'utf8'), { window: { wp } });

/**
 * Locate a rendered control, optionally distinguishing controls by their label.
 * @param {Object} node Element tree to search.
 * @param {string} type WordPress control type.
 * @param {string} [label] Exact control label when multiple controls share a type.
 * @returns {Object|undefined} First matching control.
 */
function findElement(node, type, label) {
    if (!node || typeof node !== 'object') { return undefined; }
    if (node.type === type && (!label || node.props.label === label)) { return node; }
    for (const child of node.children || []) {
        const result = findElement(child, type, label);
        if (result) { return result; }
    }
    return undefined;
}

test('all four shipped blocks register with dynamic saves and shared asset handles', () => {
    assert.deepEqual([...registered.keys()], ['osm/programme', 'osm/events', 'osm/patrols', 'osm/waiting-list']);
    for (const block of registered.values()) {
        assert.equal(block.apiVersion, 3);
        assert.equal(block.editorScript, 'osm-blocks-editor');
        assert.equal(block.save({ attributes: { sectionid: '123' } }), null);
    }
});

test('old single-section blocks remain editable and can migrate to multiple sections', () => {
    const updates = [];
    const block = registered.get('osm/programme');
    const view = block.edit({
        attributes: { sectionid: '123', sectionids: '', futureonly: true },
        setAttributes: (attributes) => updates.push(attributes),
    });
    const sectionControl = findElement(view, 'TextControl');
    assert.equal(sectionControl.props.value, '123');
    sectionControl.props.onChange('123,456');
    assert.equal(updates[0].sectionids, '123,456');
    assert.equal(updates[0].sectionid, '');
    assert.equal(findElement(view, 'ToggleControl', 'Future only').props.checked, true);
});

test('clearing a section selection clears the legacy alias too', () => {
    let update;
    const view = registered.get('osm/events').edit({
        attributes: { sectionid: '123', sectionids: '', futureonly: false },
        setAttributes: (attributes) => { update = attributes; },
    });
    findElement(view, 'TextControl').props.onChange('');
    assert.equal(update.sectionids, '');
    assert.equal(update.sectionid, '');
});

test('patrol and waiting list blocks support multiple sections without date filters', () => {
    for (const name of ['osm/patrols', 'osm/waiting-list']) {
        const view = registered.get(name).edit({
            attributes: { sectionids: '123,456' },
            setAttributes: () => {},
        });
        assert.equal(findElement(view, 'TextControl').props.value, '123,456');
        assert.equal(findElement(view, 'ToggleControl'), undefined);
    }
});

test('programme and events offer a layout dropdown without changing section selections', () => {
    for (const name of ['osm/programme', 'osm/events']) {
        let update;
        const view = registered.get(name).edit({ attributes: { sectionid: '14648,6569', futureonly: true }, setAttributes: value => { update = value; } });
        const layout = findElement(view, 'SelectControl');
        assert.equal(layout.props.value, 'table');
        assert.equal(layout.props.options[1].value, 'grid');
        layout.props.onChange('grid');
        assert.equal(update.layout, 'grid');
        assert.equal(findElement(view, 'TextControl').props.value, '14648,6569');
    }
});

test('combined table control saves independently and is disabled for tiles', () => {
    for (const name of ['osm/programme', 'osm/events']) {
        let update;
        const block = registered.get(name);
        const view = block.edit({ attributes: { sectionids: '123,456', combine: true }, setAttributes: value => { update = value; } });
        const control = findElement(view, 'ToggleControl', 'Combine sections into one table');
        assert.equal(control.props.checked, true);
        control.props.onChange(false);
        assert.equal(update.combine, false);
        assert.equal(findElement(view, 'TextControl').props.value, '123,456');
        const tiles = block.edit({ attributes: { layout: 'tiles', combine: true }, setAttributes: () => {} });
        assert.equal(findElement(tiles, 'ToggleControl', 'Combine sections into one table').props.disabled, true);
    }
});

test('vertical tiles are editable and legacy tiles display as grid without losing saved content', () => {
    for (const name of ['osm/programme', 'osm/events']) {
        let update;
        const block = registered.get(name);
        const old_view = block.edit({ attributes: { layout: 'tiles' }, setAttributes: value => { update = value; } });
        const layout = findElement(old_view, 'SelectControl');
        assert.equal(layout.props.value, 'grid');
        assert.equal(layout.props.options[2].value, 'vertical');
        layout.props.onChange('vertical');
        assert.equal(update.layout, 'vertical');
        const view = block.edit({ attributes: { layout: 'vertical', combine: true }, setAttributes: () => {} });
        assert.equal(findElement(view, 'SelectControl').props.value, 'vertical');
        assert.equal(findElement(view, 'ToggleControl', 'Combine sections into one table').props.disabled, true);
    }
});
