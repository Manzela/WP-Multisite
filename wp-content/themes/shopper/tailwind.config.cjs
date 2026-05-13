module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './resources/scripts/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
       
  },
  aspectRatio: {
    '1': '1',
  },
},
  },
  plugins: [
    require('tailwindcss-rtl'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/typography'),
  ],
};
