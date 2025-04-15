bs.util.registerNamespace( 'bs.permissionManager.dialog' );

bs.permissionManager.dialog.EditGroup = function ( cfg ) {
	bs.permissionManager.dialog.EditGroup.parent.call( this, cfg );

	this.group = cfg.group;
};

OO.inheritClass( bs.permissionManager.dialog.EditGroup, bs.permissionManager.dialog.GroupNameDialog );

bs.permissionManager.dialog.EditGroup.static.name = 'editGroup';
bs.permissionManager.dialog.EditGroup.static.title = mw.msg( 'bs-permissionmanager-edit-group' );
bs.permissionManager.dialog.EditGroup.static.size = 'medium';

bs.permissionManager.dialog.EditGroup.prototype.getUrl = function ( value ) {
	return mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/edit/' + this.group + '/' + value;
};

bs.permissionManager.dialog.EditGroup.prototype.getMethod = function () {
	return 'POST';
};
