/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.gallery.js - Gallery ACP javascript 		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPGalleryCategories = {
	popups: {},
	autoComplete: null,

	/*------------------------------*/
	/* Constructor 					*/
	/*------------------------------*/
	init: function()
	{
		Debug.write("Initializing acp.categories.js");

		document.observe("dom:loaded", function(){

			// Moderator stuff
			if( $('mod_type') )
			{
				$('mod_type').observe( 'change', ACPGalleryCategories.monitorModType );

				ACPGalleryCategories.monitorModType();
			}

			// Sort out album or image sort field
			if( $('category_type') )
			{
				$('category_type').observe( 'change', ACPGalleryCategories.monitorCategoryType );

				ACPGalleryCategories.monitorCategoryType();
			}
		});
	},

	/*------------------------------*/
	/* Toggle the sorting options	*/
	/*------------------------------*/
	monitorCategoryType: function( e )
	{
		if( $F('category_type') == 1 )
		{
			$('category_type_albums_wrap').show();
			$('category_type_images_wrap').hide();
		}
		else
		{
			$('category_type_albums_wrap').hide();
			$('category_type_images_wrap').show();
		}
	},

	/*------------------------------*/
	/* Toggle the moderator type	*/
	/*------------------------------*/
	monitorModType: function( e )
	{
		if( $F('mod_type') == 'member' )
		{
			$('mod_type_member').show();
			$('mod_type_group').hide();

			if( $('_modmid') && this.autoComplete == null )
			{
				this.autoComplete = new ipb.Autocomplete( $('_modmid'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		}
		else
		{
			$('mod_type_member').hide();
			$('mod_type_group').show();
		}
	},

	/*------------------------------*/
	/* Show the confirm delete box	*/
	/*------------------------------*/
	confirmDelete: function( catid )
	{
		Debug.write("Attempting to delete category ID: " + catid);

		if ( ! Object.isUndefined( ACPGalleryCategories.popups['deleteCategory'] ) )
		{
			ACPGalleryCategories.popups['deleteCategory'].kill();
		}
		
		var _url  = ipb.vars['base_url'] + 'app=gallery&module=ajax&section=categories&do=deleteDialogue&secure_key=' + ipb.vars['md5_hash'] + '&category=' + catid;
		
		ACPGalleryCategories.popups['deleteCategory']	= new ipb.Popup( 'deleteCategory', { type: 'modal',
																            ajaxURL: _url.replace(/&amp;/g, '&'),
																            stem: false,
																            hideAtStart: false,
																            warning: true,
																            w: '400px'
																			});
	},
	
	/*------------------------------*/
	/* Confirm empty category		*/
	/*------------------------------*/
	confirmEmpty: function( catid )
	{
		if( catid > 0 )
		{
			return acp.confirmDelete( ipb.vars['app_url'].replace(/&amp;/g, '&' ) + 'module=categories&section=manage&code=empty&category=' + catid, ipb.lang['gal_confirm_empty_text'] );
		}
	}

};

ACPGalleryCategories.init();