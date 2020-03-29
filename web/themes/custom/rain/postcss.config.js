const tailwindcss = require('tailwindcss');
const purgecss = require('@fullhuman/postcss-purgecss');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

// Custom PurgeCSS extractor for Tailwind that allows special characters in
// class names.
//
// https://github.com/FullHuman/purgecss#extractor
module.exports = ({ file, options, env }) => {
  return {
    plugins: [
      tailwindcss('./tailwind.config.js'),
      autoprefixer,
      cssnano({
        preset: 'default'
      }),
      options.mode === 'production'
        ? purgecss({
          content: [
            'templates/**/*.html.twig',
            'layouts/**/*.html.twig',
            'js/main.bundle.js',
            './rain.theme'
          ],
          defaultExtractor: content => content.match(/[\w-/:()]+(?<!:)/g) || [],
          whitelistPatternsChildren: [/select2-container--rain$/]
        })
        : false
    ]
  };
};
