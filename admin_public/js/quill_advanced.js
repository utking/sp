(function() {
  var advancedEditor, cursorManager;
  var rulesText = $('#rules-text input');

  advancedEditor = new Quill('.advanced-wrapper .editor-container', {
    modules: {
      'toolbar': {
        container: '.advanced-wrapper .toolbar-container'
      },
      'link-tooltip': true,
      'image-tooltip': true,
      'multi-cursor': true
    },
    theme: 'snow'
  });

  cursorManager = advancedEditor.getModule('multi-cursor');

  advancedEditor.on('selection-change', function(range) {
      return true;
      //return console.info('advanced', 'selection', range);
  });

  advancedEditor.on('text-change', function(delta, source) {
    var sourceDelta;
    if (source === 'api') {
      return;
    }
    sourceDelta = advancedEditor.getHTML();
    rulesText.val(sourceDelta);
  });

}).call(this);
