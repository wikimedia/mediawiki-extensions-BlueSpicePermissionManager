Ext.define( 'BS.PermissionManager.panel.Manager', {
	extend: 'Ext.Panel',
	requires: [
		'Ext.state.Manager',
		'BS.PermissionManager.data.Manager',
		'BS.PermissionManager.grid.Roles',
		'BS.PermissionManager.tree.Groups'
	],
	layout: 'border',
	border: false,
	header: false,
	resizable: true,
	height: 800,
	initComponent: function() {
		var me = this;

		$(window).bind( 'beforeunload', function() {
			var dataManager = Ext.create( 'BS.PermissionManager.data.Manager' );
			if( dataManager.isDirty() ) {
				var msg = mw.message( 'bs-permissionmanager-unsaved-changes' ).plain();
				if(/chrome/.test( navigator.userAgent.toLowerCase() ) ) { //chrome compatibility
					return msg;
				}
				if( window.event ) {
					window.event.returnValue = msg;
				} else {
					return msg;
				}
			}
		} );

		me.chkShowSystemGroups = new Ext.form.field.Checkbox( {
			boxLabel: mw.message( 'bs-permissionmanager-show-system-groups-label' ).text(),
			checked: false,
			listeners: {
				change: function( chk, newValue, oldValue ) {
					me.treeGroups.showSystemGroups( newValue );
				}
			}
		} );

		me.gridRoles = new BS.PermissionManager.grid.Roles( {
			region: 'center',
			listeners: {
				cellclick: function() {
					var dataManager = Ext.create( 'BS.PermissionManager.data.Manager' );
					me.fireEvent( 'dirtycheck', me, dataManager.isDirty() );
				}
			}
		} );

		me.treeGroups = new BS.PermissionManager.tree.Groups({
			region: 'west',
			collapsible: false,
			split: true,
			width: 250
		});

		me.items = [
			me.gridRoles,
			me.treeGroups
		];
		me.tbar = [
			me.chkShowSystemGroups,
			'->'
		];

		$( document ).trigger(
			'BSPermissionManagerAfterInitComponent',
			[me]
		);
		me.callParent(arguments);
	},

	onBtnSaveClick: function() {
		return Ext.create( 'BS.PermissionManager.data.Manager' ).saveRoles( this );
	},

	onBtnResetClick: function () {
		var dataManager = Ext.create( 'BS.PermissionManager.data.Manager' );
		dataManager.resetAllSettings();

		Ext.data.StoreManager
			.lookup( 'bs-permissionmanager-role-store' )
			.loadRawData( dataManager.buildRoleData().roles );
	},

	getHTMLTable: function() {
		var me = this;
		var dfd = $.Deferred();
		var aNs = mw.config.get( 'bsPermissionManagerNamespaces', [] );

		var $table = $( '<table>' );
		var $row = $( '<tr>' );
		var $cell = $( '<td>' );
		$table.append($row);
		$cell.append(
			mw.message( 'bs-permissionmanager-header-permissions' ).plain()
		);
		$row.append( $cell );
		$cell = $( '<td>' );
		$row.append( $cell );
		$cell.append(
			mw.message( 'bs-permissionmanager-header-global' ).plain()
		);

		for( var i = 0; i < aNs.length; i++ ) {
			$cell = $( '<td>' );
			$row.append( $cell );
			$cell.append( aNs[i].name );
		}

		me.gridRoles.store.data.each( function( record, i ) {
			$row = $( '<tr>' );
			$table.append( $row );
			$cell = $( '<td>' );
			$row.append( $cell );
			$cell.append( record.data.role );
			$cell = $( '<td>' );
			$row.append( $cell );
			$cell.append( record.data['userCan_Wiki'] ? 'X' : '' );
			for( var i = 0; i < aNs.length; i++ ) {
				$cell = $( '<td>' );
				$row.append( $cell );
				$cell.append( record.data['userCan_'+aNs[i].id] ? 'X' : '' );
			}
		});

		//Returning a deffered object is reuired by current export handlers
		dfd.resolve( '<table>' + $table.html() + '</table>' );
		return dfd;
	}
});
