/**
 * @fileOverview This file defines the DataManager and all of its helper
 *     functions. The DataManager prepares and formats all data for the
 *     PermissionManager user interface and creates dynamic models which contain
 *     all the business logic of the PermissionManager grid.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>
 * @author     Leonid Verhovskij <verhovskij@hallowelt.com>
 * @package    BlueSpice_Extensions
 * @subpackage PermissionManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

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
	var namespaces = mw.config.get('bsPermissionManagerNamespaces', []);
	/**
	 * Holds a local reference to the definition of all available rights. The
	 * definition contains, beside the basic informations to describe the right,
	 * also a template of the model record we use later on. All the userCan_*
	 * values in the definition have no meaning here but get copied to the model
	 * later. This happens for performance reasons, because it is much faster to
	 * copy this fields than to greate them dynamically.
	 *
	 * @typedef type {number} Holds the numeric id of the record type.
	 *     The default values are 0 for permission templates, 1 for namespace
	 *     specific rights and 2 for global rights. Every new record type must
	 *     introduce its own unique type id or set of type ids.
	 * @typedef typeHeader {string} a localized string for use in the grid headers
	 * @typedef userCan_Wiki {object<string, boolean>} specifies if the
	 *     permission for this right is given globally
	 * @typedef userCan_X {object<string, boolean>} specifies if the permission
	 *     for this right is given in a namespace. The X in the name stands for
	 *     the numeric namespace id.
	 * @typedef right {{right:string, type, typeHeader, userCan_Wiki, ...userCan_X}}
	 * @type {Array.<right>}
	 */
	var rights = mw.config.get('bsPermissionManagerRights', []);

	// we use Ext.Object.merge() here because we need copies and no references
	/**
	 * Holds a local copy of the server side variable {@see $wgGroupPermissions}.
	 * We use {@see Ext.Object.merge} here because we ned a real copy and not
	 * just a reference to the object. This is so that the original data keep
	 * unchanged and we can use them to keep track of changes in the settings
	 * and to reset the grid to its initial state, if needed.
	 *
	 * @typedef permission {object<string, boolean>} specifies if the permission
	 *     for a right is given or not
	 * @type {object<string, permission>} holds all defined settings
	 */
	var groupPermissions = Ext.Object.merge({}, mw.config.get('bsPermissionManagerGroupPermissions', {}));
	/**
	 * Holds a local copy of the server side variable
	 * {@see $wgNamespacePermissionLockdown}. Whe use {@see Ext.Object.merge}
	 * here. Explaination: {@see groupPermissions}
	 *
	 * @typedef lockdownRule {object<string, Array.<string>>} holds an array which
	 *     contain all groups that have permission for the right, defined as key
	 * @type {object<number, lockdownRule>} holds the lockdown rules for each
	 *     namespace
	 */
	var permissionLockdown = Ext.Object.merge({}, mw.config.get('bsPermissionManagerPermissionLockdown', {}));

	/**
	 * @typedef template {{id:number, description:string, leaf:boolean, ruleSet:Array.<string>, text:string}}
	 * @type {Array.<template>}
	 */
	var permissionTemplates = mw.config.get('bsPermissionManagerPermissionTemplates', []);
	/**
	 * @type {Array.<{name:string, enabled:boolean}>}
	 */
	var templateRights = [];
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
		{name: 'right', type: 'string'},
		{name: 'type', type: 'int'},
		{name: 'typeHeader', type: 'string'},
		{name: 'ruleSet', type: 'auto'},
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
		xtype: 'bs-pm-permissionhint',
		locked: true,
		sortable: false,
		hideable: false,
		width: 20
	}, {
		header: mw.message('bs-permissionmanager-header-permissions').plain(),
		dataIndex: 'right',
		locked: true,
		stateId: 'right',
		sortable: false,
		hideable: false,
		width: 200
	}, {
		header: mw.message('bs-permissionmanager-header-global').plain(),
		dataIndex: 'userCan_Wiki',
		locked: true,
		xtype: 'bs-pm-permissioncheck',
		stateId: 'userCan_Wiki',
		sortable: false,
		hideable: false,
		width: 80
	}, {
		header: mw.message('bs-permissionmanager-header-namespaces').plain(),
		sortable: false,
		hideable: true,
		flex: 1,
		defaults: {
				flex: 1,
				minWidth: 120
		},
		columns: []
		}];

	// for every namespace we have, we add one column to the grid and one field to the grid model.
	for (var i = 0, len = namespaces.length; i < len; i++) {
		if (!Ext.isObject(namespaces[i])) {
			continue;
		}
		var namespace = namespaces[i];

		columns[3].columns.push({
			header: namespace.name,
			dataIndex: 'userCan_' + namespace.id,
			flex: 1,
			stateId: 'userCan_' + namespace.id,
			sortable: false,
			xtype: 'bs-pm-permissioncheck',
			hideable: namespace.hideable,
			hidden: namespace.hideable
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

	// we transform the rights into the right format for the store of the template editor.
	for (var i = 0, rightslen = rights.length; i < rightslen; i++) {
		if (!Ext.isObject(rights[i])) {
			continue;
		}
		var right = {
			name: rights[i].right,
			enabled: false
		};
		templateRights.push(right);
	}

	/**
	 * Builds the grid data set for the current "workingGroup". The returned
	 * object can be given directly to the loadRawData method of the grid
	 * store.
	 *
	 * @returns {object}
	 */
	function buildPermissionData() {
		var data = [];

		// add one row for every right
		for (var i = 0, rightslen = rights.length; i < rightslen; i++) {
			if (!Ext.isObject(rights[i])) {
				continue;
			}
			var row = rights[i];
			var permissionRes = checkPermission( row.right );
			row.userCan_Wiki = permissionRes;
			var affectedBy = getAffectedBy( row.right, permissionRes );
			row.affectedBy_Wiki = affectedBy.message;

			for (var j = 0, nslen = namespaces.length; j < nslen; j++) {
				if (!Ext.isObject(namespaces[j])) {
					continue;
				}
				var namespaceId = namespaces[j].id;
				var permissionRes = checkPermissionInNamespace( row.right, namespaceId );
				row['userCan_' + namespaceId] = permissionRes;
				var affectedBy = getAffectedBy( row.right, permissionRes, namespaceId );
				row['affectedBy_' + namespaceId] = affectedBy.message;
				row['isBlocked_' + namespaceId] = affectedBy.isBlocked;
			}

			data.push(row);
		}

		// add one row for every permission template
		for (var i = 0, templen = permissionTemplates.length; i < templen; i++) {
			if (!Ext.isObject(permissionTemplates[i])) {
				continue;
			}

			var record = permissionTemplates[i];
			// templates have a different data format. so we need to transform them into the required format.
			var row = buildTemplateForGrid(record);

			data.push(row);
		}

		var result = {
			permissions: data
		};

		$(document).trigger('BSPermissionManagerBuildPermissionData', [this, result]);

		return result;
	}

	/**
	 * Transforms the ruleSet of a permission template record into a valid
	 * record for the PermissionManager grid.
	 *
	 * @param {object} record
	 * @returns {Ext.data.Model}
	 */
	function buildTemplateForGrid(record) {
		var row = {
			right: record.text,
			ruleSet: record.ruleSet,
			type: 0,
			typeHeader: mw.message('bs-permissionmanager-labeltemplates'),
			userCan_Wiki: checkTemplate(record.ruleSet)
		};

		for (var j = 0, nslen = namespaces.length; j < nslen; j++) {
			if (!Ext.isObject(namespaces[j])) {
				continue;
			}
			var namespaceId = namespaces[j].id;
			row['userCan_' + namespaceId] = checkTemplateInNamespace(record.ruleSet, namespaceId);
		}
		return row;
	}

	/**
	 * Checks if a given template rule set is fullfilled for the wiki in general.
	 * Returns one of the Bs.PermissionManager constants ALLOWED_EXPLICITE, ALLOWED_IMPLICITE or NOT_ALLOWED.
	 *
	 * @param {Array.<string>} ruleSet
	 * @returns {number}
	 */
	function checkTemplate(ruleSet) {
		if ( ruleSet === null ) {
			return NOT_ALLOWED;
		}

		var result = ALLOWED_EXPLICIT;
		for ( var i = 0, setlen = ruleSet.length; i < setlen; i++ ) {
			var singleResult = checkPermission( ruleSet[i] );
			if ( singleResult === NOT_ALLOWED ) {
				return NOT_ALLOWED;
			} else if( singleResult === ALLOWED_IMPLICIT ) {
				result = ALLOWED_IMPLICIT;
			}
		}
		return result;
	}

	/**
	 * Checks if a given template rule set is fullfilled in the given namespace.
	 * Returns one of the Bs.PermissionManager constants ALLOWED_EXPLICITE, ALLOWED_IMPLICITE or NOT_ALLOWED.
	 *
	 * @param {Array.<string>} ruleSet
	 * @param {number} namespace
	 * @returns {number}
	 */
	function checkTemplateInNamespace(ruleSet, namespace) {
		if (checkTemplate(ruleSet) === NOT_ALLOWED) {
			return NOT_ALLOWED;
		}

		var result = ALLOWED_EXPLICIT;
		for (var i = 0, setlen = ruleSet.length; i < setlen; i++) {
			var singleResult = checkPermissionInNamespace( ruleSet[i], namespace );
			if ( singleResult === NOT_ALLOWED ) {
				return NOT_ALLOWED;
			} else if( singleResult === ALLOWED_IMPLICIT ) {
				result = ALLOWED_IMPLICIT;
			}
		}
		return result;
	}

	/**
	 * Sets or revokes the permission of the current "workingGroup" for the given right.
	 *
	 * @param {string} right
	 * @param {boolean} permission
	 */
	function setPermission(right, permission) {
		if (!Ext.isDefined(groupPermissions[workingGroup])) {
			groupPermissions[workingGroup] = {};
		}
		if (permission) {
			groupPermissions[workingGroup][right] = true;
		} else {
			groupPermissions[workingGroup][right] = false;
			for (var i = 0, len = namespaces.length; i < len; i++) {
				setPermissionInNamespace(right, namespaces[i].id, false);
			}
		}
	}

	/**
	 * Sets or revokes the permission of the current "workingGroup" for the given namespace and right.
	 *
	 * @param {string} right
	 * @param {number} namespace
	 * @param {boolean} permission
	 */
	function setPermissionInNamespace(right, namespace, permission) {
		if (permission) {
			setPermission(right, permission);
			if (!Ext.isDefined(permissionLockdown[namespace])) {
				permissionLockdown[namespace] = {};
			}
			if (!Ext.isDefined(permissionLockdown[namespace][right])) {
				permissionLockdown[namespace][right] = [];
			}
			if (!Ext.Array.contains(permissionLockdown[namespace][right], workingGroup)) {
				permissionLockdown[namespace][right].push(workingGroup);
			}
		} else {
			if (Ext.isDefined(permissionLockdown[namespace])) {
				if (Ext.isDefined(permissionLockdown[namespace][right])) {
					Ext.Array.remove(permissionLockdown[namespace][right], workingGroup);
				}
			}
		}
	}

	/**
	 * Checks if the current "workingGroup" or, if the optional parameter group
	 * is set, the given group has the permission for the given right.
	 * Returns one of the BS.PermissionManager constants ALLOWED_EXPLICIT,
	 * ALLOWED_IMPLICIT or NOT_ALLOWED.
	 *
	 * @param {string} right
	 * @param {string=} group (optional)
	 * @returns {number}
	 */
	function checkPermission(right, group) {
		// if no group is given, we use the current workingGroup
		group = group || workingGroup;

		if (Ext.isDefined(groupPermissions[group])
				&& Ext.isDefined(groupPermissions[group][right])
				&& groupPermissions[group][right]) {
			return ALLOWED_EXPLICIT;
		}
		// if the group doesn't have the explicit permission for the given
		// right, we need to check, if it inherits it from another group
		if (group !== '*') {
			if (group !== 'user') {
				if (checkPermission(right, 'user')) {
					return ALLOWED_IMPLICIT;
				}
			}
			if (checkPermission(right, '*')) {
				return ALLOWED_IMPLICIT;
			}
		}
		// if we reach this point then there is no explicit or implicit
		// permission configured.
		return NOT_ALLOWED;
	}

	/**
	 * Checks if the current "workingGroup" has either implicit or explicit
	 * permission for the given right in the given namespace.
	 *
	 * @param {string} right
	 * @param {number} namespace
	 * @param {string=} group (optional)
	 * @returns {number}
	 */
	function checkPermissionInNamespace(right, namespace, group) {
		group = group || workingGroup;
		if (checkPermission(right)) {
			// if there is no lockdown rule for this namespace
			// the group has the permission
			if (!Ext.isDefined(permissionLockdown[namespace])) {
				return ALLOWED_IMPLICIT;
			}

			// if there is no lockdown rule for this right in this namespace
			// the group has the permission
			if (!Ext.isDefined(permissionLockdown[namespace][right])) {
				return ALLOWED_IMPLICIT;
			}

			// if there is a lockdown rule and it contains this group
			// the group has the permission
			if (Ext.isArray(permissionLockdown[namespace][right])) {
				if (Ext.Array.contains(permissionLockdown[namespace][right], group)) {
					return ALLOWED_EXPLICIT;
				} else if (permissionLockdown[namespace][right].length === 0) {
					return ALLOWED_IMPLICIT;
				}
			}
		}
		// anything else means this group doesn't have the permission
		return NOT_ALLOWED;
	}

	/**
	 * Checks if permission is granted or revoked by some other group.
	 *
	 * @param {string} right
	 * @param {number} type
	 * @param {number} namespace (optional)
	 * @returns {string}
	 */
	function getAffectedBy( right, type, namespace ) {
		if( type == ALLOWED_EXPLICIT ) {
			return {
				message: mw.message( 'bs-permissionmanager-affected-by-explicitlyset' ).plain(),
				isBlocked: false
			};
		}
		var groups = Object.keys( groupPermissions );
		var explicitGroupsNS = [];
		var groupsWiki = [];
		for( var i = 0; i < groups.length; i++ ) {
			var group = groups[i];
			var res = checkPermissionInNamespace( right, namespace, group );
			if( res === ALLOWED_EXPLICIT ) {
				explicitGroupsNS.push( group );
				continue;
			}
			res = checkPermission( right, group );
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


	/**
	 * Sends the complete groupPermissions and permissionLockdown with all its changes to the server.
	 * The method PermissionManager::savePermissions at the server side is responible for actually write the new settings
	 * in the right files.
	 *
	 * @param {Ext.Component=} caller a component which called this method (optional)
	 */
	function savePermissions(caller) {
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
			'savePermissions',
			{
				groupPermission: groupPermissions,
				permissionLockdown: permissionLockdown
			}
		).done(function (response) {
			//var result = Ext.JSON.decode(response.responseText);

			if (response.success === true) {
				caller.unmask();

				mw.notify( mw.msg( 'bs-permissionmanager-save-success' ), { title: mw.msg( 'bs-extjs-title-success' ) } );

				// Reset modification cache
				modifiedValues = {};
				modifiedValues[workingGroup] = {};
				// Reset modification counter
				isDirty = {};
				isDirty[workingGroup] = 0;

				// We save the current work data back to the source data so
				// that we can "reset" the grid to the current save point.
				// We also use {@see Ext.Object.merge} again, to have an
				// independent copy of the data.
				mw.config.set(
						'bsPermissionManagerGroupPermissions',
						Ext.Object.merge({}, groupPermissions));
				mw.config.set(
						'bsPermissionManagerPermissionLockdown',
						Ext.Object.merge({}, permissionLockdown));

				// For performance reasons we don't sync every single record
				// in the store, anymore but just recreate the whole dataset
				// from the current settings. This bypasses a lot of checks
				// and prevents browser freezing.
				Ext.data.StoreManager
						.lookup('bs-permissionmanager-permission-store')
						.loadRawData(buildPermissionData().permissions);
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

	/**
	 * adds or changes a template record
	 *
	 * @param {Ext.data.Model} record
	 * @param {Boolean} isNewRecord show if record is now or existing one
	 */
	function setTemplate(record, isNewRecord) {
		if (!isNewRecord) {
			var length = permissionTemplates.length;
			for (var i = 0; i < length; i++) {
				if (permissionTemplates[i].id === record.id) {
					permissionTemplates[i] = record;
					break;
				}
			}
		} else {
			permissionTemplates.push(record);
		}
	}

	/**
	 * removes a template record
	 *
	 * @param {number} id
	 */
	function deleteTemplate(id) {
		var length = permissionTemplates.length;
		for (var i = 0; i < length; i++) {
			if (permissionTemplates[i].id == id) {
				permissionTemplates.splice(i, 1);
				break;
			}
		}
	}

	/**
	 * Here we define our dynamic model. We override {@see Ext.data.Model} and
	 * add some custom business logic to handle our three possible states and
	 * transform them into binary states to enable the grid to work them as
	 * checkboxes.
	 * @override Ext.data.Model
	 * @class PermissionGridModel
	 */
	Ext.define('PermissionGridModel', {
		extend: 'Ext.data.Model',
		fields: modelFields,
		idProperty: 'right',
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
		constructor: function (data, id, raw, convertedData) {
			this.callParent(arguments);
			if (!Ext.isDefined(modifiedValues[workingGroup][id])) {
				modifiedValues[workingGroup][id] = {};
			}
			this.modified = modifiedValues[workingGroup][id];
			if (this.modified !== {}) {
				this.dirty = true;
			}
		},
		/**
		 * This is a modified version of the Ext.data.Model set method. It
		 * doesn't sets the value directly but changes the corresponding
		 * settings in groupPermissions, permissionLockdowns or
		 * permissionTemplates and runs all required checks on all the
		 * depending records and fields afterwards.
		 *
		 * @param {string} fieldName
		 * @param {(boolean|number)} newValue
		 * @param {boolean=} justCheck (optional, default:false)
		 * @returns {(*|null)}
		 */
		set: function (fieldName, newValue, justCheck) {
			var me = this,
					data = me.data,
					fields = me.fields,
					modified = me.modified,
					id = data.right,
					type = data.type,
					ruleSet = data.ruleSet,
					namespace = parseInt(fieldName.substring(8)), //fieldName = "userCan_23454" || "userCan_Wiki"
					currentValue, field, key, modifiedFieldNames, name,
					ns, namespaceId, rule, right, record, value;

			justCheck = justCheck || false;

			if (!Ext.isNumber(namespace)) { //e.g. parseInt("Wiki"), see above
				namespace = false;
			}

			// type marks different types of records with different logic
			// type == 0 means the record is a permission template
			// type > 0 means the record is an actual right which can be set and unset
			if (type) { // HERE STARTS THE LOGIC FOR RIGHTS
				me.beginEdit();
				if (namespace === false) {
					// newValue can either be a boolean or an int. If it is an int,
					// we don't need to convert it because it already represents one of
					// the triple state values. Otherwise, we convert it into a triple
					// state value.
					if (Ext.isBoolean(newValue)) {
						value = checkPermission(id);
						// A field can have the value ALLOWED_EXPLICIT, ALLOWED_IMPLICITE
						// and NOT_ALLOWED, whereof ALLOWED_EXPLICIT is the only value
						// which shows as a checked checkbox.
						if (value < ALLOWED_EXPLICIT) {
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
					setPermission(id, value);
				} else {
					// same logic as above
					if (Ext.isBoolean(newValue)) {
						value = checkPermissionInNamespace(id, namespace);
						if (value < ALLOWED_EXPLICIT) {
							value = ALLOWED_EXPLICIT;
						} else {
							value = NOT_ALLOWED;
						}
					} else {
						value = newValue;
					}
					setPermissionInNamespace(id, namespace, value);
				}

				// The following code checks if the value "userCan_Wiki" field
				// changed since the last commit. If so, we keep track of that
				// not just in this record itself but also in the data manager
				// so that we can restore this informations even after group
				// changes which would otherwise destroy this data.
				name = 'userCan_Wiki';
				value = checkPermission(id);
				if (fields && (field = fields.get(name)) && field.convert) {
					value = field.convert(value, me);
				}
				currentValue = data[name];
				if (!me.isEqual(currentValue, value)) {
					data[name] = value;
					(modifiedFieldNames || (modifiedFieldNames = [])).push(name);

					if (field && field.persist) {
						if (modified.hasOwnProperty(name)) {
							if (me.isEqual(modified[name], value)) {
								// The original value in me.modified equals the new value, so
								// the field is no longer modified:
								delete modified[name];
								me.dirty = false;
								isDirty[workingGroup]--;
							}
						} else {
							me.dirty = true;
							modified[name] = currentValue;
							isDirty[workingGroup]++;
						}
					}
				}
				// now we repeat the check above for every "userCan_X" field
				for (ns in namespaces) {
					if (!namespaces.hasOwnProperty(ns)) {
						continue;
					}
					namespaceId = namespaces[ns].id;
					name = 'userCan_' + namespaceId;
					value = checkPermissionInNamespace(id, namespaceId);
					if (fields && (field = fields.get(name)) && field.convert) {
						value = field.convert(value, me);
					}
					currentValue = data[name];
					if (!me.isEqual(currentValue, value)) {
						data[name] = value;
						(modifiedFieldNames || (modifiedFieldNames = [])).push(name);

						if (field && field.persist) {
							if (modified.hasOwnProperty(name)) {
								if (me.isEqual(modified[name], value)) {
									// The original value in me.modified equals the new value, so
									// the field is no longer modified:
									delete modified[name];
									me.dirty = false;
									isDirty[workingGroup]--;
								}
							} else {
								me.dirty = true;
								modified[name] = currentValue;
								isDirty[workingGroup]++;
							}
						}
					}
				}
				// We might have removed the last modified field, so check to
				// see if there are any modified fields remaining and correct
				// me.dirty:
				me.dirty = false;
				for (key in modified) {
					if (modified.hasOwnProperty(key)) {
						me.dirty = true;
						break;
					}
				}

				me.endEdit();

				// At last we check all existing permission templates if their
				// ruleSet contains the right of this record. If so, then we
				// tell the template, that we changed and that it have to recheck
				// its value. (we use the third parameter "justCheck" of the set
				// method)
				me.store.query('type', 0).each(function (record) {
					var ruleSet = record.get('ruleSet');
					if (Ext.Array.contains(ruleSet, id)) {
						record.set(fieldName, true, true);
					}
				}, me);
			} else { // HERE STARTS THE LOGIC FOR PERMISSION TEMPLATES
				// If the "justCheck" parameter was not set or is false, we have
				// to set all records in our ruleSet to ALLOWED_EXPLICIT in the
				// column of this record.
				if (justCheck === false) {
					for (rule in ruleSet) {
						if (!ruleSet.hasOwnProperty(rule)) {
							continue;
						}
						right = ruleSet[rule];
						record = me.store.getById(right);
						if (record) {
							record.set(fieldName, ALLOWED_EXPLICIT);
						}
					}
				}

				me.beginEdit();
				// The following code checks first, if the template is fulfilled
				// globally and then if this is a change of the value of this
				// record. Afterwards we keep track of any changes again.
				// See the description above in the code for the rights logic.
				name = 'userCan_Wiki';
				value = checkTemplate(ruleSet);
				if (fields && (field = fields.get(name)) && field.convert) {
					value = field.convert(value, me);
				}
				currentValue = data[name];
				if (!me.isEqual(currentValue, value)) {
					data[name] = value;
					(modifiedFieldNames || (modifiedFieldNames = [])).push(name);

					if (field && field.persist) {
						if (modified.hasOwnProperty(name)) {
							if (me.isEqual(modified[name], value)) {
								// The original value in me.modified equals the new value, so
								// the field is no longer modified:
								delete modified[name];
								me.dirty = false;
							}
						} else {
							me.dirty = true;
							modified[name] = currentValue;
						}
					}
				}
				// here we do the same than above for all the "userCan_X" fields
				// again
				for (ns in namespaces) {
					if (!namespaces.hasOwnProperty(ns)) {
						continue;
					}
					namespaceId = namespaces[ns].id;
					name = 'userCan_' + namespaceId;
					value = checkTemplateInNamespace(ruleSet, namespaceId);
					if (fields && (field = fields.get(name)) && field.convert) {
						value = field.convert(value, me);
					}
					currentValue = data[name];
					if (!me.isEqual(currentValue, value)) {
						data[name] = value;
						(modifiedFieldNames || (modifiedFieldNames = [])).push(name);

						if (field && field.persist) {
							if (modified.hasOwnProperty(name)) {
								if (me.isEqual(modified[name], value)) {
									// The original value in me.modified equals the new value, so
									// the field is no longer modified:
									delete modified[name];
									me.dirty = false;
								}
							} else {
								me.dirty = true;
								modified[name] = currentValue;
							}
						}
					}
				}

				me.endEdit();
			}

			return modifiedFieldNames || null;
		}
	});

	Ext.define('BS.PermissionManager', {
		statics: {
			NOT_ALLOWED: NOT_ALLOWED,
			ALLOWED_IMPLICIT: ALLOWED_IMPLICIT,
			ALLOWED_EXPLICIT: ALLOWED_EXPLICIT
		}
	});

	Ext.define('BS.PermissionManager.data.Manager', function () {
		return {
			getPermissionTemplates: function () {
				return permissionTemplates;
			},
			getTemplateRights: function () {
				return templateRights;
			},
			getWorkingGroup: function () {
				return workingGroup;
			},
			setWorkingGroup: function (group) {
				workingGroup = group;
				if (!Ext.isDefined(modifiedValues[group])) {
					modifiedValues[group] = {};
				}
				if (!Ext.isDefined(isDirty[group])) {
					isDirty[group] = 0;
				}
			},
			setGroupPermissions: function ( permissions ) {
				groupPermissions = permissions;
			},
			setTemplate: setTemplate,
			deleteTemplate: deleteTemplate,
			buildTemplateForGrid: buildTemplateForGrid,
			buildPermissionData: buildPermissionData,
			checkTemplate: checkTemplate,
			checkTemplateInNamespace: checkTemplateInNamespace,
			checkPermission: checkPermission,
			checkPermissionInNamespace: checkPermissionInNamespace,
			getColumns: function () {
				return columns;
			},
			setPermission: setPermission,
			setPermissionInNamespace: setPermissionInNamespace,
			savePermissions: savePermissions,
			/**
			 * Resets all changes made in this session
			 */
			resetAllSettings: function () {
				// get the original settings and save them as the new working set
				// we use Ext.Object.merge() here because we need copies and no references
				groupPermissions = Ext.Object.merge({}, mw.config.get('bsPermissionManagerGroupPermissions', {}));
				permissionLockdown = Ext.Object.merge({}, mw.config.get('bsPermissionManagerPermissionLockdown', {}));

				// remove all cached modifications
				for (var group in modifiedValues) {
					if (modifiedValues.hasOwnProperty(group)) {
						modifiedValues[group] = {};
					}
				}
			},
			/**
			 * Checks if there are unsaved changes in the grid.
			 *
			 * @returns {boolean}
			 */
			isDirty: function () {
				for (var group in isDirty) {
					if (!isDirty.hasOwnProperty(group)) {
						continue;
					}
					if (isDirty[group] > 0) {
						return true;
					}
				}
				return false;
			}
		};
	});
})();
