const path = require('path');
const globby = require('globby');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FriendlyErrorsWebpackPlugin = require('friendly-errors-webpack-plugin');

module.exports = (env, argv) => ({
  entry: {
    main: globby.sync([
      './js/src/**/*.js',
      './scss/style.scss'
    ])
  },
  mode: process.env.NODE_ENV,
  devtool: 'source-map',
  output: {
    path: path.resolve(__dirname),
    filename: 'js/[name].bundle.js'
  },
  stats: 'none',
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/style.bundle.css',
      chunkFilename: 'css/[id].bundle.css'
    }),
    new FriendlyErrorsWebpackPlugin()
  ],
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
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: true
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true
            }
          }
        ]
      }
    ]
  }
});
