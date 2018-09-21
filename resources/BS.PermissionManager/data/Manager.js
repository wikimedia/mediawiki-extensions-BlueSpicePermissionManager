(function () {
	/** @const {number} */
	var NOT_ALLOWED = 0;
	/** @const {number} */
	var ALLOWED_IMPLICIT = 1;
	/** @const {number} */
	var ALLOWED_EXPLICIT = 2;

	// basic data setup
	/**
	 * a local reference to the definition of all available namespaces
	 *
	 * @typedef namespace {{id:number, name:string, hideable:boolean}}
	 * @type {Array.<namespace>}
	 */
	var namespaces = mw.config.get( 'bsPermissionManagerNamespaces', [] );

	var roles = mw.config.get( 'bsPermissionManagerRoles', [] );

	var groupRoles = Ext.Object.merge( {}, mw.config.get( 'bsPermissionManagerGroupRoles', {} ) );

	var roleLockdown = Ext.Object.merge( {}, mw.config.get( 'bsPermissionManagerRoleLockdown', {} ) );

	/**
	 * holds references to the modified values of every defined user group
	 * @type {object<string, Array.<Ext.data.Model>>}
	 */
	var modifiedValues = {
		user: {}
	};
	/**
	 * holds the current pending changes for each group
	 * @type {object<string, number>}
	 */
	var isDirty = {
		user: 0
	};

	/**
	 * Holds the group we are currently working on. It is initialized with
	 * "user" because that is the default group.
	 *
	 * @type {string}
	 */
	var workingGroup = 'user';

	/**
	 * holds the basic field definitions for the grid model which get dynamically
	 * extended later on
	 *
	 * @type {Array.<object>}
	 */
	var modelFields = [
		{name: 'hint', type: 'auto'},
		{name: 'role', type: 'string'},
		{name: 'type', type: 'int'},
		{name: 'userCan_Wiki', type: 'auto'}
	];

	/**
	 * holds the basic column definitions for the grid which get dynamically
	 * extended later on
	 *
	 * @type {Array.<Ext.grid.column.Column>}
	 */
	var columns = [{
		header: '',
		dataIndex: 'hint',
		stateId: 'hint',
		xtype: 'bs-pm-rolehint',
		locked: true,
		sortable: false,
		hideable: false,
		width: 20
	},{
		header: mw.message('bs-permissionmanager-header-role').plain(),
		dataIndex: 'role',
		locked: true,
		stateId: 'role',
		sortable: false,
		hideable: false,
		width: 150
	}, {
		header: mw.message('bs-permissionmanager-header-global').plain(),
		dataIndex: 'userCan_Wiki',
		locked: true,
		xtype: 'bs-pm-rolecheck',
		stateId: 'userCan_Wiki',
		sortable: false,
		hideable: false,
		width: 50,
		cls: 'global-namespace-column'
	}, {
		header: mw.message('bs-permissionmanager-header-namespaces').plain(),
		sortable: false,
		hideable: true,
		defaults: {
			flex: 1,
			minWidth: 60
		},
		columns: []
	}];

	// for every namespace we have, we add one column to the grid and one field to the grid model.
	for( var i = 0, len = namespaces.length; i < len; i++ ) {
		if( !Ext.isObject(namespaces[i] ) ) {
			continue;
		}
		var namespace = namespaces[i];

		columns[3].columns.push({
			header: namespace.name,
			dataIndex: 'userCan_' + namespace.id,
			flex: 1,
			stateId: 'userCan_' + namespace.id,
			sortable: false,
			xtype: 'bs-pm-rolecheck',
			hideable: namespace.hideable,
			hidden: ! namespace.content,
			lockable: false,
			width: 65,
			cls: 'namespace-column'
		});

		modelFields.push({
			name: 'userCan_' + namespace.id,
			type: 'auto'
		}, {
			name: 'affectedBy_' + namespace.id,
			type: 'auto'
		}, {
			name: 'affectedBy_Wiki',
			type: 'auto'
		}, {
			name: 'isBlocked_' + namespace.id,
			type: 'auto'
		});
	}

	function buildRoleData() {
		var data = [];
		// add one row for every role
		for( var i = 0, roleslen = roles.length; i < roleslen; i++ ) {
			var row = roles[i];
			var roleRes = checkRole( row.role );
			row.userCan_Wiki = roleRes;
			var affectedBy = getAffectedBy( row.role, roleRes );
			row.affectedBy_Wiki = affectedBy.message;

			for( var j = 0, nslen = namespaces.length; j < nslen; j++ ) {
				if( !Ext.isObject(namespaces[j] ) ) {
					continue;
				}
				var namespaceId = namespaces[j].id;
				var roleRes = checkRoleInNamespace( row.role, namespaceId );
				row[ 'userCan_' + namespaceId ] = roleRes;
				var affectedBy = getAffectedBy( row.role, roleRes, namespaceId );
				row[ 'affectedBy_' + namespaceId ] = affectedBy.message;
				row[ 'isBlocked_' + namespaceId ] = affectedBy.isBlocked;
			}

			data.push(row);
		}

		var result = {
			roles: data
		};

		$( document ).trigger( 'BSPermissionManagerBuildRoleData', [ this, result ] );

		return result;
	}

	function setRole( role, granted ) {
		if( !Ext.isDefined( groupRoles[ workingGroup ] ) ) {
			groupRoles[ workingGroup ] = {};
		}
		if( granted ) {
			groupRoles[ workingGroup ][ role ] = true;
		} else {
			groupRoles[ workingGroup ][ role ] = false;
			for( var i = 0, len = namespaces.length; i < len; i++ ) {
				setRoleInNamespace( role, namespaces[i].id, false );
			}
		}
	}

	function setRoleInNamespace( role, namespace, granted ) {
		if( granted ) {
			setRole( role, granted );
			if( !Ext.isDefined( roleLockdown[ namespace ] ) ) {
				roleLockdown[ namespace ] = {};
			}
			if( !Ext.isDefined( roleLockdown[ namespace ][ role ] ) ) {
				roleLockdown[ namespace ][ role ] = [];
			}
			if( !Ext.Array.contains( roleLockdown[ namespace ][ role ], workingGroup)) {
				roleLockdown[ namespace ][ role ].push( workingGroup );
			}
		} else {
			if( Ext.isDefined( roleLockdown[ namespace ] ) ) {
				if( Ext.isDefined( roleLockdown[ namespace ][ role ] ) ) {
					Ext.Array.remove( roleLockdown[ namespace ][ role ], workingGroup );
				}
			}
		}
	}

	function checkRole(role, group) {
		// if no group is given, we use the current workingGroup
		group = group || workingGroup;

		if( Ext.isDefined( groupRoles[ group ] )
				&& Ext.isDefined( groupRoles[ group ][ role ])
				&& groupRoles[ group ][ role ] ) {
			return ALLOWED_EXPLICIT;
		}
		// if the group doesn't have the explicit permission for the given
		// role, we need to check, if it inherits it from another group
		if( group !== '*' ) {
			if( group !== 'user' ) {
				if( checkRole( role, 'user' ) ) {
					return ALLOWED_IMPLICIT;
				}
			}
			if( checkRole( role, '*' ) ) {
				return ALLOWED_IMPLICIT;
			}
		}
		// if we reach this point then there is no explicit or implicit
		// permission configured.
		return NOT_ALLOWED;
	}

	function checkRoleInNamespace( role, namespace, group ) {
		group = group || workingGroup;
		if( checkRole( role ) ) {
			// if there is no lockdown rule for this namespace
			// the group has the permission
			if( !Ext.isDefined( roleLockdown[ namespace ] ) ) {
				return ALLOWED_IMPLICIT;
			}

			// if there is no lockdown rule for this right in this namespace
			// the group has the permission
			if( !Ext.isDefined( roleLockdown[ namespace ][ role ] ) ) {
				return ALLOWED_IMPLICIT;
			}

			// if there is a lockdown rule and it contains this group
			// the group has the permission
			if( Ext.isArray( roleLockdown[ namespace ][ role ] ) ) {
				if( Ext.Array.contains( roleLockdown[ namespace ][ role ], group ) ) {
					return ALLOWED_EXPLICIT;
				} else if( roleLockdown[ namespace ][ role ].length === 0 ) {
					return ALLOWED_IMPLICIT;
				}
			}
		}
		// anything else means this group doesn't have the permission
		return NOT_ALLOWED;
	}

	function getAffectedBy( role, type, namespace ) {
		if( type == ALLOWED_EXPLICIT ) {
			return {
				message: mw.message( 'bs-permissionmanager-affected-by-explicitlyset' ).plain(),
				isBlocked: false
			};
		}
		var groups = Object.keys( groupRoles );
		var explicitGroupsNS = [];
		var groupsWiki = [];
		for( var i = 0; i < groups.length; i++ ) {
			var group = groups[i];
			var res = checkRoleInNamespace( role, namespace, group );
			if( res === ALLOWED_EXPLICIT ) {
				explicitGroupsNS.push( group );
				continue;
			}
			res = checkRole( role, group );
			if( res === ALLOWED_EXPLICIT || res === ALLOWED_IMPLICIT ) {
				groupsWiki.push( { group : group, type: res } );
			}
		}

		if( explicitGroupsNS.length === 0 && groupsWiki.length === 0 ) {
			return {
				message: mw.message( 'bs-permissionmanager-affected-by-notset' ).plain(),
				isBlocked: false
			};
		}

		var sNSGroups = '';
		var sWikiGroups = '';
		if( explicitGroupsNS.length > 0 ) {
			sNSGroups = explicitGroupsNS.join();
		}
		if( groupsWiki.length > 0 ) {
			for( var i = 0; i < groupsWiki.length; i++ ) {
				if( groupsWiki[i].group !== workingGroup && groupsWiki[i].type === ALLOWED_EXPLICIT ) {
					if( groupsWiki[i].group !== '*' && groupsWiki[i].group !== 'user' ) {
						continue;
					}
					if( sWikiGroups ){
						sWikiGroups += ", ";
					}
					sWikiGroups += groupsWiki[i].group;
				}
			}
		}

		for( var i = 0; i < groupsWiki.length; i++ ) {
			if( groupsWiki[i].group === workingGroup && groupsWiki[i].type === ALLOWED_EXPLICIT && type === ALLOWED_IMPLICIT ) {
				return {
					message: mw.message( 'bs-permissionmanager-affected-by-setonwiki' ).plain(),
					isBlocked: false
				};
			}

			if( groupsWiki[i].group === workingGroup && type === NOT_ALLOWED ) {
				return {
					message: mw.message( 'bs-permissionmanager-affected-by-explicit', sNSGroups ).plain(),
					isBlocked: true
				};
			}
		}
		if( type === NOT_ALLOWED ) {
			return {
				message: mw.message( 'bs-permissionmanager-affected-by-notset' ).plain(),
				isBlocked: false
			};
		} else {
			return {
				message: mw.message( 'bs-permissionmanager-affected-by-inherited', sWikiGroups ).plain(),
				isBlocked: false
			};
		}
	}

	function saveRoles(caller) {
		// if no caller is given we create a dummy to avoid errors
		caller = caller || {
			mask: function () {
			},
			unmask: function () {
			}
		};

		caller.mask();

		bs.api.tasks.exec(
			'permissionmanager',
			'saveRoles',
			{
				groupRoles: groupRoles,
				roleLockdown: roleLockdown
			}
		).done(function (response) {
			if (response.success === true) {
				caller.unmask();

				mw.notify( mw.msg( 'bs-permissionmanager-save-success' ), { title: mw.msg( 'bs-extjs-title-success' ) } );

				// Reset modification cache
				modifiedValues = {};
				modifiedValues[ workingGroup ] = {};
				// Reset modification counter
				isDirty = {};
				isDirty[ workingGroup ] = 0;

				// We save the current work data back to the source data so
				// that we can "reset" the grid to the current save point.
				// We also use {@see Ext.Object.merge} again, to have an
				// independent copy of the data.
				mw.config.set(
						'bsPermissionManagerGroupRoles',
						Ext.Object.merge( {}, groupRoles ) );
				mw.config.set(
						'bsPermissionManagerRoleLockdown',
						Ext.Object.merge( {}, roleLockdown ) );

				// For performance reasons we don't sync every single record
				// in the store, anymore but just recreate the whole dataset
				// from the current settings. This bypasses a lot of checks
				// and prevents browser freezing.
				Ext.data.StoreManager
						.lookup( 'bs-permissionmanager-role-store' )
						.loadRawData( buildRoleData().roles );
			} else {
				caller.unmask();
				bs.util.alert( 'bs-pm-save-error', {
					text: result.message
				});
			}
		}).fail( function ( response ) {
			caller.unmask();
		} );

	}

	Ext.define( 'RoleGridModel', {
		extend: 'Ext.data.Model',
		fields: modelFields,
		idProperty: 'role',
		/**
		 * Runs the standard Ext.data.Model constructor and then copies the cached modified fields into the model instance.
		 * This is needed, because otherwise we lose track of all modifications on the data set, when the group is changed.
		 *
		 * @param {object} data An object containing keys corresponding to this model's fields, and their associated values
		 * @param {mixed} id meant for internal use only
		 * @param {object} raw meant for internal use only
		 * @param {object} convertedData meant for internal use only
		 * @constructor
		 */
		constructor: function( data, id, raw, convertedData ) {
			this.callParent( arguments );
			if( !Ext.isDefined( modifiedValues[ workingGroup ][ id ] ) ) {
				modifiedValues[ workingGroup ][ id ] = {};
			}
			this.modified = modifiedValues[ workingGroup ][ id ];
			if( this.modified !== {} ) {
				this.dirty = true;
			}
		},

		set: function( fieldName, newValue, justCheck ) {
			var me = this,
				data = me.data,
				fields = me.fields,
				modified = me.modified,
				id = data.role,
				namespace = parseInt( fieldName.substring( 8 ) ), //fieldName = "userCan_23454" || "userCan_Wiki"
				currentValue, field, key, modifiedFieldNames, name,
				ns, namespaceId, value;

			justCheck = justCheck || false;
			if( !Ext.isNumber( namespace ) ) { //e.g. parseInt("Wiki"), see above
				namespace = false;
			}
			me.beginEdit();
			if( namespace === false ) {
				// newValue can either be a boolean or an int. If it is an int,
				// we don't need to convert it because it already represents one of
				// the triple state values. Otherwise, we convert it into a triple
				// state value.
				if( Ext.isBoolean( newValue ) ) {
					value = checkRole( id );
					// A field can have the value ALLOWED_EXPLICIT, ALLOWED_IMPLICITE
					// and NOT_ALLOWED, whereof ALLOWED_EXPLICIT is the only value
					// which shows as a checked checkbox.
					if( value < ALLOWED_EXPLICIT ) {
						// So if the field has any of the other values then it
						// means that the user want to check it.
						value = ALLOWED_EXPLICIT;
					} else {
						// Otherwise the user wants to uncheck it.
						value = NOT_ALLOWED;
					}
				} else {
					value = newValue;
				}
				setRole( id, value );
			} else {
				// same logic as above
				if( Ext.isBoolean( newValue ) ) {
					value = checkRoleInNamespace( id, namespace );
					if( value < ALLOWED_EXPLICIT ) {
						value = ALLOWED_EXPLICIT;
					} else {
						value = NOT_ALLOWED;
					}
				} else {
					value = newValue;
				}
				setRoleInNamespace( id, namespace, value );
			}

			// The following code checks if the value "userCan_Wiki" field
			// changed since the last commit. If so, we keep track of that
			// not just in this record itself but also in the data manager
			// so that we can restore this informations even after group
			// changes which would otherwise destroy this data.
			name = 'userCan_Wiki';
			value = checkRole( id );
			if( fields && ( field = me.getField( name ) ) && field.convert ) {
				value = field.convert( value, me );
			}

			currentValue = data[ name ];
			if( !me.isEqual( currentValue, value ) ) {
				data[ name ] = value;
				( modifiedFieldNames || ( modifiedFieldNames = [] ) ).push( name );

				if( field && field.persist ) {
					if( modified.hasOwnProperty( name ) ) {
						if( me.isEqual( modified[ name ], value ) ) {
							// The original value in me.modified equals the new value, so
							// the field is no longer modified:
							delete modified[ name ];
							me.dirty = false;
							isDirty[ workingGroup ]--;

						}
					} else {
						me.dirty = true;
						modified[ name ] = currentValue;
						isDirty[ workingGroup ]++;

					}
				}
			}
			// now we repeat the check above for every "userCan_X" field
			for( ns in namespaces ) {
				if( !namespaces.hasOwnProperty( ns ) ) {
					continue;
				}
				namespaceId = namespaces[ ns ].id;
				name = 'userCan_' + namespaceId;
				value = checkRoleInNamespace( id, namespaceId );
				if( fields && ( field = me.getField( name ) ) && field.convert ) {
					value = field.convert( value, me );
				}
				currentValue = data[ name ];
				if( !me.isEqual( currentValue, value ) ) {
					data[ name ] = value;
					( modifiedFieldNames || ( modifiedFieldNames = [] ) ).push( name );

					if( field && field.persist ) {
						if( modified.hasOwnProperty( name ) ) {
							if( me.isEqual( modified[name], value ) ) {
								// The original value in me.modified equals the new value, so
								// the field is no longer modified:
								delete modified[ name ];
								me.dirty = false;
								isDirty[ workingGroup ]--;
							}
						} else {
							me.dirty = true;
							modified[ name ] = currentValue;
							isDirty[ workingGroup ]++;
						}
					}
				}
			}

			// We might have removed the last modified field, so check to
			// see if there are any modified fields remaining and correct
			// me.dirty:
			me.dirty = false;
			for( key in modified ) {
				if( modified.hasOwnProperty( key ) ) {
					me.dirty = true;
					break;
				}
			}

			me.endEdit();
			return modifiedFieldNames || null;
		}
	});

	Ext.define( 'BS.PermissionManager', {
		statics: {
			NOT_ALLOWED: NOT_ALLOWED,
			ALLOWED_IMPLICIT: ALLOWED_IMPLICIT,
			ALLOWED_EXPLICIT: ALLOWED_EXPLICIT
		}
	} );

	Ext.define( 'BS.PermissionManager.data.Manager', function () {
		return {
			getWorkingGroup: function() {
				return workingGroup;
			},
			setWorkingGroup: function( group ) {
				workingGroup = group;
				if( !Ext.isDefined( modifiedValues[ group ] ) ) {
					modifiedValues[ group ] = {};
				}
				if( !Ext.isDefined( isDirty[ group ] ) ) {
					isDirty[ group ] = 0;
				}
			},
			setGroupRoles: function( roles ) {
				groupRoles = roles;
			},
			buildRoleData: buildRoleData,
			checkRole: checkRole,
			checkRoleInNamespace: checkRoleInNamespace,
			getColumns: function() {
				return columns;
			},
			setRole: setRole,
			setRoleInNamespace: setRoleInNamespace,
			saveRoles: saveRoles,
			/**
			 * Resets all changes made in this session
			 */
			resetAllSettings: function() {
				// get the original settings and save them as the new working set
				// we use Ext.Object.merge() here because we need copies and no references
				groupRoles = Ext.Object.merge({}, mw.config.get('bsPermissionManagerGroupRoles', {}));
				roleLockdown = Ext.Object.merge({}, mw.config.get('bsPermissionManagerRoleLockdown', {}));

				// remove all cached modifications
				for( var group in modifiedValues ) {
					if( modifiedValues.hasOwnProperty( group ) ) {
						modifiedValues[ group ] = {};
					}
				}
			},
			/**
			 * Checks if there are unsaved changes in the grid.
			 *
			 * @returns {boolean}
			 */
			isDirty: function() {
				for( var group in isDirty ) {
					if( !isDirty.hasOwnProperty( group ) ) {
						continue;
					}
					if( isDirty[ group ] > 0 ) {
						return true;
					}
				}
				return false;
			}
		};
	} );
} )();
