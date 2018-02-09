Ext.define('BS.PermissionManager.tree.Groups', {
	extend: 'Ext.tree.Panel',
	requires: [
		'BS.PermissionManager.data.Manager'
	],
	border: true,
	preventHeader: true,
	viewConfig:{
		markDirty:false
	},
	listeners: {
		viewready: function(panel) {
			var group = Ext.create( 'BS.PermissionManager.data.Manager' ).getWorkingGroup();
			var node = panel.getStore().getNodeById( group );
			panel.getSelectionModel().select(node, false, true);
			var rootNode = this.getRootNode();
			rootNode.set( 'text', mw.message( 'bs-permissionmanager-btn-group-label' ).plain() + group );
		},
		beforeselect: function( self, record ) {
			if( record.get( 'root' ) ) {
				return false;
			}
			return true;
		},
		select: function( self, record ) {
			if( record.get( 'root' ) ) {
				return;
			}
			var group = record.get( 'text' );
			var dataManager = Ext.create( 'BS.PermissionManager.data.Manager' );
			dataManager.setWorkingGroup( group );

			var rootNode = this.getRootNode();
			rootNode.set( 'text', mw.message( 'bs-permissionmanager-btn-group-label' ).plain() + group );
			Ext.data.StoreManager.lookup( 'bs-permissionmanager-role-store' ).loadRawData( dataManager.buildRoleData().roles );
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