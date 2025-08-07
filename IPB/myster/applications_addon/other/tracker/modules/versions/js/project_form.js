var versions_project_form = {

	init: function()
	{
		ipb.tracker.versionsInit();
	},
	
	save: function()
	{
		if ( ! $('versions') )
		{
			return false;
		}
		
		var hash = {},
			weHitADefault = 0,
			weHitRows = 0;
		
		$('versions').childElements().each(
			function(li)
			{
				if ( li.hasClassName('isDraggable') )
				{
					weHitRows = 1;
					var versionID = li.id.replace( 'version_', '' );
					
					if ( ! $('versionValue-' + versionID).getValue() )
					{
						hash['error']	= true;
						hash['message']	= "You cannot leave the version number blank!";
						
						return Object.toJSON($H(hash));
					}
					
					/* Sort out form inputs */
					$('versionTypeRel-' + versionID).value = $('versionType-' + versionID).readAttribute('rel');
					
					if ( $('defaultVersion-' + versionID).checked && $('versionSaveType-' + versionID).value != 'delete' )
					{
						weHitADefault = 1;
						$('versionDefault-' + versionID).value = 1;
					}
				}
			}
		);
		
		if ( weHitADefault == 0 && weHitRows == 1 )
		{
			hash['error']	= true;
			hash['message']	= "Please select a default version!";
						
			return Object.toJSON($H(hash));
		}
		
		return Object.toJSON($H(hash));
	}
};