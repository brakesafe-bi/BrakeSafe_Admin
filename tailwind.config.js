import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                background: 'hsl(var(--background) / <alpha-value>)',
                foreground: 'hsl(var(--foreground) / <alpha-value>)',
                card: 'hsl(var(--card) / <alpha-value>)',
                'card-foreground': 'hsl(var(--card-foreground) / <alpha-value>)',
                popover: 'hsl(var(--popover) / <alpha-value>)',
                'popover-foreground': 'hsl(var(--popover-foreground) / <alpha-value>)',
                primary: 'hsl(var(--primary) / <alpha-value>)',
                'primary-foreground': 'hsl(var(--primary-foreground) / <alpha-value>)',
                secondary: 'hsl(var(--secondary) / <alpha-value>)',
                'secondary-foreground': 'hsl(var(--secondary-foreground) / <alpha-value>)',
                muted: 'hsl(var(--muted) / <alpha-value>)',
                'muted-foreground': 'hsl(var(--muted-foreground) / <alpha-value>)',
                accent: 'hsl(var(--accent) / <alpha-value>)',
                'accent-foreground': 'hsl(var(--accent-foreground) / <alpha-value>)',
                destructive: 'hsl(var(--destructive) / <alpha-value>)',
                'destructive-foreground': 'hsl(var(--destructive-foreground) / <alpha-value>)',
                warning: 'hsl(var(--warning) / <alpha-value>)',
                border: 'hsl(var(--border) / <alpha-value>)',
                success: 'hsl(var(--success) / <alpha-value>)',
                input: 'hsl(var(--input) / <alpha-value>)',
                ring: 'hsl(var(--ring) / <alpha-value>)',
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            keyframes: {
                'pulse-glow': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.5' },
                },
            },
            animation: {
                'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
            },
        },
    },
    plugins: [],
};
