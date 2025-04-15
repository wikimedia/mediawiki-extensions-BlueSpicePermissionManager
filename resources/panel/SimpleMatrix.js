bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.SimpleMatrix = function ( cfg ) {
	cfg = cfg || {};
	cfg = Object.assign( { expanded: false, padded: true, classes: [ 'bs-permission-manager-simple-matrix' ] }, cfg );
	bs.permissionManager.panel.SimpleMatrix.parent.call( this, cfg );
	this.manager = cfg.manager;
	this.activeGroup = null;
	this.activeView = null;
	this.views = {};
	this.originalValues = {};
	this.items = {};

	this.setUsableRoles();
	this.setUsableNamespaces();
};

OO.inheritClass( bs.permissionManager.panel.SimpleMatrix, OO.ui.PanelLayout );

bs.permissionManager.panel.SimpleMatrix.prototype.setUsableRoles = function () {
	const blacklist = [ 'accountselfcreate', 'commenter', 'author', 'structuremanager', 'accountmanager', 'bot', 'maintenanceadmin' ],
		nsRoleBlacklist = blacklist.concat( [ 'admin' ] );

	// Define roles user can set - blacklist instead of whitelist to allow for custom roles
	this.usableRoles = this.manager.permissionRawData.roles.filter( ( role ) => blacklist.indexOf( role.role ) === -1 );
	this.usableNsRoles = this.manager.permissionRawData.roles.filter( ( role ) => nsRoleBlacklist.indexOf( role.role ) === -1 );
};

bs.permissionManager.panel.SimpleMatrix.prototype.setUsableNamespaces = function () {
	this.usableNamespaces = this.manager.permissionRawData.namespaces.filter( ( ns ) => ns.content && ns.talk === false );
};

bs.permissionManager.panel.SimpleMatrix.prototype.render = function ( data ) {
	if ( this.activeGroup === null ) {
		return;
	}
	if ( this.activeView ) {
		this.activeView.$element.hide();
	}
	if ( this.views.hasOwnProperty( this.activeGroup ) ) {
		this.activeView = this.views[ this.activeGroup ];
		this.updateMetaForCurrentView();
		this.activeView.$element.show();
		return;
	}
	this.views[ this.activeGroup ] = this.makeView( this.activeGroup, data );
	this.activeView = this.views[ this.activeGroup ];
	this.$element.append( this.activeView.$element );
};

bs.permissionManager.panel.SimpleMatrix.prototype.setActiveGroup = function ( group ) {
	this.activeGroup = group;
};

bs.permissionManager.panel.SimpleMatrix.prototype.reset = function ( data ) {
	for ( const key in this.views ) {
		this.views[ key ].$element.remove();
	}
	this.items = {};
	this.views = {};
	this.originalValues = {};
	this.activeView = null;
	this.render( data );
};

bs.permissionManager.panel.SimpleMatrix.prototype.toggle = function ( active ) {
	this.$element.toggle( active );
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeView = function ( group, data ) {
	const view = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false
	} );
	view.$element.addClass( 'bs-permission-manager-simple-matrix-view' );
	view.$element.append( this.getGroupHeader( group ).$element );
	view.$element.append( this.makeGlobal( data ).$element );
	view.$element.append( this.makeNamespaces( data ).$element );
	return view;
};

