import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import vm from 'node:vm';

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const registered = new Map();
const controls = { PanelBody: 'PanelBody', TextControl: 'TextControl', ToggleControl: 'ToggleControl' };
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

function findElement(node, type) {
    if (!node || typeof node !== 'object') { return undefined; }
    if (node.type === type) { return node; }
    for (const child of node.children || []) {
        const result = findElement(child, type);
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
    assert.equal(findElement(view, 'ToggleControl').props.checked, true);
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
