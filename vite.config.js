import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import obfuscator from 'rollup-plugin-obfuscator';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        tailwindcss(),
    ],

    // Aqui entram as config do Rollup
    build: {
        minify: 'esbuild',

        rollupOptions: {
            plugins: [
                obfuscator(
                    {
                        compact: true,
                        controlFlowFlattening: true,
                        controlFlowFlatteningThreshold: 1,
                        deadCodeInjection: true,
                        deadCodeInjectionThreshold: 1,
                        stringArray: true,
                        stringArrayThreshold: 1,
                        stringArrayEncoding: ['base64'],
                        splitStrings: true,
                        splitStringsChunkLength: 5,
                        disableConsoleOutput: true,
                        renameProperties: true,
                        transformObjectKeys: true,
                        selfDefending: true,
                        debugProtection: true,
                        debugProtectionInterval: true,
                    },
                    [
                        '**/*.js', // arquivos a serem ofuscados
                    ]
                ),
            ],
        },
    },
});
