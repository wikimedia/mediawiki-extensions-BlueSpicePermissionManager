bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.SimpleMatrix = function( cfg ) {
	cfg = cfg || {};
	cfg = $.extend( { expanded: false, padded: true, classes: [ 'bs-permission-manager-simple-matrix' ] }, cfg );
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

bs.permissionManager.panel.SimpleMatrix.prototype.setUsableRoles = function() {
	var blacklist = [ 'accountselfcreate', 'commenter', 'author', 'structuremanager', 'accountmanager', 'bot', 'maintenanceadmin' ],
		nsRoleBlacklist = blacklist.concat( [ 'admin' ] );

	// Define roles user can set - blacklist instead of whitelist to allow for custom roles
	this.usableRoles = this.manager.permissionRawData.roles.filter( ( role ) => {
		return blacklist.indexOf( role.role ) === -1;
	} );
	this.usableNsRoles = this.manager.permissionRawData.roles.filter( ( role ) => {
		return nsRoleBlacklist.indexOf( role.role ) === -1;
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.setUsableNamespaces = function() {
	this.usableNamespaces = this.manager.permissionRawData.namespaces.filter( ( ns ) => {
		return ns.content && ns.talk === false;
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.render = function( data ) {
	if ( this.activeGroup === null ) {
		return;
	}
	if ( this.activeView ) {
		this.activeView.$element.hide();
	}
	if ( this.views.hasOwnProperty( this.activeGroup ) ) {
		this.activeView = this.views[this.activeGroup];
		this.updateMetaForCurrentView();
		this.activeView.$element.show();
		return;
	}
	this.views[this.activeGroup] = this.makeView( this.activeGroup, data );
	this.activeView = this.views[this.activeGroup];
	this.$element.append( this.activeView.$element );
};

bs.permissionManager.panel.SimpleMatrix.prototype.setActiveGroup = function( group ) {
	this.activeGroup = group;
};

bs.permissionManager.panel.SimpleMatrix.prototype.reset = function( data ) {
	for ( var key in this.views ) {
		this.views[key].$element.remove();
	}
	this.items = {};
	this.views = {};
	this.originalValues = {};
	this.activeView = null;
	this.render( data );
};

bs.permissionManager.panel.SimpleMatrix.prototype.toggle = function( active ) {
	this.$element.toggle( active );
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeView = function( group, data ) {
	var view = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false
	} );
	view.$element.addClass( 'bs-permission-manager-simple-matrix-view' );
	view.$element.append( this.getGroupHeader( group ).$element );
	this.makeGlobal( data );
	this.makeNamespaces( data );

	view.$element.append( this.global.$element, this.namespaces.$element );
	return view;
};

bs.permissionManager.panel.SimpleMatrix.prototype.getGroupHeader = function( group ) {
	var groupLabel = this.manager.groupSelector.getGroupLabel( group );
	return new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-group', groupLabel ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-group-header' ]
	} );
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeGlobal = function( data ) {
	this.global = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false,
		classes: [ 'bs-permission-manager-simple-matrix-global' ]
	} );
	var heading = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-global' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-heading' ]
	} );
	var subtitle = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-global-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle' ]
	} );
	this.global.$element.append( heading.$element, subtitle.$element );
	for ( var i = 0; i < this.usableRoles.length; i++ ) {
		var role = this.usableRoles[i],
			roleData = this.findRoleData( role.role, data );
		if ( !roleData ) {
			continue;
		}

		this.global.$element.append( this.makeRoleItem(
			role,
			roleData.global,
			roleData.global_meta
		).$element );
	}
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeNamespaces = function( data ) {
	this.namespaces = new OO.ui.PanelLayout( {
		expanded: false,
		padded: false,
		classes: [ 'bs-permission-manager-simple-matrix-ns' ]
	} );
	var heading = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-namespace' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-heading' ]
	} );
	var subtitle = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permission-manager-simple-matrix-namespace-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle' ]
	} );
	var hint = new OO.ui.LabelWidget( {
		label: mw.message( 'bs-permissionmanager-simple-setonwiki-sub' ).text(),
		classes: [ 'bs-permission-manager-simple-matrix-subtitle', 'hint' ]
	} );
	this.namespaces.$element.append( heading.$element, subtitle.$element, hint.$element );
	if ( this.usableNamespaces.length > 5 ) {
		this.makeNamespaceSearch();
	}
	for ( var i = 0; i < this.usableNamespaces.length; i++ ) {
		var nsPanel = new OO.ui.PanelLayout( {
			expanded: false,
			padded: false,
			classes: [ 'bs-permission-manager-simple-matrix-ns-panel' ]
		} );
		var label = new OO.ui.LabelWidget( {
			label: this.usableNamespaces[i].name
		} );
		nsPanel.$element.append( label.$element );
		for ( var j = 0; j < this.usableNsRoles.length; j++ ) {
			var roleData = this.findRoleData( this.usableNsRoles[j].role, data );
			if ( !roleData ) {
				continue;
			}
			nsPanel.$element.append( this.makeRoleItem(
				this.usableNsRoles[j],
				roleData['ns_' + this.usableNamespaces[i].id] || false,
				roleData['ns_' + this.usableNamespaces[i].id + '_meta'] || {},
				{ nsId: this.usableNamespaces[i].id, nsName: this.usableNamespaces[i].name }
			).$element );
		}
		this.namespaces.$element.append( nsPanel.$element );
	}
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeNamespaceSearch = function() {
	var nsSearch = new OO.ui.SearchInputWidget( {
		placeholder: mw.msg( 'bs-permissionmanager-search-namespaces' ),
		classes: [ 'bs-permission-manager-simple-matrix-ns-search' ]
	} );
	this.namespaces.$element.append( nsSearch.$element );
	nsSearch.connect( this, {
		change: function ( val ) {
			this.namespaces.$element.find( '.bs-permission-manager-simple-matrix-ns-panel' ).each( function() {
				var $panel = $( this );
				if ( val === '' || $panel.text().toLowerCase().indexOf( val.toLowerCase() ) !== -1 ) {
					$panel.show();
				} else {
					$panel.hide();
				}
			} );
		}
	} );
	return nsSearch;
}

