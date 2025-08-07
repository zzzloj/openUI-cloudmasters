/**
* Tracker 2.1.0
* 
* Projects Javascript
* Last Updated: $Date: 2012-12-16 11:31:10 +0000 (Sun, 16 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1397 $
*/
ipb.vars['loading_img'] 		= ipb.vars['image_url'] + 'loading.gif';

var fields = {};

(function($){
	
	fields = function($){
		var popup			= {};
		var dataStore		= {};
		var javascriptStore	= {};
		var storedApps		= {};
		var storedPermsLi	= {};
		var activeTab		= '';
		
		var init = function() {
		
			$(document).ready(
				function() {
					runEvents();
					
					$('#loadingAjax')
						.ajaxStart(function() {
								$(this).fadeIn(200);
						})
						.ajaxStop(function() {
							$(this).fadeOut(200);
						}
					);
				}
			);
		};
		
		var runEvents = function() {

			$('li.field').each(
				function(index)
				{
					$(this).click( fieldScreen );
				}
			);

			$('#fields input[type=checkbox]').click( function(e)
			{
				e.stopPropagation();
				return true;
			});
			
			$('#fields').sortable( { "containment": 'parent', "axis": 'y' } );
		};
		
		var fieldScreen	= function(event){
			event.preventDefault();
			event.stopPropagation();
		
			var li = event.currentTarget;
			
			// Do we have a current active tab? If so, send it for saving!
			if ( activeTab )
			{
				saveField();
			}
			
			/* If we've already grabbed content once, show it instead of requesting it */
			if ( typeof( popup[li.id] ) != 'undefined' )
			{
				// Hide any other popups
				$('#fields').children().each(
					function(index)
					{
						$(this).removeClass('active');
					}
				);
				
				// Assign new active
				$(li).addClass('active');
				
				permsLi = storedPermsLi[li.id];
				app = storedApps[li.id];
				popup[li.id].show();
	
				// Remove active class names
				$('#field_content').children().each(
					function(index)
					{
						if ( $(this).attr('id') != li.id + '_wrap' )
						{
							$(this).hide();
						}
					}
				);
				
				return true;
			}
			
			/* First time, request */
			$.ajax(
				{
					cache: false,
					type: 'POST',
					url: ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'module=ajax&section=projects&do=fieldOptions&md5check=' + ipb.vars['md5_hash'],
					data: $.param(
						{
							'field': li.id.replace('field_', ''),
							'project_id': projects.project_id
						}
					),
					success: function(data) {
						
						if ( typeof( data.tabs ) != 'undefined' )
						{
							// Already JSON
							var result = data;
						}
						else
						{
							// Have we got JSON?
							try {
								var result = $.parseJSON( data );
							}
							catch(e) {
								var result = $.parseJSON( '{"error":"true"}' );
							}
						}
												
						// Error
						if ( typeof( result.error ) != 'undefined' )
						{
							alert( "Error" );
							return false;
						}
						
						var tData = {
							"Field":	li.id,
							"Title":	$(li).children('div.item_info').html()
						};
						
						// Store it in our cache and hide
						popup[li.id] = $('#fieldjQTemplate').tmpl( tData ).appendTo('#field_content').hide();
						
						// Remove active class names
						$('#fields').children().each(
							function(index)
							{
								$(this).removeClass('active');
							}
						);
						
						// Assign new active
						$(li).addClass('active');
						
						// Create our tabs and basically sort them out! brap brap
						for ( tab in result.tabs )
						{
							if ( ! result.tabs.hasOwnProperty(tab) )
							{
								continue;
							}
							
							// Create our tab
							var menu = $('<li />', {
									id: li.id + '_tab_' + result.tabs[tab].key
								}
							).html( result.tabs[tab].title );
							
							// Is it the active one by default?
							if ( result.tabs[tab].active )
							{
								$(menu).addClass('active');
							}
							
							/* If button */
							if ( result.tabs[tab].button )
							{
								$( '#' + li.id + '_menu' ).append( "<li id='" + result.tabs[tab].button_id + "' class='button' rel='" + result.tabs[tab].show_for + "'>" + result.tabs[tab].title + "</li>" );
								
								// Show by default?
								if ( ! result.tabs[tab].active )
								{
									$( result.tabs[tab].button_id ).hide();
								}
							}
							else
							{
								$( '#' + li.id + '_menu' ).append( menu );
							}
						}
						
						// Sort out tab switching
						$('#' + li.id + '_menu' ).children().each(
							function(index)
							{
								if ( ! $(this).hasClass('button') && ! $(this).hasClass('save') )
								{
									$(this).click( switchTab );
								}
							}
						);
						
						// Content of tab
						for ( content in result.content )
						{
							if ( ! result.content.hasOwnProperty(content) )
							{
								continue;
							}
							
							// Create the form
							var form = $('<form />', {
									'id': li.id + '_form_' + result.content[content].key
								}
							).hide();
							
							// Create the div
							var div = $('<div />', {
									'id': li.id + '_content_' + result.content[content].key 
								}
							).append( result.content[content].content );
							
							// Show the form
							if ( $('#' + li.id + '_tab_' + result.content[content].key).hasClass('active') )
							{
								form.show();
							}
							
							// Custom CSS
							if ( result.content[content].css )
							{
								$(div).addClass(result.content[content].css);
							}
							
							// Add the div to the form
							form.append(div);
							
							$( '#' + li.id + '_content' ).prepend( form );
							
							// Permissions tab
							if ( result.content[content].key == 'perms' )
							{
								app = result.content[content].field;
								storedApps[li.id] = app;
								
								// Bug fix with perms yay
								permsLi = result.content[content].type;
								storedPermsLi[li.id] = permsLi;
								
								$( '#' + result.content[content].type + '_perm_matrix' ).click( boxChecked );
				
								$( '#' + result.content[content].type + '_perm_matrix .select_row' ).each( function(index){
									$( this ).click( selectRow );
								} );
								
								$( '#' + result.content[content].type + '_perm_matrix .column').each( function(index){
									$( this ).click( selectColumn );
								} );
							}
						}
						
						// Set our active varible
						activeTab = li.id;
						
						// Stop original save project
						$('#save_project').unbind( 'click', projects.saveProject );
						
						// ALWAYS save fields
						$('#prev, #next, #steps_additional, #steps_perms, #steps_fields, #steps_basic, #save_project').click( saveField );
								
						// Show config
						popup[li.id].show();
						
						// Hide other content
						$('#field_content').children().each(
							function(index)
							{
								if ( $(this).attr('id') != li.id + '_wrap' )
								{
									$(this).hide();
								}
							}
						);
						
						// After show javascript
						if ( result.javascript )
						{
							if ( result.javascript.additional )
							{
								eval( result.javascript.additional );
							}
							
							
							$.getScript( result.javascript.file,
								function(){
									if ( result.javascript.className )
									{
										javascriptStore[li.id] = window[result.javascript.className];
										javascriptStore[li.id].init();
									}
								}
							);
						}
					}
				}
			);
		};
		
		var stepClicked = function(event) {
		
			var weHaveAnActive = 0;
			
			// See if we have an active
			$('#fields').children().each(
				function(index)
				{
					if ( $(this).hasClass('active') )
					{
						weHaveAnActive = 1;
					}
				}
			);
			
			// We dont
			if ( ! weHaveAnActive )
			{
				$('#fields li:first-child').click();
			}
		};
		
		var switchTab = function(event) {
			var menu = event.currentTarget;
			var main = menu.id.replace( '_tab_', '_form_' ),
				id	 = $(menu).parent('ul.trackerMenu').attr('id').replace( '_menu', '' );
			
			// Show it
			$('#' + main).show();
			
			// Hide all content
			$( '#' + id + '_content' ).children().each(
				function(index)
				{
					// This is the content we want, show it!
					if ( $(this).attr('id') != main )
					{
						$(this).hide();
					}
				}
			);
			
			// Sort out buttons
			$( '#' + id + '_menu' ).children().each(
				function(index)
				{
					if ( $(this).hasClass('button') && $(this).attr('rel') != menu.id.replace( /field_(.+?)_tab_/, '') && $(this).attr('rel') != 'all' )
					{
						$(this).hide();
					}
					else
					{
						$(this).show();
					}
					
					$(this).removeClass('active');
				}
			);
			
			$(menu).addClass('active');
		};
		
		var saveField = function(event) {
			var li = activeTab;
			
			/* Custom save */
			if ( typeof( javascriptStore[li] ) != 'undefined' )
			{
				var response = javascriptStore[li].save();
			}
			
			/* STOP! */
			if ( response && $.parseJSON(response).error )
			{
				alert( $.parseJSON(response).message );
				return false;
			}
			
			dataStore[li] = {};
			
			/* Get project keys */
			$( '#' + li + '_content form').each(
				function(index)
				{
					var key = $(this).attr('id').replace( li + '_form_', '' );
					
					/* Set up data store */
					dataStore[li][key] = $(this).serializeObject();
				}
			);
			
			// Were we saving?
			if ( event && event.target.id == 'save_project' )
			{
				$('#save_project').unbind('click', saveField);
				$('#save_project').click( projects.saveProject );
				
				// Send the click
				$('#save_project').click();
			}
		};
		
		var boxChecked = function(event) {

			var elem = event.target;

			if( !elem ){ return; }

			var input = $(elem).children('input')[0];
			if( !input ){ var input = event.target; var elem = $(elem).parent('td'); }
			if( !input ){ return; }

			// If this is on the input itself, lets skip this bit
			if( $(event.target).get(0).tagName != 'INPUT' )
			{
				if( !input.checked ){
					input.checked = true;
				} else {
					input.checked = false;
				}
			}

			if( $(elem).hasClass('column') )
			{
				// Toggle all in this column
				toggleColumn( $(elem).attr('id').replace(permsLi + '_column_', ''), input.checked );
			}
			else
			{
				// See whether we need to uncheck the column header
				if( !input.checked )
				{
					try {
						var colid = $(input).attr('id').replace( /(.+?)_perm_(.+?)_/, '');
						var col = $( '#' + permsLi + '_column_' + colid).children('input')[0];
						col.checked = false;
					} catch(err) { }
				}
			}
		};
		
		var selectColumn = function(event) {
		
			var elem = event.currentTarget;
			if( !elem ){ return; }
			
			// Bug fix
			if ( elem.id.match( '_column_' ) )
			{
				elem = $(elem).find('input');
			}
			
			var parts = $(elem).attr('id').replace( permsLi + '_col_', '');
			
			if ( !parts ){ return; }
			
			var boo = false;
			
			if ( $(elem).checked )
			{
				boo = true;
			}
			
			$( '#' + permsLi + '_perm_matrix input[type=checkbox]' ).each( function( index ){
				if( this.id.match( "^_" + parts + "$" ) )
				{
					this.checked = boo;
				}
			});
		};
		
		var selectRow = function(event) {
		
			var elem = event.currentTarget;
			if( !elem ){ return; }
			
			// Get ID
			var parts = elem.id.replace( permsLi + '_select_row_', '' );
			if( !parts ){ return; }
			
			parts = parts.split( '_' );
			
			if( !$( '#' + permsLi + '_row_' + parts[1]) ){ return; }
			
			$( '#' + permsLi + '_row_' + parts[1] + ' input[type=checkbox]').each( function(index){
				if( parts[0] == 1 ){
					this.checked = true;
				} else {
					this.checked = false;
					
					// If column header is checked, uncheck it
					var tmpid = this.id.replace(/(.+?)_perm_(.+?)_/, '');
					if( $( '#' + permsLi + '_col_' + tmpid) && $( '#' + permsLi + '_col_' + tmpid).checked ){
						$( '#' + permsLi + '_col_' + tmpid).checked = false;
					}
				}
			});
		};
		
		var toggleColumn = function(id, boo) {
			
			$( '#' + permsLi + '_perm_matrix input[type=checkbox]').each( function( index ){
				if( this.id.match( permsLi + "_perm_(.+?)_" + id ) )
				{
					this.checked = boo;
				}
			});
		}
		
		// Public Methods
		return {
			init: init,
			stepClicked: stepClicked,
			dataStore: dataStore
		};
	}($);
	

	// Serialize Object, why isn't it default in jQuery :S
	$.fn.serializeObject = function()
	{
	   var o = {};
	   var a = this.serializeArray();
	   $.each(a, function() {
	       if (o[this.name]) {
	           if (!o[this.name].push) {
	               o[this.name] = [o[this.name]];
	           }
	           o[this.name].push(this.value || '');
	       } else {
	           o[this.name] = this.value || '';
	       }
	   });
	   return o;
	};
	
}(jQuery));

fields.init();