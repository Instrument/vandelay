const webpack = require('webpack');
const argv = require('yargs').argv;
const resolve = require('path').resolve;
const extname = require('path').extname;
const fs = require('fs');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const stylusLoader = "style-loader!css-loader?minimize!stylus-loader";

//PATHS
const PUBLIC_PATH = '/';
const DIST = resolve('public');
const ENTRY = resolve('js');

function WriteStatsPlugin() {
  const args = [].slice.call(arguments);
  const destination = args.shift();

  return function writeStats() {
    this.plugin('done', stats => {
      const assets = {
        'js': [PUBLIC_PATH + 'bundle.js'],
        'css': []
      };

      fs.writeFileSync(
        destination,
        JSON.stringify(assets)
      );
    });
  };
};

module.exports = {
  name: 'Site Client',
  entry: {
    bundle: [
      ENTRY
    ],
  },
  output: {
    path: '/public',
    publicPath: PUBLIC_PATH,
    filename: '[name].js',
  },
  module: {
    loaders: [{
      test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
      loader: 'file?name=fonts/[name].[ext]'
        + '&limit=10000&minetype=application/font-woff',
    }, {
      test: /\.(jpe?g|png|gif|svg)$/,

      loader: 'file?name=assets/[name].[ext]',
    }, {
      test: /\.css$/,
      include: /node_modules/,
      loader: 'style!css',
    }, {
      test: /\.styl$/,
      use:ExtractTextPlugin.extract({fallback:"style-loader", use:["css-loader?minimize","stylus-loader"]}),
      // include:path.join(__dirname,"client/src"),
    }, {
      test: /\.js$/,
      exclude: /node_modules/,
      loader: "babel-loader",
    }]
  },
  node: {
    console: true,
    fs: 'empty',
    net: 'empty',
    tls: 'empty'
  },
  plugins: [
    // new webpack.NoErrorsPlugin(),
    new WriteStatsPlugin(
      `config/webpack-stats.json`,
      '/public/'
    ),
    new ExtractTextPlugin('/styles/[hash].css'),
  ]
};