const tailwindcss = require('tailwindcss');
const purgecss = require('@fullhuman/postcss-purgecss');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

module.exports = ({ mode }) => {
  return {
    plugins: [
      tailwindcss('./tailwind.config.js'),
      autoprefixer,
      cssnano({
        preset: 'default'
      }),
      mode === 'production'
        ? purgecss({
          content: [
            'templates/**/*.html.twig',
            'layouts/**/*.html.twig',
            'js/**/*.js',
            'rain.theme',
            '../../../../config/sync/*.yml'
          ],
          defaultExtractor: content => content.match(/[\w-/:()]+(?<!:)/g) || [],
          safelist: {
            deep: [/select2-container--rain$/]
          }
        })
        : false
    ]
  };
};
