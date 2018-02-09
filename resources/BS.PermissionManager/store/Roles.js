Ext.define( 'BS.PermissionManager.store.Roles', {
	requires: [
		'BS.PermissionManager.data.Manager',
		'RoleGridModel'
	],
	extend: 'Ext.data.Store',
	autoLoad: true,
	model: 'RoleGridModel',
	groupField: 'type',
	proxy: {
		type: 'memory'
	},

	constructor: function( cfg ) {
		cfg.data = cfg.data || Ext.create( 'BS.PermissionManager.data.Manager' ).buildRoleData().roles;
		this.callParent( arguments );
	}
} );