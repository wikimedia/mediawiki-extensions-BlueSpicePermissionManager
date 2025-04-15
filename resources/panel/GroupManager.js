bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.GroupManager = function ( cfg ) {
	cfg = cfg || {};
	cfg = Object.assign( { expanded: false, padded: false }, cfg );
	bs.permissionManager.panel.GroupManager.parent.call( this, cfg );
	this.editable = cfg.editable || false;
	this.groupTypes = cfg.types || [ 'explicit', 'implicit' ];
	this.blacklist = cfg.blacklist || [];

	this.$element.addClass( 'bs-permission-manager-group-manager' );

	this.groupWidgets = [];
};

OO.inheritClass( bs.permissionManager.panel.GroupManager, OO.ui.PanelLayout );

bs.permissionManager.panel.GroupManager.prototype.init = async function () {
	this.renderHeader();
	await this.getGroups();
	this.groupPanel = new OO.ui.ButtonSelectWidget( {
		items: [],
		classes: [ 'group-panel' ]
	} );
	this.groupPanel.connect( this, { select: 'groupSelected' } );
	this.$element.append( this.groupPanel.$element );
	this.renderGroups();
};

bs.permissionManager.panel.GroupManager.prototype.getGroups = async function () {
	try {
		this.groups = await this.doGetGroups();
	} catch ( e ) {
		this.showError( e );
	}
};

bs.permissionManager.panel.GroupManager.prototype.doGetGroups = async function () {
	return new Promise( ( resolve, reject ) => {
		const data = {};
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
			success: function ( data ) { // eslint-disable-line no-shadow
				resolve( data );
			},
			error: function ( xhr, status, error ) {
				reject( error );
			}
		} );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.renderHeader = function () {
	this.$header = $( '<div>' ).addClass( 'group-manager-header' );
	const label = new OO.ui.LabelWidget( {
		label: mw.msg( 'bs-permissionmanager-group-manager-heading' )
	} );
	this.$header.append( label.$element );
	if ( this.editable ) {
		this.addButton = new OO.ui.ButtonWidget( {
			icon: 'add',
			title: mw.msg( 'bs-permissionmanager-group-create' ),
			flags: [ 'progressive' ],
			framed: false
		} );
		this.addButton.connect( this, { click: 'addGroup' } );
		this.$header.append(
			$( '<div>' ).addClass( 'group-manager-header-actions' ).append( this.addButton.$element )
		);
	}

	this.$element.append( this.$header );
};

bs.permissionManager.panel.GroupManager.prototype.selectFirst = function () {
	this.groupPanel.selectItem( this.groupPanel.findFirstSelectableItem() );
};

bs.permissionManager.panel.GroupManager.prototype.renderGroups = function () {
	this.groupPanel.clearItems();
	this.groupWidgets = {};
	const groups = this.getSortedGroups( this.groups );

	let currentGroup = null;
	const menuItems = [];
	for ( let i = 0; i < groups.length; i++ ) {
		if ( currentGroup === null ) {
			menuItems.push( new bs.permissionManager.widget.GroupManagerSectionHeader( {
				label: mw.msg( 'bs-permissionmanager-group-header-implicit' )
			} ) );
			currentGroup = 'implicit';
		}
		const group = groups[ i ];
		if ( group.group_type !== 'implicit' && currentGroup === 'implicit' ) {
			menuItems.push( new bs.permissionManager.widget.GroupManagerSectionHeader( {
				label: mw.msg( 'bs-permissionmanager-group-header-groups' )
			} ) );
			currentGroup = group.group_type;
		}
		const groupItem = new bs.permissionManager.widget.GroupManagerItem( $.extend( // eslint-disable-line no-jquery/no-extend
			group, { editable: this.editable }
		) );
		groupItem.connect( this, { remove: 'removeGroup', edit: 'editGroup' } );
		this.groupWidgets[ group.group_name ] = groupItem;
		menuItems.push( groupItem );
	}
	this.groupPanel.addItems( menuItems );
};

bs.permissionManager.panel.GroupManager.prototype.getGroupLabel = function ( group ) {
	for ( let i = 0; i < this.groups.length; i++ ) {
		if ( this.groups[ i ].group_name === group ) {
			return this.groups[ i ].displayname;
		}
	}
	return null;
};

bs.permissionManager.panel.GroupManager.prototype.groupSelected = function ( item ) {
	if ( !item ) {
		return;
	}
	this.emit( 'groupSelected', item.getData(), item );
};

bs.permissionManager.panel.GroupManager.prototype.showError = function ( error ) {
	const msg = error || mw.msg( 'bs-permissionmanager-error' );
	this.$element.html( new OO.ui.MessageWidget( {
		type: 'error',
		label: msg
	} ).$element );
};

bs.permissionManager.panel.GroupManager.prototype.getSortedGroups = function ( groups ) {
	// Order: implicit first, custom, then the rest. Alphabetically sorted within each group
	return groups.sort( ( a, b ) => {
		const order = [ 'implicit', 'extension-minimal', 'core-minimal', 'custom' ];
		const aIndex = order.indexOf( a.group_type );
		const bIndex = order.indexOf( b.group_type );

		if ( aIndex !== -1 && bIndex !== -1 ) {
			if ( aIndex !== bIndex ) {
				return aIndex - bIndex;
			}
			// If group_type is the same, sort by group_name
			return a.group_name.localeCompare( b.group_name );
		}
		if ( aIndex !== -1 ) {
			return -1;
		}
		if ( bIndex !== -1 ) {
			return 1;
		}
		// If group_type is not in the order array, sort by group_name
		return a.group_name.localeCompare( b.group_name );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.removeGroup = function ( groupName ) {
	OO.ui.confirm( mw.msg( 'bs-permissionmanager-group-remove-confirm', groupName ), { size: 'medium' } )
		.done( async ( confirmed ) => {
			if ( !confirmed ) {
				return;
			}
			await this.doRemoveGroup( groupName );
			await this.getGroups();
			this.renderGroups();
		} );
};

bs.permissionManager.panel.GroupManager.prototype.doRemoveGroup = async function ( groupName ) {
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/delete/' + groupName,
			type: 'POST',
			success: function () {
				resolve();
			},
			error: function ( xhr, status, error ) {
				reject( error );
			}
		} );
	} );
};

