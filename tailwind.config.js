import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                // Charte graphique Benin Petro (extraite du logo)
                petro: {
                    50:  '#EAF7EF',
                    100: '#CDEDDA',
                    200: '#9BDBB6',
                    300: '#5FC98C',
                    400: '#01C96D', // vert vif du chevron
                    500: '#04A75C',
                    600: '#02804A',
                    700: '#01582D', // vert institutionnel (fond du logo)
                    800: '#014324',
                    900: '#00301A',
                },
                lime: {
                    400: '#9ADB5A', // degrade clair du chevron
                },
                ardoise: {
                    50:  '#F6F8F7',
                    100: '#ECF0EE',
                    200: '#DCE3E0',
                    300: '#BCC7C2',
                    400: '#8B9A94',
                    500: '#647570',
                    600: '#4A5854',
                    700: '#374340',
                    800: '#232C29',
                    900: '#141A18',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                carte: '0 1px 2px rgba(20,26,24,.04), 0 8px 24px -12px rgba(20,26,24,.12)',
            },
            borderRadius: {
                xl2: '1rem',
            },
        },
    },
    plugins: [forms],
};
