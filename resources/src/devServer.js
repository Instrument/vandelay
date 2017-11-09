const express = require('express');
const webpack = require('webpack');
const resolve = require('path').resolve;
const config = require('./webpack.dev.config');
const PORT = parseInt((4000), 10) + 1;
const compiler = webpack(config);
const app = express();

app.use(require('webpack-dev-middleware')(compiler, {
  hot: true,
  silent: true,
  noInfo: true,
  stats: {
    colors: true
  },
  historyApiFallback: true,
  publicPath: config.output.publicPath,
  contentBase: __dirname + '/public/index.html'
}));

app.use(require('webpack-hot-middleware')(compiler));

app.use(express.static('public'));

app.get('*', function (request, response){
  response.sendFile(resolve('public', 'index.html'))
});
app.listen(PORT, 'localhost', function onStart(err) {
  if (err) {
    process.exit(0);
  }
  console.log('Listening at http://localhost:%s', PORT);
});