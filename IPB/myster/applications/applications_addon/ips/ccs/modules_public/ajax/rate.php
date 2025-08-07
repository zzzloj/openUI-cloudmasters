<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.0.1
 * Profile AJAX Tab Loader
 * Last Updated: $Date: 2011-12-08 21:50:46 -0500 (Thu, 08 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 9974 $
 *
 */

class public_ccs_ajax_rate extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$rating_id	= intval($this->request['rating']);
		$rating_id	= $rating_id > 5 ? 5 : $rating_id;
		$rating_id	= $rating_id < 0 ? 0 : $rating_id;
		$record_id	= intval($this->request['record']);
		$databaseId	= intval($this->request['id']);
		$type		= 'new';
		$error 		= array();
		
		if( !$databaseId )
		{
			$error['error_key'] = 'topics_no_tid';
			$this->returnJsonArray( $error );
		}
		
		$database	= $this->caches['ccs_databases'][ $databaseId ];

    	//-----------------------------------------
    	// Check
    	//-----------------------------------------

    	if ( !$database['database_id'] OR !$record_id )
    	{
			$error['error_key'] = 'topics_no_tid';
			$this->returnJsonArray( $error );
    	}
    	
    	if( $this->registry->permissions->check( 'rate', $database ) != TRUE )
    	{
			$error['error_key'] = 'topic_rate_no_perm';
			$this->returnJsonArray( $error );
    	}

		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
    	$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $database['database_database'], 'where' => 'primary_id_field=' . $record_id ) );
    	
    	if ( ! $record['primary_id_field'] )
    	{
			$error['error_key'] = 'topics_no_tid';
			$this->returnJsonArray( $error );
    	}
    	
		if( $record['category_id'] )
		{
			$category	= $this->registry->ccsFunctions->getCategoriesClass( $database )->categories[ $record['category_id'] ];
			
			if( $record['category_id'] AND !$category['category_id'] )
			{
				$error['error_key'] = 'topics_no_tid';
				$this->returnJsonArray( $error );
			}
			else if( $category['category_id'] AND $category['category_has_perms'] )
			{
				if ( $this->registry->permissions->check( 'rate', $category ) != TRUE )
				{
					$error['error_key'] = 'topic_rate_no_perm';
					$this->returnJsonArray( $error );
				}
			}
		}

    	//-----------------------------------------
    	// Have we already rated?
    	//-----------------------------------------

		if( $this->memberData['member_id'] )
		{
			$rating = $this->DB->buildAndFetch( array(	'select'	=> '*',
														'from'		=> 'ccs_database_ratings',
														'where'		=> "rating_user_id={$this->memberData['member_id']} AND rating_database_id={$databaseId} AND rating_record_id={$record_id}" ) );
		}
		else
		{
			$rating = $this->DB->buildAndFetch( array(	'select'	=> '*',
														'from'		=> 'ccs_database_ratings',
														'where'		=> "rating_user_id=0 AND rating_ip_address='{$this->member->ip_address}' AND rating_database_id={$databaseId} AND rating_record_id={$record_id}" ) );

		}
    	
		//-----------------------------------------
		// Already rated?
		//-----------------------------------------
		
		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------
			
			if ( $rating_id != $rating['rating_rating'] )
			{
				$record['rating_value']	= intval( $record['rating_value'] );
				$record['rating_value']	= ( $record['rating_value'] + $rating_id ) - $rating['rating_rating'];
				
				$this->DB->update( 'ccs_database_ratings', array( 'rating_rating' => $rating_id ), 'rating_id=' . $rating['rating_id'] );
				
				$this->DB->update( $database['database_database'], array(	'rating_value'	=> $record['rating_value'],
				 															'rating_real'	=> round( $record['rating_value'] / $record['rating_hits'] ) ), 'primary_id_field=' . $record_id );
				
				$type = 'update';
			}
		}
		
		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------
		
		else
		{
			$record['rating_value']	= intval($record['rating_value']) + $rating_id;
			$record['rating_hits']	= intval($record['rating_hits'])  + 1;
			
			$this->DB->insert( 'ccs_database_ratings', array( 'rating_user_id'			=> $this->memberData['member_id'],
																'rating_database_id'	=> $databaseId,
																'rating_record_id'		=> $record_id,
																'rating_rating'			=> $rating_id,
																'rating_added'			=> time(),
																'rating_ip_address'		=> $this->member->ip_address ) );
																    
			$this->DB->update( $database['database_database'], array(	'rating_hits'	=> intval($record['rating_hits']),
																		'rating_value'	=> intval($record['rating_value']),
																		'rating_real'	=> round( $record['rating_value'] / $record['rating_hits'] ) ), 'primary_id_field=' . $record_id );

			
		}
    	
		$record['rating_real'] = round( $record['rating_value'] / $record['rating_hits'] );

	   	$return	= array(
	   					'rating'	=> $record['rating_value'],
	   					'total'		=> $record['rating_real'],
	   					'average'	=> $record['rating_real'],
	   					'rated'		=> $type
	   					);

		$this->returnJsonArray( $return );
	}
}
