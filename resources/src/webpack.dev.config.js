const webpack = require('webpack');
const argv = require('yargs').argv;
const resolve = require('path').resolve;
const extname = require('path').extname;
const fs = require('fs');
const stylusLoader = "style-loader!css-loader?minimize!stylus-loader";
const PORT = parseInt((4000), 10) + 1;

//PATHS
const PUBLIC_PATH = 'http://localhost:' + PORT + '/dist/';
const HMR_ROOT = 'http://localhost:' + PORT + '/';
const HMR = '__webpack_hmr';
const DIST = resolve('..');
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
      ENTRY,
      'webpack-hot-middleware/client?path=' + HMR_ROOT + HMR
    ],
  },
  output: {
    path: '/',
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
      loader: stylusLoader,
    }, {
      test: /\.js$/,
      exclude: /node_modules/,
      loader: 'babel-loader',
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
      '/dist/'
    ),
    new webpack.HotModuleReplacementPlugin(),
    new webpack.LoaderOptionsPlugin({
      test: /\.styl$/,
      stylus: {
        default: {
          use: [require('nib')()],
        },
      },
    })
  ]
};