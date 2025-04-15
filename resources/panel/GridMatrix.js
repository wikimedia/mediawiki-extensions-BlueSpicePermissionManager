bs.util.registerNamespace( 'bs.permissionManager.panel' );

bs.permissionManager.panel.GridMatrix = function ( cfg ) {
	cfg = cfg || {};
	cfg = Object.assign( { expanded: false, padded: false }, cfg );
	bs.permissionManager.panel.GridMatrix.parent.call( this, cfg );
	this.manager = cfg.manager;
	this.selectedNamespaces = [];
	this.views = {};
	this.activeGroup = null;
	this.activeGrid = null;

	this.makeNamespacePicker();
	this.$matrixCntInner = $( '<div>' ).addClass( 'permission-manager-matrix-inner' );
	this.$element.append( this.$matrixCntInner );
};

OO.inheritClass( bs.permissionManager.panel.GridMatrix, OO.ui.PanelLayout );

bs.permissionManager.panel.GridMatrix.prototype.makeNamespacePicker = function () {
	const namespaceOptions = [],
		namespaces = this.manager.permissionRawData.namespaces;
	for ( let i = 0; i < namespaces.length; i++ ) {
		namespaceOptions.push( {
			data: parseInt( namespaces[ i ].id ),
			label: namespaces[ i ].name
		} );
		if (
			this.selectedNamespaces.indexOf( namespaces[ i ].id ) === -1 &&
			( !namespaces[ i ].hideable || namespaces[ i ].content )
		) {
			this.selectedNamespaces.push( parseInt( namespaces[ i ].id ) );
		}
	}

	this.namespacePicker = new OO.ui.CheckboxMultiselectInputWidget( {
		classes: [ 'permission-manager-namespace-picker' ],
		options: namespaceOptions,
		value: this.selectedNamespaces
	} );

	this.namespacePicker.connect( this, {
		change: 'namespaceSelectionChange'
	} );
	this.namespacePickerButton = new OO.ui.PopupButtonWidget( {
		label: mw.message( 'bs-permissionmanager-namespace-picker' ).text(),
		framed: false,
		$overlay: true,
		icon: 'menu',
		classes: [ 'permission-manager-namespace-picker-btn' ],
		popup: {
			$overlay: true,
			head: false,
			align: 'backwards',
			autoFlip: false,
			$content: this.namespacePicker.$element,
			padded: true
		}
	} );
	this.manager.$matrixHeader.append( this.namespacePickerButton.$element );
};

bs.permissionManager.panel.GridMatrix.prototype.namespaceSelectionChange = function ( value ) {
	if ( !this.activeGroup ) {
		return;
	}
	value = value.map( ( val ) => parseInt( val ) );
	this.selectedNamespaces = value;
	const visible = [ 'role', 'global' ].concat( value.map( ( ns ) => 'ns_' + ns ) );
	this.views[ this.activeGroup ].setColumnsVisibility( visible );
};

bs.permissionManager.panel.GridMatrix.prototype.render = function ( data ) {
	if ( !this.activeGroup ) {
		return;
	}
	if ( this.activeGrid ) {
		this.activeGrid.$element.hide();
	}
	if ( this.views.hasOwnProperty( this.activeGroup ) ) {
		this.activeGrid = this.views[ this.activeGroup ];
		this.updateColumnsOfActiveGrid();
		this.activeGrid.$element.show();
		return;
	}

	const columns = {
		role: {
			type: 'text',
			headerText: mw.message( 'bs-permissionmanager-header-role' ).text(),
			width: 150,
			minWidth: 150,
			sticky: true,
			valueParser: function ( value, row ) {
				const hint = row.roleHint || '';
				if ( !hint ) {
					return value;
				}
				const btn = new OO.ui.ButtonWidget( {
					label: value,
					framed: false,
					title: row.roleHint || '',
					data: row
				} );
				btn.connect( btn, {
					click: function () {
						const role = this.getData().role;
						if ( !role ) {
							return;
						}
						bs.permissionManager.util.openRoleDetailsDialog( role );
					}
				} );
				return btn;
			}
		},
		global: {
			type: 'bs-permissionmanager-matrix',
			headerText: mw.message( 'bs-permissionmanager-header-global' ).text(),
			width: 50,
			minWidth: 30,
			maxWidth: 100
		}
	};

	for ( let i = 0; i < this.manager.permissionRawData.namespaces.length; i++ ) {
		columns[ 'ns_' + this.manager.permissionRawData.namespaces[ i ].id ] = {
			type: 'bs-permissionmanager-matrix',
			headerText: this.manager.permissionRawData.namespaces[ i ].name,
			hidden: this.selectedNamespaces.indexOf( this.manager.permissionRawData.namespaces[ i ].id ) === -1,
			width: 50,
			minWidth: 30,
			maxWidth: 100
		};
	}

	const store = new OOJSPlus.ui.data.store.Store( {
		data: data
	} );
	this.views[ this.activeGroup ] = new OOJSPlus.ui.data.GridWidget( {
		store: store,
		columns: columns,
		paginator: null,
		toolbar: null,
		border: 'horizontal',
		classes: [ 'permission-manager-grid' ]
	} );
	this.activeGrid = this.views[ this.activeGroup ];
	for ( const column in this.activeGrid.columns ) {
		if ( this.activeGrid.columns[ column ].type !== 'bs-permissionmanager-matrix' ) {
			continue;
		}
		this.activeGrid.columns[ column ].connect( this, {
			valueChange: 'onColumnValueChange'
		} );
	}
	this.$matrixCntInner.append( this.activeGrid.$element );
};

