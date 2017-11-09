'use strict';

const ENV = (process.env.NODE_ENV || 'DEV').trim();

let configFile;
switch (ENV) {
case 'PROD':
  configFile = require('./webpack.prod.config.js');
  break;
case 'DEV':
default:
  configFile = require('./webpack.dev.config.js');
}

module.exports = configFile;