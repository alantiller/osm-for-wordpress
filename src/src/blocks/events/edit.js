export default function Edit({ attributes, setAttributes }) {
    const { sectionid, futureonly } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Events Settings', 'osm-for-wordpress')}>
                    <TextControl
                        label={__('Section ID', 'osm-for-wordpress')}
                        value={sectionid}
                        onChange={(value) => setAttributes({ sectionid: value })}
                    />
                    <ToggleControl
                        label={__('Future Only', 'osm-for-wordpress')}
                        checked={futureonly}
                        onChange={(value) => setAttributes({ futureonly: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                <strong>{__('OSM Events', 'osm-for-wordpress')}</strong>
                <p>{__('Section ID:', 'osm-for-wordpress')} {sectionid || __('Not set', 'osm-for-wordpress')}</p>
                <p>{__('Future Only:', 'osm-for-wordpress')} {futureonly ? __('Yes', 'osm-for-wordpress') : __('No', 'osm-for-wordpress')}</p>
            </div>
        </>
    );
}