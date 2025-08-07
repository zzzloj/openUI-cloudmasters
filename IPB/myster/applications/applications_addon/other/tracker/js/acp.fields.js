/**
* Tracker 2.1.0
* 
* Fields Javascript
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

ACPFields = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.fields.js");
		
		document.observe( 'dom:loaded', function()
			{
				Sortable.create( 'fields', { tag: 'li', only: 'isDraggable', revert: true, format: 'field_([0-9]+)', onUpdate: ACPFields.dropItLikeItsHot, handle: 'draghandle' } );
		
				$('recacheFields').observe( 'click', ACPFields.recacheFieldsClick );
			}
		);
	},
	
	dropItLikeItsHot: function( draggableObject, mouseObject )
	{
		var options = {
						method : 'post',
						parameters : Sortable.serialize( 'fields', { tag: 'li', name: 'fields' } )
					};
	 
		new Ajax.Request( ipb.vars['app_url'].replace( /&amp;/g, '&' ) + 'module=ajax&section=fields&do=reorder&md5check=' + ipb.vars['md5_hash'], options );
		
		/* Color rows */
		var css = 'acp-row-on';
		$$('.isDraggable').each(
			function(li)
			{
				li.removeClassName('acp-row-on').removeClassName('acp-row-off');
				li.addClassName(css);
				
				if ( css == 'acp-row-on' ) { css = 'acp-row-off'; }
				else if ( css == 'acp-row-off' ) { css = 'acp-row-on'; }
			}
		);
		
		ACPFields.recacheFields($('recacheFields'));
	
		return false;
	},
	
	recacheFieldsClick: function(e)
	{
		elem = Event.element(e);
		ACPFields.recacheFields(elem);
	},
	
	recacheFields: function(elem)
	{
		/* Switch buttons */
		elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('database_refresh.png', 'loading-green.gif') );
		elem.stopObserving();
		
		/* Send the data */
		new Ajax.Request(
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=fields&secure_key=' + ipb.vars['md5_hash'] + '&do=recache',
			{
				method: 'get',
				onSuccess: function(s)
				{
					/* Switch buttons */
					$('recacheFields').hide();
					$('recacheSuccess').up('li').show();
					
					/* Replace with normal button */
					setTimeout( function()
						{
							$('recacheSuccess').up('li').hide();
							elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'database_refresh.png') );
							$('recacheFields').show();
						},
						500
					);
					
					$('recacheFields').observe( 'click', ACPFields.recacheFieldsClick );
				}
			}
		);
	}
};

ACPFields.init();