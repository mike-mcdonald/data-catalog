const path = require('path');
const globby = require('globby');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = (env, argv) => ({
  entry: './scss/style.scss',
  entry: {
    main: globby.sync(['./js/src/**/*.js', './scss/style.scss'])
  },
  mode: process.env.NODE_ENV,
  output: {
    path: path.resolve(__dirname),
    filename: 'js/[name].bundle.js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.svg$/,
        loader: 'svg-inline-loader'
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract(
          {
            fallback: 'style-loader',
            use: [
              {
                loader: 'css-loader',
                options: {
                  sourceMap: true,
                }
              },
              {
                loader: 'postcss-loader',
                options: {
                  sourceMap: true,
                  config: {
                    path: './postcss.config.js',
                    ctx: {
                      mode: argv.mode,
                    },
                  }
                }
              },
              {
                loader: 'sass-loader',
                options: {
                  sourceMap: true
                }
              }],
          })
      }
    ]
  },
  plugins: [
    new ExtractTextPlugin(
      {
        filename: 'css/style.bundle.css'
      }
    )
  ]
});
