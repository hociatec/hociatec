const autoprefixer = require('autoprefixer');

module.exports = {
  plugins: {
    '@tailwindcss/postcss': {}, // ✅ nouveau plugin Tailwind v4+
    autoprefixer,               // ✅ pas besoin de l'appeler en fonction
  },
};
