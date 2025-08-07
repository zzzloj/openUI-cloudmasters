<?php
/**
 * @file		rate.php 	Rate an image or album
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

class public_gallery_images_rate extends ipsCommand
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
		// Check secure key
		//-----------------------------------------

		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'nopermission', '10727.1', null, null, 403 );
		}
		
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
			$result	= $this->registry->gallery->helper('rate')->rateAlbum( $rating, $id, true );
		}
		else
		{
			$result	= $this->registry->gallery->helper('rate')->rateImage( $rating, $id, true );
		}
		
		//-----------------------------------------
		// If successful, redirect
		//-----------------------------------------

		if ( $result !== false )
		{
			if ( $where == 'album' )
			{
				$this->registry->output->redirectScreen( $this->lang->words['album_rated'], $this->settings['base_url'] . 'app=gallery&amp;album=' . $id, $result['albumData']['album_name_seo'], 'viewalbum' );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['rated'], $this->settings['base_url'] . 'app=gallery&amp;image=' . $id, $result['imageData']['image_caption_seo'], 'viewimage' );
			}
		}

		//-----------------------------------------
		// Otherwise, show error
		//-----------------------------------------

		else
		{
			$this->registry->output->showError( $this->registry->gallery->helper('rate')->getError(), 10727 );
		}
	}
}