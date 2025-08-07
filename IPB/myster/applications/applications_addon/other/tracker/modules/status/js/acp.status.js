/**
* Tracker 2.1.0
*	- IPS Community Project Developers
*		- Javascript written by Alex Hobbs
* 
* Fields Javascript
* Last Updated: $Date: 2012-07-25 01:31:40 +0100 (Wed, 25 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2009 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1382 $
*/
ipb.vars['loading_img'] 		= ipb.vars['image_url'] + 'loading.gif';
var isLoading = 0;

ACPMStatus = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.status.js");
		
		document.observe( 'dom:loaded', function()
			{
				Sortable.create( 'status', { tag: 'li', only: 'isDraggable', revert: true, format: 'status_([0-9]+)', onUpdate: ACPMStatus.dropItLikeItsHot, handle: 'draghandle' } );
		
				$('recacheStatuses').observe( 'click', ACPMStatus.recacheStatusClick );
				ipb.delegate.register( '.add_status', ACPMStatus.addStatus );
				ipb.delegate.register( '.edit_status', ACPMStatus.editStatus );
				ipb.delegate.register( '.delete_status', ACPMStatus.deleteStatus );
				
				/* Default Status */
				$$('.default_status').each(
					function(input)
					{
						input.observe('click', ACPMStatus.setDefault );
					}
				);
			}
		);
	},
	
	setDefault: function(event)
	{
		var elem	= Event.element(event),
			id		= elem.up('li').id.replace('status_','');
			
		if ( !id )
		{
			return false;
		}
		
		if ( isLoading == 1 )
		{
			elem.checked = false;
			return false;
		}
		
		/* Stop observing */
		elem.stopObserving();
		
		isLoading = 1;
		
		var oldData	= elem.up('td').innerHTML,
			td		= elem.up('td');
		td.update( "<img src='" + ipb.vars['loading_img'] + "' alt='' />" );
		
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=default&md5check=' + ipb.vars['md5_hash'],
			{
				method: 'POST',
				parameters: {
					'id': id
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						td.update( oldData );
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					td.update( oldData );
					td.down('input').checked = true;
					isLoading = 0;
					
					/* Reobserve */
					td.down('input').observe( 'click', ACPMStatus.setDefault );
				}
			}
		);
	},
	
	addStatus: function(event)
	{
		ACPMStatus.popup = new ipb.tracker.popup( 'statusForm',
			{
				width: 70,
				height: 75,
				initial: ACPMStatus.addStatusHTML.evaluate(
					{
						'random':		Math.floor(Math.random()*101)
					}
				),
				title: 'Add Status',
				className: 'light'
			}
		);

		ipb.textEditor.initialize( $('editor_name').innerHTML,
			{
				type: 'mini',
				height: 150,
				minimize: 0,
				bypassCKEditor: 0,
				delayInit: 0,
				isRte: 1,
				isTypingCallBack: 0,
				ips_AutoSaveKey: '',
				ips_AutoSaveData: ''
			}
		);
		
		ACPMStatus.popup.addCloseEvent( ACPMStatus.displaySaveChanges );
		ACPMStatus.formType = 'add';
		
		ACPMStatus.startFormObserving();
	},
	
	editStatus: function(event)
	{
		var element		= Event.element(event),
			statusId	= element.id.replace('edit_',''),
			loading		= ipb.tracker.loadingPopup();
			
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=load&md5check=' + ipb.vars['md5_hash'],
			{
				method: 'GET',
				parameters: {
					'id':	statusId
				},
				onSuccess: function(s)
				{
					loading.destroy();
					
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					var json = s.responseText.evalJSON();
					
					/* Work out certain settings */
					if ( json.closed == 1 )
					{
						var closed_yes 		= "checked='checked'",
							closed_no  		= '',
							allownew_yes	= '',
							allownew_no		= "checked='checked'";
					}
					else if ( json.allow_new == 1 )
					{
						var closed_yes 		= '',
							closed_no  		= "checked='checked'",
							allownew_yes	= "checked='checked'",
							allownew_no		= '';
					}
					else
					{
						var closed_yes 		= '',
							closed_no  		= "checked='checked'",
							allownew_yes	= '',
							allownew_no		= "checked='checked'";
					}
					
					ACPMStatus.popup = new ipb.tracker.popup( 'statusForm',
						{
							width: 70,
							height: 75,
							initial: ACPMStatus.addStatusHTML.evaluate(
								{
									'title': 		json.title,
									'allownew_yes':	allownew_yes,
									'allownew_no':	allownew_no,
									'closed_yes':	closed_yes,
									'closed_no':	closed_no,
									'reply_text':	json.reply_text,
									'id':			json.status_id,
									'random':		Math.floor(Math.random()*101)
								}
							),
							title: 'Edit Status',
							className: 'light'
						}
					);
					
					// Set reply text
					$($('editor_name').innerHTML).setValue( json.reply_text );

					ipb.textEditor.initialize( $('editor_name').innerHTML,
						{
							type: 'mini',
							height: 150,
							minimize: 0,
							bypassCKEditor: 0,
							delayInit: 0,
							isRte: 1,
							isTypingCallBack: 0,
							ips_AutoSaveKey: '',
							ips_AutoSaveData: ''
						}
					);
					
					ACPMStatus.popup.addCloseEvent( ACPMStatus.displaySaveChanges );
					ACPMStatus.startFormObserving();
					ACPMStatus.formType = 'edit';
					
					$('save_status').observe('click', ACPMStatus.saveStatus );
					$('save_status').removeClassName('disabled');
				}
			}
		);
	},
	
	deleteStatus: function(event)
	{
		var element		= Event.element(event),
			statusId	= element.id.replace('delete_',''),
			loading		= ipb.tracker.loadingPopup();
			
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=load&dropdown=1&md5check=' + ipb.vars['md5_hash'],
			{
				method: 'GET',
				parameters: {
					'id':	statusId
				},
				onSuccess: function(s)
				{
					loading.destroy();
					
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					var json = s.responseText.evalJSON();
					
					ACPMStatus.popup = new ipb.tracker.popup( 'deleteForm',
						{
							width: 60,
							height: 25,
							initial: ACPMStatus.deleteStatusHTML.evaluate(
								{
									'title': 		json.title,
									'id':			json.status_id,
									'options':		json.options,
									'text':			json.text
								}
							),
							title: 'Delete Status'
						}
					);
					
					if ( json.type && json.type == 'no_issues' )
					{
						$('move_to_new_status').hide();
						ACPMStatus.no_issues = 1;
					}
					
					$('delete_close').observe( 'click', function(){ACPMStatus.popup.hide();} );
					$('delete_status_go').observe( 'click', ACPMStatus.doDelete );
				}
			}
		);
	},
	
	doDelete: function(event)
	{
		if ( ! confirm('Are you sure you want to delete this status? There is no going back.') )
		{
			return false;
		}
		
		if ( $('move_to_new_status').getValue() == '-1'  && ! ACPMStatus.no_issues )
		{
			alert( 'Please select a status to move existing issues to.' );
			return;
		}
		
		var popup = ipb.tracker.loadingPopup('info');
		
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=delete&md5check=' + ipb.vars['md5_hash'],
			{
				method: 'POST',
				parameters: {
					'id': Event.element(event).readAttribute('rel'),
					'move': $('move_to_new_status').getValue()
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						popup.destroy();
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					new Effect.Fade( $('status_' + Event.element(event).readAttribute('rel') ),
						{
							duration: 0.25,
							delay: 0.25,
							afterFinish: function()
							{
								$('status_' + Event.element(event).readAttribute('rel') ).remove();
							}
						}
					);
					
					popup.destroy();
					ACPMStatus.popup.hide();
					ipb.tracker.adminColorRows();
					
					Sortable.destroy('status');
					Sortable.create( 'status', { tag: 'li', only: 'isDraggable', revert: true, format: 'status_([0-9]+)', onUpdate: ACPMStatus.dropItLikeItsHot, handle: 'draghandle' } );
				}
			}
		);
	},
	
	displaySaveChanges: function(event)
	{
		if ( confirm('Are you sure you want to close this form? You will lose any unsaved changes.') )
		{
			ACPMStatus.popup.hide();
			ACPMStatus.popup.destroy();
		}
	},
	
	saveStatus: function(event)
	{
		var popup = ipb.tracker.loadingPopup('info');
		
		/* Allow new */
		if ( $('allownew_yes').checked )
		{
			var allow_new = 1;
		}
		else
		{
			var allow_new = 0;
		}
		
		/* Closed */
		if ( $('closed_yes').checked )
		{
			var closed = 1;
		}
		else
		{
			var closed = 0;
		}
		
		/* Get position */
		var count = 1;
		
		$('status').childElements().each(
			function(li)
			{
				if ( li.hasClassName('isDraggable') )
				{
					count++;
				}
			}
		);
			
		var data = {
			'title': 		$('status_title').getValue(),
			'allow_new':	allow_new,
			'closed':		closed
		};
		
		if ( ACPMStatus.formType == 'add' )
		{
			data['position'] = count;
		}
		else
		{
			data['id'] = $('status_id').innerHTML;
		}
		
		var editor = ipb.textEditor.getEditor( ipb.textEditor.lastSetUp );
		
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=' + ACPMStatus.formType + '&md5check=' + ipb.vars['md5_hash'], 
			{
				method: 'POST',
				parameters: {
					data: Object.toJSON($H(data)),
					'Post': editor.getText()
				},
				onSuccess: function(s)
				{
					popup.destroy();
					
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					var json = s.responseText.evalJSON();
					
					if ( ACPMStatus.formType == 'add' )
					{
						var isDefault	= '',
							isDisabled	= '';
						
						if ( $('no_status') && $('no_status').visible() )
						{
							isDefault = "checked='checked'";
							$('no_status').hide();
						}
						
						if ( ! json.allow_new )
						{
							isDisabled = "disabled='disabled'";
						}
						
						var li = new Element('li', { 'id': 'status_' + json.statusID } ).addClassName('isDraggable');
						li.hide();
						li.update(
							ACPMStatus.statusRow.evaluate(
								{
									'id':			json.statusID,
									'title': 		$('status_title').getValue(),
									'autoreply': 	json.autoreply,
									'closed':		json.closed,
									'default':		isDefault,
									'disabled':		isDisabled
								}
							)
						);
						
						$('status').insert(li);
						
						new Effect.Appear( li, { duration: 0.25, delay: 0.25 } );
					}
					else
					{
						var isDefault = '';
						
						if ( json.default_open )
						{
							isDefault = "checked='checked'";
						}
						
						$('status_'+data['id']).update(
							ACPMStatus.statusRow.evaluate(
								{
									'id':			json.statusID,
									'title': 		$('status_title').getValue(),
									'autoreply': 	json.autoreply,
									'closed':		json.closed,
									'default':		isDefault
								}
							)
						);
						
						new Effect.Pulsate( $('status_'+data['id']) , { delay: 0.25 } );
					}
					
					ACPMStatus.popup.hide();
					ACPMStatus.popup.destroy();
					ipb.tracker.adminColorRows();
					
					// Destroy editor
					editor.remove();
					
					/* Default Status */
					$$('.default_status').each(
						function(input)
						{
							input.observe('click', ACPMStatus.setDefault );
						}
					);
					
					Sortable.destroy('status');
					Sortable.create( 'status', { tag: 'li', only: 'isDraggable', revert: true, format: 'status_([0-9]+)', onUpdate: ACPMStatus.dropItLikeItsHot, handle: 'draghandle' } );
				}
			}
		);
	},
	
	dropItLikeItsHot: function( draggableObject, mouseObject )
	{
		var options = {
						method : 'post',
						parameters : Sortable.serialize( 'status', { tag: 'li', name: 'status' } )
					};
	 
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'app=tracker&module=ajax&section=settings&component=status&file=admin&do=reorder&secure_key=' + ipb.vars['md5_hash'], options );
		
		/* Color rows */
		ipb.tracker.adminColorRows();
		
		ACPMStatus.recacheStatuses($('recacheStatuses'));
	
		return false;
	},
	
	recacheStatusClick: function(e)
	{
		elem = Event.element(e);
		ACPMStatus.recacheStatuses(elem);
	},
	
	recacheStatuses: function(elem)
	{
		if ( ipb.tracker.isLoading )
		{
			return false;
		}
		
		ipb.tracker.isLoading = 1;
		
		/* Switch buttons */
		elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('database_refresh.png', 'loading-green.gif') );
		
		/* Send the data */
		new Ajax.Request(
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=settings&component=status&file=admin&secure_key=' + ipb.vars['md5_hash'] + '&do=recache',
			{
				method: 'get',
				onSuccess: function(s)
				{
					/* Switch buttons */
					$('recacheStatuses').hide();
					$('recacheSuccess').up('li').show();
					
					/* Replace with normal button */
					setTimeout( function()
						{
							$('recacheSuccess').up('li').hide();
							elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'database_refresh.png') );
							$('recacheStatuses').show();
							ipb.tracker.isLoading = 0;
						},
						500
					);

					$('recacheStatuses').observe( 'click', ACPMStatus.recacheStatusClick );
				}
			}
		);
	},
	
	startFormObserving: function()
	{
		$('status_title').observe( 'keyup', function(e)
			{
				if ( !$('status_title').getValue().strip() )
				{
					ACPMStatus.enteredTitleText = 0;
					$('save_status').addClassName('disabled');
					$('save_status').stopObserving();
				}
				else if ( ACPMStatus.clickedAllowNew && ACPMStatus.clickedDefault && ACPMStatus.clickedClosed )
				{
					$('save_status').observe('click', ACPMStatus.saveStatus );
					$('save_status').removeClassName('disabled');
				}
				else
				{
					ACPMStatus.enteredTitleText = 1;
				}
			}
		);
		
		['allownew_yes','allownew_no','closed_yes','closed_no'].each(
			function(e)
			{
				$(e).observe('click', function(event)
					{
						if ( e == 'allownew_yes' || e == 'allownew_no' )
						{
							ACPMStatus.clickedAllowNew = 1;
							
							if ( $('allownew_yes').checked )
							{
								ACPMStatus.clickedClosed = 1;
								
								$('closed_yes').checked = null;
								$('closed_no').checked = 'checked';
							}
						}
						else
						{
							ACPMStatus.clickedClosed = 1;
							
							/* Can't have closed and allow=yes and default=yes */
							if ( $('closed_yes').checked )
							{
								ACPMStatus.clickedAllowNew = 1;
								
								$('allownew_yes').checked = null;
								$('allownew_no').checked = 'checked';
							}
						}
						
						/* Check overall progress */
						if ( ACPMStatus.enteredTitleText && ACPMStatus.clickedAllowNew && ACPMStatus.clickedClosed )
						{
							$('save_status').observe('click', ACPMStatus.saveStatus );
							$('save_status').removeClassName('disabled');
						}
					}
				);
			}
		);
	}
};

ACPMStatus.init();