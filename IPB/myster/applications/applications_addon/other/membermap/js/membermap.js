/**
 * Main Javascript
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester, Alex Hobbs
 * @package     Member Map
 * @version     1.0.8
 */

var _membermap 	= window.IPBoard,
	storedLat 	= [],
	storedLong 	= [],
	licount		= 0;

_membermap.prototype.membermap =
	{
		popups: {},
		type: '',

		init: function()
		{
			Debug.write("Initializing admin/membermap.js");

			document.observe("dom:loaded", function()
				{
					ipb.membermap.loadEvents();
				}
			);
		},

		loadEvents: function()
		{
			['findAddress'].each(
				function(element)
				{
					if ( $(element) )
					{
						$(element).observe( 'click',
							function(e)
							{
								Event.stop(e);

								if( $(element+'_popup') )
								{
									ipb.membermap.popups[element].show();
								}
								else
								{
									switch( element )
									{
										case 'findAddress':
											var template = findAddress;
										break;
									}

									ipb.membermap.popups[element] = new ipb.Popup( element,
										{
											type: 'pane',
											modal: true,
											w: '550px',
											h: 'auto',
											initial: template.evaluate(),
											hideAtStart: false,
											close: '.cancel'
										},
										{
											afterShow: function()
											{
												switch( element )
												{
													case 'findAddress':
														ipb.membermap.type = 'add';
														$('findAddressForm').observe( 'submit', ipb.membermap.formObserve );
													break;
												}
											}
										}
									);
								}
							}
						);
					}
				}
			);
		},

		formObserve: function(e)
		{
			licount = 0;
			type 	= ipb.membermap.type;
			Event.stop(e);
			$( 'findAddressForm').request(
				{
					evalJSON: 'force',
					onComplete: function(s)
					{
						var li = "<li><strong>Here are the locations that matched your search, please click one of them.</strong></li>";

						if ( typeof(s.responseJSON['error']) != 'undefined' )
						{
							alert( s.responseJSON['message'] );
							return;
						}

						s.responseJSON.each(
							function(i)
							{
								if ( typeof( i['address'] ) != 'undefined' || ( in_array( i['latitude'], storedLat ) && in_array( i['longitude'], storedLong ) ) )
								{
									licount = licount + 1;

									storedLat[i['latitude']] 	= i['latitude'];
									storedLong[i['longitude']] 	= i['longitude'];

									li += "<li lat='" + i['latitude'] + "' lon='" + i['longitude'] + "'><a href='" + ipb.vars['base_url'] + "app=membermap&module=membermap&section=map&action=" + type + "&lat=" + i['latitude'] + "&lon=" + i['longitude'] + "' title='" + i['address'] + "'>" + i['address'] + "</a></li>";
								}
							}
						);

						$( type + 'MapList').update(li).show();

						/* STUART I HATE YOU */
						$( type + 'MapList' ).childElements().each(
							function(child)
							{
								child.observe( 'click', function(c)
									{
										Event.stop(c);

										$('lat').setAttribute( 'value', child.readAttribute('lat') );
										$('lon').setAttribute( 'value', child.readAttribute('lon') );
										$('addressHide').setAttribute( 'value', child.down('a').innerHTML );

										$('selectedAddress').update( child.down('a').innerHTML );

										/* Close popup */
										ipb.membermap.popups['findAddress'].hide();
									}
								);
							}
						);
					}
				}
			);
			return false;
		}
	}

ipb.membermap.init();

function in_array (needle, haystack, argStrict) {
    // Checks if the given value exists in the array
    //
    // version: 911.718
    // discuss at: http://phpjs.org/functions/in_array
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: vlado houba
    // +   input by: Billy
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);
    // *     returns 1: true
    // *     example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
    // *     returns 2: false
    // *     example 3: in_array(1, ['1', '2', '3']);
    // *     returns 3: true
    // *     example 3: in_array(1, ['1', '2', '3'], false);
    // *     returns 3: true
    // *     example 4: in_array(1, ['1', '2', '3'], true);
    // *     returns 4: false
    var key = '', strict = !!argStrict;

    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {
                return true;
            }
        }
    } else {
        for (key in haystack) {
            if (haystack[key] == needle) {
                return true;
            }
        }
    }

    return false;
}