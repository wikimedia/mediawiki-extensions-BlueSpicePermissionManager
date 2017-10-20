Ext.define('BS.PermissionManager.GridPanelExtra', {
	extend: 'Ext.grid.Panel',
	renderTo: 'panelPermissionManagerExtra', //TODO: Is this a good place?
	columnLines: true,
	width: 970,
	height: 250,
        //hidden: true,
	frame: true,
	header: false,
	iconCls: 'icon-grid',
	selModel: {
		selType: 'cellmodel'
	},
	dockedItems: [],
	columns: []
});