bs.permissionManager.panel.SimpleMatrix.prototype.findRoleData = function( role, data ) {
	for ( var i = 0; i < data.length; i++ ) {
		if ( data[i].role === role ) {
			return data[i];
		}
	}
	return null;
};

bs.permissionManager.panel.SimpleMatrix.prototype.makeRoleItem = function( role, value, meta, additionalData ) {

	var item = new bs.permissionManager.widget.SimpleMatrixRoleItem( {
		role: role,
		value: value,
		meta: meta,
		additionalData: additionalData,
		matrix: this
	} );

	if ( !this.items.hasOwnProperty( this.activeGroup ) ) {
		this.items[this.activeGroup] = [];
	}
	this.items[this.activeGroup].push( item );
	return item;
};

bs.permissionManager.panel.SimpleMatrix.prototype.valueChange = function( role, id, value, dirty ) {
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

bs.permissionManager.panel.SimpleMatrix.prototype.updateMetaForCurrentView = function() {
	for ( var i = 0; i < this.items[this.activeGroup].length; i++ ) {
		if ( this.items[this.activeGroup][i].type === 'global' ) {
			this.items[this.activeGroup][i].setMeta(
				this.manager.getGlobalMeta( this.activeGroup, this.items[this.activeGroup][i].role.role )
			);
		} else {
			this.items[this.activeGroup][i].setMeta(
				this.manager.getNamespaceMeta(
					this.activeGroup,
					this.items[this.activeGroup][i].role.role,
					this.items[this.activeGroup][i].additionalData.nsId,
					this.items[this.activeGroup][i].additionalData.nsName
				)
			);
		}
	}
};

bs.permissionManager.panel.SimpleMatrix.prototype.setDataForActiveRole = function( role, data ) {
	if ( !this.activeGroup ) {
		return;
	}

	for ( var i = 0; i < this.items[this.activeGroup].length; i++ ) {
		if ( this.items[this.activeGroup][i].role.role !== role ) {
			continue;
		}
		var type = this.items[this.activeGroup][i].type;
		if ( type !== 'global' ) {
			type = 'ns_' + type;
		}
		if ( !data.hasOwnProperty( type ) ) {
			continue;
		}
		this.items[this.activeGroup][i].setValue( data[type] );
	}
};
