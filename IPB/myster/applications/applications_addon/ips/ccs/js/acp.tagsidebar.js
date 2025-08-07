/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.tagsidebar.js - Template tag JS			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _sidebar = window.IPBACP;
_sidebar.prototype.ipcsidebar = {

	databaseLoaded: false,
	isArticlesTemplate: 0,
	cache: {},

	itemWrap: new Template("<ul data-path=\"#{path}\">#{content}</ul>"),
	goBackItem: new Template("<li class='tag_row media_folder go_back' data-path='#{path}'>Back to #{name}</li>"),
	folderItem: new Template("<li class='tag_row media_folder' data-path='#{path}'><h5><img src='" + ipb.vars['image_acp_url'] + "icons/folder.png' />  #{name}</h5></li>"),
	fileItem: new Template("<li class='ipsControlRow tag_row media_item' data-tag='{parse ipcmedia=\"#{tag}\"}' data-path='#{path}' data-fileid='#{fileid}'><ul class='ipsControlStrip'><li class='i_add'><a href='#' title='" + ipb.lang['insert_tag'] + "'>" + ipb.lang['insert_tag'] + "</a></li></ul><img src='#{icon}' /> #{name}</li>"),

	prevDir: '',
	curDir: '',

	/*
	 * Init function
	 */
	init: function()
	{
		Debug.write("Initializing acp.ipcsidebar.js");
		
		document.observe("dom:loaded", function(){
			ipb.delegate.register('.tag_toggle', acp.ipcsidebar.expandHelp);
			ipb.delegate.register('.insert_tag', acp.ipcsidebar.insertTag);

			ipb.delegate.register('#tag_tabbar li', acp.ipcsidebar.toggleTagPane);
			ipb.delegate.register('.database_link', acp.ipcsidebar.fetchDatabase);

			ipb.delegate.register('.media_folder', acp.ipcsidebar.loadMediaFolder);
			ipb.delegate.register('.media_item a', acp.ipcsidebar.insertTag);
		});

	},

	/**	
	 * Expands a help row
	 */
	expandHelp: function(e, elem)
	{
		Event.stop(e);

		var row		= $(elem).up('.tag_row');
		var help	= $(row).down('div.tag_help');
		var pre		= $(help).down('pre');
		var child	= $(row).down('ul:not([class~=ipsControlStrip])');

		if( $(row).hasClassName('open') )
		{
			$(row).removeClassName('open').addClassName('closed');
			
			if( !$(help).hasClassName('always_open') )
			{
				new Effect.BlindUp( $(help), { duration: 0.2 } );
			}
			
			if( child )
			{
				new Effect.BlindUp( $(child), { duration: 0.2 } );
			}
		}
		else
		{
			$(row).removeClassName('closed').addClassName('open');

			try {
				if( pre )
				{
					$(pre).update( prettyPrintOne( pre.innerHTML ) );
				}
				
				if( child )
				{
					new Effect.BlindDown( $(child), { duration: 0.2 } );
				}
			} 
			catch(err){ Debug.write(err); }

			if( !$(help).hasClassName('always_open') )
			{
				new Effect.BlindDown( $(help), { duration: 0.2 } );
			}
		}
	},

	/**	
	 * Toggles between tags/databases/media
	 */
	toggleTagPane: function(e, elem)
	{	
		Event.stop(e);

		var tab = $(elem).id.replace('tab_', '');

		$$('#templateTags_inner > div').invoke('hide');
		$$('#tags_tabs > li').invoke('removeClassName', 'active');

		$('tab_' + tab + '_pane').show();
		$('tab_' + tab ).addClassName('active');

		if( elem == $('tab_databases') && !acp.ipcsidebar.databaseLoaded )
		{
			acp.ipcsidebar.loadDatabases();
		}

		if( elem == $('tab_media') && !acp.ipcsidebar.mediaLoaded )
		{
			acp.ipcsidebar.initMedia();	
		}
	},

	initMedia: function()
	{
		acp.ipcsidebar.loadMediaFolder();
		acp.ipcsidebar.mediaLoaded = true;
	},

	loadMediaFolder: function(e, elem)
	{
		if( !elem )
		{
			var toLoad = '/';
		}
		else
		{
			var toLoad = $(elem).readAttribute('data-path');
		}

		if( !Object.isUndefined( acp.ipcsidebar.cache[ toLoad ] ) )
		{
			acp.ipcsidebar.buildMediaFolder( acp.ipcsidebar.cache[ toLoad ] );
			return;
		}

		var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=media&do=listdirall";

		new Ajax.Request( url + '&secure_key=' + ipb.vars['md5_hash'],
						  {
							method: 'post',
							parameters: {
								dir: toLoad
							},
							evalJSON: 'force',
							onSuccess: function (t)
							{
								if( !Object.isUndefined( t.responseJSON['error'] ) )
								{
									alert( t.responseJSON['error'] );
									return;
								}

								Debug.write( t.responseJSON );

								acp.ipcsidebar.buildListing( t.responseJSON );
							}
						  } );

	},

	buildListing: function( json )
	{
		var html = '';

		if( json['parent'] )
		{
			if( json['parent']['name'] == '' )
			{
				json['parent']['name'] = "IP.Content Media";
			}

			html += acp.ipcsidebar.goBackItem.evaluate({ path: json['parent']['path'], name: json['parent']['name'] });
		}

		if( json['folders'] )
		{
			for( var folder in json['folders'] )
			{
				if( json['folders'][folder]['full_path'] )
				{
					html += acp.ipcsidebar.folderItem.evaluate({ name: folder, path: json['folders'][folder]['full_path'] });
				}
			}
		}
		
		if( json['files'] )
		{
			for( var file in json['files'] )
			{
				if( json['files'][file]['tag'] )
				{
					html += acp.ipcsidebar.fileItem.evaluate({ 
						name: json['files'][file]['name'],
						fileid: file,
						tag: json['files'][file]['tag'],
						icon: json['files'][file]['icon']
					});
				}
			}
		}		

		$('tab_media_pane').update( acp.ipcsidebar.itemWrap.evaluate({ content: html }) );
	},

	/**	
	 * fetches a database
	 */
	fetchDatabase: function(e, elem)
	{
		// Get DB id & type
		var id = $(elem).readAttribute('data-id');
		//var type = $(elem).readAttribute('data-type');

		// does the pane exist already?
		if( $('tag_database_' + id ) )
		{
			if( $('tag_database_' + id ).visible() )
			{
				$('tag_database_' + id ).hide();
				$('tab_database_overview').show();
			}
			else
			{
				$('tag_database_' + id).show();
				$('tab_database_overview').hide();
			}
		}
		else
		{
			$('tab_database_overview').hide().insert( { after: new Element('div', { id: 'tag_database_' + id }) } );
			$('tab_databases_pane').addClassName('loading');

			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=help&do=tags&id=" + id + "&type=" + dbType;

			new Ajax.Request( url + '&secure_key=' + ipb.vars['md5_hash'],
							  {
								method: 'get',
								onSuccess: function (t)
								{
									//alert( t.responseText );
									$('tab_databases_pane').removeClassName('loading');
									$('tag_database_' + id).update( t.responseText ).show();
								}
							  } );
		}

	},

	/**	
	 * Initialises the database pane
	 */
	loadDatabases: function()
	{
		acp.ipcsidebar.databaseLoaded = true;

		$('tab_databases_pane').addClassName('loading');
		
		if( acp.ipcsidebar.isArticlesTemplate )
		{
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=help&do=list&type=" + acp.ipcsidebar.isArticlesTemplate;
		}
		else
		{
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=help&do=list";
		}

		new Ajax.Request( url + '&secure_key=' + ipb.vars['md5_hash'],
						  {
							method: 'get',
							onSuccess: function (t)
							{
								$('tab_database_overview').update( t.responseText );
								$('tab_databases_pane').removeClassName('loading');
							}
						  } );

	},

	/**	
	 * Inserts a tag into the template text
	 */
	insertTag: function(e, elem)
	{
		Event.stop(e);

		if( !$(elem).hasClassName('tag_row') )
		{
			var elem = $(elem).up('.tag_row');
		}

		var tag = $(elem).readAttribute('data-tag');

		Debug.write( tag );

		if( !tag ){
			return;
		}

		try {
			// this is a function inserted into the page depending on which
			// editor is being used
			insertTag( tag );
		} 
		catch(err){ Debug.write( err ); }

	},
};

acp.ipcsidebar.init();