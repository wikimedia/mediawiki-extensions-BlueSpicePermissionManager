Ext.define( 'BS.PermissionManager.grid.column.PermissionHint', {
	extend: 'Ext.grid.column.Action',
	alias: 'widget.bs-pm-permissionhint',
	width: 20,
	items: [ {
			iconCls: 'bs-extjs-actioncolumn-icon icon-help question bs-pm-actioncolumn-icon',
			glyph: true, //Needed to have the "BS.override.grid.column.Action" render an <span> instead of an <img>,
			getTip:  function ( value, metadata, record ) {
				return value;
			}
		} ]
} );