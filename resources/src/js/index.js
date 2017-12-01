import './fetch';
import '../stylus/index.styl';
import React from 'react';
import { render } from 'react-dom';
import App from './App';
const props = JSON.parse(document.getElementById('props').innerHTML);

render((
  <App {...props} />
), document.getElementById('vandelay'));
