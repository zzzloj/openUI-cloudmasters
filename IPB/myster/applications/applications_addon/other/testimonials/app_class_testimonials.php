<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

class app_class_testimonials
{
	public function __construct( ipsRegistry $registry )
	{
		require_once( IPSLib::getAppDir( 'testimonials' ) . "/sources/classes/library.php" );
		$registry->setClass( 'testemunhosLibrary', new testemunhosLibrary( $registry ) );

		if ( IN_ACP )
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_testemunhos' ),  'testimonials' );
		}
		else
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_testemunhos' ), 'testimonials' );
		}
	}
}