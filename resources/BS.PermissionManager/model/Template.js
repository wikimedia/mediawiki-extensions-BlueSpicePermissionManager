Ext.define('BS.PermissionManager.model.Template', {
	extend: 'Ext.data.Model',
	fields: [
		{name: 'id', type: 'int'},
		{name: 'text', type: 'string'},
		{name: 'leaf', type: 'boolean'},
		{name: 'ruleSet'},
		{name: 'description', type: 'string'}
	],
	idProperty: 'text'
});