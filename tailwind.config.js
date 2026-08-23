import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Helvetica Neue', 'Arial', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                aurevia: {
                    navy: '#0E2A47',
                    gold: '#C5944A',
                    'gold-light': '#E3C892',
                    'label-gray': '#8A94A0',
                    pearl: '#F5F2EB',
                    mist: '#D9DDE3',
                    ink: '#1C1C1C',
                },
            },
        },
    },

    plugins: [forms],
};
