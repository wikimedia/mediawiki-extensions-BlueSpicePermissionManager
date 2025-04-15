bs.util.registerNamespace( 'bs.permissionManager.dialog' );

bs.permissionManager.dialog.RoleDetailsDialog = function ( cfg ) {
	bs.permissionManager.dialog.RoleDetailsDialog.parent.call( this, cfg );
	this.role = cfg.role;

};

OO.inheritClass( bs.permissionManager.dialog.RoleDetailsDialog, OO.ui.ProcessDialog );

bs.permissionManager.dialog.RoleDetailsDialog.static.name = 'roleDetails';
bs.permissionManager.dialog.RoleDetailsDialog.static.title = mw.msg( 'bs-permissionmanager-header-permissions' );
bs.permissionManager.dialog.RoleDetailsDialog.static.size = 'larger';
bs.permissionManager.dialog.RoleDetailsDialog.static.actions = [
	{
		action: 'close',
		label: mw.msg( 'bs-permissionmanager-cancel' ),
		flags: [ 'safe' ]
	}
];

bs.permissionManager.dialog.RoleDetailsDialog.prototype.initialize = function () {
	bs.permissionManager.dialog.RoleDetailsDialog.parent.prototype.initialize.call( this );

	this.panel = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true
	} );
	this.store = new OOJSPlus.ui.data.store.RemoteRestStore( {
		path: 'bs-permission-manager/v1/role_details/' + this.role
	} );

	this.grid = new OOJSPlus.ui.data.GridWidget( {
		store: this.store,
		exportable: true,
		$overlay: this.$overlay,
		columns: {
			permission: {
				headerText: mw.msg( 'bs-permissionmanager-header-permissions' ),
				minWidth: 200
			},
			description: {
				headerText: mw.msg( 'bs-permissionmanager-header-description' ),
				valueParser: function ( value ) {
					return new OO.ui.HtmlSnippet( value );
				}
			}
		},
		provideExportData: () => {
			const deferred = $.Deferred();

			$.ajax( {
				url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/role_details/' + this.role,
				type: 'GET',
				dataType: 'json',
				success: function ( response ) {
					const $table = $( '<table>' );
					let $row = $( '<tr>' );

					$row.append( $( '<td>' ).text( mw.message( 'bs-permissionmanager-header-permissions' ).text() ) );
					$row.append( $( '<td>' ).text( mw.message( 'bs-permissionmanager-header-description' ).text() ) );

					$table.append( $row );
					for ( const id in response.results ) {
						if ( response.results.hasOwnProperty( id ) ) {
							const record = response.results[ id ];
							$row = $( '<tr>' );
							$row.append( $( '<td>' ).text( record.permission ) );
							$row.append( $( '<td>' ).text( record.description ) );

							$table.append( $row );
						}
					}
					deferred.resolve( '<table>' + $table.html() + '</table>' );
				},
				error: function () {
					deferred.reject( 'Failed to load data' );
				}
			} );

			return deferred.promise();
		}
	} );
	this.grid.connect( this, {
		datasetChange: 'updateSize'
	} );
	this.panel.$element.append( this.grid.$element );

	this.$body.append( this.panel.$element );
};

bs.permissionManager.dialog.RoleDetailsDialog.prototype.getActionProcess = function ( action ) {
	if ( action ) {
		this.close();
	}
	return bs.permissionManager.dialog.RoleDetailsDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.permissionManager.dialog.RoleDetailsDialog.prototype.getBodyHeight = function () {
	return this.$body[ 0 ].scrollHeight + 50;
};
