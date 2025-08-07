/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.ccs.js - General IP.Content JS			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var titlePop, cPop, cachePop, furlPop;
var _ccs = window.IPBACP;
_ccs.prototype.ccs = {
	
	popups:	new Array(),
	rowIds:	0,
	autoComplete: null,
	
	/*
	 * Init function
	 */
	init: function()
	{
		Debug.write("Initializing acp.ccs.js");
		
		document.observe("dom:loaded", function(){
			ipb.delegate.register( '#close-tags-link', acp.ccs.closeInlineHelp );
			ipb.delegate.register( '#template-tags.closed', acp.ccs.showInlineTemplateTags );
			ipb.delegate.register( '.block-preview-link', acp.ccs.showBlockPreview );
			ipb.delegate.register( '.block-widget-link', acp.ccs.showBlockWidgetCode );
			ipb.delegate.register( '.m_new_folder', acp.ccs.addFolder );
			ipb.delegate.register( '.templates-view-pages', acp.ccs.showPagesUsedBy );
			ipb.delegate.register( '.templates-view-dbs', acp.ccs.showDbsUsedBy );
			ipb.delegate.register( '#template-add-category', acp.ccs.addTemplateCategory );
			ipb.delegate.register( '#block-add-category', acp.ccs.addBlockCategory );
			ipb.delegate.register( '#furl_help', acp.ccs.showFurlHelp );
			ipb.delegate.register( '#explain_content_field', acp.ccs.showExplainContentField );
			ipb.delegate.register( '#explain_title_field', acp.ccs.showExplainTitleField );
			ipb.delegate.register( '#cache_help', acp.ccs.showCacheHelp );
			ipb.delegate.register( '.toggle_article_status', acp.ccs.toggleArticleStatus );
			ipb.delegate.register( '.help_blurb', acp.ccs.toggleHelpBlurb );
			ipb.delegate.register( '#page_name_as_title', acp.ccs.togglePageTitle );
			ipb.delegate.register( '.delete_menu_row', acp.ccs.deleteSubmenuRow );
			ipb.delegate.register( '.duplicate_block', acp.ccs.duplicateBlockPopup );

			if( $('tpb_content_types') )
			{
				$('tpb_content_types').observe( 'change', acp.ccs.loadBlockTemplateTags );
			}

			if( $('submenu_items_yes') )
			{
				ipb.delegate.register( '#submenu_items_yes', acp.ccs.toggleSubmenuItems );
				ipb.delegate.register( '#submenu_items_no', acp.ccs.toggleSubmenuItems );
				ipb.delegate.register( '#submenu_another', acp.ccs.addSubmenuItemRow );
				
				acp.ccs.toggleSubmenuItems();
			}
			
			if( $('page_name_as_title') && $('page_title') )
			{
				acp.ccs.togglePageTitle();
			}

			if( $('uploadForm') )
			{
				$('uploadForm').hide();
				$('uploadTrigger').observe('click', acp.ccs.showUploadForm );
			}
			
			if( $('field_type') )
			{
				$('field_type').observe('change', acp.ccs.showAppropriateFields );
				
				acp.ccs.showAppropriateFields();
			}
			
			if( $('validator_custom') )
			{
				// Hide by default
				if( $F('field_validator') != 'custom' )
				{
					$('validator_custom').hide();
				}
				
				// Then add handler
				$('field_validator').observe('change', acp.ccs.showValidatorStuff );
			}

			/* Selectively hide/show fields based on other opts */
			if( $('all_masks_no') )
			{
				$('all_masks_no').observe( 'click', function(e) {
					new Effect.BlindDown( $('not_all_masks'), { duration: 0.2 } );
					//$('not_all_masks').show();
				});
			}

			if( $('all_masks_yes') )
			{
				$('all_masks_yes').observe( 'click', function(e) {
					new Effect.BlindUp( $('not_all_masks'), { duration: 0.2 } );
					//$('not_all_masks').hide();
				});
			}
			
			if( $('template') )
			{
				$('template').observe( 'change', function(e){
					if( $F('template') == 'none' || parseInt($F('template')) == 0 )
					{
						new Effect.BlindUp( $('only_page_content'), { duration: 0.2 } );
						//$('only_page_content').hide();
					}
					else
					{
						if( !$('only_page_content').visible() )
						{
							new Effect.BlindDown( $('only_page_content'), { duration: 0.2 } );
							//$('only_page_content').show();
						}
					}
				});
			}
			
			if( $('multi-action-form') )
			{
				$('multi-action-form').observe('submit', acp.ccs.confirmSubmit );
			}

			if( $('mediafiles') )
			{
				var count = 0;
				
				$$('td.page_date').each( function(elem){
					count++;
				});
				
				if( !count )
				{
					$('manage-opts').hide();
				}
			}
			else if( $('page_manager') )
			{
				var count = 0;
				
				$$('.infotext').each( function(elem){
					count++;
				});
				
				if( !count )
				{
					$('manage-opts').hide();
				}
			}

			if( $('page_name') )
			{
				$('page_name').observe( 'keyup', acp.ccs.checkPageName );
		
				if( $('page_name').value == '' )
				{
					acp.ccs.setUpAutoName();
				}
		
				acp.ccs.checkPageName();
			}

			if( $('cache_override') && $('cache_ttl') )
			{
				if( $('cache_ttl').value == '' || $('cache_ttl').value == '0' )
				{
					$('cache_override').checked = false;
					$('cache_edit').hide();
					$('cache_ttl').value = '';
				}
				else
				{
					$('cache_override').checked = true;
					$('cache_edit').show();
				}
		
				$('cache_override').observe('click', function(e)
				{
					if( this.checked )
					{
						new Effect.BlindDown( $('cache_edit'), { duration: 0.2 } );
					}
					else
					{
						new Effect.BlindUp( $('cache_edit'), { duration: 0.2 } );
						$('cache_ttl').value = '';
					}
				});
			}

			if( $('forum_opts') && $('database_forum_record_no') )
			{
				if( !$('database_forum_record_yes').checked )
				{
					$('forum_opts').hide();
				}
				
				$('database_forum_record_no').observe( 'click', acp.ccs.toggleForumOpts );
				$('database_forum_record_yes').observe( 'click', acp.ccs.toggleForumOpts );
			}

			if( $('moderator_type') )
			{
				acp.ccs.toggleModOpts();
				
				$('moderator_type').observe( 'change', acp.ccs.toggleModOpts );
			}
		});
	},

	loadBlockTemplateTags: function()
	{
		var _type		= $F('tpb_app_types');
		var _subtype	= $F('tpb_content_types');

		var req			= ipb.vars['base_url'] + "app=ccs&module=ajax&section=blocks&do=templatetags&parentType=" + _type + '&subType=' + _subtype;
		
		new Ajax.Request(	req.replace(/&amp;/g, "&"),
							{
								method: 'post',
								parameters: { secure_key: ipb.vars['md5_hash'] },

								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['js_error_enc'] );
										return;
									}

									$('tab_templates_pane').update( t.responseText );
								}
							}
						);
	},
	
	deleteSubmenuRow: function( e, elem )
	{
		$(elem).up("tr").remove();
		
		Event.stop(e);
		return false;
	},
	
	addSubmenuItemRow: function()
	{
		var newTemplate	= ipb.templates['submenu_item'].evaluate( { trid: acp.ccs.rowIds } );
		
		$('submenu_items_body').insert( newTemplate );

		var curRowId	= acp.ccs.rowIds;

		$('nosubmenu_perms_' + acp.ccs.rowIds ).observe( 'click', function(e) {
			if( $('nosubmenu_perms_' + curRowId ).checked == true )
			{
				$('submenu_permissions_' + curRowId ).hide();

				$$('select#submenu_permissions_' + curRowId + '_menu option').each( function(elem) {
					$(elem).selected	= false;
				} );
			}
			else
			{
				$('submenu_permissions_' + curRowId ).show();
			}
		} );
		
		acp.ccs.rowIds	= acp.ccs.rowIds + 1;
	},
	
	initSubmenuItems: function( jsonData )
	{
		$H(jsonData).each( function( value )
		{
			var templateVars	= {
									trid:				acp.ccs.rowIds,
									id:					value[1].menu_id,
									title_value:		value[1].menu_title,
									url_value:			value[1].menu_url,
									value_description:	value[1].menu_description,
									value_attributes:	value[1].menu_attributes
								};

			var newTemplate		= ipb.templates['submenu_item'].evaluate( templateVars );
			
			$('submenu_items_body').insert( newTemplate );
			
			/* Select the groups in the multiselect */
			var options			= $$('select#submenu_permissions_' + acp.ccs.rowIds + '_menu option');
			var thisGroups		= value[1].menu_permissions.split(',');
			var selectCount		= 0;
			
			for ( var i = 0, j=options.length; i < j; i++ )
			{
				for( var k = 0, m = thisGroups.length; k < m; k++ )
				{
					if( options[i].value == thisGroups[k] )
					{
						options[i].selected	= true;
						selectCount++;
					}
				}
			}

			if( !selectCount )
			{
				$('nosubmenu_perms_' + acp.ccs.rowIds ).checked	= true;
				$('submenu_permissions_' + acp.ccs.rowIds ).hide();
			}
			else
			{
				$('submenu_permissions_' + acp.ccs.rowIds ).show();
				$('nosubmenu_perms_' + acp.ccs.rowIds ).checked	= false;
			}

			var curRowId	= acp.ccs.rowIds;

			$('nosubmenu_perms_' + acp.ccs.rowIds ).observe( 'click', function(e) {
				if( $('nosubmenu_perms_' + curRowId ).checked == true )
				{
					$('submenu_permissions_' + curRowId ).hide();

					$$('select#submenu_permissions_' + curRowId + '_menu option').each( function(elem) {
						$(elem).selected	= false;
					} );
				}
				else
				{
					$('submenu_permissions_' + curRowId ).show();
				}
			} );

			acp.ccs.rowIds	= acp.ccs.rowIds + 1;
		});
		
		if( acp.ccs.rowIds == 0 )
		{
			var newTemplate	= ipb.templates['submenu_item'].evaluate( { trid: acp.ccs.rowIds } );
			
			$('submenu_items_body').insert( newTemplate );

			var curRowId	= acp.ccs.rowIds;

			$('nosubmenu_perms_' + acp.ccs.rowIds ).observe( 'click', function(e) {
				if( $('nosubmenu_perms_' + curRowId ).checked == true )
				{
					$('submenu_permissions_' + curRowId ).hide();

					$$('select#submenu_permissions_' + curRowId + '_menu option').each( function(elem) {
						$(elem).selected	= false;
					} );
				}
				else
				{
					$('submenu_permissions_' + curRowId ).show();
				}
			} );
			
			acp.ccs.rowIds	= acp.ccs.rowIds + 1;
		}
	},
	
	toggleSubmenuItems: function(e)
	{
		if( $('submenu_items_yes').checked )
		{
			$('submenu_items_wrapper').show();
			$('submenu_items_method').show();
		}
		else
		{
			$('submenu_items_wrapper').hide();
			$('submenu_items_method').hide();
		}
	},
	
	togglePageTitle: function(e)
	{
		if( $('page_name_as_title').checked )
		{
			$('page_title').hide();
		}
		else
		{
			$('page_title').show();
		}
	},
	
	toggleHelpBlurb: function(e)
	{
		Event.stop(e);
		
		if( $('help_blurb_text').visible() )
		{
			new Effect.BlindUp( $('help_blurb_text'), { duration: 0.4 } );
		}
		else
		{
			new Effect.BlindDown( $('help_blurb_text'), { duration: 0.4 } );
		}
					
		return false;
	},
	
	toggleArticleStatus: function(e, elem)
	{
		var id	= $(elem).readAttribute('rel');
		var req	= ipb.vars['base_url'] + "app=ccs&module=ajax&section=articles&do=toggle&id=" + id;
		
		new Ajax.Request(	req.replace(/&amp;/g, "&"),
							{
								method: 'post',
								parameters: { secure_key: ipb.vars['md5_hash'] },
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['js_error_enc'] );
										return;
									}

									$(elem).update( t.responseText );
									
									if( $( 'result_' + id ).hasClassName('acp-row-red') )
									{
										$( 'result_' + id ).removeClassName('acp-row-red');
										$( 'result_' + id ).addClassName('acp-row-off');
									}
									else
									{
										$( 'result_' + id ).removeClassName('acp-row-on');
										$( 'result_' + id ).removeClassName('acp-row-off');
										
										$( 'result_' + id ).addClassName('acp-row-red');
									}
								}
							}
						);
	},

	duplicateBlockPopup: function(e, elem)
	{
		Event.stop(e);

		var blockid	= $(elem).id.replace( 'duplicate_', '' );

		acp.ccs.popups['duplicateblock'] = new ipb.Popup( 'duplicateblock', {
																type: 'modal',
																initial: ipb.templates['duplicate_block'].evaluate( { block_id: blockid } ),
																w: '450px',
																stem: false,
																hideAtStart: false
															} );
	},

	showCacheHelp: function(e)
	{
		Event.stop(e);

		if( cachePop )
		{
			cachePop.show();
		}
		else
		{
			cachePop = new ipb.Popup( 'cache_help', {
											type: 'pane',
											initial: $('cache_help_content').show(),
											hideAtStart: false,
											w: '500px'
										});
		}
	},
	
	showExplainTitleField: function(e)
	{
		Event.stop(e);

		if( titlePop )
		{
			titlePop.show();
		}
		else
		{
			titlePop = new ipb.Popup( 'explain_title_field', {
											type: 'pane',
											initial: $('title_help_content').show(),
											hideAtStart: false,
											w: '500px'
										});
		}
	},
	
	showExplainContentField: function(e)
	{
		Event.stop(e);

		if( cPop )
		{
			cPop.show();
		}
		else
		{
			cPop = new ipb.Popup( 'explain_content_field', {
											type: 'pane',
											initial: $('content_help_content').show(),
											hideAtStart: false,
											w: '500px'
										});
		}
	},
	
	showFurlHelp: function(e)
	{
		Event.stop(e);

		if( furlPop )
		{
			furlPop.show();
		}
		else
		{
			furlPop = new ipb.Popup( 'furl_help', {
											type: 'pane',
											initial: $('furl_help_content').show(),
											hideAtStart: false,
											w: '500px'
										});
		}
	},
	
	toggleModOpts: function()
	{
		if( $F('moderator_type') == 'member' )
		{
			$('mod_group').hide();
			$('mod_user').show();

			if( !this.autoComplete )
			{
				this.autoComplete = new ipb.Autocomplete( $('moderator_member'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		}
		else
		{
			$('mod_user').hide();
			$('mod_group').show();
		}
	},

	toggleForumOpts: function(e)
	{
		if( !$('database_forum_record_yes').checked )
		{
			if( $('forum_opts').visible() )
			{
				new Effect.BlindUp( $('forum_opts'), { duration: 0.2 } );
			}
		}
		else
		{
			if( !$('forum_opts').visible() )
			{
				new Effect.BlindDown( $('forum_opts'), { duration: 0.2 } );
			}
		}

		return true;
	},				

	checkPageName: function(e)
	{
		if( $('page_name').value == defaultPageName )
		{
			if( !$('omit_page').visible() )
			{
				new Effect.BlindDown( $('omit_page'), { duration: 0.2 } );
			}
		}
		else
		{
			if( $('omit_page').visible() )
			{
				new Effect.BlindUp( $('omit_page'), { duration: 0.2 } );
			}
		}
	},
	
	setUpAutoName: function()
	{
		var checking = true;

		$('name').observe('keyup', function(e)
		{
			if( checking )
			{
				$('page_name').value = acp.ccs.transformPageName( $('name').value );
			}
		});

		$('page_name').observe('focus', function(e)
		{
			checking = false;
		});
	},
	
	transformPageName: function( val )
	{
		if( !val )
		{
			return '';
		}

		return ( val.replace(/[^0-9a-zA-Z]+/gi, '_') + '.html' ).toLowerCase();
	},
	
	addBlockCategory: function( e )
	{
		Event.stop(e);

		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=blocks&secure_key=" + ipb.vars['md5_hash'] + '&do=addcategory';

		// Make a new popup
		acp.ccs.popups['addcat'] = new ipb.Popup( 'addcat', {
																type: 'modal',
																ajaxURL: url,
																w: '450px',
																stem: false,
																modal: true,
																hideAtStart: false
															} );

		return false;
	},
	
	addTemplateCategory: function( e )
	{
		Event.stop(e);

		var elem	= Event.findElement(e);
		var type	= $(elem).rel;
		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=addcategory&type=' + type;

		// Make a new popup
		acp.ccs.popups['addcat'] = new ipb.Popup( 'addcat', {
																type: 'modal',
																ajaxURL: url,
																w: '450px',
																stem: false,
																modal: true,
																hideAtStart: false
															} );

		return false;
	},

	showPagesUsedBy: function( e, elem )
	{
		Event.stop(e);

		var id		= $(elem).rel;
		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=getpages&id=' + id;

		// Make a new popup
		acp.ccs.popups['usedby_' + id] = new ipb.Popup( 'usedby_' + id, {
																type: 'balloon',
																attach: { target: elem, position: 'auto' },
																ajaxURL: url,
																w: '450px',
																stem: true,
																hideAtStart: false
															} );
	},

	showDbsUsedBy: function( e, elem )
	{
		Event.stop(e);

		var id		= $(elem).rel;
		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=getdbs&id=' + id;

		// Make a new popup
		acp.ccs.popups['usedby_' + id] = new ipb.Popup( 'usedby_' + id, {
																type: 'balloon',
																attach: { target: elem, position: 'auto' },
																ajaxURL: url,
																w: '450px',
																stem: true,
																hideAtStart: false
															} );
	},
	
	confirmSubmit: function(e)
	{
		if( $F('multi-action') == 'delete' )
		{
			if( !confirm( ipb.lang['confirm_submit_del'] ) )
			{
				Event.stop(e);
				return false;
			}
		}
		
		return true;
	},
	
	addFolder: function( e, elem )
	{
		Event.stop(e);

		// Make a new popup
		acp.ccs.popups['addfolder'] = new ipb.Popup( 'addfolder', {
																type: 'modal',
																initial: ipb.templates['ccs_new_folder'].evaluate(),
																w: '450px',
																stem: false,
																hideAtStart: false
															} );
	},
	
	/*
	 * Change widget code type
	 */
	setMethod: function (elem, method)
	{
		var _mySpan	= $(elem).up('.block-widget-code').down('.widget-method');
		
		if( method )
		{
			$(_mySpan).update('&amp;amp;method=div');
		}
		else
		{
			$(_mySpan).update();
		}
	},
	
	/*
	 * Show template diff report popup
	 */
	viewCompare: function( url )
	{
		new Ajax.Request( url + '&secure_key=' + ipb.vars['md5_hash'],
						  {
							method: 'get',
							onSuccess: function (t)
							{
								popup = new ipb.Popup('diffPopUp', { type: 'pane', modal: false, hideAtStart: true, w: '800px', h: 600, initial: t.responseText } );
								popup.show();
							}
						  } );

		return false;
	},
	
	/*
	 * Show and hide validator fields for "custom" type
	 */
	showValidatorStuff: function( e )
	{
		var fieldValue	= $F('field_validator');
		
		if( fieldValue == 'custom' )
		{
			$('validator_custom').show();
		}
		else
		{
			$('validator_custom').hide();
		}
	},

	/*
	 * Alter new field form appropriately based on field type
	 */
	showAppropriateFields: function(e)
	{
		var fieldValue	= $F('field_type');
		
		$$('.field_type_wrapper').each( function(elem) {
			$(elem).hide();
		});
		
		$('field_truncate_li').show();
						
		if( fieldValue == 'input' )
		{
			$('field_required_li').show();
			$('field_length_li').show();
			$('field_html_li').show();
			$('field_formatopts_li').show();
			$('field_validator_li').show();
		}
		else if( fieldValue == 'textarea' || fieldValue == 'editor' )
		{
			$('field_required_li').show();
			$('field_length_li').show();
			$('field_html_li').show();
			$('field_validator_li').show();
		}
		else if ( fieldValue == 'date' )
		{
			$('field_required_li').show();
			$('field_extra_li').show();
		}
		else if( fieldValue == 'checkbox' || fieldValue == 'radio' || fieldValue == 'select' || fieldValue == 'multiselect' )
		{
			$('field_required_li').show();
			$('field_extra_li').show();
			$('field_filter_li').show();
		}
		else if( fieldValue == 'member' )
		{
			$('field_required_li').show();
		}
		else if( fieldValue == 'relational' )
		{
			$('field_reltype_li').show();
			$('field_required_li').show();
			$('field_rellink_li').show();
			$('field_crosslink_li').show();
			
			acp.ccs.fieldFormRelational();
		}
		else if( fieldValue == 'attachments' )
		{
			$('field_required_li').show();
		}
		else if( fieldValue == 'upload' )
		{
			$('field_extra_li').show();
			$('field_required_li').show();
		}
		else
		{
			// @see	http://community.invisionpower.com/index.php?app=tracker&showissue=20456
			//$$('.field_type_wrapper').each( function(elem) {
			//	$(elem).show();
			//});
		}

		if( fieldDescriptions.get( fieldValue ) )
		{
			$('extra_options_text').update( fieldDescriptions.get( fieldValue ) );
		}
		else
		{
			$('extra_options_text').update( fieldDescriptions.get('standard') );
		}
	},
	
	/*
	 * Extra handling for relational database fields
	 */
	fieldFormRelational: function()
	{
		var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=fields&do=fetchDatabases";
		
		new Ajax.Request(	url,
							{
								method: 'post',
								parameters: {
									md5check: 	ipb.vars['md5_hash'],
									field:		$F('field_edit_id')
								},
								onSuccess: function(t)
								{
									$('field_databases_li').show();
									$('field_databases_select').update( t.responseText );
									
									if( t.responseText.indexOf( "selected='selected'" ) )
									{
										acp.ccs.fetchRelationalFields();
										
										$('field_databases_select').observe( 'change', acp.ccs.fetchRelationalFields );
									}
								}
							}
						);
	},
	
	/*
	 * Fetch fields for a specific database
	 */
	fetchRelationalFields: function()
	{
		var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=fields&do=fetchFields";
		
		new Ajax.Request(	url,
							{
								method: 'post',
								parameters: {
									md5check: 	ipb.vars['md5_hash'],
									field:		$F('field_edit_id'),
									database:	$F('field_databases_select')
								},
								onSuccess: function(t)
								{
									$('field_fields_li').show();
									
									$('field_fields_select').update( t.responseText );
								}
							}
						);
	},
	
	/*
	 * Show the upload form
	 */
	showUploadForm: function( e )
	{
		Event.stop(e);
		popup = new ipb.Popup( 'showuploadform', { type: 'pane', modal: false, w: '500px', initial: $('uploadForm').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		
		return false;
	},

	/*
	 * Show block preview
	 */
	showBlockPreview: function( e )
	{
		Event.stop(e);
		var elem	= Event.findElement(e, 'a' );
		var id		= elem.id.replace( '-blockPreview', '' );
		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=blocks&secure_key=" + ipb.vars['md5_hash'] + '&do=preview&id=' + id;
		
		popup = new ipb.Popup( 'blockPreview', { type: 'pane', modal: false, w: '600px', h: '450px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	/*
	 * Show block widget code
	 */
	showBlockWidgetCode: function( e )
	{
		Event.stop(e);
		var elem	= Event.findElement(e, 'a' );
		var id		= elem.id.replace( '-blockWidget', '' );

		popup = new ipb.Popup( 'widgetCode', { 
												type:			'pane', 
												modal:			false, 
												w:				'600px; height: 500px', 
												h:				'500px', 
												initial:		ipb.templates['block_widget'].evaluate( { blockid: id, blockkey: elem.rel, realblockkey: elem.readAttribute('krel') } ), 
												hideAtStart:	false, 
												close:			'a[rel="close"]' 
								} 				);
		return false;
	},

	/*
	 * Show inline template tag help
	 */
	showInlineTemplateTags: function( e )
	{
		Event.stop(e);
		
		$('template-tags').removeClassName( 'closed' );
		$('content-label').addClassName( 'withSidebar' );
		
		if( window.resizeEditor )
		{
			resizeEditor();
		}
		
		ipb.Cookie.set( 'hideContentHelp', null, -1 );
		
		return false;
	},
	
	/*
	 * Close the inline help
	 */
	closeInlineHelp: function( e )
	{
		Event.stop(e);
		
		if( $('template-tags') )
		{
			$('template-tags').addClassName( 'closed' );
			$('content-label').removeClassName( 'withSidebar' );
			
			if( window.restoreResizeEditor )
			{
				restoreResizeEditor();
			}
		}
		
		ipb.Cookie.set( 'hideContentHelp', 1 );
	},

	/*
	 * Block categorization
	 */
	initBlockCategorization: function( categoryIds )
	{
		categoryReorder = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'category-containers', { tag: 'div', name: 'category' } )
						};
		 
			new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorderCats&md5check=" + ipb.vars['md5_hash'], options );
		
			return false;
		};
			
		for( var i=0; i < categoryIds.length; i++ )
		{
			if( $( categoryIds[ i ] ) )
			{
				Sortable.create( categoryIds[ i ], { 
						containment: categoryIds, 
						only: 'record',
						dropOnEmpty: true, 
						revert: 'failure', 
						format: 'record_([0-9]+)', 
						handle: 'draghandle',
						onUpdate: function( draggableObject, mouseObject )
						{
							if( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) )
							{
								draggableObject.removeChild( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) );
							}
							else
							{
								if( parseInt( draggableObject.childElements().length ) == 0 )
								{
									// Create empty row
									var temp = ipb.templates['cat_empty'].evaluate( { category: parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) } );
																							
									draggableObject.innerHTML = temp;
								}
							}
	
							new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorder&category=" + draggableObject.id.replace( "sortable_handle_", "" ) + "&md5check=" + ipb.vars['md5_hash'], {
												method : 'post',
												parameters : Sortable.serialize( draggableObject.id, { tag: 'li', name: 'block' } )
											} );
						
							return false;
						}  
				} );
				
				if( categoryIds[ i ] == 0 )
				{
					return true;
				}
			}
		}
		
		Sortable.create( 'category-containers', { tag: 'div', revert: 'failure', format: 'container_([0-9]+)', onUpdate: categoryReorder, handle: 'draghandle', only: 'isDraggable' } );
	},
	
	/*
	 * Template categorization
	 */
	initTemplateCategorization: function( categoryIds )
	{
		categoryPReorder = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'category-containers-p', { tag: 'div', name: 'category' } )
						};
		 
			new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorderCats&md5check=" + ipb.vars['md5_hash'], options );
		
			return false;
		};
		
		categoryDReorder = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'category-containers-d', { tag: 'div', name: 'category' } )
						};
		 
			new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorderCats&md5check=" + ipb.vars['md5_hash'], options );
		
			return false;
		};
			
		for( var i=0; i < categoryIds.length; i++ )
		{
			Sortable.create( categoryIds[ i ], { 
					containment: categoryIds, 
					only: 'record',
					dropOnEmpty: true,
					revert: 'failure', 
					format: 'record_([0-9]+)', 
					handle: 'draghandle',
					onUpdate: function( draggableObject, mouseObject )
					{
						if( draggableObject.id.match( "sortable_handle_p_" ) )
						{
							var _thisId	= draggableObject.id.replace( "sortable_handle_p_", "" );
							var _type	= 'page';
						}
						else
						{
							var _thisId	= draggableObject.id.replace( "sortable_handle_d_", "" );
							var _type	= 'db';
						}

						if( _type == 'db' && $('record_000' + parseInt( _thisId ) ) )
						{
							draggableObject.removeChild( $('record_000' + parseInt( _thisId ) ) );
						}
						else if( _type == 'page' && $('record_00' + parseInt( _thisId ) ) )
						{
							draggableObject.removeChild( $('record_00' + parseInt( _thisId ) ) );
						}
						else
						{
							if( parseInt( draggableObject.childElements().length ) == 0 )
							{
								// Create empty row
								var temp = ipb.templates['cat_empty_' + _type ].evaluate( { category: parseInt( _thisId ) } );
																						
								draggableObject.innerHTML = temp;
							}
						}

						new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=templates&do=reorder&category=" + _thisId + "&md5check=" + ipb.vars['md5_hash'], {
											method : 'post',
											parameters : Sortable.serialize( draggableObject.id, { tag: 'li', name: 'template' } )
										} );
					
						return false;
					}  
			} );
			
			if( categoryIds[ i ] == 0 )
			{
				return true;
			}
		}
		
		if( $('category-containers-p') )
		{
			Sortable.create( 'category-containers-p', { tag: 'div', revert: 'failure', format: 'container_p_([0-9]+)', onUpdate: categoryPReorder, handle: 'draghandle', only: 'isDraggable' } );
		}
		
		if( $('category-containers-d') )
		{
			Sortable.create( 'category-containers-d', { tag: 'div', revert: 'failure', format: 'container_d_([0-9]+)', onUpdate: categoryDReorder, handle: 'draghandle', only: 'isDraggable' } );
		}
	}
};

acp.ccs.init();