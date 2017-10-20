Ext.define('BS.PermissionManager.store.Permissions', {
	requires: [
		'BS.PermissionManager.data.Manager',
		'PermissionGridModel'
	],
	extend: 'Ext.data.Store',
	autoLoad: true,
	model: 'PermissionGridModel',
	groupField: 'type',
	proxy: {
		type: 'memory'
	},

	constructor: function(cfg) {
		cfg.data = cfg.data || Ext.create('BS.PermissionManager.data.Manager').buildPermissionData().permissions;
		cfg.sorters = cfg.sorters || [
			{
				property: 'right',
				direction: 'asc'
			}
		];
		this.callParent(arguments);
	}
});