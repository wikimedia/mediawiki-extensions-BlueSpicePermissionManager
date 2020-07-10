( function( $, mw, d ) {
	$( function () {
		$( '#bs-permission-manager-preset-select' ).append(
			new bs.permissionManager.widget.PresetSelect( {
				data: mw.config.get( 'bsPermissionManagerPresets' ),
				$customPanel: $( '#bs-permission-manager-custom-preset' )
			} ).$element
		);
	} );
} )( jQuery, mediaWiki, document );
