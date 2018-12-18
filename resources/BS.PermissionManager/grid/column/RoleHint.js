Ext.define( 'BS.PermissionManager.grid.column.RoleHint', {
	requires: [ 'BS.PermissionManager.dialog.RolePermissions' ],
	extend: 'Ext.grid.column.Action',
	alias: 'widget.bs-pm-rolehint',
	width: 25,
	renderer: function( value, metadata, record ) {
		var cssPrefix = Ext.baseCSSPrefix;
		var cls = [cssPrefix + 'grid-rolehint'];
		return '<div class="' + cls + '"><p>' + value + '</p></div>';
	},
	items: [ {
			iconCls: 'bs-extjs-actioncolumn-icon bs-icon-info question bs-pm-actioncolumn-icon',
			glyph: true, //Needed to have the "BS.override.grid.column.Action" render an <span> instead of an <img>,
			handler: function( grid, rowIndex, colIndex ) {
				var roleId = grid.getStore().getAt( rowIndex ).getId();
				var dialog = new BS.PermissionManager.dialog.RolePermissions( {
					role: roleId
				} );
				dialog.show();
			}
		} ]
} );