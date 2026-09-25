/** @type {import('tailwindcss').Config} */
export default {
  content: ["../public/public_views/**/*.php", "../public/**/*.php","../public/*.php","./src/**/*.ts","../src/**/*.php"],
  darkMode: 'selector',
  theme: {
    extend: {
      letterSpacing: {
        '1': '1px',
        '2': '2px',
        '4': '4px',
      },
      fontFamily: {
        'poppins': ['"Poppins"', 'sans-serif'],
        'concert-one': ['"ConcertOne"', 'sans-serif'],
      },
      colors: {
        'black': '#111827',
        'brand': '#3B6EA8',
        'brand-dark': '#234A73',
        'brand-soft': '#EAF2FA',
        'white': '#F7F8FA',
        'true-white': '#FFFFFF',
        'slate': '#64748B'
      },
    },
  },
  plugins: [],
}
