/**
* Tracker 2.1.0
* 
* Fields Javascript
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

ACPTemplates = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.fields.js");
		
		document.observe( 'dom:loaded', function()
			{
				ipb.delegate.register( '.add_template', ACPTemplates.addTemplate );
			}
		);
	},
	
	addTemplate: function(event)
	{
		var elem = Event.element(event);
		
		/* New popup */
		var popup = new ipb.tracker.popup( 'add_template',
			{
				width: 60,
				height: 25,
				initial: ACPTemplates.addHTML.evaluate(),
				title: 'Template Management'
			}
		);
		
		/* Observe the elements */
		$('template_name').observe( 'keyup', function()
			{
				$('saveTemplateName').removeClassName('disabled');
				$('saveTemplateName').stopObserving();
				
				if ( ! $('template_name').getValue().trim() )
				{
					$('saveTemplateName').addClassName('disabled');
					$('saveTemplateName').observe( 'click', ACPTemplates.saveName );
				}
			}
		);
	},
	
	saveName: function(event)
	{
	
	}
};

ACPTemplates.init();