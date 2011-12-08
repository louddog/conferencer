(function() {
	tinymce.create('tinymce.plugins.conferencer_agenda', {
		init: function(editor, url) {
			editor.addButton('conferencer_agenda', {
				title: "Agenda",
				image: url + '/agenda.png',
				onclick: function() {
					editor.execCommand('mceInsertContent', 0, '[agenda]');
				}
			});
		},
		createControl: function(n, cm) {
			return null;
		}
	});
	
	tinymce.PluginManager.add('conferencer_agenda', tinymce.plugins.conferencer_agenda);
})();