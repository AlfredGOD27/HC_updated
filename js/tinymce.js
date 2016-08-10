(function() {

	tinymce.create('tinymce.plugins.HC', {
		init : function(ed, url) {
			ed.addButton('hc_component', {
				title : 'Insert Component',
				icon : ' dashicons-before dashicons-grid-view',
				onclick : function() {
					ed.windowManager.open({
						title: 'Insert Component',
						body: [
							{type: 'listbox', name: 'component_id', label: 'Name', values: window.components}
						],
						onsubmit: function(e) {
							ed.focus();
							ed.insertContent('[hc_component id="' + e.data.component_id + '"]');
						}
					});
				}
            });
        }
    });

    tinymce.PluginManager.add( 'hc_admin', tinymce.plugins.HC );

})();
