const webpack = require('webpack');
const argv = require('yargs').argv;
const path = require('path');
const resolve = require('path').resolve;
const extname = require('path').extname;
const fs = require('fs');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const stylusLoader = "style-loader!css-loader?minimize!stylus-loader";

//PATHS
const PUBLIC_PATH = '/';
const DIST = resolve('public');
const ENTRY = resolve('js');

// function WriteStatsPlugin() {
//   const args = [].slice.call(arguments);
//   const destination = args.shift();

//   return function writeStats() {
//     this.plugin('done', stats => {
//       const assets = {
//         'js': [PUBLIC_PATH + 'bundle.js'],
//         'css': []
//       };

//       fs.writeFileSync(
//         destination,
//         JSON.stringify(assets)
//       );
//     });
//   };
// };
function WriteStatsPlugin({publicPath, target}) {
  return function writeStats() {
    this.plugin('done', (stats) => {
      const json = stats.toJson();
      let chunks = json.assetsByChunkName['bundle'];

      if (!Array.isArray(chunks)) {
        chunks = [chunks];
      }

      const assets = chunks.filter((chunk) => {
        return ['.js', '.css'].indexOf(path.extname(chunk) > -1);
      }).reduce((memo, chunk) => {
        const ext = path.extname(chunk).match(/\.(.+)$/)[1];

        memo[ext] = memo[ext] || [];
        memo[ext].push(publicPath + chunk);

        return memo;
      }, {css: [], js: []});

      fs.writeFileSync(
        target,
        JSON.stringify(assets, null, 2)
      );
    });
  };
}


module.exports = {
  name: 'Site Client',
  entry: {
    bundle: [
      ENTRY
    ],
  },
  output: {
    path: resolve('../dist'),
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
      use: ExtractTextPlugin.extract({fallback:"style-loader", use:["css-loader?minimize","stylus-loader"]}),
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
    new ExtractTextPlugin('/styles/[hash].css'),
    new WriteStatsPlugin({
      publicPath: PUBLIC_PATH,
      target: `config/webpack-stats.json`,
    }),
  ]
};