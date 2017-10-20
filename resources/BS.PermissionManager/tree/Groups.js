Ext.define('BS.PermissionManager.model.Group', {
	extend: 'Ext.data.Model',
	fields: [
		{name: 'text', type: 'string'}
	],
	idProperty: 'text'
});

Ext.define('BS.PermissionManager.tree.Groups', {
	extend: 'Ext.tree.Panel',
	requires: [
		'BS.PermissionManager.data.Manager'
	],
	border:true,
	title: mw.message('bs-permissionmanager-header-group').plain(),
	listeners: {
		viewready: function(panel) {
			var group = Ext.create('BS.PermissionManager.data.Manager').getWorkingGroup();
			var node = panel.getStore().getNodeById(group);
			panel.getSelectionModel().select(node, false, true);
		},
		select: function( self, record ) {
			var group = record.get('text');
				var dataManager = Ext.create('BS.PermissionManager.data.Manager');

				dataManager.setWorkingGroup(group);
			this.up('window').setTitle(mw.message('bs-permissionmanager-btn-group-label').plain() + ' ' + group);
				Ext.data.StoreManager.lookup('bs-permissionmanager-permission-store').loadRawData(dataManager.buildPermissionData().permissions);
		}
	},
	stateful: true,
	stateId: 'bs-pm-group-tree-state',
	initComponent: function() {
		this.store = new Ext.data.TreeStore({
			storeId: 'bs-pm-group-tree',
			model: 'BS.PermissionManager.model.Group',
			root: mw.config.get( 'bsPermissionManagerGroupsTree' )
		});
		this.callParent(arguments);
	}
});