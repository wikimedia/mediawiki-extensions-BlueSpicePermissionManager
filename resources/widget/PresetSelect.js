( function ( $, mw, bs, d ) {
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

		this.makeButtons();
		this.$customPresetPanel.insertBefore( this.buttonLayout.$element );

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

	bs.permissionManager.widget.PresetSelect.prototype.makeButtons = function() {
		this.saveButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-permissionmanager-btn-save-label' ).text(),
			flags: [ 'primary', 'progressive' ],
			disabled: true
		} );

		this.resetButton = new OO.ui.ButtonWidget( {
			label: mw.message( 'bs-premissionmanager-reset-button-label' ).text(),
			disabled: true
		} );

		this.saveButton.connect( this, {
			click: 'onSave'
		} );

		this.resetButton.connect( this, {
			click: 'onReset'
		} );

		this.buttonLayout = new OO.ui.HorizontalLayout( {
			items: [
				this.saveButton,
				this.resetButton
			],
			classes: [ 'button-layout' ]
		} );

		this.$element.append( this.buttonLayout.$element );
		this.resetButton.$element.hide();
	};

	bs.permissionManager.widget.PresetSelect.prototype.setButtonState = function ( dirty ) {
		dirty = dirty || false;
		this.saveButton.setDisabled( !(dirty || this.dirty ) );
		if ( this.active === 'custom' ) {
			this.resetButton.setDisabled( !dirty );
		}
	};

	bs.permissionManager.widget.PresetSelect.prototype.onSave = function() {
		var promises = [],
			mainPromise;
		if ( this.active === 'custom' && this.customPresetManager ) {
			promises.push( this.customPresetManager.onBtnSaveClick() );
		}
		promises.push( this.save() );

		mainPromise = Promise.all( promises );
		mainPromise.then(
			function() {
				this.showSuccess();
				this.initialActive = this.active;
				this.dirty = false;
				this.setButtonState();
			}.bind( this ),
			function() {
				this.reportError();
			}.bind( this )
		);
	};

	bs.permissionManager.widget.PresetSelect.prototype.onReset = function() {
		if ( this.active === 'custom' && this.customPresetManager ) {
			this.customPresetManager.onBtnResetClick();
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
			this.resetButton.$element.show();
		} else {
			this.hideCustom();
			this.resetButton.$element.hide();
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
			mw.loader.using( 'ext.bluespice.permissionManager.customPreset' ).done( function() {
				this.customPresetManager = Ext.create( 'BS.PermissionManager.panel.Manager', {
					renderTo: 'bs-permission-manager-custom-preset',
					listeners: {
						dirtycheck: function( panel, dirty ) {
							this.setButtonState( dirty );
						}.bind( this )
					}
				} );
				this.presetsSetDisabled( false );
				this.customPanelLoaded = true;
				this.$customPresetPanel.find( '.placeholder-loader' ).remove();
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
} )( jQuery, mediaWiki, blueSpice, document );
