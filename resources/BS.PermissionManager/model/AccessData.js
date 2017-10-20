Ext.define('BS.PermissionManager.model.AccessData', {
	extend: 'Ext.data.Model',
	fields: [
		{name: 'permission', type: 'string'},
		{name: 'data', type: 'auto'}
	],
	idProperty: 'permission'
});