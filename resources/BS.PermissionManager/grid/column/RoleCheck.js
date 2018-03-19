Ext.define( 'BS.PermissionManager.grid.column.RoleCheck', {
	extend: 'Ext.grid.column.CheckColumn',
	alias: 'widget.bs-pm-rolecheck',
	renderer: function( value, meta, record ) {
		var me = this;
		var dataIndex = me.dataIndex;
		var cssPrefix = Ext.baseCSSPrefix;
		var cls = [me.checkboxCls, cssPrefix + 'grid-checkcolumn'];

		if ( this.disabled ) {
			meta.tdCls += ' ' + this.disabledCls;
		}
		if ( value === BS.PermissionManager.ALLOWED_EXPLICIT ) {
			cls.push( cssPrefix + 'grid-checkcolumn-checked' );
			cls.push( me.checkboxCheckedCls );
		}

		if ( value ) {
			meta.tdCls = 'allowed';
		}

		var nsIdx = dataIndex.split( "_" )[1];
		if( record.get( 'isBlocked_' + nsIdx ) === true ) {
			meta.tdCls = 'blocked';
		}
		var affectedByMessage = record.get( 'affectedBy_' + nsIdx );

		if ( affectedByMessage ) {
			return '<span class="bs-pm-checkcolumn ' + cls.join( ' ' ) + '" title="' + affectedByMessage + ' "  src="' + Ext.BLANK_IMAGE_URL + '"></span>';
		}
		return '<span class="bs-pm-checkcolumn ' + cls.join( ' ' ) + '" src="' + Ext.BLANK_IMAGE_URL + '"></span>';
	}
} );