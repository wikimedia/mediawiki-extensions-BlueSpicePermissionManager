Ext.define( 'BS.PermissionManager.grid.column.RoleHint', {
	extend: 'Ext.grid.column.Action',
	alias: 'widget.bs-pm-rolehint',
	width: 20,
	renderer: function( value, metadata, record ) {
		var cssPrefix = Ext.baseCSSPrefix;
		var cls = [cssPrefix + 'grid-rolehint'];
		return '<div class="' + cls + '"><p>' + value + '</p></div>';
	},
	items: [ {
			iconCls: 'bs-extjs-actioncolumn-icon icon-help question bs-pm-actioncolumn-icon',
			glyph: true, //Needed to have the "BS.override.grid.column.Action" render an <span> instead of an <img>,
			getTip:  function ( value, metadata, record ) {
				return value;
			}
		} ]
} );