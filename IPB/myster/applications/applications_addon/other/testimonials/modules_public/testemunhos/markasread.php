<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */
 
if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_markasread extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
		$cat_id       = 1;

        $this->registry->classItemMarking->markAppAsRead( 'testimonials' );
        
        if ( $this->memberData['member_id'] )
        {
        	//$this->registry->classItemMarking->writeMyMarkersToDB();
        }
		
       	$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=testimonials" );
	}
}
