<?php
/**
 * @file		img_ctrl.php 	Outputs the correct image, primarily for non-web accessible images
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $ (Orginal: Matt Mecham)
 * $LastChangedDate: 2013-04-11 18:03:45 -0400 (Thu, 11 Apr 2013) $
 * @version		v5.0.5
 * $Revision: 12168 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_img_ctrl extends ipsCommand
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
		// Check image
		//-----------------------------------------

		if( empty( $this->request['img'] ) )
		{
			$this->returnNotFound();
		}

		//-----------------------------------------
		// Get image data
		//-----------------------------------------

		if( strlen( $this->request['img'] ) == 32 )
		{
			$image	= $this->registry->gallery->helper('upload')->fetchImage( $this->request['img'] );
		}
		else
		{
			$image	= $this->registry->gallery->helper('image')->fetchImages( null, array( 'imageIds' => array( intval($this->request['img']) ) ) );
			$image	= array_pop($image);
		}
		
		//-----------------------------------------
		// Check if we can view
		//-----------------------------------------

		if( !$image['image_id'] )
		{
			$this->returnNotFound();
		}
		
		if ( $image['image_album_id'] )
		{
			if ( !$this->registry->gallery->helper('albums')->isViewable( $image['image_album_id'] ) )
			{
				$this->returnNoPermission();
			}
		}
		else
		{
			if ( !$this->registry->gallery->helper('categories')->isViewable( $image['image_category_id'] ) )
			{
				$this->returnNoPermission();
			}
		}

		//-----------------------------------------
		// Locate image on disk
		//-----------------------------------------

		$image_loci	= $image['image_directory'] ? $this->settings['gallery_images_path'] . '/' . $image['image_directory'] . '/' : $this->settings['gallery_images_path'] . '/';
		
		if( $this->request['tn'] OR $this->request['file'] == 'thumb' )
		{
			$theimg	= $image_loci . 'tn_' . $image['image_masked_file_name'];
		}
		else if( $this->request['file'] == 'small' )
		{
			$theimg	= $image_loci . 'sml_' . $image['image_masked_file_name'];
		}
		else if( $this->request['file'] == 'med' OR $this->request['file'] == 'medium' )
		{
			$theimg	= $image_loci . $image['image_medium_file_name'];
		}
		else if( $this->request['file'] == 'media' )
		{
			$theimg	= $image_loci . $image['image_media_thumb'];
		}
		else if( $this->request['file'] == 'mediafull' )
		{
			$exploded_array	= explode( ".", $image['image_masked_file_name'] );
			$ext			= '.' . strtolower( array_pop( $exploded_array ) );
			
			if( ! $this->registry->gallery->helper('media')->isAllowedExtension( $ext ) )
			{
				$this->returnNoPermission();
			}

			$image['image_file_type']	= $this->registry->gallery->helper('media')->getMimeType( $ext );
			
			$theimg	= $image_loci . $image['image_masked_file_name'];
		}
		else
		{
			$theimg	= $image_loci . $image['image_masked_file_name'];
		}

		if( is_dir( $theimg ) OR !is_file( $theimg ) )
		{
			$this->returnNotFound();
		}
		
		//-----------------------------------------
		// Push image data to client
		//-----------------------------------------
		
		$delivery	= $this->request['type'] == 'download' ? 'download' : 'inline';
		$fileName	= $this->request['type'] == 'download' ? $image['image_file_name'] : $image['image_masked_file_name'];
		
		header( "Content-Type: {$image['image_file_type']}" );
		header( "Content-Disposition: {$delivery}; filename=\"{$fileName}\"" );
		
		@ob_end_clean();
		
		if( $fh = fopen( $theimg, 'rb' ) )
		{
			while( !feof($fh) )
			{
				echo fread( $fh, 4096 );
				flush();
				@ob_flush();
			}
			
			@fclose( $fh );
		}

		exit();
	}
	
	/**
	 * Display a no permission image
	 *
	 * @return	@e void
	 */
	protected function returnNoPermission()
	{
		@ob_end_clean();
		@header( "Content-Type: image/gif" );
		@header( "Content-Disposition: inline; filename='no_permission.gif'" );
		print file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_extra/gallery_media_types/no_permission.gif' );
		exit;
	}
	
	/**
	 * Display a not found image
	 *
	 * @return	@e void
	 */
	protected function returnNotFound()
	{
		@ob_end_clean();
		@header( "Content-Type: image/gif" );
		@header( "Content-Disposition: inline; filename='no_image_found.gif'" );
		print file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_extra/gallery_media_types/no_image_found.gif' );
		exit;
	}
}