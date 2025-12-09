import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Marketing website
                'resources/css/app.css',
                'resources/js/app.js',
                // Filament
                'resources/css/filament/app/theme.css',
                'resources/css/filament/admin/theme.css',
                // Documentation
                'app-modules/Documentation/resources/css/documentation.css',
                'app-modules/Documentation/resources/js/documentation.js',
            ],
            refresh: [
                // Blade templates
                'resources/views/**/*.blade.php',
                'app/Filament/**/*.php',
                'app/Livewire/**/*.php',
                'app-modules/**/resources/views/**/*.blade.php',
                // Routes
                'routes/**/*.php',
                // Config files that affect UI
                'config/filament.php',
                'config/app.php',
            ],
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '~': path.resolve(__dirname, './resources'),
        },
    },
});
