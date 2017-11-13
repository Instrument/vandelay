import React, { Component } from 'react';
import axios from 'axios';
import { saveAs } from './fetch';
import SectionSelector from './SectionSelector';
import Controls from './Controls';

export default class App extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showLocales: true,
      selectedSections: [],
      sections: this.props.sections,
    };
  }
  handleToggle = (e) => {
    console.log('toggled', e);
    this.setState({
      [e.target.name]: !this.state[e.target.name],
    });
  };
  handleSelect = (rows) => {
    const selectedSections = [];
    if (rows === 'all') {
      this.setState({
        selectedSections: this.props.sections
      });
    } else if (rows === 'none') {
      this.setState({
        selectedSections
      });
    } else {
      [].slice.call(rows).forEach(row => {
        const item = this.props.sections[row];
        selectedSections.push(item);
      });
      this.setState({
        selectedSections
      });
    }
  }
  handleExport(e) {
    e.preventDefault();
    const { selectedSections } = this.state;
    [].slice.call(selectedSections).forEach(section => {
      if (section === 'globals') {
        axios.get(`/actions/vandelay/getGlobals?download=1`, data => {
          const filename = `globals-en_us`;
          const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
          saveAs(blob, filename+".json");
        });
      } else if (section === 'categories') {
        axios.get(`/actions/vandelay/getCategories?download=1`, data => {
          const filename = `categories-en_us`;
          const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
          saveAs(blob, filename+".json");
        });
      } else {
        const {sections} = this.state;
        sections.indexOf
        this.setState({
          sections: newSections
        });
        axios.get(`/vandelay/getSection/${section.handle}?download=1`, data => {
          const filename = `${section.handle}-en_us`;
          const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
          saveAs(blob, filename+".json");
        });
      }
    });
  }
  render() {
    return (
      <div>
        <Controls
          handleExport={::this.handleExport}
          handleToggle={::this.handleToggle}
          showLocales={this.state.showLocales}
        />
        <SectionSelector
          showLocales={this.state.showLocales}
          locales={this.props.locales}
          selectedSections={this.state.selectedSections}
          sections={this.state.sections}
          handleSelect={::this.handleSelect}/>
      </div>
    );
  }
}