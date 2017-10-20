//TODO: move to BSF
Ext.define('BS.PermissionManager.model.Namespace', {
	extend: 'Ext.data.Model',
	fields: [
		{name: 'id', type: 'int'},
		{name: 'name', type: 'string'}
	],
	idProperty: 'id'
});