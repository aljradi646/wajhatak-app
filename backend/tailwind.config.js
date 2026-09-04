import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                wajhatak: {
                    50: '#EDF7F3',
                    100: '#D5F3E9',
                    200: '#ADE7D3',
                    300: '#35C39E',
                    400: '#0E8A6D',
                    500: '#0E8A6D',
                    600: '#075E4A',
                    700: '#04523F',
                    800: '#03251D',
                    900: '#0A1512',
                },
                amber: {
                    50: '#FEF7EC',
                    100: '#FCEED2',
                    200: '#F6C363',
                    300: '#EDA83C',
                    400: '#D69530',
                    500: '#B97D1B',
                    600: '#9A6718',
                    700: '#6B4A0E',
                    800: '#3D2A08',
                    900: '#2B1B02',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
