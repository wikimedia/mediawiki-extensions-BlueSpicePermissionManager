bs.util.registerNamespace( 'bs.permissionManager.dialog' );

bs.permissionManager.dialog.EditGroup = function( cfg ) {
	bs.permissionManager.dialog.EditGroup.parent.call( this, cfg );

	this.group = cfg.group;
};

OO.inheritClass( bs.permissionManager.dialog.EditGroup, OO.ui.ProcessDialog );

bs.permissionManager.dialog.EditGroup.static.name = 'editGroup';
bs.permissionManager.dialog.EditGroup.static.title = mw.msg( 'bs-permissionmanager-edit-group' );
bs.permissionManager.dialog.EditGroup.static.size = 'medium';
bs.permissionManager.dialog.EditGroup.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-permissionmanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'close', label: mw.msg( 'bs-permissionmanager-cancel' ), flags: 'safe' }
];

bs.permissionManager.dialog.EditGroup.prototype.initialize = function() {
	bs.permissionManager.dialog.EditGroup.parent.prototype.initialize.apply( this, arguments );

	this.panel = new OO.ui.PanelLayout( {
		padded: true
	} );

	this.input = new OO.ui.TextInputWidget( {
		value: this.group,
		required: true
	} );
	this.layout = new OO.ui.FieldLayout( this.input, {
		label: mw.msg( 'bs-permissionmanager-group-name' )
	} );

	this.panel.$element.append( this.layout.$element );
	this.$body.append( this.panel.$element );
};

bs.permissionManager.dialog.EditGroup.prototype.getActionProcess = function( action ) {
	if ( action === 'save' ) {
		return new OO.ui.Process( function() {
			var dfd = $.Deferred();
			this.pushPending();
			this.input.getValidity().done( function() {
				var value = this.input.getValue();
				$.ajax( {
					url: mw.util.wikiScript( 'rest' ) + '/bs-permission-manager/v1/groups/edit/' + this.group + '/' + value,
					type: 'POST',
					success: function() {
						dfd.resolve();
						this.close( { action: 'save', newGroup: value } );
					}.bind( this ),
					error: function( xhr, status, error ) {
						this.popPending();
						dfd.reject( xhr.hasOwnProperty( 'responseJSON' ) ? xhr.responseJSON.message : '' );
					}.bind( this )
				} );
			}.bind( this ) ).fail( function() {
				this.popPending();
				dfd.reject();
			}.bind( this ) );

			return dfd.promise();
		}.bind( this ) );
	}

	return bs.permissionManager.dialog.EditGroup.parent.prototype.getActionProcess.call( this, action );
};

bs.permissionManager.dialog.EditGroup.prototype.getBodyHeight = function () {
	return 80;
};