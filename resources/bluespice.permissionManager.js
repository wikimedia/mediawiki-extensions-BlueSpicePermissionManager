
( function( $, mw, d ) {
	$( function () {
		var $cnt = $( '#bs-permission-manager-preset-select' );
		if ( !$cnt.length ) {
			return;
		}

		$cnt.html(
			new bs.permissionManager.widget.PresetSelect( {
				data: JSON.parse( $cnt.attr( 'data' ) ),
				$customPanel: $( '#bs-permission-manager-custom-preset' )
			} ).$element
		);
	} );
} )( jQuery, mediaWiki, document );