bs.permissionManager.panel.GroupManager.prototype.addGroup = function () {
	const dialog = new bs.permissionManager.dialog.AddGroup( {} );
	this.openGroupNameDialog( dialog );
};

bs.permissionManager.panel.GroupManager.prototype.editGroup = function ( groupName ) {
	const dialog = new bs.permissionManager.dialog.EditGroup( { group: groupName } );
	this.openGroupNameDialog( dialog );
};

bs.permissionManager.panel.GroupManager.prototype.openGroupNameDialog = function ( dialog ) {
	const windowManager = new OO.ui.WindowManager();
	$( 'body' ).append( windowManager.$element );
	windowManager.addWindows( [ dialog ] );
	windowManager.openWindow( dialog ).closed.then( async ( data ) => {
		if ( data && data.action === 'save' ) {
			await this.getGroups();
			this.renderGroups();
			this.groupPanel.selectItemByData( data.newGroup );
			windowManager.destroy();
		}
	} );
};

bs.permissionManager.panel.GroupManager.prototype.setDirty = function ( group, dirty ) {
	if ( !this.groupPanel ) {
		return;
	}
	const items = this.groupPanel.items.filter( ( item ) => item.getData() === group );
	if ( items.length ) {
		items[ 0 ].setDirty( dirty );
	}
};

bs.permissionManager.panel.GroupManager.prototype.resetDirty = function () {
	if ( !this.groupPanel ) {
		return;
	}
	const items = this.groupPanel.items;
	for ( let i = 0; i < items.length; i++ ) {
		items[ i ].setDirty( false );
	}
};
