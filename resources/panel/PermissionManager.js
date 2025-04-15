bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.PermissionManager = function ( cfg ) {
	cfg = cfg || {};
	cfg = Object.assign( { expanded: false, padded: false, classes: [ 'bs-permission-manager-inner' ] }, cfg );
	bs.permissionManager.panel.PermissionManager.parent.call( this, cfg );
	this.activeGroup = null;
	this.dirtyState = {};
	this.matrixMode = 'simple';
	this.matrixInstances = {};

	this.disableModeToggle = false;
};

OO.inheritClass( bs.permissionManager.panel.PermissionManager, OO.ui.PanelLayout );

bs.permissionManager.panel.PermissionManager.prototype.init = async function () {
	this.groupSelector = new bs.permissionManager.panel.GroupManager( {
		editable: true,
		classes: [ 'permission-manager-group-selector' ],
		types: [ 'core-minimal', 'implicit', 'custom', 'extension-minimal' ],
		blacklist: [ 'autoconfirmed' ]
	} );

	this.$matrixCnt = $( '<div>' ).addClass( 'permission-manager-matrix' );
	const [ groupResult, permissionResult ] = await Promise.all( [ // eslint-disable-line no-unused-vars
		this.groupSelector.init(),
		this.loadData()
	] );
	this.permissionOriginalData = permissionResult;
	this.permissionRawData = $.extend( true, {}, this.permissionOriginalData );

	this.advancedSwitch = new OO.ui.ToggleSwitchWidget( {
		value: false,
		classes: [ 'permission-manager-advanced-switch' ]
	} );
	this.advancedSwitch.connect( this, { change: 'toggleAdvanced' } );

	this.$matrixHeader = $( '<div>' ).addClass( 'permission-manager-matrix-header' );
	this.$matrixCnt.append( this.$matrixHeader );

	this.$matrixHeader.append( new OO.ui.FieldLayout( this.advancedSwitch, {
		align: 'right',
		label: mw.message( 'bs-permissionmanager-advanced-switch' ).text()
	} ).$element );

	this.matrixInstances.simple = new bs.permissionManager.panel.SimpleMatrix( { manager: this } );
	this.$matrixCnt.append( this.matrixInstances.simple.$element );

	this.$element.append( this.groupSelector.$element, this.$matrixCnt );

	this.groupSelector.connect( this, {
		groupSelected: 'groupSelected'
	} );
	this.groupSelector.selectFirst();
};

bs.permissionManager.panel.PermissionManager.prototype.toggleAdvanced = function ( value ) {
	if ( this.disableModeToggle ) {
		return;
	}
	if ( this.isDirty() ) {
		OO.ui.confirm( mw.message( 'bs-permissionmanager-advanced-switch-confirm' ).text() )
			.done( ( confirmed ) => {
				if ( confirmed ) {
					this.doToggleAdvanced( value );
				} else {
					this.disableModeToggle = true;
					this.advancedSwitch.setValue( !value );
					this.disableModeToggle = false;
				}
			} );
	} else {
		this.doToggleAdvanced( value );
	}
};

bs.permissionManager.panel.PermissionManager.prototype.doToggleAdvanced = function ( value ) {
	this.reset();
	this.matrixMode = value ? 'advanced' : 'simple';
	if ( this.matrixMode === 'advanced' && !this.matrixInstances.hasOwnProperty( 'advanced' ) ) {
		this.matrixInstances.advanced = new bs.permissionManager.panel.GridMatrix( { manager: this } );
		this.$matrixCnt.append( this.matrixInstances.advanced.$element );
	}
	this.matrixInstances.simple.toggle( this.matrixMode === 'simple' );
	this.matrixInstances.advanced.toggle( this.matrixMode === 'advanced' );
	if ( this.activeGroup ) {
		this.groupSelected( this.activeGroup );
	}
};

bs.permissionManager.panel.PermissionManager.prototype.groupSelected = function ( group ) {
	const matrixData = this.makeMatrixData( group );
	this.activeGroup = group;
	this.matrixInstances[ this.matrixMode ].setActiveGroup( group );
	this.matrixInstances[ this.matrixMode ].render( matrixData );
};

bs.permissionManager.panel.PermissionManager.prototype.reset = function () {
	this.permissionRawData = $.extend( true, {}, this.permissionOriginalData );
	this.dirtyState = {};
	this.groupSelector.resetDirty();
	this.matrixInstances[ this.matrixMode ].reset( this.makeMatrixData( this.activeGroup ) );
};

bs.permissionManager.panel.PermissionManager.prototype.isDirty = function () {
	return !$.isEmptyObject( this.dirtyState );
};

bs.permissionManager.panel.PermissionManager.prototype.loadData = async function () {
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/permissions',
			success: function ( data ) {
				resolve( data );
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				reject( errorThrown );
			}
		} );
	} );
};

