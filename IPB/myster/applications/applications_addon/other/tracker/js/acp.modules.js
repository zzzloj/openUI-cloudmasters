/**
* Tracker 2.1.0
* 
* Modules Javascript
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

ACPModules = {

	step: 					0,
	totalSteps: 			0,
	inError: 				0,
	inInstall:				0,
	queueCount:				0,
	
	installingElement:		'',
	status: 				'',
	url: 					'',
	module: 				'',
	newProgress:			'',
	stepStatus: 			'',
	moduleData:				'',
	requestType:			'install',
	
	/* Old HTML storage */
	oldHTML: 				{},
	queuedItems:			{},
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.modules.js");
		
		document.observe( 'dom:loaded', function()
			{
				$$('.install').each(
					function(e)
					{
						e.observe( 'click', ACPModules.installModuleYesNo );
					}
				);
			}
		);
	},
	
	installModuleYesNo: function(e)
	{
		element = Event.element(event);
		li 		= element.up('li.isDraggable');
		
		/* Save current HTML */
		ACPModules.oldHTML[li.id] = li.innerHTML;
		
		/* New HTML */
		li.update( ACPModules.confirmInstallHTML.evaluate(
			{
				id: li.id,
				name: element.up('tr').down('div.title strong').innerHTML
			}
		) );
		
		/* Events */
		$( li.id + '-yes' ).observe( 'click', ACPModules.beginInstallation );
		$( li.id + '-no' ).observe( 'click', ACPModules.cancelInstallation );
	},
	
	beginInstallation: function(event)
	{
		element = Event.element(event);
		li 		= element.up('li');
		
		if ( ACPModules.inInstall )
		{
			ACPModules.queueInstall(li);
			return;
		}
		
		ACPModules.doingInstall(li);
	},
	
	cancelInstallation: function(event)
	{
		element = Event.element(event);
		li 		= element.up('li');
		li.hide();
		
		/* Update HTML backup */
		li.update( ACPModules.oldHTML[li.id] );
		
		/* Move back to available */
		$('available_modules').insert(li);
		li.show();
		
		/* Update counters */
		ACPModules.installedLICount--;
		ACPModules.availableLICount++;
		
		/* Show correct list items */
		if ( $('noAvailableModules').visible() )
		{
			$('noAvailableModules').hide();
		}
		
		if ( ACPModules.installedLICount == 0 && !$('noInstalledModules').visible() )
		{
			$('noInstalledModules').show();
		}
	},
	
	doingInstall: function(li)
	{
		/* Installing one */
		ACPModules.installingElement	= li;
		ACPModules.inInstall			= 1;
		
		/* Installation HTML */
		li.update(
			ACPModules.installationHTML.evaluate(
				{
					'id': li.id
				}
			)
		);
		
		/* Start our while variables */
		ACPModules.status 		= '';
		ACPModules.url			= '&do=prerequisites';
		ACPModules.module		= li.id.replace( 'module_', '' );
		ACPModules.step			= 0;
		ACPModules.totalSteps	= 0;
		ACPModules.stepStatus	= '';
		
		/* Update counters */
		ACPModules.installedLICount++;
		ACPModules.availableLICount--;
		
		/* Progress */		
		ACPModules.newProgress	= new ipb.tracker.progress( ACPModules.module,
			{
				insert: { bottom: li.id + '-progress-td' }
			}
		);
		
		/* Start the longggggg process */
		ACPModules.performRequest();
	},
	
	finishInstallationAndStartQueue: function()
	{
		/* Allow queued items to go */
		ACPModules.inInstall	= 0;
		
		element = ACPModules.installingElement;
		element.hide();
		
		element.update(
			ACPModules.postInstallHTML.evaluate(
				{
					'title': 		ACPModules.moduleData.title,
					'author':		ACPModules.moduleData.author,
					'version':		ACPModules.moduleData.version,
					'id':			ACPModules.moduleData.id,
					'directory':	ACPModules.moduleData.directory
				}
			)
		);

		$('installed_modules').insert( element );
		new Effect.Appear( element, { duration: 0.25 } );

		/* Add to installed list */
		if ( ACPModules.installedLICount > 0 )
		{
			$('noInstalledModules').hide();
		}
		
		if ( ACPModules.availableLICount <= 0 )
		{
			$('noAvailableModules').show();
		}
		
		if ( typeof(ACPModules.queuedItems[ACPModules.queueCount]) != 'undefined' )
		{
			li = ACPModules.queuedItems[ACPModules.queueCount];
			
			/* Update for next queue */
			ACPModules.queueCount++;
			
			/* Fire off install */
			ACPModules.doingInstall(li);
		}
	},
	
	queueInstall: function(li)
	{
		var count = $H(ACPModules.queuedItems).size();
		
		/* Update HTML */
		li.update(
			ACPModules.queuedInstallHTML.evaluate()
		);
		
		/* Add to queue */
		ACPModules.queuedItems[count] = li;
	},
	
	performRequest: function()
	{
		new Ajax.Request(
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=modules&secure_key=' + ipb.vars['md5_hash'] + '&type=' + ACPModules.requestType + '&moduleName=' + ACPModules.module + ACPModules.url,
			{
				method: 'get',
				evalJSON: true,
				onSuccess: function(s)
				{
					/* Get results */
					results = s.responseText.evalJSON();

					/* Total steps */
					if ( typeof( results.totalSteps ) != 'undefined' )
					{
						ACPModules.totalSteps = results.totalSteps;
						ACPModules.newProgress.setTotalSteps( results.totalSteps );
					}
					
					/* Check we have data as it should be step 1 */
					if ( ! ACPModules.totalSteps )
					{
						if ( !results.error )
						{
							results.error 		= 'true';
							results.message 	= 'We could not determine how many installation steps there were.';
						}
					}
					
					/* Did we error? */
					if ( results.error == 'true' )
					{
						ACPModules.inError = 1;
						$('errormessage').update(results.message);
						$('errormessage').show();
						$('progress').up('td').addClassName('error');
					}
					
					/* Update and go */
					ACPModules.step			= results.step;
					ACPModules.url 			= results.url;
					ACPModules.status		= results.status;
					ACPModules.stepStatus	= results.stepStatus;
					
					/* Step and Status */
					$(ACPModules.installingElement.id + '-step').update( "Step " + results.step + ":" );
					$(ACPModules.installingElement.id + '-step-detail').update( results.stepStatus );
					
					/* Progress bar */
					ACPModules.newProgress.increment();


					/* Keep us going */
					if ( ACPModules.status != 'success' && ! ACPModules.inError )
					{
						ACPModules.performRequest();
					}
					else
					{
						/* Store data */
						ACPModules.moduleData = results.moduleData;
						$('indicator').hide();
						setTimeout( "ACPModules.finishInstallationAndStartQueue();", 1500 );
					}
				}
			}
		);
	}
};

ACPModules.init();