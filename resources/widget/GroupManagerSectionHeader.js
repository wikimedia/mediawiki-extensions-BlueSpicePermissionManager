bs.util.registerNamespace( 'bs.permissionManager.widget' );

bs.permissionManager.widget.GroupManagerSectionHeader = function ( cfg ) {
	cfg.disabled = true;
	bs.permissionManager.widget.GroupManagerSectionHeader.parent.call( this, cfg );

	this.label = new OO.ui.LabelWidget( {
		label: cfg.label,
		classes: [ 'group-section-header' ]
	} );
	this.$element.html( this.label.$element );
};

OO.inheritClass( bs.permissionManager.widget.GroupManagerSectionHeader, OO.ui.ButtonOptionWidget );

bs.permissionManager.widget.GroupManagerSectionHeader.prototype.setDirty = function () {
	// NOOP
};
