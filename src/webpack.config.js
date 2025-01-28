const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'programme-editor': path.resolve(__dirname, 'src/blocks/programme/index.js'),
        'events-editor': path.resolve(__dirname, 'src/blocks/events/index.js'),
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js', // Use [name] to dynamically generate filenames like programme-editor.js
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.scss$/,
                use: [
                    'style-loader', // Inject CSS into the DOM
                    'css-loader',   // Translates CSS into CommonJS
                    'sass-loader',  // Compiles Sass to CSS
                ],
            },
        ],
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            '@blocks': path.resolve(__dirname, 'src/blocks/'),
        },
    },
};