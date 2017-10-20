Ext.define('BS.PermissionManager.grid.Permissions', {
	extend: 'Ext.grid.Panel',
	requires: [
		'BS.PermissionManager.grid.column.PermissionCheck',
		'BS.PermissionManager.grid.column.PermissionHint',
		'BS.PermissionManager.store.Permissions'
	],
	sortableColumns: false,
	forceFit: true,
	stateful: true,
	stateId: 'bs-pm-grid-state',

	initComponent: function() {
		this.store = new BS.PermissionManager.store.Permissions({
			storeId: 'bs-permissionmanager-permission-store'
		});

		this.features = [{
				ftype: 'grouping',
				groupHeaderTpl: [
					'{children:this.formatName}',
					{
						formatName: function(children) {
							return children[0].get('typeHeader');
						}
					}
				],
				hideGroupedHeader: true,
				enableGroupingMenu: false,
				collapsible: true
			}];

		this.columns = Ext.create('BS.PermissionManager.data.Manager').getColumns();
		this.callParent(arguments);
	}
});
