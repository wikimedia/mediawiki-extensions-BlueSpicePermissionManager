bs.util.registerNamespace( 'bs.permissionManager.widget' );

bs.permissionManager.widget.PresetWidget = function ( data ) {
	bs.permissionManager.widget.PresetWidget.parent.call( this, data );

	this.icon = new OO.ui.IconWidget( { icon: data.icon } );
	this.title = new OO.ui.LabelWidget( {
		label: data.label,
		classes: [ 'permission-manager-preset-title' ]
	} );
	this.desc = new OO.ui.LabelWidget( {
		label: new OO.ui.HtmlSnippet( data.help ),
		classes: [ 'permission-manager-preset-help' ]
	} );
	this.activeFlag = new OO.ui.IconWidget( { icon: '' } );

	this.$element.append(
		this.icon.$element,
		$( '<div>' ).addClass( 'permission-manager-preset-data-wrapper' )
			.append( this.title.$element, this.desc.$element ),
		this.activeFlag.$element
	);

	this.disabled = false;

	this.$element.addClass( 'permission-manager-preset-widget' )
		.attr( 'role', 'button' )
		.attr( 'tabindex', '0' );

	this.$element.on( 'click', () => {
		if ( this.disabled ) {
			return;
		}
		this.emit( 'click' );
	} );
};

OO.inheritClass( bs.permissionManager.widget.PresetWidget, OO.ui.Widget );

bs.permissionManager.widget.PresetWidget.static.tagName = 'div';

bs.permissionManager.widget.PresetWidget.prototype.setActive = function ( active ) {
	this.activeFlag.setIcon( active ? 'check' : '' );
	if ( active ) {
		this.$element.addClass( 'active' );
	} else {
		this.$element.removeClass( 'active' );
	}
};

bs.permissionManager.widget.PresetWidget.prototype.setDisabled = function ( disabled ) {
	this.disabled = disabled;
	if ( disabled ) {
		this.$element.addClass( 'disabled' );
	} else {
		this.$element.removeClass( 'disabled' );
	}
};
