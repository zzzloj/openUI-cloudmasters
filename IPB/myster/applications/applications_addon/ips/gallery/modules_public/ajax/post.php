<?php
/**
 * @file		post.php 	AJAX functions to facilitate uploads and posting
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-06-13 21:46:55 -0400 (Wed, 13 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10918 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_post extends ipsAjaxCommand
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
		// What are we doing?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'process':
				$this->_process();
			break;

			case 'uploadSave':
				$this->_uploadSave();
			break;

			case 'upload':
				$this->_uploadIframe();
			break;
		}
	}

	/**
	 * Shows the add new image form
	 *
	 * @return	@e void
	 */
	protected function _uploadIframe()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$type	= trim( $this->request['type'] );
		$id		= ( ! empty( $this->request['album_id'] ) ) ? intval( $this->request['album_id'] ) : ( ( ! empty( $this->request['category_id'] ) ) ? intval( $this->request['category_id'] ) : trim( $this->request['id'] ) );
		$sKey	= trim( $this->request['sessionKey'] );

		//-----------------------------------------
		// Show upload form
		//-----------------------------------------

		if ( $type == 'album' OR $type == 'category' )
		{
			$this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey ) );
		}
		else
		{
			$this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id ) );
		}
	}

	/**
	 * Process the upload
	 *
	 * @return	@e void		JSON array
	 */
	protected function _process( $returnJson=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sessionKey	= trim( $this->request['sessionKey'] );
		$albumId	= intval( $this->request['album_id'] );
		$categoryId	= intval( $this->request['category_id'] );
		$msg		= '';
		$isError	= 0;
		$newId		= 0;

		//-----------------------------------------
		// Upload the file
		//-----------------------------------------

		try
		{
			$newId	= $this->registry->gallery->helper('upload')->process( $sessionKey, $albumId ? $albumId : $categoryId, array( 'containerType' => $albumId ? 'album' : 'category' ) );
			$msg	= 'upload_ok';
		}
		catch( Exception $e )
		{
			//-----------------------------------------
			// Got an error
			//-----------------------------------------

			$msg		= $this->_convertException( $e->getMessage() );
			$isError	= 1;
		}

		//-----------------------------------------
		// Build JSON and return or print
		//-----------------------------------------

		$JSON	= $this->registry->gallery->helper('upload')->fetchSessionUploadsAsJson( $sessionKey, $albumId, $categoryId, $msg, $isError, $newId );
		
		if ( $returnJson )
		{
			return $JSON;
		}
		
		$this->returnJsonArray( $JSON );
	}

	/**
	 * Processes the thumbnail upload
	 *
	 * @return	@e void
	 */
	protected function _uploadSave()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$type	= trim( $this->request['type'] );
		$id		= trim( $this->request['id'] );
		$sKey	= trim( $this->request['sessionKey'] );

		//-----------------------------------------
		// Try to upload/save
		//-----------------------------------------

		try
		{
			if ( $type == 'mediaThumb' )
			{
				$return	= $this->registry->gallery->helper('upload')->mediaThumb( $id );
			}
			else
			{
				if( $this->request['type'] == 'album' )
				{
					$this->request['album_id']		= intval($id);
					$this->request['category_id']	= 0;
				}
				else
				{
					$this->request['album_id']		= 0;
					$this->request['category_id']	= intval($id);
				}

				$return	= $this->_process( true );
			}
		}
		catch( Exception $error )
		{
			//-----------------------------------------
			// Got an error
			//-----------------------------------------

			$msg		= $this->_convertException( $e->getMessage() );

			if ( $type == 'mediaThumb' )
			{
				return $this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id, json_encode( array( 'error' => $msg ) ) ) );
			}
			else
			{
				return $this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey, json_encode( array( 'error' => $msg ) ) ) );
			}
		}

		//-----------------------------------------
		// Return appropriate HTML
		//-----------------------------------------

		if ( $type == 'mediaThumb' )
		{
			return $this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameMediaThumb( $id, json_encode( $return ) ) );
		}
		else
		{
			return $this->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( $sKey, json_encode( $return ) ) );
		}
	}

	/**
	 * Convert exception code to language string
	 *
	 * @param	string	Exception message
	 * @return	@e string
	 */
	protected function _convertException( $msg )
	{
		switch( $msg )
		{
			case 'OUT_OF_DISKSPACE':
				return 'out_of_diskspace';
			break;
			default:
			case 'FAIL':
			case 'FAILX':
				return 'silly_server';
			break;
			case 'TOO_BIG':
				return 'upload_too_big';
			break;
			case 'BAD_TYPE':
				return 'invalid_mime_type';
			break;
			case 'NOT_VALID':
				return 'invalid_mime_type';
			break;
			case 'ALBUM_FULL':
				return 'album_full';
			break;
		}

		return strtolower( $msg );
	}
}