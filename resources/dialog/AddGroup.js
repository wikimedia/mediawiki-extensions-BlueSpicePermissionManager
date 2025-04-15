bs.util.registerNamespace( 'bs.permissionManager.dialog' );

bs.permissionManager.dialog.AddGroup = function ( cfg ) {
	bs.permissionManager.dialog.AddGroup.parent.call( this, cfg );
};

OO.inheritClass( bs.permissionManager.dialog.AddGroup, bs.permissionManager.dialog.GroupNameDialog );

bs.permissionManager.dialog.AddGroup.static.name = 'addGroup';
bs.permissionManager.dialog.AddGroup.static.title = mw.msg( 'bs-permissionmanager-group-create-label' );
bs.permissionManager.dialog.AddGroup.static.size = 'medium';

bs.permissionManager.dialog.AddGroup.prototype.getUrl = function ( value ) {
	return mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/create/' + value;
};

bs.permissionManager.dialog.AddGroup.prototype.getMethod = function () {
	return 'POST';
};
