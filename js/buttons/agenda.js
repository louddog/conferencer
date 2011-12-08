conferencer.tinymce_button({
	slug: 'conferencer_agenda',
	title: "Agenda",
	image: 'agenda.png',
	onclick: function(editor) {
		editor.execCommand('mceInsertContent', 0, '[agenda]');
	}
});