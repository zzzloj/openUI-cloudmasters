<?php
/**
 * @file		categories.php 	Category AJAX methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		5.0.0
 * $LastChangedDate: 2012-06-15 18:18:40 -0400 (Fri, 15 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10935 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_ajax_categories extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What to do
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'fetchCategoryJson':
				$this->_fetchCategoryJson();
			break;
        }
    }

	/**
	 * Fetches all uploads for this 'session'
	 *
	 * @return	@e void
	 */
	public function _fetchCategoryJson()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$categoryId					= intval( $this->request['category_id'] );
		$category					= $this->registry->gallery->helper('categories')->fetchCategory( $categoryId );
		$category['_coverImage']	= $category['category_cover_img_id'] ? $category['category_cover_img_id'] : $category['category_last_img_id'];

		//-----------------------------------------
		// Get cover image thumb
		//-----------------------------------------

		$image   = $this->registry->gallery->helper('image')->fetchImage( $category['_coverImage'] );

		$category['thumb'] = $this->registry->gallery->helper('image')->makeImageLink( $image, array( 'type' => 'thumb', 'coverImg' => true, 'link-container-type' => 'category' ) );
		
		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->returnJsonArray( $category['category_id'] ? $category : array() );
	}
}