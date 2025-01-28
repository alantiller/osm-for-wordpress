import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { sectionid, futureonly } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Programme Settings', 'osm-for-wordpress')}>
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
                <strong>{__('OSM Programme', 'osm-for-wordpress')}</strong>
                <p>{__('Section ID:', 'osm-for-wordpress')} {sectionid || __('Not set', 'osm-for-wordpress')}</p>
                <p>{__('Future Only:', 'osm-for-wordpress')} {futureonly ? __('Yes', 'osm-for-wordpress') : __('No', 'osm-for-wordpress')}</p>
            </div>
        </>
    );
}