bs.util.registerNamespace( 'bs.permissionManager.dialog' );

bs.permissionManager.dialog.GroupNameDialog = function( cfg ) {
	bs.permissionManager.dialog.GroupNameDialog.parent.call( this, cfg );

	this.group = cfg.group;
};

OO.inheritClass( bs.permissionManager.dialog.GroupNameDialog, OO.ui.ProcessDialog );

bs.permissionManager.dialog.GroupNameDialog.static.actions = [
	{ action: 'save', label: mw.msg( 'bs-permissionmanager-save' ), flags: [ 'primary', 'progressive' ] },
	{ action: 'close', label: mw.msg( 'bs-permissionmanager-cancel' ), flags: 'safe' }
];

bs.permissionManager.dialog.GroupNameDialog.prototype.initialize = function() {
	bs.permissionManager.dialog.GroupNameDialog.parent.prototype.initialize.apply( this, arguments );

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

bs.permissionManager.dialog.GroupNameDialog.prototype.getActionProcess = function( action ) {
	if ( action === 'save' ) {
		return new OO.ui.Process( function() {
			var dfd = $.Deferred();
			this.pushPending();
			this.input.getValidity().done( function() {
				var value = this.input.getValue();
				$.ajax( {
					url: this.getUrl( value ),
					type: this.getMethod(),
					success: function() {
						dfd.resolve();
						this.close( { action: 'save', newGroup: value } );
					}.bind( this ),
					error: function( xhr ) {
						this.popPending();
						if ( xhr.hasOwnProperty( 'responseJSON' ) ) {
							dfd.reject(
								new OO.ui.Error( xhr.responseJSON.message || mw.msg( 'bs-permissionmanager-error' ) )
							);
						} else {
							dfd.reject();
						}
					}.bind( this )
				} );
			}.bind( this ) ).fail( function() {
				this.popPending();
				this.input.setValidityFlag( false );
				dfd.reject();
			}.bind( this ) );

			return dfd.promise();
		}.bind( this ) );
	}
	if ( action === 'close' ) {
		this.close( { action: 'cancel' } );
	}

	return bs.permissionManager.dialog.GroupNameDialog.parent.prototype.getActionProcess.call( this, action );
};

bs.permissionManager.dialog.GroupNameDialog.prototype.getUrl = function( value ) {
	return '';
};

bs.permissionManager.dialog.GroupNameDialog.prototype.getMethod = function() {
	return '';
};

bs.permissionManager.dialog.GroupNameDialog.prototype.getBodyHeight = function () {
	if ( !this.$errors.hasClass( 'oo-ui-element-hidden' ) ) {
		return this.$element.find( '.oo-ui-processDialog-errors' )[0].scrollHeight;
	}
	return this.$body[0].scrollHeight + 20;
};

bs.permissionManager.dialog.GroupNameDialog.prototype.onDismissErrorButtonClick = function () {
	this.hideErrors();
	this.updateSize();
};

bs.permissionManager.dialog.GroupNameDialog.prototype.showErrors = function () {
	bs.permissionManager.dialog.GroupNameDialog.parent.prototype.showErrors.call( this, arguments );
	this.updateSize();
};
