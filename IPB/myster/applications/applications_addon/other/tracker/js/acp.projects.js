/**
* Tracker 2.1.0
* 
* Projects Javascript
* Last Updated: $Date: 2012-07-25 01:31:40 +0100 (Wed, 25 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1382 $
*/
var projects = {};

(function($){
	
	projects = function($){
		var project_id = '';
		
		var init = function(){
			if ( $('#next').size() && $('#quick_done').val() == 0 )
			{
				$('#prev').hide();
				$('#next').click( quickProject );
			}
			
			if ( projects.project_id != 'new' )
			{
				$("#steps_bar").ipsWizard( { allowJumping: true, allowGoBack: true } );
				
				// Observe fields
				$('#steps_fields').click( fields.stepClicked );
							
				// Save button
				$('#save_project').click( saveProject );
				$('#finish').show();
				
				// Hacky attempt to get finish to ALWAYS show
				$('#steps_bar, #next, #prev').click( function(event) {
						$('#finish').show();
					}
				);
			}
			else
			{
				$('#steps_fields').addClass('steps_disabled');
				$('#steps_additional').addClass('steps_disabled');
				$('#steps_perms').addClass('steps_disabled');
			}
		};
		
		var quickProject = function(event){
			if ( projects.project_id == 'new' )
			{
				if ( !$('#title').val() )
				{
					alert("Please enter a project title");
					return;
				}
				
				if ( typeof(isLoading) != 'undefined' && isLoading == 1 )
				{
					return false;
				}
				
				var isLoading = 1,
					element = event.currentTarget,
					category	= 0,
					rss			= 0;
				
				if ( $('#category_yes').checked )
				{
					category = 1;
				}
				
				if ( $('#rss_yes').checked )
				{
					rss = 1;
				}
				
				new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'module=ajax&section=projects&do=createSimple&md5check=' + ipb.vars['md5_hash'],
					{
						method: 'POST',
						parameters: {
							'title': $('#title').val(),
							'description': $('#description').val(),
							'parent_id': $('#parent_id').val(),
							'category': category,
							'rss': rss
						},
						onSuccess: function(s)
						{
							var json = s.responseText.evalJSON();
	
							/* Update form */
							$('#adminform').attr( 'action', ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'module=projects&section=projects&do=edit_save&project_id=' + json.project_id );
							
							$('#steps_fields').removeClass('steps_disabled');
							$('#steps_additional').removeClass('steps_disabled');
							$('#steps_perms').removeClass('steps_disabled');
							
							// Observe fields
							$('#steps_fields').click( fields.stepClicked );
							
							/* Javascript */
							if ( json.javascript )
							{
								for ( file in json.javascript )
								{
									if ( ! json.javascript.hasOwnProperty(file) )
									{
										continue;
									}
									
									var s = document.createElement("script");
									s.type = "text/javascript";
									s.src = json.javascript[file];
									$("head").append(s);
								}
							}
							
							/* New project ID */
							project_id = json.project_id;
							$('#fields').html( json.fields );
							//ACPPF.runEvents();
							
							/* Cancel button */
							if ( $('#cancel').size() )
							{
								$('#cancel').click( deleteHalfProject );
							}
							
							isLoading = 0;
							
							/* Unbind our sneaky stuff and make the user thing nothing happened */
							$('#next').unbind('click', quickProject);
							$("#steps_bar").ipsWizard( { allowJumping: true, allowGoBack: true } );
							$('#next').trigger('click');
							
							// Save button
							$('#save_project').click( saveProject );
						}
					}
				);
				
				return;
			}
		};
		
		var saveProject = function(event) {		
			// Stop defaults
			event.preventDefault();
			event.stopPropagation();
			
			var enableStore = {};
			
			$('.field_enable').each(
				function(index)
				{
					enableStore[this.id] = 0;
					
					if ( this.checked )
					{
						enableStore[this.id] = 1;
					}
				}
			);
			
			$('#enable_data').attr( 'value', Object.toJSON($H(enableStore)) );
			$('#json_data').attr( 'value', Object.toJSON($H(fields.dataStore)) );	
			$('#adminform').submit();
		};
		
		var deleteHalfProject = function(event){
			event.preventDefault();
			
			if( typeof(isLoading) != 'undefined' )
			{
				return false;
			}
			
			// Grab the current element
			var element = event.currentTarget;
			
			// Start our loading process
			var isLoading = 1;
			
			$.ajax(
				{
					url: ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'module=ajax&section=projects&do=deleteSimple&md5check=' + ipb.vars['md5_hash'],
					data: $.param( { 'project_id': project_id } ),
					success: function(){
						window.location = $(element).attr('href');
					}
				}
			);
		};
		
		return {
			init: init,
			project_id: project_id,
			saveProject: saveProject
		}
	}($);
			
}(jQuery));

