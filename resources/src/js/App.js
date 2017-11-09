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
  render() {
    return (
      <div>
        <Controls
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