Ext.define('BS.PermissionManager.TemplateEditor', {
	extend: 'Ext.window.Window',
	requires: [
		'Ext.tree.Panel',
		'BS.PromptDialog',
		'BS.PermissionManager.store.TemplateTree',
		'BS.PermissionManager.store.TemplatePermissions'
	],
	title: mw.message('bs-permissionmanager-labeltpled').plain(),
	loadMask: false,
	width: 500,
	height: 450,
	modal: true,
	layout: 'border',
	closeAction: 'hide',
	_cleanState: true,
	_hasChanged: false,
	_closeDialog: true,
	constructor: function(config) {
		this._treeStore = Ext.create('BS.PermissionManager.store.TemplateTree');
		this._permissionStore = Ext.create('BS.PermissionManager.store.TemplatePermissions');
		this.callParent([config]);
	},
	setCleanState: function(clean) {
		this._cleanState = clean;
		if (this._cleanState === true) {
			Ext.getCmp('pmTemplateEditorSaveButton').disable();
		} else {
			Ext.getCmp('pmTemplateEditorSaveButton').enable();
		}
	},
	getCleanState: function() {
		return this._cleanState;
	},
	hasChanged: function() {
		return this._hasChanged;
	},
	setCloseDialog: function(close) {
		this._closeDialog = close;
	},
	getCloseDialog: function() {
		return this._closeDialog;
	},
	saveTemplate: function() {
		var me = this;
		var record = Ext.getCmp('bs-template-editor-treepanel')
						.getSelectionModel().getLastSelected();
		var newRecord = {
			id: record.get('id'),
			text: record.get('text'),
			leaf: record.get('leaf'),
			ruleSet: [],
			description: Ext.getCmp('bs-template-editor-description').getRawValue()
		};

		if (typeof record !== 'undefined') {
			for (var i in me._permissionStore.data.items) {
				var dataSet = me._permissionStore.data.items[i].data;
				if (dataSet.enabled === true) {
					newRecord.ruleSet.push(dataSet.name);
				}
			}

			bs.api.tasks.exec(
				'permissionmanager',
				'setTemplateData',
				newRecord
			).done(function (response) {

				var result = response.payload;
				if (result.success === true) {
					var isNewRecord = (newRecord.id === 0 || newRecord.id === null) ? true : false;
					newRecord.id = result.id;
					var rootNode = me._treeStore.getRootNode();
					rootNode.replaceChild(newRecord, record);

					var dataManager = Ext.create('BS.PermissionManager.data.Manager');
					dataManager.setTemplate(newRecord, isNewRecord);

					me.setCleanState(true);
					me._permissionStore.sync();
					me._hasChanged = true;

					Ext.data.StoreManager
							.lookup('bs-permissionmanager-permission-store')
							.loadRawData(dataManager.buildPermissionData().permissions);

					if( me.getCloseDialog() ) {
						Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().select(
								me._treeStore.getNodeById(newRecord.text)
								);
					}

					mw.notify( mw.msg( 'bs-permissionmanager-msgtpled-success' ), { title: mw.msg( 'bs-extjs-title-success' ) } );
				} else {
					bs.util.alert( 'bs-pm-save-tpl-error', {
						text: result.msg
					});
				}

				if ( me.getCloseDialog() ) {
					me.hide();
				}
				me.setCloseDialog(true);
			});

		}
	},
	discardChanges: function() {
		var record = Ext.getCmp('bs-template-editor-treepanel')
						.getSelectionModel().getLastSelected();
		if(record !== null && record.get('id') === 0){
			record.parentNode.removeChild(record);
		}

		Ext.getCmp('bs-template-editor-description').setRawValue('');
		this._permissionStore.each(function(record) {
			record.set('enabled', false);
		});
		this.setCleanState(true);
	},
	noUnsavedChanges: function(record) {
		var me = this;
		if (typeof record == 'undefined') {
			record = false;
		}
		if (me.getCleanState() === false) {
			var dialog = Ext.create('BS.ConfirmDialog', {
				text: mw.message('bs-permissionmanager-msgtpled-saveonabort').plain()
			});
			dialog.on('ok', function() {
				me.setCloseDialog(false);
				me.saveTemplate();
				me.setCleanState(true);
				if (record !== false) {
					Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().select(record);
				}
			});
			dialog.on('cancel', function() {
				me.discardChanges();
				if (record !== false) {
					Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().select(record);
				}
			});
			dialog.show();
			return false;
		}
		return true;
	},
	initComponent: function() {
		var me = this;
		me.items = [{
				xtype: 'treepanel',
				region: 'west',
				id: 'bs-template-editor-treepanel',
				useArrows: false,
				width: 160,
				store: me._treeStore,
				rootVisible: false,
				margins: '0 0 5 0',
				listeners: {
					'select': function ( rm, record ) {
						var data = [ ];
						for ( var i in me._permissionStore.data.items ) {
							var dataSet = me._permissionStore.data.items[i].data;
							dataSet.enabled = Ext.Array.contains(record.get('ruleSet'), dataSet.name);
							data.push(dataSet);
						}
						me._permissionStore.loadRawData(data);
						Ext.getCmp('bs-template-editor-description').setRawValue(record.raw.description);
						Ext.getCmp('pmTemplateEditorEditButton').enable();
						Ext.getCmp('pmTemplateEditorRemoveButton').enable();
						me.setCleanState(true);
					},
					'beforeselect': function(rm, record) {
						return me.noUnsavedChanges(record);
					}
				}
			}, {
				xtype: 'container',
				layout: 'border',
				region: 'center',
				items: [{
						xtype: 'panel',
						layout: 'form',
						region: 'center',
						title: mw.message('bs-permissionmanager-labeltpled-desc').plain(),
						id: 'bs-template-editor-formpanel',
						margins: '0 0 5 5',
						items: [{
								xtype: 'textareafield',
								grow: false,
								id: 'bs-template-editor-description',
								name: 'description',
								hideLabel: true,
								margin: 0,
								padding: 0,
								height: 80,
								anchor: '100%'
							}]
					}, {
						xtype: 'gridpanel',
						region: 'south',
						id: 'bs-template-editor-gridpanel',
						height: 250,
						margins: '0 0 5 5',
						store: me._permissionStore,
						columns: [{
								xtype: 'checkcolumn',
								text: mw.message('bs-permissionmanager-labeltpled-active').plain(),
								dataIndex: 'enabled',
								listeners: {
									'checkchange': function() {
										me.setCleanState(false);
									}
								}
							}, {
								text: mw.message('bs-permissionmanager-header-permissions').plain(),
								dataIndex: 'name',
								flex: 1
							}
						]
					}]
			}];
		me.bbar = [{
				text: mw.message('bs-permissionmanager-labeltpled-add').plain(),
				id: 'pmTemplateEditorAddButton',
				handler: function() {
					if (me.noUnsavedChanges()) {
						var dialog = Ext.create('BS.PromptDialog', {
							text: mw.message('bs-permissionmanager-msgtpled-new').plain()
						});
						dialog.on('ok', function(input) {
							var node = me._treeStore.tree.root.appendChild({
								id: 0,
								text: input.value,
								leaf: true,
								description: '',
								ruleSet: []
							});
							Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().select(node);
						});
						dialog.show();
					}
				}
			}, {
				text: mw.message('bs-permissionmanager-labeltpled-edit').plain(),
				disabled: true,
				id: 'pmTemplateEditorEditButton',
				handler: function() {
					var dialog = Ext.create('BS.PromptDialog', {
						text: mw.message('bs-permissionmanager-msgtpled-edit').plain()
					});
					dialog.on('ok', function(input) {
						Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().getLastSelected().set('text', input.value);
						me.setCleanState(false);
					});
					dialog.show();
				}
			}, {
				text: mw.message('bs-permissionmanager-labeltpled-delete').plain(),
				disabled: true,
				id: 'pmTemplateEditorRemoveButton',
				handler: function() {
					var record = Ext.getCmp('bs-template-editor-treepanel')
									.getSelectionModel().getLastSelected(),
							id = record.get( 'id' );
					//id == 0 if the template hasn't been saved yet
					if ( id === 0 ) {
						record.remove( );
						return true;
					}
					bs.api.tasks.exec(
						'permissionmanager',
						'deleteTemplate',
						{id:id}
					).done(function(response){
						var result = response.payload;
							if (result.success === true) {
								me.setCleanState(true);
								try {
									record.remove(true);
								} catch(e) {
									// this try-catch-finally block is a bad hack
									// because ext always throws an exception
									// when the record is removed from the tree
									// store. we just throw that exception away
									// here and go directly to finally.
								} finally {
									me._permissionStore.sync();
									me._hasChanged = true;

									mw.notify( mw.msg( 'bs-permissionmanager-msgtpled-delete' ), { title: mw.msg( 'bs-extjs-title-success' ) } );

									var dataManager = Ext.create('BS.PermissionManager.data.Manager');
									dataManager.deleteTemplate(id);

									Ext.data.StoreManager
										.lookup('bs-permissionmanager-permission-store')
										.loadRawData(dataManager.buildPermissionData().permissions);
								}
							} else {
								bs.util.alert( 'bs-pm-delete-tpl-error', {
									text: result.msg
								});
							}
					});
				}
			}, '->', {
				text: mw.message('bs-permissionmanager-btn-save-label').plain(),
				disabled: true,
				id: 'pmTemplateEditorSaveButton',
				handler: function() {
					me.saveTemplate();
				}
			}, {
				text: mw.message('bs-permissionmanager-labeltpled-cancel').plain(),
				handler: function() {
					me.discardChanges();
					me.hide();
				}
			}];
		me.on('show', function() {
			Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().deselectAll();
			Ext.getCmp('bs-template-editor-treepanel').getSelectionModel().select(me._treeStore.getRootNode().getChildAt(0));
		});
		me.on( 'hide', me.discardChanges, this ); //prevent empty templates to be saved
		this.callParent();
	}
});
