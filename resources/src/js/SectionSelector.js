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

export default class SectionSelector extends Component {
  isSelected(section) {
    let selected =false;
    this.props.selectedSections.map(sec => {
      if (sec.name === section.name) {
        selected = true;
      }
    });
    return selected;
  }
  getColor(section) {
    if (section.exported === 'pending') {
      return '#2196F3';
    } else if (section.exported) {
      return '#3cad49';
    }  else {
      return '#CCCCCC';
    }
  }
  render() {
    return (
      <MuiThemeProvider>
          <Paper style={{
            padding: '2em'
          }}>
            <Table onRowSelection={this.props.handleSelect} multiSelectable>
              <TableHeader enableSelectAll>
                <TableRow>
                  <TableHeaderColumn>Section name</TableHeaderColumn>
                  <TableHeaderColumn>Status</TableHeaderColumn>
                  { this.props.showLocales && this.props.locales.map((loc) => 
                    <TableHeaderColumn key={loc}>
                      {loc}
                    </TableHeaderColumn>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody deselectOnClickaway={false}>
              {this.props.sections.map(section => 
                <TableRow key={section.name} selected={this.isSelected(section)}>
                  <TableRowColumn>{section.name}</TableRowColumn>
                  <TableRowColumn>
                    {section.exported === 'pending' && 
                      <CircularProgress size={20}/>
                    }
                    {section.exported !== 'pending' && 
                      <span>
                        <FontIcon 
                          color={this.getColor(section)}
                          title={(section.exported) ?
                              'Exported' : 'Not exported'
                            }
                          className='material-icons'>
                          import_export
                        </FontIcon>
                      </span>
                    }
                  </TableRowColumn>
                  { this.props.showLocales && this.props.locales.map(loc => 
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
    )
  }
}