const path = require('path');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    entry: {
      avatarmaker: path.resolve(__dirname, './source/app/avatarmaker.js'),
    },

    output: {
      path: path.resolve(__dirname, 'upload', 'app'),
      filename: '[name].js'
    },

    target: 'web',

    externals: {
        jquery: 'jQuery'
    },

    plugins: [new MiniCssExtractPlugin()],

    module: {
        rules: [
            {
              test: /\.s[ac]ss$/i,
              use: [
                'style-loader',
                MiniCssExtractPlugin.loader,
                {
                  loader: 'css-loader',
                  options: {
                    modules: true,
                    importLoaders: 1,
                    modules: {
                      localIdentName:'[local]'
                    }
                  }
                },
                'postcss-loader',
                'sass-loader'
              ]
            },
            {
              test: /\.js$/,
              /* ... */
            }
        ]
    }

  };
