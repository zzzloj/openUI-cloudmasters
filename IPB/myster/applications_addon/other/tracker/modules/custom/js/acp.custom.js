/**
* Tracker 2.1.0
*
* Projects Javascript
* Last Updated: $Date: 2011-06-14 21:02:56 +0100 (Tue, 14 Jun 2011) $
*
* @author		$Author: AlexHobbs $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1288 $
*/
var custom = {};

(function($){
	custom = function($){
		var type = '';

		var init = function(){
			$('#save_field').click( saveField );
			if( custom.type == 'edit' )
			{
				$('#finish').show();

				// Hacky attempt to get finish to ALWAYS show
				$('#steps_bar, #next, #prev').click( function(event) {
						$('#finish').show();
					}
				);
			}

			// Delete button
			$('.delete_field').each(
				function(index)
				{
					$(this).click( deleteField );
				}
			);
		};

		var deleteField = function(event) {
			if( ! confirm( 'Are you sure you want to delete this field? This cannot be undone and there will be NO more warnings!' ) )
			{
				event.preventDefault();
				event.stopPropagation();
			}
		}

		var saveField = function(event) {
			// Stop defaults
			event.preventDefault();
			event.stopPropagation();

			// Set input
			$('#projects_input').attr( 'value', $('#projectsData').val() );

			// Send form
			$('#adminform').submit();
		};

		return {
			init: init,
			type: type
		}
	}($);

}(jQuery));