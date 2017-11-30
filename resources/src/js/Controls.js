import React, { Component } from 'react';
import {
  RaisedButton,
  Paper,
  Toolbar,
  Table,
  TableHeader,
  TableHeaderColumn,
  TableFooter,
  TableRow,
  TableRowColumn,
  TableBody,
  ToolbarGroup,
  FontIcon,
  CircularProgress,
  Dialog
} from 'material-ui';
import { findDOMNode } from 'react-dom';
import MuiThemeProvider from 'material-ui/styles/MuiThemeProvider';
import './controls.styl';
import classNames from 'classnames';
import axios from 'axios';

export default class Controls extends Component {
  constructor(props) {
    super(props);
    this.state = {
      modalOpen: false,
      files: [],
      dragging: false,
    };
  }
  openModal(e) {
    e.preventDefault();
    this.setState({
      modalOpen: true,
    });
    setTimeout(() => {
      const form = findDOMNode(this.refs.formWrapper);
      form.addEventListener('dragenter', e => {
        this.handleDrag(e);
      });
      form.addEventListener('dragover', e => {
        this.handleDrag(e);
      });
      form.addEventListener('drop', e => {
        this.handleDrop(e);
      });
      form.addEventListener('dragleave', e => {
        this.handleEndDrag(e);
      });
    }, 500);
  }
  handleClose() {
    this.setState({
      modalOpen: false,
    });
  }
  setFiles(dropFiles) {
    const files = [];
    for( var i=0; i < dropFiles.length; i++) {
      files.push({
        name: dropFiles[i].name
      });
    }
    this.setState({
      files,
      dragging: false
    });
  }
  handleFile(e) {
    const files = [];
    for( var i=0; i < e.target.files.length; i++) {
      files.push({
        name: e.target.files[i].name
      });
    }
    this.setState({
      files
    });
  }
  handleDrag(e) {
    e.preventDefault();
    this.setState({
      dragging: true,
    });
  }
  handleEndDrag(e) {
    e.preventDefault();
    this.setState({
      dragging: false
    });
  }
  handleDrop(e) {
    e.preventDefault();
    console.log(e);
    const files = e.dataTransfer.files;
    const fileBuffer = [];
    this.setFiles(files);
    Array.prototype.push.apply(fileBuffer, files);
    for( var i=0; i < fileBuffer.length; i++) {
      this.setStatus({
        name: fileBuffer[i].name,
        status: 100
      });
    }
    const response = this.props.handleFileUpload(files, (res) => {
      this.setStatus(res);
    });
  }
  addFile(e) {
    const inputs = e.target.querySelectorAll('input');
    [].slice.call(inputs).forEach(input => {
      input.click();
    });
  }
  getLocale(name) {
    let locale;
    let regex = /(..\_..)+/gi;
    let m = name.match(regex);
    
    name.replace(regex, function(match, g1, g2) { 
      locale = g1.toLowerCase();
    });
    if (locale == 'es_00') {
      locale = 'es_es';
    }
    return locale;
  }
  setStatus(file) {
    const currentFiles = this.state.files;
    currentFiles.map((currFile, i) => {
      if (currFile.name === file.name) {
        currentFiles[i].status = file.status;
      }
    })
    this.setState({
      files: currentFiles
    });
  }
  handleUpload(e) {
    e.preventDefault();
    const input = findDOMNode(this.refs.fileInput);
    const files = input.files;
    const fileBuffer = [];
    Array.prototype.push.apply(fileBuffer, files);
    for( var i=0; i < fileBuffer.length; i++) {
      this.setStatus({
        name: fileBuffer[i].name,
        status: 100
      });
    }
    const response = this.props.handleFileUpload(files, (res) => {
      this.setStatus(res);
    });
  }
  getColor(file) {
    if (file.status == 200) {
      return '#3cad49';
    } else if (file.status == 500) {
      return '#cc0000';
    }  else {
      return '#CCCCCC';
    }
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
            contentClassName={'dialog-content'}
          >
            <form 
                  className={classNames(['box', {
                    dragging: this.state.dragging
                  }])}
                  ref="formWrapper"
                  onClick={::this.addFile}
                  onSubmit={::this.handleUpload}>
              <div className="box__input">
                {(this.state.files.length > 0) &&
                  <div>
                  <Table selectable={false}>
                    <TableHeader
                      adjustForCheckbox={false}
                      displaySelectAll={false}>
                      <TableHeaderColumn>
                        Filename
                      </TableHeaderColumn>
                      <TableHeaderColumn>
                        Target Locale
                      </TableHeaderColumn>
                      <TableHeaderColumn>
                        Status
                      </TableHeaderColumn>
                    </TableHeader>
                    <TableBody displayRowCheckbox={false}>
                    {this.state.files.map((file, index) => 
                      <TableRow
                        key={`file-${index}`}>
                        <TableRowColumn>
                          {file.name}
                        </TableRowColumn>
                        <TableRowColumn>
                          {this.getLocale(file.name)}
                        </TableRowColumn>
                        <TableRowColumn>
                        {(file.status !== 100) &&
                          <FontIcon 
                            color={this.getColor(file)}
                            title={(file.status) ?
                                'Exported' : 'Not exported'
                              }
                            className='material-icons'>
                            import_export
                          </FontIcon>
                        }
                        {(file.status === 100) &&
                          <CircularProgress size={20}/>
                        }
                        </TableRowColumn>
                      </TableRow>
                    )}
                    </TableBody>
                  </Table>
                  <RaisedButton
                      label="Upload"
                      className="box__button"
                      type="submit"/>
                  </div>
                }
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
              </div>
            </form>
          </Dialog>
          </Toolbar>
      </MuiThemeProvider>
    );
  }
}