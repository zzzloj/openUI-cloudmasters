<?php
/**
 * @file		categories.php 	Provides ajax methods to mange categories
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-05-22 19:52:27 -0400 (Tue, 22 May 2012) $
 * @version		v5.0.5
 * $Revision: 10785 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_ajax_categories
 * @brief		Provides ajax methods to mange categories
 */
class admin_gallery_ajax_categories extends ipsAjaxCommand 
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

		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_gallery_categories');

		$this->lang->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		//-----------------------------------------
		// Switch
		//-----------------------------------------

    	switch( $this->request['do'] )
    	{
			case 'deleteDialogue':
				$this->deleteDialogue();
			break;

			case 'resyncCategories':
				$this->_resyncCategories();
			break;

			case 'reorder':
				$this->reorder();
			break;
		}
	}

	/**
	 * Resyncs categories
	 *
	 * @return	@e void
	 */
	public function _resyncCategories()
	{
		//-----------------------------------------
		// Getting options?
		//-----------------------------------------

		if ( $this->request['pb_act'] == 'getOptions' )
		{
			$json	= array( 'total' => count( $this->registry->gallery->helper('categories')->fetchCategories() ), 'pergo' => 10000 );
		}
		else
		{
			//-----------------------------------------
			// Resync
			//-----------------------------------------

			$this->registry->gallery->helper('categories')->rebuildCategory( $this->request['categoryId'] );
			
			//-----------------------------------------
			// Format response
			//-----------------------------------------

			$json	= array( 'status' => 'done', 'lastId' => $lastId );
		}

		//-----------------------------------------
		// Return json
		//-----------------------------------------

		$this->returnJsonArray( $json );
	}

	/**
	 * Delete category dialog
	 *
	 * @return	@e void
	 */
	public function deleteDialogue()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$category	= intval( $this->request['category'] );
		$jumpList	= $this->registry->gallery->helper('categories')->catJumpList( true );

		//-----------------------------------------
		// Return confirmation dialog box
		//-----------------------------------------

		$this->returnHtml( $this->html->ajaxDeleteDialog( $this->registry->gallery->helper('categories')->fetchCategory( $category ), $jumpList ) );
	}

	/**
	 * Reorder categories
	 *
	 * @return	@e void
	 */
	public function reorder()
	{
		//-----------------------------------------
		// Save new positions
		//-----------------------------------------

		$position	= 1;
		
		if( is_array($this->request['cats']) AND count($this->request['cats']) )
		{
			foreach( $this->request['cats'] as $this_id )
			{
				$this->DB->update( 'gallery_categories', array( 'category_position' => $position ), 'category_id=' . $this_id );
				
				$position++;
			}
		}
		
		$this->registry->gallery->helper('categories')->rebuildCatCache();

		$this->returnString( 'OK' );
	}
}