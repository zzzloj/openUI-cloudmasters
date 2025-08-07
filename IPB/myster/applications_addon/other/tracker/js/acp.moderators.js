/**
* Tracker 2.1.0
* 
* Moderator/Permission Mask Javascript
* Last Updated: $Date: 2012-09-02 21:38:05 +0100 (Sun, 02 Sep 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1384 $
*/

ACPModerators = {
	
	memberOrGroup:	'member',
	permlist:		'',
	inAnimation:	false,
	popup:			{},
	template:		false,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.moderators.js");
		
		document.observe("dom:loaded", function()
			{
				ipb.tracker.hideLoading();
				
				if( $('moderator_name') )
				{
					ACPModerators.autoComplete = new ipb.Autocomplete( $('moderator_name'),
						{
							multibox: false,
							url: acp.autocompleteUrl,
							templates:
							{
								wrap: acp.autocompleteWrap,
								item: acp.autocompleteItem
							}
						}
					);
				}
				
				ipb.delegate.register( '.mg_list', ACPModerators.switchMemberGroup );
				ipb.delegate.register( '.show_popup', ACPModerators.showPopup );
				ipb.delegate.register( '.collapsehandle', ACPModerators.collapseHandle );
				
				/* Super moderator */
				if ( $('is_super_yes') )
				{
					$('is_super_yes').observe( 'click', ACPModerators.isSuperMod );
				}
				
				if ( $('is_super_no') )
				{
					$('is_super_no').observe( 'click', ACPModerators.isNotSuperMod );
				}
				
				if ( $('submit_mod_form') )
				{
					if ( ACPModerators.type == 'add' )
					{
						$('submit_mod_form').observe('click', ACPModerators.submitModAddForm );
					}
					else
					{
						$('submit_mod_form').observe('click', ACPModerators.submitEditForm );
					}
				}
				else if ( $('submit_template_form') )
				{
					if ( ACPModerators.type == 'add' )
					{
						$('submit_template_form').observe('click', ACPModerators.submitTemplateAddForm );
					}
					else
					{
						$('submit_template_form').observe('click', ACPModerators.submitEditForm );
					}
				}
				
				$('adform').observe( 'submit', function(e){ Event.stop(e); } );
				
				/* Populate templates */
				if ( $('populate_form') )
				{
					$('populate_form').observe( 'click', ACPModerators.populateTemplateEvent );
				}
				
				if ( $('use_template_yes') )
				{
					$('use_template_yes').observe( 'click', ACPModerators.showTemplateOptions );
				}
				
				if ( $('use_template_no') )
				{
					$('use_template_no').observe( 'click', ACPModerators.hideTemplateOptions );
				}
			}
		);
	},
	
	showTemplateOptions: function(e)
	{
		/* Remove disabled stuff */
		$$("span.yesno_yes input, span.yesno_no input").each(
			function(input)
			{
				/* Is it a module? */
				var tabID	= $(input).up('ul').up('div').id.replace('tabpane-PROJECTS|',''),
					tab		= $('tabtab-PROJECTS|' + tabID).readAttribute('module');
				
				if ( tab != 'setup' )
				{
					input.disabled = 1;
				}
			}
		);
		
		/* Update statuses of the elements please! */
		$('moderator_form_menu').childElements().each(
			function(tab)
			{
				tab.removeClassName('modified');
			}
		);
		
		ACPModerators.templateDDChanged(e);
		
		/* Instant reload so we can't fiddle with templates */
		ACPModerators.populateTemplate($('populate_form'));
	},
	
	hideTemplateOptions: function(e)
	{
		/* Remove disabled stuff */
		$$("span.yesno_yes input, span.yesno_no input").each(
			function(input)
			{
				input.disabled = 0;
				input.up('li').removeClassName('changed');
			}
		);
		
		/* Update statuses of the elements please! */
		$('moderator_form_menu').childElements().each(
			function(tab)
			{
				tab.removeClassName('modified');
			}
		);
	},
	
	collapseHandle: function(e, elem)
	{
		/* Is it already loading, thats just silly */
		if ( elem.readAttribute('src').match('loading.gif') || ACPModerators.inAnimation )
		{
			return;
		}
		
		/* Instead of writing two functions, be a clever Alex and write one! */
		if ( elem.readAttribute('src').match('folder_open.png') )
		{
			ACPModerators.inAnimation = true;
			
			new Effect.BlindUp( elem.up('li').down('ul.alternate_rows'),
				{
					duration: 0.5,
					afterFinish: function()
					{
						/* Remove animation */
						ACPModerators.inAnimation = false;
						
						elem.writeAttribute( 'src', elem.readAttribute('src').replace('folder_open.png', 'folder_closed.png') );
					}
				}
			);
			return;
		}

		/* Have we already made a request, bug fix FTW */
		if ( elem.readAttribute('src').match('folder_closed') && elem.up('li').down('ul.alternate_rows') )
		{
			ACPModerators.inAnimation = true;
			
			/* We already got the content previously, just show it again! */
			new Effect.BlindDown( elem.up('li').down('ul.alternate_rows'),
				{
					duration: 0.5,
					afterFinish: function()
					{
						/* Remove animation */
						ACPModerators.inAnimation = false;
						
						elem.writeAttribute( 'src', elem.readAttribute('src').replace('folder_closed.png', 'folder_open.png') );
					}
				}
			);
			return;
		}
		
		/* Show the loading screen */
		elem.writeAttribute( 'src', elem.readAttribute('src').replace('folder_closed.png', 'loading.gif') );
		
		/* Send the request */
		new Ajax.Request( ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=moderators&do=getProjects&secure_key=' + ipb.vars['md5_hash'],
			{
				method: 'POST',
				parameters: {
					'mg_id': elem.up('li').id.replace( 'moderator_group_', '' )
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						elem.writeAttribute( 'src', elem.readAttribute('src').replace('loading.gif', 'folder_closed.png') );
						return;
					}
					
					/* Create a list! */
					var weNeedAListLikezNow	= new Element( 'ul' ).setStyle('margin-left:5%;').addClassName('alternate_rows collapsed_moderator').update( s.responseText.evalJSON().content ).hide();
					
					/* Add it, please work, I have no idea if this will :( */
					elem.up('li').insert( { bottom: weNeedAListLikezNow } );
					ACPModerators.inAnimation = true;
					
					/* Show it */
					new Effect.BlindDown( weNeedAListLikezNow,
						{
							duration: 0.5,
							afterFinish: function()
							{
								/* Remove animation */
								ACPModerators.inAnimation = false;
								
								/* Switch images back, we are done! */
								elem.writeAttribute( 'src', elem.readAttribute('src').replace('loading.gif', 'folder_open.png') );
							}
						}
					);
				}
			}
		);
	},
	
	populateTemplateEvent: function(e)
	{
		var anchor = Event.element(e);
		
		/* Weird bug, its not giving me a correct element each time, depends where you click o_0
		 * Hacked patch below
		 */
		if ( anchor.id != 'populate_form' )
		{
			anchor = anchor.up('#populate_form');
		}
		
		ACPModerators.populateTemplate(anchor);
	},
	
	populateTemplate: function(anchor)
	{
		anchor.down('img').writeAttribute( 'src', anchor.down('img').readAttribute('src').replace('table_go.png', 'loading.gif') );
		$('template_id').writeAttribute('disabled','disabled');
		
		/* Find out what the template allows */
		new Ajax.Request( ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=moderators&do=templateData&secure_key=' + ipb.vars['md5_hash'],
			{
				method: 'GET',
				parameters: {
					'template_id': $F('template_id')
				},
				onSuccess: function(s)
				{
					var changedModules = {};
					
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						elem.writeAttribute( 'src', elem.readAttribute('src').replace('loading.gif', 'folder_closed.png') );
						return;
					}
					
					var data = s.responseText.evalJSON();
					
					$H(data.data).each(
						function(id)
						{
							if ( $(id.key+'_yes') && $(id.key+'_no') )
							{
								if ( id.value == 1 )
								{
									$(id.key+'_yes').up('tr').addClassName('changed');
									$(id.key+'_yes').checked	= 1;
									$(id.key+'_no').checked		= 0;
								}
								else
								{
									$(id.key+'_yes').up('tr').removeClassName('changed');
									$(id.key+'_no').checked		= 1;
									$(id.key+'_yes').checked	= 0;
								}
								
								/* Where did we come from? */
								var tabID	= $(id.key+'_yes').up('table').up('div').id.replace('tabpane-PROJECTS|',''),
									tab		= $('tabtab-PROJECTS|' + tabID).readAttribute('module');
								
								if ( id.value == 1 )
								{
									changedModules[tab] = 1;
								}
							}
						}
					);
					
					/* Update statuses of the elements please! */
					var storedClicks = {};
					
					$('moderator_form_menu').childElements().each(
						function(e)
						{
							e.removeClassName('modified');
							
							if ( typeof(changedModules[e.readAttribute('module')]) != 'undefined' )
							{
								e.addClassName('modified');
							}
							
							e.stopObserving();
							
							/* Override default tabs */
							e.observe( 'click', function(event)
								{
									e.removeClassName('modified');
									
									/* Remove all activeness */
									$$('ul.form_menu li.active').each(
										function(tab)
										{
											tab.removeClassName('active');
											
											$('tabpane-PROJECTS|' + tab.id.replace('tabtab-PROJECTS|', '')).hide();
										}
									);
									
									var removeId	= e.id.replace('tabtab-PROJECTS|', '');
									
									/* Remove previous marks */
									if( storedClicks[removeId] == 1 )
									{
										/* Remove disabled stuff */
										$$('.changed').each(
											function(input)
											{
												if ( input.up('ul').up('div').id.replace( 'tabpane-PROJECTS|', '' ) == removeId )
												{
													input.removeClassName('changed');
												}
											}
										);
									}
									
									e.addClassName('active');
									$('tabpane-PROJECTS|' + e.id.replace('tabtab-PROJECTS|', '')).show();
									
									/* Store the click */
									storedClicks[removeId] = 1;
								}
							);
						}
					);
					
					/* Update our images */
					anchor.down('img').writeAttribute( 'src', anchor.down('img').readAttribute('src').replace('loading.gif', 'accept.png') );
					anchor.stopObserving();
					anchor.setStyle('cursor:default;');
					$('template_id').writeAttribute('disabled',null);
					
					/* Add a handler */
					$('template_id').observe( 'change', ACPModerators.templateDDChanged );
				}
			}
		);
	},
	
	templateDDChanged: function(e)
	{
		$('populate_form').down('img').writeAttribute( 'src', $('populate_form').down('img').readAttribute('src').replace('accept.png', 'table_go.png') );
		$('populate_form').setStyle('cursor:pointer;');
		$('populate_form').observe( 'click', ACPModerators.populateTemplateEvent );
	},
	
	submitEditForm: function(e)
	{
		/* Send original form */
		document.adform.submit();
		return;
	},
	
	submitModAddForm: function(e)
	{
		var anchor	= Event.element(e),
			data	= {
						'type': ACPModerators.memberOrGroup
					},
			errors	= {};
		
		/* Weird bug, its not giving me a correct element each time, depends where you click o_0
		 * Hacked patch below
		 */
		if ( anchor.id != 'submit_mod_form' )
		{
			anchor = anchor.up('#submit_mod_form');
		}
		
		/* Did we select any projects? */
		if ( $('projectsData') && $F('projectsData') == '' )
		{
			errors['no_projects']	= 'Please select projects for this moderator to moderate';
		}
		
		/* Did we input a name? */
		if ( data.type == 'member' && $('moderator_name') && $F('moderator_name') == '' )
		{
			errors['no_member'] 	= 'Please select a member';
		}
		
		if ( ACPModerators.showErrors(errors) )
		{
			return;
		}
		
		/* Projects */
		if ( $('projectsData') )
		{
			data.projects = $A( $F('projectsData') ).toArray();
		}
		
		/* Member ID + Group ID */
		if ( data.type == 'member' )
		{
			data.member_id	= $F('moderator_name');
		}
		else
		{
			data.group_id	= $F('group_id');
		}
		
		/* Update image */
		anchor.down('img').setAttribute( 'src', anchor.down('img').readAttribute('src').replace('accept.png', 'loading-green.gif') );
		
		/* AJAX DA CONFLICT BLED */
		new Ajax.Request( ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=moderators&do=conflicts&secure_key=' + ipb.vars['md5_hash'],
			{
				type: 'POST',
				parameters: {
					'data': Object.toJSON($H(data))
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() )
					{
						var sessTimeout = new ipb.tracker.popup( 'session-timeout', { width: 60, height: 15, initial: "<p>Your Admin CP session has expired, please log back in.</p><p style='margin-top: 14px; text-align:center; font-weight: bold;'><img src='"+ipb.vars['image_url']+"loading.gif' alt='Redirecting' />&nbsp;Redirecting you to login screen</p>", title: 'Session Timed-Out' } );
						
						/* Redirect to login screen, which will just be our URL again */
						setTimeout( function(){location.reload(true);}, 2000 );
						return;
					}
					
					/* Get results */
					results = s.responseText.evalJSON();
					
					// missed inputs
					if ( results.error )
					{
						var error = new ipb.tracker.popup( 'mod-error', { width: 60, height: 15, initial: "<p>Please make sure you fill in all parts of the form.</p>", title: 'Complete Form' } );
					}
					
					/* Did we pass? */
					if ( results.result == 'ok' )
					{
						/* Set project data */
						$('projects_input').writeAttribute( 'value', $F('projectsData') );
						
						/* Send original form */
						document.adform.submit();
						return;
					}
					
					var projects = "";
					
					/* We errored out on conflicts, show popup */
					for( i in results.projects )
					{
						if ( !results.projects.hasOwnProperty(i) )
						{
							continue;
						}
						
						projects += "<li>" + results.projects[i] + "</li>";
					}
					
					var content = ACPModerators.conflictHTML.evaluate(
						{
							'projects': projects
						}
					);
					
					var weHasConflicts = new ipb.tracker.popup( 'conflicts-detail', { width: 60, height: 30, initial: content, title: 'Projects Conflict, Cannot Continue!' } );
					
					/* Observe buttons */
					$('continueConflicts').observe( 'click', function()
						{
							/* Set project data */
							$('projects_input').writeAttribute( 'value', $F('projectsData') );
						
							document.adform.submit();
							return;
						}
					);
					
					$('cancelConflicts').observe( 'click', function()
						{
							weHasConflicts.hide();
						}
					);
					
					/* Errors, so change image back */
					anchor.down('img').setAttribute( 'src', anchor.down('img').readAttribute('src').replace('loading-green.gif', 'accept.png') );
				}
			}
		);
	},
	
	submitTemplateAddForm: function(e)
	{
		var anchor	= Event.element(e),
			errors	= {};
		
		/* Weird bug, its not giving me a correct element each time, depends where you click o_0
		 * Hacked patch below
		 */
		if ( anchor.id != 'submit_template_form' )
		{
			anchor = anchor.up('#submit_template_form');
		}
		
		if ( $F('template_name') == '' )
		{
			errors['no_name']	= 'Please enter a template name.';
		}
		
		if ( ACPModerators.showErrors(errors) )
		{
			return;
		}
		
		document.adform.submit();
		return;
	},
	
	showErrors: function(errors)
	{
		var initialHeight = 10;
		
		if ( $H(errors).size() > 0 )
		{
			initialHeight = initialHeight + ( 3 * $H(errors).size() );
			var errorText = "<p>The following errors occurred:</p><ul class='errors'>";
			
			$H(errors).each(
				function(e)
				{
					errorText += "<li>" + e.value + "</li>";
				}
			);
			
			errorText += "</ul>";
			
			var weHaveErrorsOhNo	= new ipb.tracker.popup( 'conflicts', { width: 60, height: initialHeight, initial: errorText, title: 'Incomplete Form!' } );
			
			return true;
		}
		
		return false;
	},
	
	showPopup: function(e)
	{
		Event.stop(e);
		
		var anchor			= Event.element(e);
		var popup_loading	= new ipb.tracker.popup( 'permissions-loading',
			{
				width: 60,
				height: 10,
				title: 'Loading Permissions... Please wait.',
				initial: "<p style='text-align: center; padding: 0;'><img src='"+ipb.vars['image_url']+"loading.gif' alt='Redirecting' /></p>",
				className: 'no_overflow',
				close: false
			}
		);
		
		new Ajax.Request( ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=moderators&do=permissions&secure_key=' + ipb.vars['md5_hash'],
			{
				method: 'GET',
				parameters: {
					'moderate_id': anchor.id.replace( 'show_popup_', '' ),
					'template':	ACPModerators.template
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						/* Destroy old one */
						popup_loading.destroy();
						
						var oops = new ipb.tracker.popup( 'oops', { width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						elem.writeAttribute( 'src', elem.readAttribute('src').replace('loading.gif', 'folder_closed.png') );
						return;
					}
					
					/* Loop through JSON */
					var json = s.responseText.evalJSON();
					
					/* Loop through data */
					if ( typeof(ACPModerators.popup[anchor.id]) != 'undefined' )
					{
						ACPModerators.popup[anchor.id].destroy();
					}
					
					ACPModerators.popup[anchor.id] = new ipb.tracker.popup( 'permissions-' + anchor.id, { title: 'Viewing Permissions for ' + json.name, initial: '', hide: true } );
					
					/* Make HTMLs */
					switch( ACPModerators.template )
					{
						case true:
							var summary		= new Element( 'strong' ).update( json.name + ' gives moderators using this template the following privileges.' );
						break;
						
						case false:
						default:
							var summary		= new Element( 'strong' ).update( json.name + ' has the following moderator privileges in this project.' );
						break;
					}
					
					$('permissions-'+anchor.id+'_tform').insert(summary);
					
					/* What modules have we made already? */
					var madeModules	= {},
						count		= 0;
					
					$H(json.data).each(
						function(k)
						{
							/* We made this module before? */
							if ( typeof(madeModules[k.value.module]) == 'undefined' )
							{
								count++;
								
								if ( count == 1 )
								{
									ACPModerators.permlist = new Element( 'ul' ).addClassName('permissions');
									$('permissions-'+anchor.id+'_tform').insert(ACPModerators.permlist);
								}
								else if ( count == 3 )
								{
									var clear 	= "<br class='clear' />";
									count = 0;
								}
									
								/* Need to clear? */
								if ( count == 0 )
								{
									ACPModerators.permlist.insert( { after: clear } );
								}
								
								/* Create a list */
								var moduleList	= new Element('li').addClassName( 'cat ' + k.value.module + '_perms' ),
									h4			= new Element('h4'),
									ul			= new Element('ul', { id: k.value.module + '_list' });
								
								if ( k.value.module == 'core' )
								{
									h4.update( 'Main Permissions' );
								}
								else
								{
									h4.update( 'Field: ' + k.value.title + ' Permissions' );
								}
								
								moduleList.insert(h4);
								moduleList.insert(ul);
								
								ACPModerators.permlist.insert(moduleList);
								
								madeModules[k.value.module] = ul;
							}
							
							/* Create the entry */
							var entry 	= new Element('li');
							
							if ( typeof(json.perm_map[k.key]) != 'undefined' )
							{
								entry.update( json.perm_map[k.key].replace('[b]','<strong>').replace('[/b]','</strong>') );
							}
							else
							{
								entry.update( k.key );
							}
							
							/* Is it a valid perm for this user? */
							if ( ! k.value.field || parseInt(k.value.field) == 0 )
							{
								entry.addClassName('no_perm');
							}
							
							madeModules[k.value.module].insert(entry);
						}
					);
					
					// I wouldn't uncomment if I were you.
					//$('permissions-'+anchor.id+'_tform').insert( s.responseText );
					
					/* Destroy old one */
					popup_loading.destroy();
					ACPModerators.popup[anchor.id].show();
				}
			}
		);
	},
	
	isNotSuperMod: function(e)
	{
		var elem = Event.element(e);
		
		elem.next().removeClassName('disabled');
		$('projectsData').writeAttribute('disabled', null);
		
		/* Remove all selects */
		$('projectsData').childElements().each(
			function(s)
			{
				s.writeAttribute('selected', null);
			}
		);
	},
	
	isSuperMod: function(e)
	{
		var elem = Event.element(e);
		
		elem.next().addClassName('disabled');
		$('projectsData').writeAttribute('disabled', 'disabled');
		
		/* Select All */
		$('projectsData').childElements().each(
			function(s)
			{
				s.writeAttribute('selected', 'selected');
			}
		);
	},
	
	switchMemberGroup: function(e, elem)
	{
		if ( ! elem )
		{
			return;
		}
		
		/* Add active to class and remove from others */
		$('mg_list').childElements().each(
			function(child)
			{
				child.removeClassName('active');
				child.down('input').writeAttribute('checked', null);
			}
		);
		
		elem.addClassName('active');
		elem.down('input').writeAttribute('checked', 'checked');
		
		/* Save the data */
		ACPModerators.memberOrGroup = elem.id.replace('li_', '');
		
		/* Show correct list items */
		if ( elem.id == 'li_member' )
		{
			$('form_group').hide();
			$('form_member').show();
			$('form_group_title').hide();
			$('form_member_title').show();
		}
		else
		{
			$('form_group').show();
			$('form_member').hide();
			$('form_group_title').show();
			$('form_member_title').hide();
		}
	}
};

ACPModerators.init();

//ipb.delegate.register( '.delete_mod', deleteConfirm );

function deleteConfirm(e, elem)
{
	if( ! confirm( 'Are you sure you want to delete this moderator?' ) )
	{
		Event.stop(e);
	}
};