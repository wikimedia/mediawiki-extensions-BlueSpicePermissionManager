Ext.Loader.setPath(
	'BS.panel.Maximizable',
		mw.config.get( "wgScriptPath" ) + '/extensions/BlueSpicePermissionManager' +
	'/resources/BS.panel/Maximizable.js'
);

Ext.onReady( function(){
	Ext.create( 'BS.PermissionManager.panel.Manager', {
		renderTo: 'panelPermissionManager'
	} );
} );