bs.permissionManager.panel.PermissionManager.prototype.makeMatrixData = function ( forGroup ) {
	const data = [];

	for ( let i = 0; i < this.permissionRawData.roles.length; i++ ) {
		data.push( this.getMatrixDataForRoleAndGroup( this.permissionRawData.roles[ i ].role, forGroup ) );
	}
	return data;
};

bs.permissionManager.panel.PermissionManager.prototype.getMatrixDataForRoleAndGroup = function ( role, forGroup ) {
	const row = {}, roleData = this.permissionRawData.roles.find( ( r ) => r.role === role );
	row.role = role;
	row.roleHint = roleData.hint;
	row.roleHintHtml = roleData.hintHtml;
	row.global = this.isGlobalExplicitlyAssigned( forGroup, row.role );
	row.global_meta = this.getGlobalMeta( forGroup, row.role ); // eslint-disable-line camelcase
	for ( let j = 0; j < this.permissionRawData.namespaces.length; j++ ) {
		const namespaceInfo = this.permissionRawData.namespaces[ j ];
		row[ 'ns_' + namespaceInfo.id ] = this.isNamespaceAssigned( forGroup, row.role, namespaceInfo.id );
		row[ 'ns_' + namespaceInfo.id + '_meta' ] = this.getNamespaceMeta( forGroup, row.role, namespaceInfo.id, namespaceInfo.name );
	}
	return row;
};

bs.permissionManager.panel.PermissionManager.prototype.isGlobalExplicitlyAssigned = function ( group, role ) {
	return this.permissionRawData.groupRoles.hasOwnProperty( group ) &&
		this.permissionRawData.groupRoles[ group ].hasOwnProperty( role ) &&
		this.permissionRawData.groupRoles[ group ][ role ] === true;
};

bs.permissionManager.panel.PermissionManager.prototype.getGlobalMeta = function ( group, role ) {
	const explicit = this.isGlobalExplicitlyAssigned( group, role );
	const upper = [];
	if ( explicit ) {
		return { assignment: 'explicit' };
	}
	if ( group === 'user' ) {
		upper.push( '*' );
	} else if ( group !== '*' ) {
		upper.push( '*' );
		upper.push( 'user' );
	}
	for ( let i = 0; i < upper.length; i++ ) {
		if ( this.isGlobalExplicitlyAssigned( upper[ i ], role ) ) {
			return { assignment: 'inherit', inheritFrom: upper[ i ] };
		}
	}

	return { assignment: false };
};

bs.permissionManager.panel.PermissionManager.prototype.isNamespaceAssigned = function ( group, role, namespace ) {
	return this.permissionRawData.nsLockdown.hasOwnProperty( namespace ) &&
		this.permissionRawData.nsLockdown[ namespace ].hasOwnProperty( role ) &&
		this.permissionRawData.nsLockdown[ namespace ][ role ].indexOf( group ) !== -1;
};

bs.permissionManager.panel.PermissionManager.prototype.getNamespaceMeta = function ( group, role, namespace, namespaceName ) {
	const meta = { nsId: namespace, nsName: namespaceName };
	const explicit = this.permissionRawData.nsLockdown.hasOwnProperty( namespace ) &&
		this.permissionRawData.nsLockdown[ namespace ].hasOwnProperty( role ) &&
		this.permissionRawData.nsLockdown[ namespace ][ role ].indexOf( group ) !== -1;
	if ( explicit ) {
		meta.assignment = 'explicit';
	} else if ( this.getGlobalMeta( group, role ).assignment !== false ) {
		meta.assignment = 'global';
	} else {
		meta.assignment = false;
		return meta;
	}

	if (
		this.permissionRawData.roleDependencyTree.hasOwnProperty( role ) &&
		Object.keys( this.permissionRawData.roleDependencyTree[ role ] ).length > 0
	) {
		const blockingDependencies = this.getBlockingDependencies(
			role,
			this.permissionRawData.roleDependencyTree[ role ],
			namespace,
			group
		);
		if ( !$.isEmptyObject( blockingDependencies ) ) {
			meta.dependencies = blockingDependencies;
			meta.isBlocked = true;
		}
	}
	meta.blocking = this.getAssignedGroupsForNamespaceAndRole( namespace, role ).filter( ( g ) => g !== group );
	meta.blocking = meta.blocking.map( ( g ) => this.groupSelector.getGroupLabel( g ) || g );
	if ( meta.blocking.length > 0 && meta.assignment !== 'explicit' ) {
		meta.isBlocked = true;
	}
	return meta;
};

bs.permissionManager.panel.PermissionManager.prototype.getBlockingDependencies = function ( role, dependencies, namespace, group ) {
	const res = {};
	for ( const permission in dependencies ) {
		if ( !dependencies.hasOwnProperty( permission ) ) {
			continue;
		}
		const roles = dependencies[ permission ];
		for ( let i = 0; i < roles.length; i++ ) {
			const blocking = this.getAssignedGroupsForNamespaceAndRole( namespace, roles[ i ] );
			if ( blocking.length > 0 && blocking.indexOf( group ) === -1 ) {
				if ( !res.hasOwnProperty( permission ) ) {
					res[ permission ] = [];
				}
				res[ permission ].push( roles[ i ] );
			}
		}
	}
	return res;
};

