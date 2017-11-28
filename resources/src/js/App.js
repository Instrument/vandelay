import React, { Component } from 'react';
import saveAs from './fetch';
import axios from 'axios';
import SectionSelector from './SectionSelector';
import Controls from './Controls';

export default class App extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showLocales: false,
      selectedSections: [],
      sections: this.props.sections,
      imports: [],
    };
  }
  handleToggle = (e) => {
    this.setState({
      [e.target.name]: !this.state[e.target.name],
    });
  };
  handleSelect = (rows) => {
    console.log(rows)
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
        selectedSections.push({
          ...item,
          sectionIndex: row,
        });
      });
      this.setState({
        selectedSections
      });
    }
  }
  handleExport(e) {
    e.preventDefault();
    const { selectedSections, sections } = this.state;
    [].slice.call(selectedSections).forEach(section => {
      sections[section.sectionIndex].exported = 'pending';
      this.setState({
        sections
      });
      if (section.name === 'Globals') {
        axios.get(`/actions/vandelay/getGlobals?download=1`)
          .then(res => {
            const { data } = res;
            const filename = `globals-en_us`;
            const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
            saveAs(blob, filename+".json");
            sections[section.sectionIndex].exported = true;
            this.setState({
              sections
            });
          });
      } else if (section.name === 'Categories') {
        axios.get(`/actions/vandelay/getCategories?download=1`)
          .then(res => {
            const { data } = res;
            const filename = `categories-en_us`;
            const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
            saveAs(blob, filename+".json");
            sections[section.sectionIndex].exported = true;
            this.setState({
              sections
            });
          });
      } else if (section.id) {
        axios.get(`/vandelay/Entry/${section.id}/en_us?download=1`)
          .then(res => {
            const { data } = res;
            const filename = `${section.slug}-en_us`;
            const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
            saveAs(blob, filename+".json");
            sections[section.sectionIndex].exported = true;
            this.setState({
              sections
            });
          });
      } else {
        axios.get(`/vandelay/getSection/${section.handle}?download=1`)
        .then(res => {
          const { data } = res;
          const filename = `${section.handle}-en_us`;
          const blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
          saveAs(blob, filename+".json");
          sections[section.sectionIndex].exported = true;
          this.setState({
            sections
          });
        });
      }
    });
  }
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
          handleExport={::this.handleExport}
          handleFileUpload={::this.handleUpload}
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