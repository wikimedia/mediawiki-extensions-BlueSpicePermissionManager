Ext.define('BS.PermissionManager.store.TemplateTree', {
	requires: [
		'BS.PermissionManager.data.Manager',
		'BS.PermissionManager.model.Template'
	],
	extend: 'Ext.data.TreeStore',
	model: 'BS.PermissionManager.model.Template',
	root: {
		expanded: true,
		children: Ext.create('BS.PermissionManager.data.Manager').getPermissionTemplates(),
		proxy: {
			type: 'memory',
			reader: {
				type: 'json'
			}
		}
	}
});