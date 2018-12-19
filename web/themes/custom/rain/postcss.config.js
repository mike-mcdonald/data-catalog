const tailwindcss = require('tailwindcss')
const purgecss = require('@fullhuman/postcss-purgecss')
const cssnano = require('cssnano')
const autoprefixer = require('autoprefixer')

// Custom PurgeCSS extractor for Tailwind that allows special characters in
// class names.
//
// https://github.com/FullHuman/purgecss#extractor
class TailwindExtractor {
  static extract(content) {
    return content.match(/[A-Za-z0-9-_:\/]+/g) || [];
  }
}

module.exports = ({ file, options, env }) => {
  console.log('options:', options)

  return {
    plugins: [
      tailwindcss('./tailwind.config.js'),
      autoprefixer,
      cssnano({
        preset: 'default',
      }),
      options.mode === 'production' ? purgecss({
        content: [
          'templates/**/*.html.twig',
          'js/main.bundle.js',
          './rain.theme',
        ],
        extractors: [
          {
            extractor: TailwindExtractor,

            // Specify the file extensions to include when scanning for
            // class names.
            extensions: ["html", "twig", "js", "php", "vue", "theme"]
          }
        ]
      }) : false,
    ]
  }
}
