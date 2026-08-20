/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './pages/**/*.php',
    './includes/**/*.php',
    './assets/js/**/*.js',
    './assets/css/src.css'
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          950: '#06101c',
          900: '#0a1628',
          800: '#0e2240',
          700: '#123056',
          600: '#1a3d73',
          500: '#1e4a8c',
          400: '#3b6bb5'
        },
        royal: {
          DEFAULT: '#1e3a8a',
          light: '#2563eb',
          dark: '#172554'
        }
      },
      fontFamily: {
        sans: ['Tahoma', 'Segoe UI', 'Microsoft Sans Serif', 'sans-serif']
      }
    }
  },
  plugins: []
};
