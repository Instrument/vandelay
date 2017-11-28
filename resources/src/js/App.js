import React, { Component } from 'react';
import {
  Table,
  TableHeader,
  TableHeaderColumn,
  TableFooter,
  TableRow,
  TableRowColumn,
  TableBody,
  FontIcon,
  CircularProgress,
  Paper
} from 'material-ui';
import MuiThemeProvider from 'material-ui/styles/MuiThemeProvider';
import Controls from './Controls';

export default class App extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showLocales: true,
    };
  }
  handleToggle = (e) => {
    console.log('toggled', e);
    this.setState({
      [e.target.name]: !this.state[e.target.name],
    });
  };
  handleUpload(files) {
    const { endpoint } = this.props;
    for (var i = 0, f; f=files[i]; i++) {
      var reader = new FileReader();
      reader.onload = (function(theFile) {
        var fileName = theFile.name;
        var name;
        var regex = /(..\_..)+/gi;
        var m = fileName.match(regex);
        fileName.replace(regex, function(match, g1, g2) {
          if (g1.toLowerCase() === 'es_00') {
            name = 'es_es';
          } else {
            name = g1.toLowerCase();
          }
        });
        return function(e) {
          var p = JSON.parse(e.target.result);
          p.locale = name;
          if (p[0]) {
            [].slice.call(p).forEach(function(item) {
              item.locale = name;
            });
          }
          $.post({
            url: endpoint,
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify(p),
            success: function(data) {
              if (data.status == 200) {
                console.log(data);
              } else {
                console.log('error', data);
              }
            },
            error: function(err) {
              console.log('errrr', err);
            }
          });
        };
      })(f);
      reader.readAsText(f);
    }
  }
  render() {
    return (
      <div>
        <Controls
          handleFileUpload={::this.handleUpload}
          handleToggle={::this.handleToggle}
          showLocales={this.state.showLocales}
        />
        <MuiThemeProvider>
          <Paper style={{
            padding: '2em'
          }}>
            <Table multiSelectable>
              <TableHeader enableSelectAll>
                <TableRow>
                  <TableHeaderColumn>Section name</TableHeaderColumn>
                  <TableHeaderColumn>Status</TableHeaderColumn>
                  { this.state.showLocales && this.props.locales.map((loc) => 
                    <TableHeaderColumn key={loc}>
                      {loc}
                    </TableHeaderColumn>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody deselectOnClickaway={false}>
              { this.props.sections.map(section => 
                <TableRow key={section.name}>
                  <TableRowColumn>{section.name}</TableRowColumn>
                  <TableRowColumn>
                    <FontIcon 
                      color={(section.exported) ?
                          '#2196F3' : '#DA5B4C'
                        }
                      title={(section.exported) ?
                          'Exported' : 'Not exported'
                        }
                      className='material-icons'>
                      {(section.exported) ?
                          'cloud_done' : 'cloud_off'
                        }
                      </FontIcon>
                      {section.exporting && 
                        <CircularProgress/>
                      }
                  </TableRowColumn>
                  { this.state.showLocales && this.props.locales.map(loc => 
                    <TableRowColumn key={`${section.name}-${loc}`}>
                      <FontIcon 
                      color={(section.locales.indexOf(loc) > -1) ?
                          '#2196F3' : 'red'
                        }
                      className='material-icons'>
                      {(section.locales.indexOf(loc) > -1) ?
                          'thumb_up' : 'thumb_down'
                        }
                      </FontIcon>
                    </TableRowColumn>
                  )}
                </TableRow>
              )}
              </TableBody>
            </Table>
          </Paper>
        </MuiThemeProvider>
      </div>
    );
  }
}