bs.permissionManager.panel.PermissionManager.prototype.getAssignedGroupsForNamespaceAndRole = function ( namespace, role ) {
	const groups = [];
	if ( !this.permissionRawData.nsLockdown.hasOwnProperty( namespace ) ) {
		return groups;
	}
	if ( !this.permissionRawData.nsLockdown[ namespace ].hasOwnProperty( role ) ) {
		return groups;
	}
	return this.permissionRawData.nsLockdown[ namespace ][ role ];
};

bs.permissionManager.panel.PermissionManager.prototype.setGlobal = function ( role, value ) {
	if ( !this.permissionRawData.groupRoles.hasOwnProperty( this.activeGroup ) ) {
		this.permissionRawData.groupRoles[ this.activeGroup ] = {};
	}
	this.permissionRawData.groupRoles[ this.activeGroup ][ role ] = value;
	if ( !value ) {
		// If global permission is removed, remove all NS assignments
		for ( const namespace in this.permissionRawData.nsLockdown ) {
			if ( this.permissionRawData.nsLockdown[ namespace ].hasOwnProperty( role ) ) {
				const index = this.permissionRawData.nsLockdown[ namespace ][ role ].indexOf( this.activeGroup );
				if ( index !== -1 ) {
					this.permissionRawData.nsLockdown[ namespace ][ role ].splice( index, 1 );
				}
			}
		}
	}
};

bs.permissionManager.panel.PermissionManager.prototype.setNamespace = function ( namespace, role, value ) {
	if ( !this.permissionRawData.nsLockdown.hasOwnProperty( namespace ) ) {
		this.permissionRawData.nsLockdown[ namespace ] = {};
	}
	if ( !this.permissionRawData.nsLockdown[ namespace ].hasOwnProperty( role ) ) {
		this.permissionRawData.nsLockdown[ namespace ][ role ] = [];
	}
	const index = this.permissionRawData.nsLockdown[ namespace ][ role ].indexOf( this.activeGroup );
	if ( value && index === -1 ) {
		this.permissionRawData.nsLockdown[ namespace ][ role ].push( this.activeGroup );
	} else if ( !value && index !== -1 ) {
		this.permissionRawData.nsLockdown[ namespace ][ role ].splice( index, 1 );
	}

	if ( value ) {
		// Assign global permission if namespace permission is set
		if ( !this.permissionRawData.groupRoles.hasOwnProperty( this.activeGroup ) ) {
			this.permissionRawData.groupRoles[ this.activeGroup ] = {};
		}
		this.permissionRawData.groupRoles[ this.activeGroup ][ role ] = true;
	}
};

bs.permissionManager.panel.PermissionManager.prototype.setDirty = function ( column, role, dirty ) {
	if ( !this.dirtyState.hasOwnProperty( this.activeGroup ) ) {
		this.dirtyState[ this.activeGroup ] = {};
	}
	if ( !this.dirtyState[ this.activeGroup ].hasOwnProperty( role ) ) {
		this.dirtyState[ this.activeGroup ][ role ] = [];
	}
	if ( dirty ) {
		if ( this.dirtyState[ this.activeGroup ][ role ].indexOf( column ) === -1 ) {
			this.dirtyState[ this.activeGroup ][ role ].push( column );
		}
	} else {
		const index = this.dirtyState[ this.activeGroup ][ role ].indexOf( column );
		if ( index !== -1 ) {
			this.dirtyState[ this.activeGroup ][ role ].splice( index, 1 );
		}
		if ( this.dirtyState[ this.activeGroup ][ role ].length === 0 ) {
			delete this.dirtyState[ this.activeGroup ][ role ];
		}
		if ( Object.keys( this.dirtyState[ this.activeGroup ] ).length === 0 ) {
			delete this.dirtyState[ this.activeGroup ];
		}
	}
	this.groupSelector.setDirty( this.activeGroup, this.dirtyState.hasOwnProperty( this.activeGroup ) );
};

bs.permissionManager.panel.PermissionManager.prototype.save = async function () {
	if ( !this.isDirty() ) {
		return $.Deferred().resolve().promise();
	}
	return this.doSave( {
		groupRoles: this.permissionRawData.groupRoles,
		roleLockdown: this.permissionRawData.nsLockdown
	} );
};

bs.permissionManager.panel.PermissionManager.prototype.doSave = function ( data ) {
	const manager = this;
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/permissions',
			type: 'POST',
			data: JSON.stringify( data ),
			contentType: 'application/json',
			success: function () {
				manager.permissionOriginalData = $.extend( true, {}, manager.permissionRawData );
				manager.reset();
				resolve();
			},
			error: function ( jqXHR, textStatus, errorThrown ) {
				reject( jqXHR.hasOwnProperty( 'responseJSON' ) ? jqXHR.responseJSON : errorThrown );
			}
		} );
	} );
};
