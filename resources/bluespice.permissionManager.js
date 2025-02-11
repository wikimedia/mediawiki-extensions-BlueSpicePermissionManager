bs.util.registerNamespace( 'bs.permissionManager.util' );
bs.permissionManager.util.openRoleDetailsDialog = function( role ) {
	mw.loader.using( [ "ext.bluespice.permissionManager.roleDetailsDialog" ], function() {
		const manager = OO.ui.getWindowManager();
		const dialog = new bs.permissionManager.dialog.RoleDetailsDialog( {
			role: role
		} );
		manager.addWindows( [ dialog ] );
		manager.openWindow( dialog );
	} );
};

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
