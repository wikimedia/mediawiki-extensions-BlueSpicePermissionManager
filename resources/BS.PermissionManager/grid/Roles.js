Ext.define( 'BS.PermissionManager.grid.Roles', {
	extend: 'Ext.grid.Panel',
	requires: [
		'BS.PermissionManager.grid.column.RoleCheck',
		'BS.PermissionManager.grid.column.RoleHint',
		'BS.PermissionManager.store.Roles'
	],
	sortableColumns: false,
	forceFit: true,
	stateful: true,
	stateId: 'bs-pm-grid-state',
	border: true,
	cls: 'bs-grid-panel-roles',
	initComponent: function() {
		this.store = new BS.PermissionManager.store.Roles({
			storeId: 'bs-permissionmanager-role-store'
		});

		this.columns = Ext.create( 'BS.PermissionManager.data.Manager' ).getColumns();

		this.callParent( arguments );
	}
} );
