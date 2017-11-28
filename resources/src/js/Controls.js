import React, { Component } from 'react';
import {
  RaisedButton,
  Paper,
  Toolbar,
  ToolbarSeparator,
  ToolbarGroup,
  FontIcon,
  Chip,
  Dialog
} from 'material-ui';
import { findDOMNode } from 'react-dom';
import MuiThemeProvider from 'material-ui/styles/MuiThemeProvider';
import './controls.styl';
import axios from 'axios';

export default class Controls extends Component {
  constructor(props) {
    super(props);
    this.state = {
      modalOpen: false,
      files: []
    };
  }
  openModal(e) {
    e.preventDefault();
    this.setState({
      modalOpen: true,
    });
  }
  handleClose() {
    this.setState({
      modalOpen: false,
    });
  }
  handleFile(e) {
    const files = [];
    for( var i=0; i < e.target.files.length; i++) {
      files.push({
        name: e.target.files[i].name,
        upload: (this.state.files[i] ? this.state.files[i].upload : true),
      });
    }
    this.setState({
      files
    });
  }
  addFile(e) {
    const inputs = e.target.querySelectorAll('input');
    [].slice.call(inputs).forEach(input => {
      input.click();
    });
  }
  removeFile(e) {
    const input = findDOMNode(this.refs.fileInput);
    const files = input.files;
    const fileName = e.target.parentNode.parentNode.getAttribute('name');
    const fileBuffer = [];
    const currentFiles = this.state.files;
    Array.prototype.push.apply( fileBuffer, files );
    for( var i=0; i < fileBuffer.length; i++) {
      if (fileBuffer[i].name !== fileName) {
        currentFiles[i].upload = false;
      }
    }
    this.setState({
      files: currentFiles
    });
  }
  handleUpload(e) {
    e.preventDefault();
    const input = findDOMNode(this.refs.fileInput);
    const files = input.files;
    this.props.handleFileUpload(files);
  }
  render() {
    return (
      <MuiThemeProvider>
          <Toolbar>
          <ToolbarGroup>
          <RaisedButton
            onClick={this.props.handleExport}
            className="trigger"
            label="Export"/>
          <RaisedButton 
            onClick={::this.openModal}
            label="Import"/>
          </ToolbarGroup>
          <ToolbarSeparator/>
          <ToolbarGroup>
          <RaisedButton
            label={`${!this.props.showLocales ? 'Show' : 'Hide'} Locales`}
            backgroundColor={this.props.showLocales ? '#2196F3' : 'white'}
            labelColor={!this.props.showLocales ? '#2196F3' : 'white'}
            onClick={(e) => {
              e.target.name = "showLocales";
              this.props.handleToggle(e);
              }
            }/>
          </ToolbarGroup>
          <Dialog
            title="Import localized JSON"
            modal={false}
            open={this.state.modalOpen}
            onRequestClose={::this.handleClose}
          >
            <form className="box" onClick={::this.addFile}
                  onSubmit={::this.handleUpload}>
              <div className="box__input">
                {this.state.files.map(file => 
                  file.upload && <Chip
                    name={file.name}
                    key={file.name}>{file.name}</Chip>
                )}
                <input
                  ref="fileInput"
                  onChange={::this.handleFile}
                  className="box__file"
                  type="file"
                  name="files[]"
                  id="file"
                  data-multiple-caption="{count} files selected" 
                  multiple />
                <label htmlFor="file">
                  {!this.state.files.length &&
                    <span>
                      <FontIcon className='material-icons'>
                        file_upload
                      </FontIcon>
                      <p><strong>Choose a file</strong></p>
                      <p className="box__dragndrop"> or drag it here</p>
                    </span>
                  }
                </label>
                <RaisedButton
                  label="Upload"
                  className="box__button"
                  type="submit"/>
              </div>
            </form>
          </Dialog>
          </Toolbar>
      </MuiThemeProvider>
    );
  }
}