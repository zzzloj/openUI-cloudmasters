<?php
/**
 * @file		photostrip.php 	AJAX handler for the photostrip
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2012-06-11 17:55:50 -0400 (Mon, 11 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10910 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_photostrip extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		switch( $this->request['do'] )
		{
			case 'slide':
				$this->slide();
			break;
        }
    }
    
	/**
	 * Builds and returns the html for sliding the photostrip to the left
	 *
	 * @return	@e void
	 */  
    protected function slide()
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$directionPos	= trim( $this->request['directionPos'] );
		$direction		= ( $this->request['direction'] == 'left' ) ? 'left' : 'right';
		$left			= intval( $this->request['left'] );
		$right			= intval( $this->request['right'] );
		$imId			= ( $direction == 'left' ) ? $left : $right;
		
		//-----------------------------------------
		// Return the necessary data
		//-----------------------------------------

		$this->returnJsonArray( $this->registry->gallery->helper('image')->fetchPhotoStrip( $imId, $direction, $directionPos ) );
	}
}