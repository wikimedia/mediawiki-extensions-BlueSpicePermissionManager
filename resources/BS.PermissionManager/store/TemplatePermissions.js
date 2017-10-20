Ext.define('BS.PermissionManager.store.TemplatePermissions', {
	requires: [
		'BS.PermissionManager.data.Manager'
	],
	extend: 'Ext.data.Store',
	fields: ['name', 'enabled'],
	data: {
		'items': Ext.create('BS.PermissionManager.data.Manager').getTemplateRights()
	},
	proxy: {
		type: 'memory',
		reader: {
			type: 'json',
			root: 'items'
		}
	}
});