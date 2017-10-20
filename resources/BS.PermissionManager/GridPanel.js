Ext.define('BS.PermissionManager.GridPanel', {
	extend: 'Ext.grid.Panel',
	renderTo: 'panelPermissionManager', //TODO: Is this a good place?
	columnLines: true,
	width: 970,
	height: 550,
	frame: true,
	header: false,
	iconCls: 'icon-grid',
	features: [{
		ftype: 'grouping',
		startCollapsed: false,
		enableGroupingMenu: false,
		groupHeaderTpl: '{name}'
	}],
	selModel: {
		selType: 'cellmodel'
	},
	dockedItems: [],
	columns: []
});