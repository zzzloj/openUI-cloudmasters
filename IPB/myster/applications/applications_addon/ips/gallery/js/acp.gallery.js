/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.gallery.js - Homepage javascript 		*/
/* (c) IPS, Inc 2010							*/
/* -------------------------------------------- */
/* Author: Matt Mecham							*/
/************************************************/

ACPGallery = {
	section:		'',
	autoComplete:	null,
	popups:			{},
	inSearch: 		false,
	memberGallery:	0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.gallery.js");

		document.observe("dom:loaded", function()
		{
			if ( ACPGallery.section == 'albums' )
			{
				// Set up delete links and buttons
				ipb.delegate.register( "._albumDeleteDialogueTrigger", ACPGallery.deleteDialogue );
				
				// Set up progress bar stuff
				ipb.delegate.register( 'a[progress~="thumbs"]', ACPGallery.launchThumbRebuild );
				ipb.delegate.register( 'a[progress~="resetpermissions"]', ACPGallery.launchResetPermissions );
				ipb.delegate.register( 'a[progress~="resyncalbums"]', ACPGallery.launchResyncAlbums );
				ipb.delegate.register( 'a[progress~="resynccategories"]', ACPGallery.launchResyncCategories );

				// Set up search
				ipb.delegate.register( '.searchByMember', ACPGallery.searchAlbumsByMemberName );
				ipb.delegate.register( '.searchByCategory', ACPGallery.searchAlbumsByCategory );
				ipb.delegate.register( '#searchGo', ACPGallery.goAlbumSearch );
				ipb.delegate.register( '#clearResults', ACPGallery.clearSearchResults );
			}

			// Sort out album type field
			if( $('album_category_id') )
			{
				$('album_category_id').observe( 'change', ACPGallery.monitorCategory );

				ACPGallery.monitorCategory();
			}

			// Set up auto complete
			if ( $('album_owner_autocomplete') )
			{
				var url = ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['md5_hash'] + '&name=';
				
				ACPGallery.autoComplete = new ipb.Autocomplete( $('album_owner_autocomplete'), { multibox: false, url: url, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		});
	},

	/* Show or hide the album type field dependent upon category */
	monitorCategory: function( e )
	{
		if( $F('album_category_id') == ACPGallery.memberGallery )
		{
			$('album_category_select_wrap').show();
			$('album_category_select_text').hide();
		}
		else
		{
			$('album_category_select_wrap').hide();
			$('album_category_select_text').show();
		}
	},

	/* Search category */
	searchAlbumsByCategory: function( e, elem )
	{
		Event.stop(e);
		
		$('searchText').value			= '';
		$('searchType_member').selected	= true;
		$('searchSort_date').selected	= true;
		$('searchDir_desc').selected	= true;
		$('searchCat').value			= $(elem).readAttribute('data-album-category-id');
		
		ACPGallery.goAlbumSearch( e );
	},

	/* Search member's albums */
	searchAlbumsByMemberName: function( e, elem )
	{
		Event.stop(e);
		
		$('searchText').value			= $(elem).readAttribute('data-album-owners-name');
		$('searchType_member').selected	= true;
		$('searchSort_date').selected	= true;
		$('searchDir_desc').selected	= true;
		$('searchCat').value			= 0;
		
		ACPGallery.goAlbumSearch( e );
	},

	/* Clears search results, restoring 'normal' view */
	clearSearchResults: function( e )
	{
		$('clearResults').hide();
		
		$('galleryAlbumsHere').update( $('storedAlbums').innerHTML );
		$('storedAlbums').update();

		ACPGallery.inSearch	= false;
	},

	/* Runs the search and shows the results */
	goAlbumSearch: function( e )
	{
		// Run search
		new Ajax.Request( ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=getAlbums&secure_key=' + ipb.vars['md5_hash'],
						{
							method: 'get',
							parameters: {
										'searchType'	: $F('searchType'),
										'searchMatch'	: $F('searchMatch'),
										'searchSort'	: $F('searchSort'),
										'searchDir'		: $F('searchDir'),
										'searchText'	: $F('searchText'),
										'searchCat'		: $F('searchCat')
										},
							onSuccess: function(t)
							{
								if( !ACPGallery.inSearch )
								{
									ACPGallery.inSearch	= true;
									$('storedAlbums').update( $('galleryAlbumsHere').innerHTML );

									$('clearResults').show();
								}

								$('galleryAlbumsHere').update( t.responseText );
								
								ipb.menus.initEvents();
							}
						} );
	},

	/* Launch the resync albums progress bar */
	launchResyncAlbums: function( e, elem )
	{
		ipb.menus.closeAll(e);

		cb = new IPBProgressBar( { title: ipb.lang['js__resynch_albums'],
								   total: null,
								   pergo: null,
								   ajaxUrl: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=resyncAlbums&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + elem.readAttribute( 'album-id' ) } ).show();
	},

	/* Launch the resync categories progress bar */
	launchResyncCategories: function( e, elem )
	{
		ipb.menus.closeAll(e);

		cb = new IPBProgressBar( { title: ipb.lang['js__resynch_cats'],
								   total: null,
								   pergo: null,
								   ajaxUrl: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=categories&do=resyncCategories&secure_key=' + ipb.vars['md5_hash'] + '&categoryId=' + elem.readAttribute( 'category-id' ) } ).show();
	},
	
	/* Launch the rebuild thumbnails progress bar */
	launchThumbRebuild: function( e, elem )
	{
		ipb.menus.closeAll(e);

		cb = new IPBProgressBar( { title: ipb.lang['js__rebuild_images'],
								   total: null,
								   pergo: null,
								   ajaxUrl: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=rebuildThumbs&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + elem.readAttribute( 'album-id' ) } ).show();
	},
	
	/* Launch the reset permissions progress bar */
	launchResetPermissions: function( e, elem )
	{
		ipb.menus.closeAll(e);

		cb = new IPBProgressBar( { title: ipb.lang['js__reset_perms'],
								   total: null,
								   pergo: null,
								   ajaxUrl: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=resetPermissions&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + elem.readAttribute( 'album-id' ) } ).show();
	},

	/**
	 * Delete dialogue
	 */
	deleteDialogue: function(e, elem)
	{
		Event.stop(e);

		if ( ! Object.isUndefined( ACPGallery.popups['deleteAlbum'] ) )
		{
			ACPGallery.popups['deleteAlbum'].kill();
		}

		ACPGallery.popups['deleteAlbum'] =  new ipb.Popup( 'deleteAlbum', { type: 'modal',
																            ajaxURL: ipb.vars['base_url'].replace(/&amp;/g, '&') + 'app=gallery&module=ajax&section=albums&do=deleteDialogue&secure_key=' + ipb.vars['md5_hash'] + '&albumId=' + elem.readAttribute('album-id'),
																            stem: false,
																            hideAtStart: false,
																            warning: true,
																            w: '600px'
																			});
		
		
	}
};

ACPGallery.init();