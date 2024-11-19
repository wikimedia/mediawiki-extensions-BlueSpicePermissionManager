bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.GroupManager = function( cfg ) {
	cfg = cfg || {};
	cfg = $.extend( { expanded: false, padded: false }, cfg );
	bs.permissionManager.panel.GroupManager.parent.call( this, cfg );
	this.editable = cfg.editable || false;
	this.groupTypes = cfg.types || [ 'explicit', 'implicit' ];
	this.blacklist = cfg.blacklist || [];

	this.$element.addClass( 'bs-permission-manager-group-manager' );

	this.isInEditMode = false;
	this.groupWidgets = [];
};

OO.inheritClass( bs.permissionManager.panel.GroupManager, OO.ui.PanelLayout );

bs.permissionManager.panel.GroupManager.prototype.init = async function() {
	await this.getGroups();
	this.groupPanel = new OO.ui.ButtonSelectWidget( {
		items: [],
		classes: [ 'group-panel' ]
	} );
	this.groupPanel.connect( this, { select: 'groupSelected' } );
	this.$element.append( this.groupPanel.$element );
	this.renderGroups();
	if ( this.editable ) {
		this.addEditOption();
	}
};

bs.permissionManager.panel.GroupManager.prototype.getGroups = async function() {
	try {
		this.groups = await this.doGetGroups();
	} catch ( e ) {
		this.showError( e );
	}
};

bs.permissionManager.panel.GroupManager.prototype.doGetGroups = async function() {
	return new Promise( ( resolve, reject ) => {
		var data = {};
		if ( typeof this.groupTypes === 'object' && this.groupTypes.length ) {
			data.type = this.groupTypes.join( '|' );
		}
		if ( typeof this.blacklist === 'object' && this.blacklist.length ) {
			data.blacklist = this.blacklist.join( '|' );
		}
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups',
			type: 'GET',
			data: data,
			success: function( data ) {
				resolve( data );
			},
			error: function( xhr, status, error ) {
				reject( error );
			}
		} );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.selectFirst = function() {
	this.groupPanel.selectItem( this.groupPanel.findFirstSelectableItem() );
};

bs.permissionManager.panel.GroupManager.prototype.renderGroups = function() {
	this.groupPanel.clearItems();
	this.groupWidgets = {};
	let groups;
	groups = this.getSortedGroups( this.groups );

	for ( var i = 0; i < groups.length; i++ ) {
		var group = groups[i];
		var groupItem = new bs.permissionManager.widget.GroupManagerItem( $.extend(
			group, { editable: this.editable, isInEditMode: this.isInEditMode }
		) );
		groupItem.connect( this, { remove: 'removeGroup', edit: 'editGroup' } );
		this.groupWidgets[group.group_name] = groupItem;
	}
	this.groupPanel.addItems( Object.values( this.groupWidgets ) );
};

bs.permissionManager.panel.GroupManager.prototype.addEditOption = function() {
	this.enableEditButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'bs-permissionmanager-group-enable-edit' ),
		icon: 'edit',
		framed: false,
		flags: [ 'progressive' ],
		classes: [ 'group-toggle-edit-mode-button' ]
	} );
	this.enableEditButton.connect( this, { click: 'toggleEditMode' } );

	this.newGroupInput = new OO.ui.TextInputWidget( { required: true } );
	this.newGroupInput.connect( this, { change: 'onNewGroupInputChange'	} );
	this.newGroupConfirm = new OO.ui.ButtonWidget( {
		label: mw.msg( 'bs-permissionmanager-group-create' ),
		icon: 'add',
		flags: [ 'progressive' ]
	} );
	this.newGroupConfirm.connect( this, { click: 'createGroup' } );

	this.newGroupLayout = new OO.ui.ActionFieldLayout( this.newGroupInput, this.newGroupConfirm, {
		align: 'top',
		label: mw.msg( 'bs-permissionmanager-group-create-label' ),
		classes: [ 'group-create-layout' ]
	} );
	this.$element.append( this.newGroupLayout.$element, this.enableEditButton.$element );
};

bs.permissionManager.panel.GroupManager.prototype.toggleEditMode = function() {
	if ( !this.editable ) {
		return;
	}
	this.isInEditMode = !this.isInEditMode;
	this.enableEditButton.setLabel( mw.msg(
		this.isInEditMode ? 'bs-permissionmanager-group-disable-edit' : 'bs-permissionmanager-group-enable-edit'
	) );
	this.enableEditButton.setIcon( this.isInEditMode ? 'cancel' : 'edit' );
	this.enableEditButton.setFlags( this.isInEditMode ? [ 'destructive' ] : [ 'progressive', 'primary' ] );
	// This seems to be a bug in OOJS, destructive class never gets removed
	this.enableEditButton.$element.toggleClass( 'oo-ui-flaggedElement-destructive', this.isInEditMode );
	this.$element.toggleClass( 'edit-mode', this.isInEditMode );
	for ( var key in this.groupWidgets ) {
		if ( !this.groupWidgets.hasOwnProperty( key ) ) {
			continue;
		}
		this.groupWidgets[key].setEditMode( this.isInEditMode );
	}
};

