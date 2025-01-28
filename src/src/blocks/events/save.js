import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
    const { sectionid, futureonly } = attributes;

    return (
        <div {...useBlockProps()}>
            <strong>OSM Events</strong>
            <p>Section ID: {sectionid}</p>
            <p>Future Only: {futureonly ? 'Yes' : 'No'}</p>
        </div>
    );
}