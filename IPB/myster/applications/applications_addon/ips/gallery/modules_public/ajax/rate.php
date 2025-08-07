<?php
/**
 * @file		rate.php 	AJAX rating
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

class public_gallery_ajax_rate extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$id		= intval( $this->request['id'] );
		$rating	= intval( $this->request['rating'] );
		$where	= $this->request['where'] == 'album' ? 'album' : 'image';

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		//-----------------------------------------
		// Save rating
		//-----------------------------------------

		if ( $where == 'album' )
		{
			$result = $this->registry->gallery->helper('rate')->rateAlbum( $rating, $id );
		}
		else
		{
			$result = $this->registry->gallery->helper('rate')->rateImage( $rating, $id );
		}
		
		//-----------------------------------------
		// If successful, return JSON data
		//-----------------------------------------

		if ( $result !== false )
		{
		    $this->returnJsonArray( array(
		    							'rating'	=> $rating,
										'total'		=> $result['total'],
										'average'	=> $result['aggregate'],
										'rated'		=> 'new'
								)		);
		}

		//-----------------------------------------
		// Otherwise return an error response
		//-----------------------------------------

		else
		{
			$this->returnJsonArray( array( 'error_key' => $this->registry->gallery->helper('rate')->getError() ) );
		}
	}
}