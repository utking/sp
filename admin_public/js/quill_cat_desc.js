(function() {
  var advancedEditor, advancedEditor1, cursorManager, cursorManager1;
  var inputText = $('#category_description_text input');
  var inputText1 = $('#category_rules_text input');

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
  
  advancedEditor1 = new Quill('.advanced-wrapper1 .editor-container', {
    modules: {
      'toolbar': {
        container: '.advanced-wrapper1 .toolbar-container'
      },
      'link-tooltip': true,
      'image-tooltip': true,
      'multi-cursor': true
    },
    theme: 'snow'
  });

  cursorManager = advancedEditor.getModule('multi-cursor');
  cursorManager1 = advancedEditor1.getModule('multi-cursor');

  advancedEditor.on('selection-change', function(range) {
      return true;
      //return console.info('advanced', 'selection', range);
  });
  advancedEditor1.on('selection-change', function(range) {
      return true;
      //return console.info('advanced', 'selection', range);
  });

  advancedEditor.on('text-change', function(delta, source) {
    var sourceDelta;
    if (source === 'api') {
      return;
    }
    sourceDelta = advancedEditor.getHTML();
    inputText.val(sourceDelta);
  });
  
  advancedEditor1.on('text-change', function(delta, source) {
    var sourceDelta;
    if (source === 'api') {
      return;
    }
    sourceDelta = advancedEditor1.getHTML();
    inputText1.val(sourceDelta);
  });

}).call(this);
