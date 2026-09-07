import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'or': {
                    50: '#fdf9e9',
                    100: '#f9efc4',
                    200: '#f2dd8b',
                    300: '#e9c54d',
                    400: '#e0ae28',
                    500: '#cf951d',
                    600: '#b37417',
                    700: '#8f5416',
                    800: '#774319',
                    900: '#66391b',
                },
            },
        },
    },
}
