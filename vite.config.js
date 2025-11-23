import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import obfuscator from 'rollup-plugin-obfuscator';

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production' || process.env.NODE_ENV === 'production';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),

            vue(),
            tailwindcss(),

            // 🔥 O plugin deve ficar AQUI — fora de rollupOptions!
            isProduction && obfuscator({
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
            })
        ],

        build: {
            minify: false, // Melhor deixar o obfuscator minificar
            sourcemap: false,
        },
    };
});
