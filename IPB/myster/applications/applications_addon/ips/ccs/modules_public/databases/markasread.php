<?php
/**
 * @file		markasread.php 	Mark a database category as read
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2 Oct 2012
 * $LastChangedDate: 2011-05-05 12:40:49 -0400 (Thu, 05 May 2011) $
 * @version		v3.4.5
 * $Revision: 8655 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_ccs_databases_markasread extends ipsCommand
{
	/**
	 * Main execution point
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$database		= intval( $this->request['database'] );
		$categoryId		= intval( $this->request['category'] );
		$returnTo		= intval( $this->request['returnto'] );
		$category		= $this->registry->ccsFunctions->getCategoriesClass( $database )->categories[ $categoryId ];
		$save			= array();

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $category['category_id'] )
		{
			$this->registry->getClass('output')->showError( 'markread_no_id', '10CCS211.1', null, null, 404 );
		}

		//-----------------------------------------
		// Turn off instant updates to save resources
		//-----------------------------------------

		$this->registry->classItemMarking->disableInstantSave();

		//-----------------------------------------
		// Come from the index? Add kids
		//-----------------------------------------

		if ( $this->request['i'] )
		{
			$children		= $this->registry->ccsFunctions->getCategoriesClass( $database )->getChildren( $categoryId );

			if ( is_array( $children ) and count($children) )
			{
				foreach( $children as $id )
				{
					$this->registry->classItemMarking->markRead( array( 'catID' => $id, 'databaseID' => $database ), 'ccs' );
				}
			}
		}

		//-----------------------------------------
		// Add in the current category...
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'catID' => $categoryId, 'databaseID' => $database ), 'ccs' );

		//-----------------------------------------	
		// Where are we going back to?
		//-----------------------------------------

		if ( $returnTo )
		{
			$parent	= $this->registry->ccsFunctions->getCategoriesClass( $database )->categories[ $returnTo ];
			
			//-----------------------------------------
			// Go back to parent category
			//-----------------------------------------
			
			$this->registry->getClass('output')->silentRedirect( $this->registry->ccsFunctions->returnDatabaseUrl( $database, $categoryId ) );
		}
		else
		{
			$this->registry->getClass('output')->silentRedirect( $this->registry->ccsFunctions->returnDatabaseUrl( $database ) );
		}
	}
}