ACPProjectListing = {

	isCurrentlyDragging: 				0,
	isDraggingSub:						0,
	hidChildOnDrag:						0,
	inDropZone:							0,
	draggingElementCameFromLICount: 	0,
	dropZoneLi:							'',
	draggingElement:					'',
	draggingElementCameFrom:			'',
	alreadyOpen:						'',
	timeout:							'',
	storedDropZones:					{},
	currentDropZones:					{},
	orderOfProjects:					{},
	inAnimation:						{},
	
	init: function()
	{
		document.observe("dom:loaded", function()
			{
				ipb.tracker.hideLoading();
				
				ACPProjectListing.clickedReorderProjects();

				$('recacheProjects').observe( 'click', ACPProjectListing.recacheProjectsClick );

				Draggables.addObserver(
					{
						onEnd: function( eventName, draggable, event )
						{
							ACPProjectListing.isCurrentlyDragging 	= 0;
							ACPProjectListing.isDraggingSub		 	= 0;
							ACPProjectListing.draggingElement 		= '';
							ACPProjectListing.destroySortables();
						}
					}
				);
			}
		);
	},
	
	addDrag: function(event)
	{
		if ( ACPProjectListing.isCurrentlyDragging == 0 )
		{
			ACPProjectListing.draggingElement 			= Event.element(event).up('li');
			ACPProjectListing.draggingElementCameFrom	= ACPProjectListing.draggingElement.up('ul');
			ACPProjectListing.isCurrentlyDragging 		= 1;

			/* Show noSubProject rows where needed */
			$$('.sortable').each(
				function(e)
				{
					var count = 0;
					
					e.childElements().each(
						function(li)
						{
							if ( !li.hasClassName('noSubProjects') )
							{
								count++;
							}
						}
					);
					
					/* Now hide if we have items */
					if ( count > 0 )
					{
						e.down('li.noSubProjects').hide();
					}
					else
					{
						e.down('li.noSubProjects').show();
					}
				}
			);
			
			/* Count */
			ACPProjectListing.draggingElementCameFrom.childElements().each(
				function(e)
				{
					if ( e.hasClassName('isProject') )
					{
						ACPProjectListing.draggingElementCameFromLICount++;
					}
				}
			);

			if ( $( ACPProjectListing.draggingElement.id + '_children' ) && $( ACPProjectListing.draggingElement.id + '_children' ).visible() )
			{
				$( ACPProjectListing.draggingElement.id + '_children' ).hide();
				ACPProjectListing.hidChildOnDrag = 1;
			}
			
			if ( ACPProjectListing.draggingElement.hasClassName('subproject') )
			{
				ACPProjectListing.isDraggingSub = 1;
			}
		}
	},
	
	addDragEvents: function()
	{
		$$('.draghandle').each(
			function(e)
			{
				/* Add new ones */
				e.observe( 'mousedown', ACPProjectListing.addDrag );
				e.observe( 'mouseup', ACPProjectListing.removeDrag );
			}
		);
	},
	
	clickedReorderProjects: function(event)
	{
		/* Show handles */
		$$('.draghandle').each(
			function(e)
			{
				/* Add new ones */
				e.show();
				
				if( e.up('li').down('li.isSubProject') )
				{
					e.up('li').down('li.noSubProjects').hide();
				}
			}
		);
		
		/* Start cool stuff */
		ACPProjectListing.destroySortables();
		ACPProjectListing.addDragEvents();
	},
	
	destroySortables: function()
	{
		var allowedZones = '';
		
		$$('.sortable').each(
			function(handle)
			{
				allowedZones = allowedZones + handle.id + ',';
			}
		);
		
		allowedZones = allowedZones.slice(0, -1);
		allowedZones = allowedZones.split(',');
		
		$$('.sortable').each(
			function(handle)
			{
				Sortable.destroy( handle.id );
				Sortable.create( handle.id, { constraint: '', containment: allowedZones, revert: true, format: 'project_([0-9]+)', handle: 'draghandle' } );
			}
		);

		$$('.noSubProjects').each(
			function(handle)
			{
				handle.hide();
			}
		);
		
		ACPProjectListing.addDragEvents();
	},
	
	hideChildProjects: function(event)
	{
		var elem		= Event.element(event);
		
		var parent		= elem.up('li');
		var divid		= parent.id + '_children';
		
		if ( ACPProjectListing.inAnimation[divid] )
		{
			return false;
		}
		
		/* Update image */
		var newImage	= elem.readAttribute('src').replace( '_open', '_closed' );
		elem.setAttribute( 'src', newImage );
		elem.removeClassName( '_open' );

		if ( $(divid).visible() )
		{
			ACPProjectListing.inAnimation[divid] = 1;
			
			new Effect.BlindUp( $(divid),
				{
					duration: 0.5,
					afterFinish: function()
					{
						ACPProjectListing.inAnimation[divid] = 0;
					}
				}
			);
		}
		
		elem.stopObserving();
		elem.observe( 'click', ACPProjectListing.showChildProjects );
	},
	
	recacheProjectsClick: function(event)
	{
		/* Stop current stuff */
		Event.stop(event);
		var elem = Event.element(event);
		ACPProjectListing.recacheProjects(elem);
	},
	
	recacheProjects: function(elem)
	{
		elem.stopObserving();
		
		/* Switch buttons */
		elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('database_refresh.png', 'loading-green.gif') );
		
		/* Send the data */
		new Ajax.Request(
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=projects&secure_key=' + ipb.vars['md5_hash'] + '&do=recache',
			{
				method: 'get',
				onSuccess: function(s)
				{
					/* Switch buttons */
					elem.up('li').hide();
					$('recacheSuccess').up('li').show();
					
					/* Replace with normal button */
					setTimeout( function()
						{
							$('recacheSuccess').up('li').hide();
							elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'database_refresh.png') );
							elem.up('li').show();
						},
						3000
					);
					
					$('recacheProjects').observe( 'click', ACPProjectListing.recacheProjectsClick );
				}
			}
		);
	},
	
	removeDrag: function(event)
	{
		if ( ACPProjectListing.hidChildOnDrag )
		{
			$( ACPProjectListing.draggingElement.id + '_children' ).show();
			ACPProjectListing.hidChildOnDrag = 0;
		}
		
		ACPProjectListing.draggingElementCameFromLICount--;
		
		var parent		= ACPProjectListing.draggingElement.up('ul');
		var children	= parent.id.replace( '_children', '_nochildren' );
		
		/* This may not be necessary at all anymore... */
		$$('.sortable').each(
			function(e)
			{
				var parent = e.id.replace( '_children', '_nochildren' );
				
				/* Loop through and count how many are there */
				var count = 0;
				
				e.childElements().each(
					function(li)
					{
						if ( !li.hasClassName('noSubProjects') )
						{
							count++;
						}
					}
				);
				
				/* Is this where we just came from? */
				if ( e.id == ACPProjectListing.draggingElementCameFrom.id )
				{
					count--;
				}
				
				$(parent).show();
			}
		);
		
		/* Make sure main always shows */
		$('sortable_handle').show();
		
		ACPProjectListing.isCurrentlyDragging 				= 0;
		ACPProjectListing.isDraggingSub		 				= 0;
		ACPProjectListing.draggingElementCameFromLICount	= 0;
		ACPProjectListing.draggingElement 					= '';
		ACPProjectListing.draggingElementCameFrom			= '';
		
		/* Destroy sortables */
		ACPProjectListing.destroySortables();

		/* Save order */
		ACPProjectListing.saveOrderOfProjects();
	},
	
	saveOrderOfProjects: function()
	{
		/* Do we have data now? */
		ACPProjectListing.sortOrder();
		
		/* Send the data */
		new Ajax.Request(
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=projects&secure_key=' + ipb.vars['md5_hash'] + '&do=reorder',
			{
				method: 'post',
				parameters: {
					'order': Object.toJSON($H(ACPProjectListing.orderOfProjects))
				},
				onSuccess: function(s)
				{
					/* Recache projects for Keith */
					ACPProjectListing.recacheProjects($('recacheProjects'));
				}
			}
		);
		
		$$('li.isProject').each(
			function(e)
			{
				/* Hide any empty categories */
				if ( $(e.id+'_children').down('li.noSubProjects').visible() )
				{
					new Effect.BlindUp( $(e.id+'_children').down('li.noSubProjects').up('ul'), { duration: 0.5 } );
				}
			}
		);
	},
	
	showChildProjects: function(event)
	{
		var elem		= Event.element(event);
		var parent		= elem.up('li');
		var divid		= parent.id + '_children';
		
		if ( ACPProjectListing.inAnimation[divid] )
		{
			return false;
		}
		
		/* Update image */
		var newImage	= elem.readAttribute('src').replace( '_closed', '_open' );
		elem.setAttribute( 'src', newImage );
		elem.addClassName( '_open' );
		
		if ( ! $(divid).visible() )
		{
			ACPProjectListing.inAnimation[divid] = 1;
			
			new Effect.BlindDown( $(divid),
				{
					duration: 0.5,
					afterFinish: function()
					{
						ACPProjectListing.inAnimation[divid] = 0;
					}
				}
			);
		}
		
		elem.stopObserving();
		elem.observe( 'click', ACPProjectListing.hideChildProjects );
	},
	
	sortOrder: function()
	{
		if ( ! ACPProjectListing.isCurrentlyDragging )
		{
			/* Find out our order yo! */
			ACPProjectListing.orderOfProjects = {};
			$('sortable_handle').childElements().each(
				function(e)
				{
					if ( !e.hasClassName('noSubProjects') )
					{
						var projectId	= e.id.replace( 'project_', '' );
						var subProjects	= ACPProjectListing.sortOrderRecursively( e.id + '_children' );
					
						ACPProjectListing.orderOfProjects[projectId] = subProjects;
					}
				}
			);
		}
	},
	
	sortOrderRecursively: function(e)
	{
		if ( ! $(e) )
		{
			return {};
		}
		
		var hash = {};
		
		$(e).childElements().each(
			function(child)
			{
				if ( !child.hasClassName('noSubProjects') )
				{
					var projectId	= child.id.replace( 'project_', '' );
					var subProjects	= ACPProjectListing.sortOrderRecursively( child.id + '_children' );
				
					hash[projectId] = subProjects;
				}
			}
		);
		
		return hash;
	}
};