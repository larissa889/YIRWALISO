const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',
    
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#4F46E5',
                    '50': '#EEF2FF',
                    '100': '#E0E7FF',
                    '200': '#C7D2FE',
                    '300': '#A5B4FC',
                    '400': '#818CF8',
                    '500': '#6366F1',
                    '600': '#4F46E5',
                    '700': '#4338CA',
                    '800': '#3730A3',
                    '900': '#312E81',
                },
                secondary: {
                    DEFAULT: '#10B981',
                    '50': '#ECFDF5',
                    '100': '#D1FAE5',
                    '200': '#A7F3D0',
                    '300': '#6EE7B7',
                    '400': '#34D399',
                    '500': '#10B981',
                    '600': '#059669',
                    '700': '#047857',
                    '800': '#065F46',
                    '900': '#064E3B',
                },
                dark: {
                    DEFAULT: '#1F2937',
                    '50': '#F9FAFB',
                    '100': '#F3F4F6',
                    '200': '#E5E7EB',
                    '300': '#D1D5DB',
                    '400': '#9CA3AF',
                    '500': '#6B7280',
                    '600': '#4B5563',
                    '700': '#374151',
                    '800': '#1F2937',
                    '900': '#111827',
                },
            },
            boxShadow: {
                'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
            },
            borderRadius: {
                'xl': '1rem',
                '2xl': '1.5rem',
            },
            spacing: {
                '72': '18rem',
                '84': '21rem',
                '96': '24rem',
                '128': '32rem',
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('@tailwindcss/aspect-ratio'),
    ],
};
