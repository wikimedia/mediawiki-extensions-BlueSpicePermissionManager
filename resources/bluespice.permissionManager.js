bs.util.registerNamespace( 'bs.permissionManager.util' );
bs.permissionManager.util.openRoleDetailsDialog = function ( role ) {
	mw.loader.using( [ 'ext.bluespice.permissionManager.roleDetailsDialog' ], () => {
		const manager = OO.ui.getWindowManager();
		const dialog = new bs.permissionManager.dialog.RoleDetailsDialog( {
			role: role
		} );
		manager.addWindows( [ dialog ] );
		manager.openWindow( dialog );
	} );
};

$( () => {
	const $cnt = $( '#bs-permission-manager-preset-select' );
	if ( !$cnt.length ) {
		return;
	}

	$cnt.html(
		new bs.permissionManager.widget.PresetSelect( {
			data: JSON.parse( $cnt.attr( 'data' ) ),
			$customPanel: $( '#bs-permission-manager-custom-preset' )
		} ).$element
	);
	if ( $( document ).find( '#bs-permissionManager-skeleton-cnt' ) ) {
		$( '#bs-permissionManager-skeleton-cnt' ).empty();
	}
} );
