( function ( $, mw, bs, d ) {
	bs.util.registerNamespace( 'bs.permissionManager.widget' );

	bs.permissionManager.widget.PresetWidget = function ( data ) {


		data.title = data.help;
		bs.permissionManager.widget.PresetWidget.parent.call( this, data );
	};

	OO.inheritClass( bs.permissionManager.widget.PresetWidget, OO.ui.ButtonWidget );

} )( jQuery, mediaWiki, blueSpice, document );
