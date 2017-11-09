$('.trigger').on('click', function() {
    $('[id^=section-]').text('exporting');
    $('[id^=cat-]').text('exporting');
    $('#globals').text('exporting');
    {% for section in sections %}
      $.get( "/simpleapi/getSection/{{ section.handle }}?download=1", function( data ) {
        var filename = '{{section.handle}}-{{craft.locale}}';
        var blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
        saveAs(blob, filename+".json");
        $('#section-{{section.handle}}').text('exported');
      });
    {% endfor %}
    $.get( "/actions/simpleApi/getGlobals", function( data ) {
        var filename = data.title + '-' + data.locale;
        var blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
        saveAs(blob, filename+".json");
        $('#globals').text('exported');
    });
    $.get( "/actions/simpleApi/getCategories", function( data ) {
      var filename = 'categories-{{craft.locale}}';
      var blob = new Blob([JSON.stringify(data)], {type: "application/json;charset=utf-8"});
      saveAs(blob, filename+".json");
      {% for cat in categories %}
        $('#cat-{{ cat.title|split(' ')|join }}').text('exported');
      {% endfor %}
    });
  });
  var $form = $('form');
  var droppedFiles = false;
  var fileSelect = document.getElementById('file');
  $form.on('submit', function (e) {
    e.preventDefault();

    var ajaxData = new FormData();
    var files = fileSelect.files;
    for (var i = 0, f; f = files[i]; i++) {
      var file = files[i];
      var reader = new FileReader();

      reader.onload = (function(theFile) {
        return function(e) {
          var p = JSON.parse(e.target.result);
          $.post({
            url: $form.attr('action'),
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify(p),
            success: function(data) {
              console.log(data);
            },
          });
        };
      })(f);
      reader.readAsText(f);
    }
  })