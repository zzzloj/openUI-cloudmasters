<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS revision management
 * Last Updated: $Date: 2011-11-29 17:56:12 -0500 (Tue, 29 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		8th Sept 2009
 * @version		$Revision: 9910 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class ccs_database_revisions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;
	/**#@-*/
	
	/**
	 * Error string stored from last process
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $error		= '';
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Store a new revision
	 *
	 * @access	public
	 * @param	array 		Database data
	 * @param	array 		Data to store
	 * @return	bool		Stored successfuly
	 */
	public function storeRevision( $database=array(), $record=array() )
	{
		if( !count($record) )
		{
			return false;
		}
		
		if( !$database['database_id'] )
		{
			return false;
		}
		
		$recordId	= $record['primary_id_field'];
		
		unset($record['primary_id_field']);
		
		$insert	= array(
						'revision_database_id'	=> $database['database_id'],
						'revision_record_id'	=> $recordId,
						'revision_data'			=> serialize($record),
						'revision_date'			=> time(),
						'revision_member_id'	=> $this->memberData['member_id'],
						);

		$this->DB->insert( 'ccs_database_revisions', $insert );
		
		return true;
	}

	/**
	 * Delete all revisions
	 *
	 * @access	public
	 * @param	int			Database ID
	 * @param	int			Record id to clear revisions for
	 * @return	bool		Deleted successfuly
	 */
	public function deleteAllRevisions( $databaseId=0, $recordId=0 )
	{
		if( !$recordId )
		{
			return false;
		}
		
		$this->DB->delete( 'ccs_database_revisions', 'revision_database_id=' . $databaseId . ' AND revision_record_id=' . $recordId );
		
		return true;
	}
	
	/**
	 * Delete a revision
	 *
	 * @access	public
	 * @param	int			Revision ID to remove
	 * @return	bool		Deleted successfuly
	 */
	public function deleteRevision( $recordId=0 )
	{
		if( !$recordId )
		{
			return false;
		}
		
		$this->DB->delete( 'ccs_database_revisions', 'revision_id=' . $recordId );
		
		return true;
	}
	
	/**
	 * Restore a revision
	 *
	 * @access	public
	 * @param	array 		Database data
	 * @param	int			Revision ID to restore
	 * @return	bool		Restored successfuly
	 */
	public function restoreRevision( $database=array(), $recordId=0 )
	{
		if( !$recordId )
		{
			return false;
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $recordId ) );
		
		if( !$revision['revision_id'] )
		{
			return false;
		}
		
		$data	= unserialize( $revision['revision_data'] );
		$_save	= array();

		//-----------------------------------------
		// Make sure we only try to restore valid fields
		//-----------------------------------------
		
		foreach( $data as $k => $v )
		{
			if( $this->DB->checkForField( $k, $database['database_database'] ) )
			{
				$_save[ $k ]	= $v;
			}
		}
		
		$this->DB->update( $database['database_database'], $_save, 'primary_id_field=' . $revision['revision_record_id'] );
		
		//-----------------------------------------
		// Revision record no longer needed
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_revisions', 'revision_id=' . $recordId );
		
		//-----------------------------------------
		// Recache categories
		//-----------------------------------------

		$this->registry->ccsFunctions->getCategoriesClass( $this->database )->recache();
		
		return true;
	}
}