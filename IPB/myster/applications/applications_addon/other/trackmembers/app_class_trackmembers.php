<?php

/**
 * Product Title:		(SOS34) Track Members
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */

class app_class_trackmembers
{
	public function __construct( ipsRegistry $registry )
	{
		if ( IN_ACP )
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_trackmembers' ),  'trackmembers' );
		}
		else
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_trackmembers' ), 'trackmembers' );
		}
	}
}