bs.permissionManager.panel.GridMatrix.prototype.onColumnValueChange = function ( role, value, dirty, column ) {
	this.manager.setDirty( column.id, role, dirty );
	if ( column.id === 'global' ) {
		this.manager.setGlobal( role, value );
	} else {
		const meta = column.metas[ role ];
		this.manager.setNamespace( meta.nsId, role, value );
	}
	this.manager.emit( 'change' );
	this.setDataForActiveRole( role, this.manager.getMatrixDataForRoleAndGroup( role, this.activeGroup ) );
	this.updateColumnsOfActiveGrid( role );
};

bs.permissionManager.panel.GridMatrix.prototype.setDataForActiveRole = function ( role, data ) {
	if ( !this.activeGrid ) {
		return;
	}
	for ( const column in this.activeGrid.columns ) {
		if ( this.activeGrid.columns[ column ].type !== 'bs-permissionmanager-matrix' ) {
			continue;
		}
		if ( !data.hasOwnProperty( column ) ) {
			continue;
		}
		this.activeGrid.columns[ column ].setValue( role, data[ column ] );
	}
};

bs.permissionManager.panel.GridMatrix.prototype.updateColumnsOfActiveGrid = function ( requestedRole ) {
	if ( !this.activeGrid || !this.activeGroup ) {
		return;
	}
	function updateForRole( role, column, activeGroup ) {
		if ( column.id === 'global' ) {
			column.setMeta( role, this.manager.getGlobalMeta( activeGroup, role ) );
			column.decorateCell( role );
			return;
		}
		column.setMeta( role, this.manager.getNamespaceMeta( activeGroup, role, column.metas[ role ].nsId, column.metas[ role ].nsName ) );
		column.decorateCell( role );
	}

	for ( const column in this.activeGrid.columns ) {
		if ( this.activeGrid.columns[ column ].type !== 'bs-permissionmanager-matrix' ) {
			continue;
		}
		if ( requestedRole ) {
			updateForRole.call( this, requestedRole, this.activeGrid.columns[ column ], this.activeGroup );
			continue;
		}
		const columnRoles = this.activeGrid.columns[ column ].getRoles();
		for ( let i = 0; i < columnRoles.length; i++ ) {
			const role = columnRoles[ i ];
			updateForRole.call( this, role, this.activeGrid.columns[ column ], this.activeGroup );
		}
	}
};

bs.permissionManager.panel.GridMatrix.prototype.setActiveGroup = function ( group ) {
	this.activeGroup = group;
};

bs.permissionManager.panel.GridMatrix.prototype.reset = function ( data ) {
	for ( const key in this.views ) {
		this.views[ key ].$element.remove();
	}
	this.views = {};
	this.activeGrid = null;
	this.render( data );
};

bs.permissionManager.panel.GridMatrix.prototype.toggle = function ( active ) {
	this.$element.toggle( active );
	if ( this.namespacePickerButton ) {
		this.namespacePickerButton.$element.toggle( active );
	}
};
