Ext.define('BS.PermissionManager.model.Data', {
	requires: [ 'BS.PermissionManager.model.Namespace' ],
	extend: 'Ext.data.Model',
	hasMany: [
		{
			model: 'BS.PermissionManager.model.Namespace', 
			name: 'namespaces'
		}
	]
});