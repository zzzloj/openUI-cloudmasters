/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.uagents.js - User agent mapping			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _pagemanager = window.IPBACP;
_pagemanager.prototype.pagemanager = {
	
	popups: [],
	
	/*
	 * Init function
	 */
	init: function()
	{
		Debug.write("Initializing acp.pagemanager.js");
		
		document.observe("dom:loaded", function(){
			ipb.delegate.register('.i_new_folder', acp.pagemanager.addFolder);
			ipb.delegate.register('.delete_folder', acp.pagemanager.deleteFolder);
			ipb.delegate.register('.empty_folder', acp.pagemanager.emptyFolder);
			ipb.delegate.register('.parent', acp.pagemanager.folderToggle);
			
			// Set up live filter
			acp.pagemanager.filter.init();
		});
	},
	
	filter: {
		
		defaultText: '',
		lastText: '',
		timer: null,
		cache: [],
		pageSubtitle: '',
		
		init: function()
		{
			acp.pagemanager.filter.defaultText = $F('page_search_box');
			acp.pagemanager.filter.pageSubtitle = $('page_subtitle').innerHTML;
			
			$( 'page_search_box' ).writeAttribute('autocomplete', 'off');
			
			// Set up events for live filter
			$('page_search_box').observe('focus', acp.pagemanager.filter.timerEventFocus );
			$('page_search_box').observe('blur', acp.pagemanager.filter.timerEventBlur );
			$('cancel_filter').observe('click', acp.pagemanager.filter.endFiltering );
		},
		
		timerEventFocus: function( e )
		{
			acp.pagemanager.filter.timer = acp.pagemanager.filter.eventFocus.delay( 0.5, e );
			
			$('page_search').addClassName('active');
			$('page_search_box').value = '';
		},
		
		eventFocus: function( e )
		{
			// Keep the loop going
			acp.pagemanager.filter.timer = acp.pagemanager.filter.eventFocus.delay( 0.5, e );
			
			var text = acp.pagemanager.filter.getCurrentText();
			if( text == acp.pagemanager.filter.lastText ){ return; }
			if( text == '' ){ acp.pagemanager.filter.cancel(); return; }
			
			acp.pagemanager.filter.lastText = text;
			
			json = acp.pagemanager.filter.cacheRead( text );
			
			if( !json )
			{
				// Get it with ajax
				var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=list&do=search";

				new Ajax.Request(	url,
									{
										method: 'post',
										parameters: {
											secure_key: ipb.vars['md5_hash'],
											term: text
										},
										evalJSON: 'force',
										onSuccess: function(t)
										{
											Debug.write( t.responseJSON );
											
											if( Object.isUndefined( t.responseJSON ) )
											{
												alert( ipb.lang['js__nosearchperm'] );
												return;
											}
											
											if( t.responseJSON['error'] )
											{
												Debug.write( t.responseJSON['error'] );
												return;
											}
											
											// Seems to be OK!
											acp.pagemanager.filter.cacheWrite( text, t.responseJSON );
											acp.pagemanager.filter.updateAndShow( t.responseJSON );
										
										}
									});
			}
			else
			{
				acp.pagemanager.filter.updateAndShow( json );
			}
		},
		
		updateAndShow: function( json )
		{
			var output = '';
			
			if( !json.length )
			{
				output = ipb.templates['ccs_filter_none'].evaluate();
			}
			else
			{
				for( i=0;i<json.length;i++ )
				{
					if( json[i]['type'] == 'folder' ){ continue; }
					output += ipb.templates['ccs_filter_row'].evaluate( { id: json[i]['page_id'], type: ( json[i]['page_content_type'] != '' ) ? json[i]['page_content_type'] : 'file', name: json[i]['page_seo_name_hl'], path: json[i]['page_folder_hl'] + '/', title: json[i]['page_name_hl'] } );					
				}
			}
			
			if( !acp.pagemanager.filter.filtering )
			{
				$('f_wrap_root').hide();
				$('filter_results').show().update( output );
				$('page_subtitle').update( ipb.lang['filter_matches'] );
				
				acp.pagemanager.filter.filtering = true;
			}
			else
			{
				$('filter_results').show().update( output );
			}
			
			/* Cannot multi-moderate filtered pages */
			$('manage-opts').hide();
		},
		
		cancel: function()
		{
			$('filter_results').hide();
			$('f_wrap_root').show();
			
			acp.pagemanager.filter.filtering = false;
			
			$('manage-opts').show();			
		},
		
		endFiltering: function( e )
		{
			acp.pagemanager.filter.cancel();
			$('page_search').removeClassName('active');
			$('page_search_box').value = acp.pagemanager.filter.defaultText;
			$('filter_results').update();
			$('page_subtitle').update( acp.pagemanager.filter.pageSubtitle );
		},
			
		cacheWrite: function( text, json )
		{
			acp.pagemanager.filter.cache[ text ] = json;
		},
		
		cacheRead: function( text )
		{
			if( !Object.isUndefined( acp.pagemanager.filter.cache[ text ] ) ){
				Debug.write("Results from cache");
				return acp.pagemanager.filter.cache[ text ];
			}
			
			return false;
		},
		
		timerEventBlur: function( e )
		{
			if( $F('page_search_box').strip() == '' )
			{
				acp.pagemanager.filter.cancel();
				$('page_search').removeClassName('active');
				$('page_search_box').value = acp.pagemanager.filter.defaultText;
				$('page_subtitle').update( acp.pagemanager.filter.pageSubtitle );
			}
			
			clearTimeout( acp.pagemanager.filter.timer );
		},
		
		getCurrentText: function()
		{
			return $F('page_search_box').strip();
		}	
	},
	
	emptyFolder: function( e, elem )
	{
		Event.stop(e);
		var id = $( elem ).id.replace('empty_folder_', '');
		var path = $( elem ).readAttribute('rel');
		
		if( !id || Object.isUndefined( path ) ){ return; }
		
		var contents = ipb.templates['ccs_empty_folder'].evaluate({id: id});
		var afterInit = function( popup ){
			if( $('empty_' + id) )
			{
				$('empty_' + id).observe('click', function(e){
					// Hide popup
					popup.hide();
					// Send request to kill this folder
					var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=list&do=emptyFolder&dir=" + path;

					new Ajax.Request(	url,
										{
											method: 'post',
											parameters: {
												secure_key: ipb.vars['md5_hash']
											},
											onSuccess: function(t)
											{
												if( t.responseText != 'ok' )
												{
													alert( ipb.lang['js__folderempty'] );
													return;
												}
												else
												{
													if( $('f_wrap_' + id) ){
														new Effect.BlindUp( $('f_wrap_' + id), { duration: 0.4 } );
													}
													
													$('f_' + id).removeClassName('open');
												}
											}
										});
				});
			}
			
			if( $('cancel_empty_' + id) )
			{
				$('cancel_empty_' + id).observe('click', function(e){
					popup.hide();
				});
			}
		};
		
		var afterHide = function( popup ){
			var _popup = popup.getObj();
			$( _popup ).remove();
		};
		
		acp.pagemanager.popups['empty_' + id] = new ipb.Popup( 'empty_' + id, {
																	type: 'pane',
																	initial: contents,
																	hideAtStart: false,
																	modal: true
																},
																{
																	'afterInit': afterInit,
																	'afterHide': afterHide
																});
													
	},
	
	deleteFolder: function( e, elem )
	{
		Event.stop(e);
		var id = $( elem ).id.replace('delete_folder_', '');
		var path = $( elem ).readAttribute('rel');
		
		if( !id || Object.isUndefined( path ) ){ return; }
		
		var contents = ipb.templates['ccs_delete_folder'].evaluate({id: id});
		var afterInit = function( popup ){
			if( $('del_' + id) )
			{
				$('del_' + id).observe('click', function(e){
					// Hide popup
					popup.hide();
					// Send request to kill this folder
					var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=list&do=deleteFolder&dir=" + path;

					new Ajax.Request(	url,
										{
											method: 'post',
											parameters: {
												secure_key: ipb.vars['md5_hash']
											},
											onSuccess: function(t)
											{
												if( t.responseText != 'ok' )
												{
													alert( ipb.lang['js__deletefolder'] );
													return;
												}
												else
												{
													// Folder deleted, hide it
													if( $('f_' + id) ){
														new Effect.BlindUp( $('f_' + id), { duration: 0.4 } );
													}
													
													if( $('f_wrap_' + id ) ){
														new Effect.BlindUp( $('f_wrap_' + id), { duration: 0.4 } );
													}
													
													// Have to manually delete the menus here...
													if( $('menu_folder' + id + '_menucontent') ){
														$('menu_folder' + id + '_menucontent').remove();
													}
												}
											}
										});
				});
			}
			
			if( $('cancel_del_' + id) )
			{
				$('cancel_del_' + id).observe('click', function(e){
					popup.hide();
				});
			}
		};
		
		var afterHide = function( popup ){
			var _popup = popup.getObj();
			$( _popup ).remove();
		};
		
		acp.pagemanager.popups['del_' + id] = new ipb.Popup( 'del_' + id, {
																	type: 'pane',
																	initial: contents,
																	hideAtStart: false,
																	modal: true
																},
																{
																	'afterInit': afterInit,
																	'afterHide': afterHide
																});
	},
	
	addFolder: function( e, elem )
	{
		Event.stop(e);
		
		var id = ( $( Event.element(e) ).id == 'root_add_folder' || $( Event.element(e) ).id == 'rootmain_add_folder' ) ? 'root' : $( elem ).up('.parent').id.replace('f_', '');
		var path = ( id == 'root' ) ? $( Event.element(e) ).readAttribute('rel').replace( /\//, '%2F' ) : $('f_' + id).down('.path').readAttribute('rel');
		
		var afterInit = function( popup )
		{
			Debug.write( id );
			
			if( $('create_folder_' + id) )
			{
				$('create_folder_' + id).observe('click', function(e)
				{
					// Check for value
					var newName = $F('folder_name_' + id).strip();
					
					if( newName == '' ){
						alert( ipb.lang['js__foldername'] );
						return;
					}
					
					var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=list&do=addFolder&parent=" + path;
					
					new Ajax.Request(	url,
										{
											method: 'post',
											parameters: {
												secure_key: ipb.vars['md5_hash'],
												folder_name: newName
											},
											onSuccess: function(t)
											{
												if( t.responseText == 'alreadyexists' )
												{
													alert( ipb.lang['js__folderexists'] );
													return;
												}
												else
												{
													if( id == 'root' )
													{
														if( $('f_d41d8cd98f00b204e9800998ecf8427e') )
														{
															$('f_d41d8cd98f00b204e9800998ecf8427e').insert( { after: t.responseText } );
														}
														else
														{
															$('f_wrap_root').insert( { top: t.responseText } );
														}
														
														acp.pagemanager._fixMenus( id );
													}
													else
													{
														if( $('f_wrap_' + id) && $('f_wrap_' + id).visible() ){
															$('f_wrap_' + id).insert( { top: t.responseText } );
														} else if( $('f_wrap_' + id ) && !$('f_wrap_' + id).visible() ){
															$('f_wrap_' + id).insert( { top: t.responseText } );
															acp.pagemanager._doToggle( id, path );
														} else {
															acp.pagemanager._doToggle( id, path );
														}
													}
													
													popup.hide();
												}
											}
										}
									);
				});
			}
		};
		
		var afterHide = function( popup ){
			var _popup = popup.getObj();
			$( _popup ).remove();
		};
		
		// Make a new popup
		acp.pagemanager.popups['f_' + id] = new ipb.Popup( 'f_' + id, {
																type: 'modal',
																initial: ipb.templates['ccs_new_folder'].evaluate({ id: id }),
																w: '450px',
																stem: false,
																hideAtStart: false
															},
															{
																'afterInit': afterInit,
																'afterHide': afterHide
															});
	},
	
	folderToggle: function( e, elem )
	{
		Debug.write( e.element() );
		
		if( !e.element().hasClassName('parent') ){ return; }
		
		var folderID = $( elem ).id.replace('f_', '');
		var link = $( elem ).down('.path');
		var path = ( !Object.isUndefined( link ) ) ? link.readAttribute('rel') : null;
		
		if( !Object.isUndefined( path ) )
		{
			acp.pagemanager._doToggle( folderID, path );
		}
	},
	
	_doToggle: function( folderID, path )
	{
		Debug.write( "Path is " + path + ", ID is " + folderID );
		
		// Does this folder exist?
		if( $('f_wrap_' + folderID) && $('f_wrap_' + folderID).readAttribute('needsUpdate') != 'yes' )
		{
			Effect.toggle( $('f_wrap_' + folderID), 'slide', { duration: 0.3 } );

			if( $('f_' + folderID).hasClassName('open') )
			{
				$('f_' + folderID).removeClassName('open');
			}
			else
			{
				$('f_' + folderID).addClassName('open');
			}
		}
		else
		{
			// Get it with ajax
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=list&dir=" + path;

			new Ajax.Request(	url,
								{
									method: 'post',
									parameters: {
										secure_key: ipb.vars['md5_hash']
									},
									//evalJS: 'force',
									onSuccess: function(t)
									{
										/* Logged out? */
										if ( t.responseText.match( "__session__expired__log__out__" ) )
										{
											alert( ipb.lang['session_timed_out'] );
											
											return false;
										}
										
										Debug.write( t.responseText );

										if( !$('f_wrap_' + id) )
										{
											acp.pagemanager._buildFolderWrap( folderID, t.responseText );
										}
										else
										{
											$('f_wrap_' + id).update( t.responseText );
											$('f_wrap_' + id).writeAttribute('needsUpdate', 'no');
										}
									}
								}
							);
		}
	},
	
	_buildFolderWrap: function( folderID, content )
	{
		var html = ipb.templates['ccs_folder_wrap'].evaluate( { id: folderID, content: content } );
		
		// Insert AFTER the folder row
		$('f_' + folderID).insert( { after: html } );
		
		acp.pagemanager._fixMenus( folderID );
		
		Effect.toggle( $('f_wrap_' + folderID), 'slide', { duration: 0.3 } );
		$('f_' + folderID).addClassName('open');
	},
	
	_fixMenus: function( folderID )
	{
		// We need to build menus
		var menus = $('f_wrap_' + folderID).select('.ipbmenu');
		
		if( menus.length )
		{
			for( i=0;i<menus.length;i++ )
			{
				var menuid = $( menus[i] ).id;
				var menucontent = $( menuid + '_menucontent' );
				
				if( $( menucontent ) ){
					new ipb.Menu( $(menuid), $(menucontent) );
				}
			}
		}
	}
};

acp.pagemanager.init();