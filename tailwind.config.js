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
                sans: ['Pretendard', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Primary - Slate 계열
                primary: {
                    DEFAULT: '#0F172A',
                    hover: '#1E293B',
                    light: '#334155',
                },
                // Accent - Blue 계열
                accent: {
                    DEFAULT: '#3B82F6',
                    hover: '#2563EB',
                    light: '#EFF6FF',
                    dark: '#1D4ED8',
                },
                // Surface & Background
                surface: {
                    DEFAULT: '#FFFFFF',
                    muted: '#FAFAFA',
                    subtle: '#F3F4F6',
                },
                // Border
                border: {
                    DEFAULT: '#E5E7EB',
                    subtle: '#F3F4F6',
                    strong: '#D1D5DB',
                },
                // Text
                content: {
                    DEFAULT: '#111827',
                    secondary: '#6B7280',
                    muted: '#9CA3AF',
                },
                // Semantic colors
                success: {
                    DEFAULT: '#10B981',
                    light: '#D1FAE5',
                    dark: '#059669',
                },
                warning: {
                    DEFAULT: '#F59E0B',
                    light: '#FEF3C7',
                    dark: '#D97706',
                },
                error: {
                    DEFAULT: '#EF4444',
                    light: '#FEE2E2',
                    dark: '#DC2626',
                },
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                'soft': '0 2px 8px -2px rgba(0, 0, 0, 0.05), 0 4px 16px -4px rgba(0, 0, 0, 0.05)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02)',
                'card-hover': '0 10px 40px -10px rgba(0, 0, 0, 0.08)',
            },
            spacing: {
                '18': '4.5rem',
                '22': '5.5rem',
            },
        },
    },

    plugins: [forms],
};
