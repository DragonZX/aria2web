/*
* @version		$Id$
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
Ext.override(Ext.form.FormPanel, {

    beforeDestroy : function(){
        this.stopMonitoring();
        Ext.FormPanel.superclass.beforeDestroy.call(this);
        /*
         * Clear the items here to prevent them being destroyed again.
         * Don't move this behaviour to BasicForm because it can be used
         * on it's own.
         */
        this.form.items.clear();
        Ext.destroy(this.form);
    }

});
Ext.BLANK_IMAGE_URL = 'scripts/extjs/resources/images/default/s.gif';
Ext.onReady(function(){

	  // create the Data Store
    datastore = new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({
        	 url: 'index.php',
        }),
        action: "tellActive",
        baseParams:{offset:0, num:50, action: "tellActive"  },
        // create reader that reads the File records
        reader: new Ext.data.JsonReader({
            root: "items",
            totalProperty: "totalCount"
        }, Ext.data.Record.create([
            {name: "gid"},
            {name: "status"},
            {name: "totalLength"},
            {name: "completedLength"},
            {name: "uploadLength"},
            {name: "bitfield"},
            {name: "downloadSpeed"},
            {name: "uploadSpeed"},
            {name: "estimatedTime"},
            {name: "infohash"},
            {name: "numSeeders"},
            {name: "pieceLength"},
            {name: "numPieces"},
            {name: "connections"},
            {name: "errorCode"},
            {name: "followedBy"},
            {name: "belongsTo"}
        ]))
    });
   

    var gridtb = new Ext.Toolbar([
           	{
               	xtype: "tbbutton",
           		id: 'tb_home',
           		icon: 'images/_add.png',
           		text: 'Add new Download',
           		tooltip: 'Allows you to add a new download to the queue',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'add'); }
           	},
           	{
               	xtype: "tbbutton",
           		id: 'tb_remove',
           		icon: 'images/_remove.png',
           		text: 'Remove',
           		disabled: true,
           		tooltip: 'Allows you to remove a download from the queue',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'remove'); }
           	},
              {
           		xtype: "tbbutton",
           		id: 'tb_reload',
                icon: 'images/_reload.png',
                text: 'Refresh',
              	tooltip: 'Refreshed the download list',
                cls:'x-btn-text-icon',
                handler: function() { datastore.load(); }
              },'-',
              {
                  text: 'Show Active',
                  enableToggle: true,
                  id: 'btn_showactive',
                  toggleHandler: onItemToggle,
                  pressed: true
              },
              {
                  text: 'Show Finished/Stopped',
                  enableToggle: true,
                  id: 'btn_showstopped',
                  toggleHandler: onItemToggle,
                  pressed: false
              },
              '-',
              {
                  text: 'Auto-Refresh',
                  enableToggle: true,
                  id: 'btn_autorefresh',
                  pressed: true
              },
              
              '-',
              {
           		xtype: "tbbutton",
           		id: 'tb_globaloptions',
                icon: 'images/_options.png',
                text: 'Global Options',
              	tooltip: 'Allows you to change the global options for all downloads',
                cls:'x-btn-text-icon',
                handler: function() { openActionDialog(this, 'globalOptions') }
              },/* '-',
				{	// LOGOUT
					xtype: "tbbutton",
					id: 'tb_logout',
					icon: 'images/_logout.png',
					tooltip: 'Logout',
					cls:'x-btn-icon',
					handler: function() { document.location.href='index.php?logout'; }
				},*/
           ]);
    function onItemToggle(item, pressed){
    	
    	datastore.baseParams.action = item.id == 'btn_showstopped' ? "tellStopped" : "tellActive";
    	
    	if( item.id == 'btn_showstopped' && pressed == true ) {
    		Ext.getCmp("btn_showactive").toggle( false, true );
    	} else if(  item.id == 'btn_showactive' && pressed == true) {
    		Ext.getCmp("btn_showstopped").toggle( false, true );
    	}
    	datastore.load();
    }
    // add a paging toolbar to the grid's footer
    var gridbb = new Ext.PagingToolbar({
        store: datastore,
        pageSize: 50,
        displayInfo: true,
		items: ['-',' ',' ',' ',' ',' ',
			new Ext.ux.StatusBar({
			    defaultText: 'Done',
		        text: 'Ready',
		        iconCls: 'x-status-valid',
			    id: 'statusPanel'
			})]
    });
    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store
    var cm = new Ext.grid.ColumnModel([{
           id: 'gridcm', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
           header: "GID",
           dataIndex: 'gid',
           align:'right',
           width: 50,
           //renderer: renderFileName,
           css: 'white-space:normal;'
         },{
           header: "TotalLength",
           dataIndex: 'totalLength',
           align:'right',
           width: 120
        },{
            header: "CompletedLength",
            dataIndex: 'completedLength',
            align:'right',
            width: 120
        },{
           header: "Download Speed",
           dataIndex: 'downloadSpeed',
           align:'right',
           width: 120
        },{
            header: "Upload Speed",
            dataIndex: 'uploadSpeed',
            align:'right',
            hidden: true,
            hideable: false,
            width: 100
         },{
             header: "estimated Time",
             dataIndex: 'estimatedTime',
             align:'right',
             width: 150
          },{
             header: "# Connections",
             dataIndex: 'connections',
             align:'right',
             width: 100
          },{
              header: "Status",
              dataIndex: 'status',
              resizable: true,
              width: 200,
              //align: 'right'
           },{
              header: "File name",
              dataIndex: 'bitfield',
              resizable: true,
              width: 400
           },  
        {dataIndex: 'numPieces', hidden: true, hideable: false },
        //{dataIndex: 'bitfield', hidden: true, hideable: false },
        {dataIndex: 'infohash', hidden: true, hideable: false },
        {dataIndex: 'numSeeders', hidden: true, hideable: false },
        {dataIndex: 'pieceLength', hidden: true, hideable: false },
        {dataIndex: 'errorCode', hidden: true, hideable: false },
        {dataIndex: 'followedBy', hidden: true, hideable: false },
        {dataIndex: 'belongsTo', hidden: true, hideable: false }
        ]);

    // by default columns are sortable
    //cm.defaultSortable = true;

    // The Quicktips are used for the toolbar and Tree mouseover tooltips!
	Ext.QuickTips.init();

	  function rowContextMenu(grid, rowIndex, e, f) {
	    	if( typeof e == 'object') {
	    		e.preventDefault();
	    	} else {
	    		e = f;
	    	}
	    	gsm = Ext.getCmp("fileGrid").getSelectionModel();
	    	gsm.clickedRow = rowIndex;
	    	var selections = gsm.getSelections();
			showingFinished = Ext.getCmp("btn_showstopped").pressed;
			
			if( !showingFinished) {
				gridCtxMenu.items.get('gc_download').disable();
			} else {
	    		gridCtxMenu.items.get('gc_edit').disable();
	    		gridCtxMenu.items.get('gc_delete').disable();				
			}
	    	if( selections.length > 1 ) {
	    		gridCtxMenu.items.get('gc_edit').disable();
	    	} else if(selections.length == 1) {				
	    		//gridCtxMenu.items.get('gc_edit').enable();
	    		//gridCtxMenu.items.get('gc_delete').enable();
	    	}
			if( aria2web_mode != "local" ) {
				gridCtxMenu.items.get('gc_download').disable();
			}
			gridCtxMenu.show(e.getTarget(), 'tr-br?' );

	    }
	    gridCtxMenu = new Ext.menu.Menu({
	    	id:'gridCtxMenu',
	    
	        items: [{
	    		id: 'gc_edit',
	    		icon: 'images/_edit.png',
	    		text: 'Change Options',
	    		handler: function() { openActionDialog(this, 'getOption'); }
	    	},
	    	{
	    		id: 'gc_delete',
	    		icon: 'images/_remove.png',
	    		text: 'Remove File',
	    		handler: function() { openActionDialog(this, 'remove'); }
	    	},
	    	{
	    		id: 'gc_download',
	    		icon: 'images/_down.png',
	    		text: 'Download File',
	    		handler: function() { openActionDialog(this,'download'); }
	    	},
	    	'-',
			{
				id: 'cancel',
	    		icon: 'images/_cancel.png',
	    		text: 'Cancel',
	    		handler: function() { gridCtxMenu.hide(); }
	    	}
	    	]
	    });
	    
	// define a template to use for the detail view
	var fileTplMarkup = [
		'GID: {gid}<br/>',
		'Downloaded: {completedLength}<br/>',
		'Total Size: {totalLength}<br/>',
		'Files: <ul><tpl for="items">',
		'<li><strong>{path}</strong></li>',
		'</tpl></ul>'
	];
	var fileTpl = new Ext.XTemplate(fileTplMarkup);

	
    // create the grid
    var viewport = new Ext.Viewport({	
	    defaults: {
	        split: true,
	    	frame: true
	    },
       layout: "border",
       renderTo:'downloads-grid',
       items: [{
    	   region: 'north',
    	   height: 35,
    	   contentEl: "header"
       }, {
    	   xtype: 'grid',
    	   id: 'fileGrid',
    	   title: "Download List",
	        store: datastore,
	        colModel: cm,
	        selModel: new Ext.grid.RowSelectionModel({
        		listeners: {
					'rowselect': { fn: handleRowClick },
        			'selectionchange': { fn: handleRowClick }
    			}
    		  }),
    		 listeners: { 
    	   		'rowcontextmenu': { fn: rowContextMenu }
       		},
	       	tbar: gridtb,
	       	bbar: gridbb,
	        width:'80%',
			split: true,
			region: 'center'
       },/*{
			id: 'detailPanel',
			title: "Details",
	        width:'20%',
			region: 'east',
			bodyStyle: {
				background: '#ffffff',
				padding: '7px'
			}
       //,html: 'Please select a file to see additional details.'
		},*/{
			region: "south",
			contentEl: "bottom" 
		}]
                                                                                                             
    });
    function handleRowClick(sm, rowIndex, r) {
    	
    	var selections = sm.getSelections();
    	tb = Ext.getCmp("fileGrid").getTopToolbar();		
    	if( selections.length > 1 ) {
    		tb.items.get('tb_remove').enable();
    	} else if(selections.length == 1) {
    		tb.items.get('tb_remove').enable();
    		try {
    			var gid = r.data.gid;
    			
    			Ext.Ajax.request({
    				   url: 'index.php',
    				   callback: function(oElement, bSuccess, oResponse) {
							if( !bSuccess ) {
								msgbox = Ext.Msg.alert( "Ajax communication failure!");
								msgbox.setIcon( Ext.MessageBox.ERROR );
							}
							if( oResponse && oResponse.responseText ) {
								
								//Ext.Msg.alert("Debug", oResponse.responseText );
								try{ json = Ext.decode( oResponse.responseText );
									if( json.error && typeof json.error != 'xml' ) {
										Ext.Msg.alert( "Error", json.error );
										return false;
									}
								} catch(e) {
									msgbox = Ext.Msg.alert( "Error", "JSON Decode Error: " + e.message );
									msgbox.setIcon( Ext.MessageBox.ERROR );
									return false; 
								}

				    			//var detailPanel = Ext.getCmp('detailPanel');
					    		//fileTpl.overwrite(detailPanel.body, json);
							}
    					},
    				   params: { 
    						gid: gid,
    						action: "getFiles"
    					}
    				});

    		} catch(e) { }
    	} else {
			tb.items.get('tb_remove').disable();
    	}
    	return true;
    }
    
    firstRun = true;
    
    Ext.TaskMgr.start({
        run: function() {  
    		if( !firstRun 
    				&& Ext.getCmp("fileGrid").getStore().getTotalCount() > 0 
    				&& !Ext.getCmp("btn_showstopped").pressed
    				&&  Ext.getCmp("btn_autorefresh").pressed ) {
    			datastore.load(); 
    		} else if( firstRun ) {
    			datastore.load();firstRun = false;
    		}
    	},
        interval: 10000
    });
       
});
