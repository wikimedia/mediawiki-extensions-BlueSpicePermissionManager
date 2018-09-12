Ext.define( 'BS.PermissionManager.dialog.RolePermissions', {
	requires: [ 'BS.store.BSApi' ],
	extend: 'MWExt.Dialog',
	role: '',
	pageSize: 15,
	minHeight: 500,
	makeItems: function() {
		this.store = new BS.store.BSApi({
			apiAction: 'bs-role-permission-store',
			proxy: {
				extraParams: {
					role: this.role,
					limit: this.pageSize
				}
			},
			sorters: [{
				property: 'permission_name',
				direction: 'ASC'
			}],
			pageSize: this.pageSize,
			fields: [ 'permission_name', 'permission_desc' ]
		} );

		var cfg = {
			tbar: new Ext.toolbar.Toolbar({
				xtype: 'toolbar',
				displayInfo: true
			} ),
			getHTMLTable: this.getHTMLTable.bind( this )
		};
		$( document ).trigger( 'BSPanelInitComponent', [ cfg ] );

		this.permissionGrid = new Ext.grid.Panel( $.extend( cfg, {
			title: mw.message( 'bs-permissionmanager-role-permissions-label', this.role ).plain(),
			store: this.store,
			columns: [
				{
					text: mw.message(
						'bs-permissionmanager-role-permissions-column-permission'
					).text(),
					dataIndex: 'permission_name',
					flex: 1
				},
				{
					text: mw.message(
						'bs-permissionmanager-role-permissions-column-permission-desc'
					).text(),
					dataIndex: 'permission_desc',
					flex: 2
				}
			]
		} ) );

		return [ this.permissionGrid, new Ext.PagingToolbar( {
			dock: 'bottom',
			store: this.store,
			displayInfo: true
		} ) ];
	},

	getHTMLTable: function() {
		var dfd = $.Deferred();
		var lastRequest = this.store.getProxy().getLastRequest();
		var params = lastRequest._params;
		var store = this.store;
		var grid = this.permissionGrid;

		//This is ugly... unfortunately most AJAX interfaces can not
		//handle requests without those parameters
		params.page = 1;
		params.limit = 9999999;
		params.start = 0;

		var url = lastRequest._url;

		Ext.Ajax.request({
			url: url,
			params: params,
			success: function( response ){
				var resp = Ext.decode( response.responseText );
				var proxy = store.getProxy();
				var reader = proxy.getReader();
				var rows = resp[ reader._rootProperty ];
				var columns = grid.columns;
				var row = null;
				var col = null;
				var value = '';
				var $table = $('<table>');
				var $row = null;
				var $cell = null;
				var record = null;

				$row = $('<tr>');
				$table.append($row);
				for( var j = 0; j < columns.length; j++ ) {
					col = columns[j];

					$cell = $('<td>');
					$row.append( $cell );
					$cell.append( col.header || col.text );
				}

				for( var i = 0; i < rows.length; i++ ) {
					row = rows[i];
					$row = $('<tr>');
					record = new store.model( row );
					$table.append($row);

					for( var j = 0; j < columns.length; j++ ) {
						col = columns[j];
						$cell = $('<td>');
						$row.append( $cell );

						value = row[col.dataIndex];
						$cell.append( value );
					}
				}
				dfd.resolve( '<table>' + $table.html() + '</table>' );
			}
		});
		return dfd;
	}
} );
