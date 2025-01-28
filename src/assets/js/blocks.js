const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, ToggleControl } = wp.components;

// Helper function to create a block
const createOSMBlock = (blockName, title, icon) => {
    registerBlockType(`osm/${blockName}`, {
        title: title,
        icon: icon,
        category: 'widgets',
        attributes: {
            sectionid: { type: 'string', default: '' },
            futureonly: { type: 'boolean', default: false },
        },
        edit: ({ attributes, setAttributes }) => (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Section ID"
                            value={attributes.sectionid}
                            onChange={(value) => setAttributes({ sectionid: value })}
                        />
                        <ToggleControl
                            label="Future Only"
                            checked={attributes.futureonly}
                            onChange={(value) => setAttributes({ futureonly: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div>
                    <strong>{title}</strong>
                    <p>Section ID: {attributes.sectionid || 'Not Set'}</p>
                    <p>Future Only: {attributes.futureonly ? 'Yes' : 'No'}</p>
                </div>
            </>
        ),
        save: () => null, // Dynamic block
    });
};

// Register the OSM Programme block
createOSMBlock('programmme', 'OSM Programme', 'calendar');

// Register the OSM Events block
createOSMBlock('events', 'OSM Events', 'calendar-alt');