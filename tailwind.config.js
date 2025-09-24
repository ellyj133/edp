/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.{html,js,php}",
    "./templates/**/*.{html,php}",
    "./includes/**/*.php"
  ],
  theme: {
    extend: {
      // Custom color palette matching our design system
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        neutral: {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
        }
      },
      
      // Custom spacing based on 8pt grid system
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },
      
      // Custom font family
      fontFamily: {
        'sans': ['Inter', 'Segoe UI', 'system-ui', '-apple-system', 'sans-serif'],
      },
      
      // Custom animations for micro-interactions
      animation: {
        'fade-in': 'fadeIn 0.2s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'bounce-subtle': 'bounceSubtle 0.6s ease-in-out',
        'skeleton': 'skeleton 1.5s infinite',
      },
      
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        bounceSubtle: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-2px)' },
        },
        skeleton: {
          '0%': { backgroundPosition: '200% 0' },
          '100%': { backgroundPosition: '-200% 0' },
        }
      },
      
      // Custom box shadows for depth
      boxShadow: {
        'soft': '0 2px 15px rgba(0, 0, 0, 0.08)',
        'medium': '0 4px 25px rgba(0, 0, 0, 0.1)',
        'strong': '0 10px 40px rgba(0, 0, 0, 0.15)',
      },
      
      // Custom border radius
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
      }
    },
  },
  plugins: [
    // Add custom component classes
    function({ addComponents, theme }) {
      addComponents({
        // Button Components
        '.btn': {
          '@apply inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed': {},
        },
        '.btn-primary': {
          '@apply bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 active:bg-primary-800': {},
        },
        '.btn-secondary': {
          '@apply bg-neutral-200 text-neutral-800 hover:bg-neutral-300 focus:ring-neutral-500': {},
        },
        '.btn-outline': {
          '@apply border-neutral-300 text-neutral-700 hover:bg-neutral-50 focus:ring-neutral-500': {},
        },
        '.btn-ghost': {
          '@apply text-neutral-600 hover:text-neutral-800 hover:bg-neutral-100 focus:ring-neutral-500': {},
        },
        '.btn-sm': {
          '@apply px-4 py-2 text-xs': {},
        },
        '.btn-lg': {
          '@apply px-8 py-4 text-base': {},
        },
        
        // Input Components
        '.input': {
          '@apply w-full px-4 py-3 border border-neutral-300 rounded-lg text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors': {},
        },
        '.input-error': {
          '@apply border-red-500 focus:ring-red-500 focus:border-red-500': {},
        },
        
        // Card Components
        '.card': {
          '@apply bg-white rounded-xl shadow-soft overflow-hidden': {},
        },
        '.card-hover': {
          '@apply transition-all duration-200 hover:shadow-medium hover:-translate-y-1': {},
        },
        
        // Badge Components
        '.badge': {
          '@apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': {},
        },
        '.badge-primary': {
          '@apply bg-primary-100 text-primary-800': {},
        },
        '.badge-success': {
          '@apply bg-green-100 text-green-800': {},
        },
        '.badge-warning': {
          '@apply bg-yellow-100 text-yellow-800': {},
        },
        '.badge-error': {
          '@apply bg-red-100 text-red-800': {},
        },
        
        // Loading States
        '.skeleton': {
          '@apply bg-gradient-to-r from-neutral-200 via-neutral-100 to-neutral-200 bg-[length:200%_100%] animate-skeleton rounded': {},
        },
        
        // Focus States for Accessibility
        '.focus-ring': {
          '@apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2': {},
        }
      })
    }
  ],
}