bs.permissionManager.panel.SimpleMatrix.prototype.getGroupHeader = function ( group ) {
	const groupLabel = this.manager.groupSelector.getGroupLabel( group );
	return new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-group', groupLabel ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-group-header' ]
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeGlobal = function ( data ) {
	const global = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false,
		classes: [ 'bs-permission-manager-simple-matrix-global' ]
	} );
	const heading = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-global' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-heading' ]
	} );
	const subtitle = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-global-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle' ]
	} );
	global.$element.append( heading.$element, subtitle.$element );
	for ( let i = 0; i < this.usableRoles.length; i++ ) {
		const role = this.usableRoles[ i ],
			roleData = this.findRoleData( role.role, data );
		if ( !roleData ) {
			continue;
		}

		global.$element.append( this.makeRoleItem(
			role,
			roleData.global,
			roleData.global_meta
		).$element );
	}
	return global;
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeNamespaces = function ( data ) {
	const namespaces = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false,
		classes: [ 'bs-permission-manager-simple-matrix-ns' ]
	} );
	const heading = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-namespace' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-heading' ]
	} );
	const subtitle = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-namespace-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle' ]
	} );
	const hint = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permissionmanager-simple-setonwiki-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle', 'hint' ]
	} );
	namespaces.$element.append( heading.$element, subtitle.$element, hint.$element );
	if ( this.usableNamespaces.length > 5 ) {
		const search = this.makeNamespaceSearch();
		namespaces.$element.append( search.$element );
		search.connect( this, {
			change: 'filterNamespaces'
		} );
	}
	for ( let i = 0; i < this.usableNamespaces.length; i++ ) {
		const nsPanel = new OO.ui.PanelLayout( {
			expanded: false,
			padded: false,
			classes: [ 'bs-permission-manager-simple-matrix-ns-panel' ]
		} );
		const label = new OO.ui.LabelWidget( {
			label: this.usableNamespaces[ i ].name,
			classes: [ 'bs-permission-manager-simple-matrix-ns-label' ]
		} );
		nsPanel.$element.append( label.$element );
		for ( let j = 0; j < this.usableNsRoles.length; j++ ) {
			const roleData = this.findRoleData( this.usableNsRoles[ j ].role, data );
			if ( !roleData ) {
				continue;
			}
			nsPanel.$element.append( this.makeRoleItem(
				this.usableNsRoles[ j ],
				roleData[ 'ns_' + this.usableNamespaces[ i ].id ] || false,
				roleData[ 'ns_' + this.usableNamespaces[ i ].id + '_meta' ] || {},
				{ nsId: this.usableNamespaces[ i ].id, nsName: this.usableNamespaces[ i ].name }
			).$element );
		}
		namespaces.$element.append( nsPanel.$element );
	}
	return namespaces;
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeNamespaceSearch = function () {
	return new OO.ui.SearchInputWidget( {
		placeholder: mw.msg( 'bs-permissionmanager-search-namespaces' ),
		classes: [ 'bs-permission-manager-simple-matrix-ns-search' ]
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.filterNamespaces = function ( val ) {
	if ( !this.activeView ) {
		return;
	}
	const nsPanel = this.activeView.$element.find( '.bs-permission-manager-simple-matrix-ns' );
	if ( nsPanel.length === 0 ) {
		return;
	}
	nsPanel.find( '.bs-permission-manager-simple-matrix-ns-panel' ).each( function () {
		const $nsItem = $( this );
		const $label = $nsItem.find( '.bs-permission-manager-simple-matrix-ns-label' );
		if ( $label.length === 0 || val === '' || $label.text().toLowerCase().indexOf( val.toLowerCase() ) !== -1 ) {
			$nsItem.show();
		} else {
			$nsItem.hide();
		}
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.findRoleData = function ( role, data ) {
	for ( let i = 0; i < data.length; i++ ) {
		if ( data[ i ].role === role ) {
			return data[ i ];
		}
	}
	return null;
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeRoleItem = function ( role, value, meta, additionalData ) {

	const item = new bs.permissionManager.widget.SimpleMatrixRoleItem( {
		role: role,
		value: value,
		meta: meta,
		additionalData: additionalData,
		matrix: this
	} );

	if ( !this.items.hasOwnProperty( this.activeGroup ) ) {
		this.items[ this.activeGroup ] = [];
	}
	this.items[ this.activeGroup ].push( item );
	return item;
};

bs.permissionManager.panel.SimpleMatrix.prototype.valueChange = function ( role, id, value, dirty ) {
	this.manager.setDirty( id, role, dirty );
	if ( id === 'global' ) {
		this.manager.setGlobal( role, value );
	} else {
		this.manager.setNamespace( id, role, value );
	}

	this.manager.emit( 'change' );
	this.setDataForActiveRole( role, this.manager.getMatrixDataForRoleAndGroup( role, this.activeGroup ) );
	this.updateMetaForCurrentView();
};

bs.permissionManager.panel.SimpleMatrix.prototype.updateMetaForCurrentView = function () {
	for ( let i = 0; i < this.items[ this.activeGroup ].length; i++ ) {
		if ( this.items[ this.activeGroup ][ i ].type === 'global' ) {
			this.items[ this.activeGroup ][ i ].setMeta(
				this.manager.getGlobalMeta( this.activeGroup, this.items[ this.activeGroup ][ i ].role.role )
			);
		} else {
			this.items[ this.activeGroup ][ i ].setMeta(
				this.manager.getNamespaceMeta(
					this.activeGroup,
					this.items[ this.activeGroup ][ i ].role.role,
					this.items[ this.activeGroup ][ i ].additionalData.nsId,
					this.items[ this.activeGroup ][ i ].additionalData.nsName
				)
			);
		}
	}
};

bs.permissionManager.panel.SimpleMatrix.prototype.setDataForActiveRole = function ( role, data ) {
	if ( !this.activeGroup ) {
		return;
	}

	for ( let i = 0; i < this.items[ this.activeGroup ].length; i++ ) {
		if ( this.items[ this.activeGroup ][ i ].role.role !== role ) {
			continue;
		}
		let type = this.items[ this.activeGroup ][ i ].type;
		if ( type !== 'global' ) {
			type = 'ns_' + type;
		}
		if ( !data.hasOwnProperty( type ) ) {
			continue;
		}
		this.items[ this.activeGroup ][ i ].setValue( data[ type ] );
	}
};
