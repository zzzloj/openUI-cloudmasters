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

ACPMSev = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.severity.js");
		
		/* Hide default loading */
		ipb.tracker.hideLoading();
		
		document.observe( 'dom:loaded', function()
			{
				$('recacheSeverities').observe( 'click', ACPMSev.recacheClick );
				$('saveSeverities').observe( 'click', ACPMSev.save );
				
				$$('.color_input').each(
					function(e)
					{
						e.observe('keyup', ACPMSev.changeColor );
					}
				);
				
				$$('.font_input').each(
					function(e)
					{
						e.observe('keyup', ACPMSev.changeFont );
					}
				);
				
				$$('._toggle_font').each(
					function(e)
					{
						e.observe('click', ACPMSev.toggle.bindAsEventListener(this, 'font') );
					}
				);
				
				$$('._toggle_bg').each(
					function(e)
					{
						e.observe('click', ACPMSev.toggle.bindAsEventListener(this, 'background') );
					}
				);
			}
		);
	},
	
	toggle: function(event)
	{
		var type 	= $A(arguments)[1],
			element = Event.element(event).up('td');
		
		switch( type )
		{
			case 'font':
				element.up('tr').select('.color_wrap').each( function(e){ e.hide() } );
				element.up('tr').select('.font_wrap').each( function(e){ e.show() } );
			break;
			
			case 'background':
				element.up('tr').select('.color_wrap').each( function(e){ e.show() } );
				element.up('tr').select('.font_wrap').each( function(e){ e.hide() } );
			break;
		}
	},
	
	changeColor: function(event)
	{
		var element = Event.element(event).up('td').select('.sev_box'),
			input	= Event.element(event);
		
		if ( input.getValue() )
		{
			element.each( function(e) { e.setStyle('background:' + input.getValue() + ';'); } );
		}
		else
		{
			element.each( function(e) { e.setStyle('background:' + input.readAttribute('value') + ';'); } );
			input.setValue( input.readAttribute('value') );
		}
	},
	
	changeFont: function(event)
	{
		var element = Event.element(event).up('td').select('.sev_box'),
			input	= Event.element(event);
		
		if ( input.getValue() )
		{
			element.each( function(e) { e.setStyle('color:' + input.getValue() + ';'); } );
		}
		else
		{
			element.each( function(e) { e.setStyle('color:' + input.readAttribute('value') + ';'); } );
			input.setValue( input.readAttribute('value') );
		}
	},
	
	recacheClick: function(event)
	{
		var elem = Event.element(event);
		ACPMSev.recache(elem);
	},
	
	recache: function(elem)
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
			ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=settings&component=severity&file=admin&secure_key=' + ipb.vars['md5_hash'] + '&do=recache',
			{
				method: 'get',
				onSuccess: function(s)
				{
					if( s.responseText == 'CANNOT_WRITE' )
					{
						elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'database_refresh.png') );
						ipb.tracker.isLoading = 0;
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>We could not write to the /cache/tracker/ folder, please make sure it is writeable!</p>", title: 'Fatal Error! Cannot write to cache folder!' } );
						return;
					}
					
					/* Switch buttons */
					$('recacheSeverities').hide();
					$('recacheSuccess').up('li').show();
					
					/* Replace with normal button */
					setTimeout( function()
						{
							$('recacheSuccess').up('li').hide();
							elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'database_refresh.png') );
							$('recacheSeverities').show();
							ipb.tracker.isLoading = 0;
						},
						500
					);

					$('recacheSeverities').observe( 'click', ACPMStatus.recacheStatusClick );
				}
			}
		);
	},
	
	save: function(event)
	{
		if ( isLoading )
		{
			return false;
		}
		
		var data = {},
			elem = Event.element(event);
			
		isLoading = 1;
		
		/* Switch buttons */
		elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('accept.png', 'loading-green.gif') );
		
		$('severity').childElements().each(
			function(li)
			{
				if ( li.hasClassName('isSkin') )
				{
					var sid = li.down('.skin_id').id.replace('skin_','');
					
					data[sid] = {
						1: {},
						2: {},
						3: {},
						4: {},
						5: {}
					};
					
					data[sid][1]['background_color'] 	= li.down('.sev_1 .color_input').getValue();
					data[sid][1]['font_color']			= li.down('.sev_1 .font_input').getValue();
					
					data[sid][2]['background_color'] 	= li.down('.sev_2 .color_input').getValue();
					data[sid][2]['font_color']			= li.down('.sev_2 .font_input').getValue();
					
					data[sid][3]['background_color'] 	= li.down('.sev_3 .color_input').getValue();
					data[sid][3]['font_color']			= li.down('.sev_3 .font_input').getValue();
					
					data[sid][4]['background_color'] 	= li.down('.sev_4 .color_input').getValue();
					data[sid][4]['font_color']			= li.down('.sev_4 .font_input').getValue();
					
					data[sid][5]['background_color'] 	= li.down('.sev_5 .color_input').getValue();
					data[sid][5]['font_color']			= li.down('.sev_5 .font_input').getValue();
				}
			}
		);
		
		new Ajax.Request( ipb.vars['base_url'].replace('&amp;','&') + 'app=tracker&module=ajax&section=settings&component=severity&file=admin&secure_key=' + ipb.vars['md5_hash'] + '&do=save',
			{
				method: 'POST',
				parameters: {
					'data': Object.toJSON($H(data))
				},
				onSuccess: function(s)
				{
					/* Didn't get the expected return data */
					if ( ! s.responseText.isJSON() || s.responseText.evalJSON().error )
					{
						var oops = new ipb.tracker.popup( 'oops', { className: 'info', width: 60, height: 10, initial: "<p>Oh dear, something went wrong. Please try again!</p>", title: 'Oops!' } );
						return;
					}
					
					/* Recache */
					ACPMSev.recache($('recacheSeverities'));
					
					/* Switch buttons */
					$('saveSeverities').hide();
					$('saveSuccess').up('li').show();
					
					/* Replace with normal button */
					setTimeout( function()
						{
							$('saveSuccess').up('li').hide();
							elem.down('img').setAttribute( 'src', elem.down('img').readAttribute('src').replace('loading-green.gif', 'accept.png') );
							$('saveSeverities').show();
							isLoading = 0;
						},
						1000
					);

					$('saveSeverities').observe( 'click', ACPMSev.save );
				}
			}
		);
	}
};

ACPMSev.init();