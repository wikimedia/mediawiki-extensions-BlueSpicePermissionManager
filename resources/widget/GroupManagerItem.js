bs.util.registerNamespace( 'bs.permissionManager.widget' );

bs.permissionManager.widget.GroupManagerItem = function( cfg ) {
	cfg.label = cfg.displayname;
	cfg.data = cfg.group_name;
	cfg.framed = false;
	bs.permissionManager.widget.GroupManagerItem.parent.call( this, cfg );
	this.isCustom = cfg.custom_group || false;
	this.groupType = cfg.group_type;
	this.editable = cfg.editable;

	var type = this.isCustom ? 'custom' : cfg.group_type === 'implicit' ? 'implicit' : 'builtin';
	if ( cfg.group_name !== cfg.displayname ) {
		if ( this.groupType !== 'implicit' ) {
			this.$groupMeta = $( '<div>' ).addClass( 'group-meta' );
			this.$groupMeta.insertAfter( this.$label );
			this.$groupMeta.append( $( '<span>' ).addClass( 'group-name' ).text( cfg.group_name ) );
			this.$groupMeta.append(
				$( '<span>' )
					.addClass( 'group-type' )
					.addClass( 'type-' + type )
					.text( mw.msg( 'bs-permissionmanager-group-type-' + type ) )
			);
		}
	} else if ( this.groupType !== 'implicit' ) {
		$( '<span>' )
			.addClass( 'group-type' )
			.addClass( 'type-' + type )
			.text( mw.msg( 'bs-permissionmanager-group-type-' + type ) )
			.insertAfter( this.$label );
	}

	this.$label.addClass( 'group-name' );

	this.$element.addClass( 'group-item' );
	this.$element.addClass( 'oo-ui-outlineOptionWidget' );
	this.$element.addClass( 'group-type-' + this.groupType );
	if  ( this.isCustom && this.editable ) {
		var $editingPanel = $( '<div>' ).addClass( 'group-editing-panel' );
		this.$element.append( $editingPanel );
		var rmButton = new OO.ui.ButtonWidget( {
			icon: 'trash',
			title: mw.msg( 'bs-permissionmanager-group-remove' ),
			framed: false,
			classes: [ 'group-remove-button' ]
		} );
		rmButton.connect( this, { click: function() {
			this.emit( 'remove', this.getData() );
		} } );
		$editingPanel.append( rmButton.$element );

		var editButton = new OO.ui.ButtonWidget( {
			icon: 'edit',
			title: mw.msg( 'bs-permissionmanager-group-edit' ),
			framed: false,
			classes: [ 'group-edit-button' ]
		} );
		editButton.connect( this, { click: function() {
			this.emit( 'edit', this.getData() );
		} } );
		$editingPanel.append( editButton.$element );
	}
};

OO.inheritClass( bs.permissionManager.widget.GroupManagerItem, OO.ui.ButtonOptionWidget );


bs.permissionManager.widget.GroupManagerItem.prototype.setDirty = function( dirty ) {
	this.$element.toggleClass( 'dirty', dirty );
};