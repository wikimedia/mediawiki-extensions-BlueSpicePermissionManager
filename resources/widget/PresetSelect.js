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

		var pendingActive = false;
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
				pendingActive = presetId;
			}
		}

		this.helpLabel = new OO.ui.LabelWidget( {
			classes: [ 'preset-help' ]
		} );

		this.$element.append( this.helpLabel.$element );

		this.presetChange( pendingActive, true );
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
		if ( init ) {
			return this.setActive( presetId, init );
		}

		var confirmMessage = mw.message( 'bs-permissionmanager-switch-preset-confirm' ).text();
		OO.ui.confirm( confirmMessage ).done( function ( confirmed ) {
			if ( confirmed ) {
				this.setActive( presetId );
			}
		}.bind( this ) );
	};

	bs.permissionManager.widget.PresetSelect.prototype.setActive = function( presetId, init ) {
		if ( this.active ) {
			this.presetItems[this.active].setActive( false );
		}
		this.active = presetId;
		this.presetItems[presetId].setActive( true );

		this.helpLabel.setLabel( this.presets[presetId].help );

		if ( presetId === 'custom' ) {
			this.showCustom();
		} else {
			this.hideCustom();
		}

		if ( !init ) {
			this.save();
		}
	};

	bs.permissionManager.widget.PresetSelect.prototype.save = function() {
		var data = {
			PermissionManagerActivePreset: this.active
		};
		bs.api.tasks.execSilent( 'configmanager', 'save', data )
			.done( function( response ) {
				if ( !response.hasOwnProperty( 'success' ) || !response.success ) {
					return this.reportError();
				}
				if ( this.errorWidget ) {
					this.errorWidget.$element.remove();
				}
			}.bind( this ) ).fail( function() {
				this.reportError();
			}.bind( this ) );
	};

	bs.permissionManager.widget.PresetSelect.prototype.reportError = function() {
		if ( !this.errorWidget ) {
			this.errorWidget = new OO.ui.MessageWidget( {
				type: 'error',
				label: mw.message( 'bs-permissionmanager-preset-save-error' ).text()
			} );
			this.$element.prepend( this.errorWidget.$element );
		}
	};

	bs.permissionManager.widget.PresetSelect.prototype.showCustom = function() {
		if ( !this.customPanelLoaded ) {
			this.$customPresetPanel.show();
			this.presetsSetDisabled( true );
			mw.loader.using( 'ext.bluespice.permissionManager.customPreset' ).done( function() {
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
