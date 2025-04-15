bs.util.registerNamespace( 'bs.permissionManager.panel.column' );

bs.permissionManager.panel.column.PermissionMatrixColumn = function ( cfg ) {
	bs.permissionManager.panel.column.PermissionMatrixColumn.parent.call( this, cfg );
	this.originalValues = {};
	this.dirtyValues = {};
	this.cells = {};
	this.inputs = {};
	this.metas = {};

	this.eventsDisabled = false;
};

OO.inheritClass( bs.permissionManager.panel.column.PermissionMatrixColumn, OOJSPlus.ui.data.column.Column );

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.renderCell = function ( value, row ) {
	const $cell = bs.permissionManager.panel.column.PermissionMatrixColumn.parent.prototype.renderCell.call( this, value, row );
	$cell.addClass( 'bs-permission-manager-matrix-cell' );
	if ( row.hasOwnProperty( this.id + '_meta' ) ) {
		this.metas[ row.role ] = row[ this.id + '_meta' ];
	}
	this.cells[ row.role ] = $cell;
	this.decorateCell( row.role );
	return $cell;
};

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.getViewControls = function ( value, row ) {
	const input = new OO.ui.CheckboxInputWidget( {
		selected: value,
		data: { role: row.role }
	} );
	this.originalValues[ row.role ] = value;
	const column = this;
	input.connect( input, {
		change: function ( value ) { // eslint-disable-line no-shadow
			if ( this.eventsDisabled ) {
				return;
			}
			const role = this.getData().role;
			column.dirtyValues[ role ] = value;
			let dirty = false;
			if ( column.dirtyValues[ role ] !== column.originalValues[ row.role ] ) {
				dirty = true;
			}
			column.cells[ role ].toggleClass( 'bs-permission-manager-matrix-dirty', dirty );

			column.emit( 'valueChange', role, value, dirty, column );
		}
	} );
	this.inputs[ row.role ] = input;
	return input;
};

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.setMeta = function ( role, value ) {
	this.metas[ role ] = value;
};

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.getRoles = function () {
	return Object.keys( this.cells );
};

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.setValue = function ( role, value ) {
	if ( !this.inputs.hasOwnProperty( role ) ) {
		return;
	}
	this.eventsDisabled = true;
	this.inputs[ role ].setSelected( value );
	this.eventsDisabled = false;
};

bs.permissionManager.panel.column.PermissionMatrixColumn.prototype.decorateCell = function ( role ) {
	if ( !this.cells.hasOwnProperty( role ) || !this.metas.hasOwnProperty( role ) ) {
		return;
	}
	const $cell = this.cells[ role ],
		meta = this.metas[ role ];
	const blocked = meta.isBlocked || false;
	$cell.removeClass( 'bs-permission-manager-assigned' );
	$cell.removeClass( 'bs-permission-manager-blocked' );
	$cell.removeClass( 'bs-permission-manager-blocked' );
	$cell.attr( 'title', '' );
	if ( meta.assignment === 'inherit' ) {
		$cell.attr( 'title', mw.msg( 'bs-permissionmanager-affected-by-inherited', meta.inheritFrom ) );
	}
	if ( meta.assignment === 'explicit' ) {
		$cell.attr( 'title', mw.msg( 'bs-permissionmanager-affected-by-explicitlyset' ) );
	}
	if ( meta.assignment === false ) {
		$cell.attr( 'title', mw.msg( 'bs-permissionmanager-affected-by-notset' ) );
	}
	if ( meta.assignment === 'global' && meta.hasOwnProperty( 'nsId' ) ) {
		$cell.attr( 'title', mw.msg( 'bs-permissionmanager-affected-by-setonwiki' ) );
	}
	if ( meta.assignment === 'explicit' || ( meta.assignment !== false && !blocked ) ) {
		$cell.addClass( 'bs-permission-manager-assigned' );
	} else if ( blocked ) {
		if ( meta.assignment !== 'explicit' && meta.blocking.length ) {
			$cell.attr( 'title', mw.msg( 'bs-permissionmanager-affected-by-explicit', meta.blocking.join( ', ' ) ) );
			$cell.addClass( 'bs-permission-manager-blocked' );
		} else if ( meta.dependencies ) {
			const dependencyLines = [];
			for ( const key in meta.dependencies ) {
				dependencyLines.push( mw.msg( 'bs-permissionmanager-affected-by-dependency-single', key, meta.dependencies[ key ].join( ', ' ) ) );
			}
			// Set title without encoding
			$cell[ 0 ].title = mw.msg(
				'bs-permissionmanager-affected-by-dependency',
				dependencyLines.join( '\n' ),
				Object.keys( meta.dependencies ).length
			);
			$cell.addClass( 'bs-permission-manager-blocked' );
		}
	}
};

OOJSPlus.ui.data.registry.columnRegistry.register( 'bs-permissionmanager-matrix', bs.permissionManager.panel.column.PermissionMatrixColumn );
