bs.util.registerNamespace( 'bs.permissionManager.widget' );

bs.permissionManager.widget.SimpleMatrixRoleItem = function ( cfg ) {
	cfg = cfg || {};

	this.role = cfg.role;
	this.originalValue = !!cfg.value;
	this.value = cfg.value;
	this.meta = cfg.meta;
	this.additionalData = cfg.additionalData || {};
	this.type = this.additionalData.hasOwnProperty( 'nsId' ) ? this.additionalData.nsId : 'global';

	this.matrix = cfg.matrix;
	this.eventsDisabled = false;

	this.input = new OO.ui.CheckboxInputWidget( {
		selected: this.value,
		data: Object.assign( { role: this.role.role }, this.additionalData )
	} );
	this.input.connect( this, {
		change: function ( value ) {
			if ( this.eventsDisabled ) {
				return;
			}
			let dirty = false;
			if ( this.originalValue !== value ) {
				dirty = true;
			}
			this.input.$element.toggleClass( 'bs-permission-manager-matrix-dirty', dirty );
			this.matrix.valueChange( this.role.role, this.type, value, dirty );
		}
	} );
	let items = [
		this.input,
		new OO.ui.LabelWidget( {
			label: this.role.label
		} )
	];

	this.mainLabel = new OO.ui.LabelWidget( {
		label: '',
		classes: [ 'bs-permission-manager-simple-matrix-meta' ]
	} );
	this.subLabel = new OO.ui.LabelWidget( {
		label: '',
		classes: [ 'bs-permission-manager-simple-matrix-meta-sub' ]
	} );
	this.makeMeta( this.meta );
	items = items.concat( [ this.mainLabel, this.subLabel ] );

	cfg.items = items;
	bs.permissionManager.widget.SimpleMatrixRoleItem.parent.call( this, cfg );
	this.$element.addClass( 'bs-permission-manager-simple-matrix-role' );
};

OO.inheritClass( bs.permissionManager.widget.SimpleMatrixRoleItem, OO.ui.HorizontalLayout );

bs.permissionManager.widget.SimpleMatrixRoleItem.prototype.setValue = function ( value ) {
	this.eventsDisabled = true;
	this.input.setSelected( value );
	this.eventsDisabled = false;
};

bs.permissionManager.widget.SimpleMatrixRoleItem.prototype.makeMeta = function ( meta ) {
	const blocked = meta.isBlocked || false;
	let label = '';
	let cls = '';
	let sub = '';
	if ( meta.assignment === 'inherit' ) {
		label = mw.msg( 'bs-permissionmanager-simple-inherited', meta.inheritFrom );
		cls = 'role-granted';
	}
	if ( meta.assignment === 'explicit' ) {
		label = mw.msg( 'bs-permissionmanager-simple-explicit' );
		cls = 'role-granted';
	}
	if ( meta.assignment === 'global' && meta.hasOwnProperty( 'nsId' ) ) {
		label = mw.msg( 'bs-permissionmanager-simple-setonwiki' );
	}
	if ( meta.assignment === false ) {
		label = mw.msg( 'bs-permissionmanager-simple-notset' );
		cls = 'role-denied';
	}
	if ( meta.assignment === 'explicit' || ( meta.assignment !== false && !blocked ) ) {
		cls = 'role-granted';
	} else if ( blocked ) {
		if ( meta.dependencies ) {
			label = mw.msg( 'bs-permissionmanager-simple-notset' );
			cls = 'role-denied';
		} else if ( meta.assignment !== 'explicit' ) {
			const blocking = meta.blocking.map( ( role ) => '<b>' + role + '</b>' );
			label = mw.msg( 'bs-permissionmanager-simple-blocked', blocking.join( ', ' ), blocking.length );
			sub = mw.msg( 'bs-permissionmanager-simple-blocked-sub' );
			cls = 'role-denied';
		}
	}
	this.mainLabel.$element.removeClass( 'role-granted role-denied' ).addClass( cls ); // eslint-disable-line mediawiki/class-doc

	this.mainLabel.setLabel( new OO.ui.HtmlSnippet( label ) );
	this.subLabel.setLabel( sub );
};

bs.permissionManager.widget.SimpleMatrixRoleItem.prototype.setMeta = function ( meta ) {
	this.makeMeta( meta );
};