bs.permissionManager.panel.GroupManager.prototype.getGroupLabel = function( group ) {
	for ( var i = 0; i < this.groups.length; i++ ) {
		if (this.groups[i].group_name === group) {
			return this.groups[i].displayname;
		}
	}
	return null;
};

bs.permissionManager.panel.GroupManager.prototype.groupSelected = function( item ) {
	if ( !item ) {
		return;
	}
	this.emit( 'groupSelected', item.getData(), item );
};

bs.permissionManager.panel.GroupManager.prototype.showError = function( error ) {
	var msg = error || mw.msg( 'bs-permissionmanager-error' );
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: msg
	} ).$element );
};

bs.permissionManager.panel.GroupManager.prototype.getSortedGroups = function( groups ) {
	// Sort groups by `group_type`, in order `implicit`, `custom` and the others
	return groups.sort( function( a, b ) {
		if ( a.group_type === 'implicit' && b.group_type !== 'implicit' ) {
			return -1;
		}
		if ( a.group_type !== 'implicit' && b.group_type === 'implicit' ) {
			return 1;
		}
		if ( a.group_type === 'custom' && b.group_type !== 'custom' ) {
			return -1;
		}
		if ( a.group_type !== 'custom' && b.group_type === 'custom' ) {
			return 1;
		}
		return 0;
	} );
};

bs.permissionManager.panel.GroupManager.prototype.removeGroup = function( groupName ) {
	OO.ui.confirm( mw.msg( 'bs-permissionmanager-group-remove-confirm', groupName ) )
		.done( async function( confirmed ) {
			if ( !confirmed ) {
				return;
			}
			await this.doRemoveGroup( groupName );
			await this.getGroups();
			this.renderGroups();
	}.bind( this ) );
};

bs.permissionManager.panel.GroupManager.prototype.doRemoveGroup = async function( groupName ) {
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/' + groupName,
			type: 'DELETE',
			success: function() {
				resolve();
			},
			error: function( xhr, status, error ) {
				reject( error );
			}
		} );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.editGroup = function( groupName ) {
	// Open dialog
	var dialog = new bs.permissionManager.dialog.EditGroup( { group: groupName } ),
		windowManager = new OO.ui.WindowManager();
	$( 'body' ).append( windowManager.$element );
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog ).closed.then( async function( data ) {
		if ( data && data.action === 'save' ) {
			await this.getGroups();
			this.renderGroups();
			this.groupPanel.selectItemByData( data.newGroup );
		}
	}.bind( this ) );
};

bs.permissionManager.panel.GroupManager.prototype.createGroup = function() {
	this.newGroupInput.setDisabled( true );
	this.newGroupConfirm.setDisabled( true );
	this.newGroupInput.getValidity().done( async function() {
		const value = this.newGroupInput.getValue();
		try {
			await this.doCreateGroup( value );
			await this.getGroups();
			this.renderGroups();
			this.newGroupInput.setValue( '' );
			this.groupPanel.selectItemByData( value );
		} catch ( e ) {
			this.newGroupLayout.setErrors( [ e ] );
		}
	}.bind( this ) ).always( function() {
		this.newGroupInput.setDisabled( false );
		this.newGroupConfirm.setDisabled( false );
	}.bind( this ) );
};

bs.permissionManager.panel.GroupManager.prototype.doCreateGroup = async function( groupName ) {
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/' + groupName,
			type: 'PUT',
			success: function() {
				resolve();
			},
			error: function( xhr ) {
				if ( xhr.hasOwnProperty( 'responseJSON' ) && xhr.responseJSON.hasOwnProperty( 'message' ) ) {
					reject( xhr.responseJSON.message );
				}
				reject();
			}
		} );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.onNewGroupInputChange = function( value ) {
	// Clear errors after input change
	this.newGroupLayout.setErrors( [] );
};

bs.permissionManager.panel.GroupManager.prototype.setDirty = function( group, dirty ) {
	if ( !this.groupPanel ) {
		return;
	}
	var items = this.groupPanel.items.filter( item => item.getData() === group );
	if ( items.length ) {
		items[0].setDirty( dirty );
	}
};

bs.permissionManager.panel.GroupManager.prototype.resetDirty = function() {
	if ( !this.groupPanel ) {
		return;
	}
	var items = this.groupPanel.items;
	for ( var i = 0; i < items.length; i++ ) {
		items[i].setDirty( false );
	}
};