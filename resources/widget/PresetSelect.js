bs.util.registerNamespace( 'bs.permissionManager.widget' );

bs.permissionManager.widget.PresetSelect = function ( cfg ) {
	cfg = cfg || {};

	bs.permissionManager.widget.PresetSelect.parent.call( this, cfg );

	this.$customPresetPanel = cfg.$customPanel;
	this.$customPresetPanel.hide();
	this.presets = cfg.data || {};
	this.presetItems = {};
	this.active = false;
	this.customPanelLoaded = false;
	this.errorWidget = null;
	this.dirty = false;

	this.makeToolbar();
	this.initialActive = false;
	for ( var presetId in this.presets ) {
		if ( !this.presets.hasOwnProperty( presetId ) ) {
			continue;
		}
		this.presetItems[presetId] = new bs.permissionManager.widget.PresetWidget(
			this.presets[presetId]
		);

		this.presetItems[presetId].on( 'click', this.presetChange.bind( this ), [ presetId ] );
		this.$element.append( this.presetItems[presetId].$element );

		if ( this.presets[presetId].active ) {
			this.initialActive = presetId;
		}
	}

	this.$element.append( this.$customPresetPanel );

	this.presetChange( this.initialActive, true );
};

OO.inheritClass( bs.permissionManager.widget.PresetSelect, OO.ui.Widget );

bs.permissionManager.widget.PresetSelect.static.tagName = 'div';

bs.permissionManager.widget.PresetSelect.prototype.presetChange = function( presetId, init ) {
	if ( !this.presetItems.hasOwnProperty( presetId ) ) {
		return;
	}

	if ( this.active && this.active === presetId ) {
		// Select already active item
		return;
	}
	if ( this.active === 'custom' ) {
		this.onReset();
	}

	if ( !init ) {
		this.dirty = presetId !== this.initialActive;
	}

	this.setButtonState();
	this.setActive( presetId );
};

bs.permissionManager.widget.PresetSelect.prototype.makeToolbar = function() {
	this.toolbar = new OOJSPlus.ui.toolbar.ManagerToolbar( {
		saveable: true,
		cancelable: true
	} );
	this.toolbar.connect( this, {
		save: 'onSave',
		cancel: 'onReset',
		initialize: function() {
			this.toolbar.setAbilities( { cancel: false, save: false } );
		}
	} );
	this.$element.append( this.toolbar.$element );
	this.toolbar.setup();
	this.toolbar.initialize();

};

bs.permissionManager.widget.PresetSelect.prototype.setButtonState = function ( dirty ) {
	dirty = dirty || false;
	this.toolbar.setAbilities( { save: dirty || this.dirty } );
	if ( this.active === 'custom' ) {
		this.toolbar.setAbilities( { cancel: dirty } );
	}
};

bs.permissionManager.widget.PresetSelect.prototype.onSave = function() {
	var promises = [],
		mainPromise;
	if ( this.active === 'custom' && this.customPresetManager ) {
		promises.push( this.customPresetManager.save() );
	}
	promises.push( this.save() );

	mainPromise = Promise.all( promises );
	mainPromise.then(
		function() {
			window.location.reload();
		}.bind( this ),
		function() {
			this.reportError();
		}.bind( this )
	);
};

bs.permissionManager.widget.PresetSelect.prototype.onReset = function() {
	if ( this.active === 'custom' && this.customPresetManager ) {
		this.customPresetManager.reset();
		this.setButtonState( false );
	}
};

bs.permissionManager.widget.PresetSelect.prototype.setActive = function( presetId ) {
	if ( this.active ) {
		this.presetItems[this.active].setActive( false );
	}
	this.active = presetId;
	this.presetItems[presetId].setActive( true );

	if ( presetId === 'custom' ) {
		this.showCustom();
	} else {
		this.hideCustom();
	}
};

bs.permissionManager.widget.PresetSelect.prototype.save = function() {
	var data = {
		PermissionManagerActivePreset: this.active
	},
		dfd = $.Deferred();
	bs.api.tasks.execSilent( 'configmanager', 'save', data )
		.done( function( response ) {
			if ( !response.hasOwnProperty( 'success' ) || !response.success ) {
				dfd.reject();
				return;
			}
			dfd.resolve();
		}.bind( this ) ).fail( function() {
			dfd.reject();
		}.bind( this ) );

	return dfd.promise();
};

bs.permissionManager.widget.PresetSelect.prototype.showMessage = function( type, message ) {
	if ( this.messageWidget ) {
		this.messageWidget.$element.remove();
	}
	this.messageWidget = new OO.ui.MessageWidget( {
		type: type,
		label: message,
		classes: [ 'permission-manager-info-message' ]
	} );
	this.$element.prepend( this.messageWidget.$element );
};

bs.permissionManager.widget.PresetSelect.prototype.reportError = function() {
	this.showMessage( 'error', mw.message( 'bs-permissionmanager-preset-save-error' ).text() );
};

bs.permissionManager.widget.PresetSelect.prototype.showSuccess = function() {
	this.showMessage( 'success', mw.message( 'bs-permissionmanager-preset-save-success' ).text() );
};

bs.permissionManager.widget.PresetSelect.prototype.showCustom = function() {
	if ( !this.customPanelLoaded ) {
		this.$customPresetPanel.show();
		this.presetsSetDisabled( true );
		mw.loader.using( 'ext.bluespice.permissionManager.customPresetManager' ).done( function() {
			this.customPresetManager = new bs.permissionManager.panel.PermissionManager();
			this.customPresetManager.connect( this, {
				change: function() {
					this.setButtonState( this.customPresetManager.isDirty() );
				}
			} );

			this.customPresetManager.init().then( () => {
				this.$customPresetPanel.html( this.customPresetManager.$element );
				this.presetsSetDisabled( false );
				this.customPanelLoaded = true;
				this.$customPresetPanel.removeClass( 'loading' );
			} );

		}.bind( this ) );
	}
	this.$customPresetPanel.show();
};

bs.permissionManager.widget.PresetSelect.prototype.hideCustom = function() {
	this.$customPresetPanel.hide();
};

bs.permissionManager.widget.PresetSelect.prototype.presetsSetDisabled = function( disabled ) {
	for ( var presetId in this.presetItems ) {
		if ( !this.presetItems.hasOwnProperty( presetId ) ) {
			continue;
		}
		this.presetItems[presetId].setDisabled( disabled );
	}
};
