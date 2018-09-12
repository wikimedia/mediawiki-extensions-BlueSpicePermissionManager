Ext.define('BS.PermissionManager.tree.Groups', {
	extend: 'Ext.tree.Panel',
	requires: [
		'BS.PermissionManager.data.Manager',
		'BS.PermissionManager.model.Group'
	],
	border: true,
	header: false,
	viewConfig:{
		markDirty:false
	},
	listeners: {
		viewready: function(panel) {
			var group = Ext.create( 'BS.PermissionManager.data.Manager' ).getWorkingGroup();
			var node = panel.getStore().getNodeById( group );
			panel.getSelectionModel().select(node, false, true);
		},
		select: function( self, record ) {
			var group = record.get( 'text' );
			var dataManager = Ext.create( 'BS.PermissionManager.data.Manager' );
			dataManager.setWorkingGroup( group );

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
	},

	showSystemGroups: function( show ) {
		var selection = this.getSelection();
		var selected = selection.length == 1 ? selection[ 0 ] : null;

		if( selected && selected.data.builtin && !show ) {
			// If selected group is going to be hidden,
			// switch selection to root
			this.setSelection( this.getRootNode() );
		}
		var nodes = this.getRootNode().childNodes[ 0 ];
		for( var idx in nodes.childNodes ) {
			var childNode = nodes.childNodes[ idx ];
			if( !childNode.data.builtin ) {
				continue;
			}
			var viewNode = this.getView().getNodeByRecord( childNode );
			if( show ) {
				$( viewNode ).show();
			} else {
				$( viewNode ).hide();
			}
		}
	}
});