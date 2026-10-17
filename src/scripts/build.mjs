/** Reproducible block assets without third-party build dependencies. */
import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, rmSync, watch, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const source = join(root, 'src/blocks');
const destination = join(root, 'build');
const blocks = ['programme', 'events', 'patrols', 'waiting-list'];
const legacy_assets = ['programme-editor.js', 'programme-editor.asset.php', 'events-editor.js', 'events-editor.asset.php'];
const check_only = process.argv.includes('--check');

/**
 * Build deterministic editor assets and copy dynamic block metadata/render files.
 *
 * Check mode compares source-derived output without changing committed files.
 *
 * @returns {void}
 * @throws {Error} If source JSON is invalid, filesystem access fails or output is stale.
 */
function build() {
    const outputs = new Map();
    const metadata = blocks.map((name) => {
        const json = readFileSync(join(source, name, 'block.json'), 'utf8');
        const block = JSON.parse(json);
        outputs.set(join(destination, 'blocks', name, 'block.json'), json);
        outputs.set(join(destination, 'blocks', name, 'render.php'), readFileSync(join(source, name, 'render.php'), 'utf8'));
        return block;
    });
    const script = readFileSync(join(source, 'editor.js'), 'utf8')
        .replace('/* OSM_BLOCK_METADATA */[]', JSON.stringify(metadata));
    const style = readFileSync(join(source, 'editor.css'), 'utf8');
    const version = createHash('sha256').update(script).update(style).digest('hex').slice(0, 20);
    outputs.set(join(destination, 'osm-blocks-editor.js'), script);
    outputs.set(join(destination, 'osm-blocks-editor.css'), style);
    outputs.set(join(destination, 'osm-blocks-editor.asset.php'),
        `<?php\ndeclare(strict_types=1);\nreturn [ 'dependencies' => [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ], 'version' => '${version}' ];\n`);

    for (const [file, content] of outputs) {
        if (check_only) {
            if (!existsSync(file) || readFileSync(file, 'utf8') !== content) {
                throw new Error(`Build output is stale: ${file}`);
            }
        } else {
            mkdirSync(dirname(file), { recursive: true });
            writeFileSync(file, content);
        }
    }
    if (!check_only) {
        for (const file of legacy_assets) {
            rmSync(join(destination, file), { force: true });
        }
    }
    console.log(check_only ? 'Block assets match their source.' : 'Built four OSM blocks.');
}

build();
if (process.argv.includes('--watch')) {
    let timer;
    // Rebuild once after a burst of editor/metadata changes without stopping watch
    // mode when a temporary source-file error occurs during an editor save.
    watch(source, { recursive: true }, () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            try { build(); } catch (error) { console.error(error.message); }
        }, 100);
    });
    console.log('Watching block source files.');
}
