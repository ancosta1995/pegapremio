import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import obfuscator from 'rollup-plugin-obfuscator';

export default defineConfig(({ mode, command }) => {
    // Garante que o obfuscator seja aplicado em produção
    // Verifica tanto o mode quanto o command para garantir compatibilidade
    // NODE_ENV também é verificado para garantir que funcione no servidor
    const isProduction = mode === 'production' || command === 'build' || process.env.NODE_ENV === 'production';
    
    if (isProduction) {
        console.log('🔒 Obfuscator será aplicado - Mode:', mode, 'Command:', command, 'NODE_ENV:', process.env.NODE_ENV);
    }
    
    const plugins = [
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
    ];

    return {
        plugins,

        // Aqui entram as config do Rollup
        build: {
            minify: 'esbuild',

            rollupOptions: {
                plugins: isProduction || process.env.FORCE_OBFUSCATE === 'true' ? [
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
                ] : [],
            },
        },
    };
});
