Ext.define('BS.panel.Maximizable', {
	extend: 'MWExt.Dialog',
	draggable: false,
	shadow: false,
	closable: false,
	border: false,
	resizable: false,
	oldX: 0,
	oldY: 0,
	bsRenderToTarget: null,
	style: 'padding: 0; border-width: 0;',

	constructor: function( cfg ) {
		this.bsRenderToTarget = Ext.get(cfg.renderTo);

		cfg.height = cfg.height || this.bsRenderToTarget.getHeight();
		cfg.width = cfg.width || this.bsRenderToTarget.getWidth();

		cfg.renderTo = Ext.getBody();

		this.callParent( arguments );
	},

	initComponent: function() {
		this.maximizeTool = new Ext.panel.Tool({
			type: 'maximize'
		});
		this.maximizeTool.on('click', this.onMaximizeToolClick, this);

		this.minimizeTool = new Ext.panel.Tool({
			type: 'minimize'
		});
		this.minimizeTool.on('click', this.onMinimizeToolClick, this);
		this.minimizeTool.setVisible(false);

		//Because of no-border design, "tools" section
		//is no longer visible
		this.tbar.push( this.maximizeTool );
		this.tbar.push( this.minimizeTool );

		this.on('afterrender', this.onAfterRender, this);
		Ext.get(window).on('resize', this.onWindowResize, this);

		this.callParent(arguments);
	},
	onAfterRender: function(sender, eOpts) {
		this.showAt(this.bsRenderToTarget.getXY());
	},
	onMaximizeToolClick: function() {
		$('html').addClass('bs-maximizable-panel-fullscreen');
		this.bsRenderToTarget.addCls('fullscreen');
		this.updateSize();
		this.maximizeTool.setVisible(false);
		this.minimizeTool.setVisible(true);
		this.showAt(this.bsRenderToTarget.getXY());

	},
	onMinimizeToolClick: function() {
		$('html').removeClass('bs-maximizable-panel-fullscreen');
		this.bsRenderToTarget.removeCls('fullscreen');
		this.updateSize();
		this.maximizeTool.setVisible(true);
		this.minimizeTool.setVisible(false);
		this.showAt(this.bsRenderToTarget.getXY());
	},
	firstCall: true,
	showAt: function(x, y) {
		if (this.firstCall) {
			this.oldX = x;
			this.oldY = y;
			this.firstCall = false;
		}
		this.callParent(arguments);
	},
	updateSize: function() {
		this.setHeight(this.bsRenderToTarget.getHeight());
		this.setWidth(this.bsRenderToTarget.getWidth());
	},
	onWindowResize: function( event, target, eOpts ) {
		this.setX( this.bsRenderToTarget.getX() );
		this.setY( this.bsRenderToTarget.getY() );
		this.updateSize();
	}
});