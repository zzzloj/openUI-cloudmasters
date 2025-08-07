<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_form extends search_engine
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{		
		parent::__construct( $registry );
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @access public
	 * @return array
	 */
	public function search()
	{
		/* INIT */ 
		$count       		= 0;
		$results     		= array();
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$post_search_only   = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$sortKey			= '';
		$sortType			= '';
		$rows               = array();
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('display.onlyTitles') )
		{
			$group_by = 'l.log_id';
		}
		
		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'l.log_date';
				$sortType = 'numerical';
			break;
			case 'title':
				$sortKey  = 'l.message';
				$sortType = 'string';
			break;
		}
		
		/* Query the count */	
		$count = $this->DB->buildAndFetch(
											array( 
													'select'   => 'COUNT(*) as total_results',
													'from'	   => array( 'form_logs' => 'l' ),
 													'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only ),
 													'group'    => $group_by,
													'add_join' => array(
																		array(
																				'from'   => array( 'permission_index' => 'i' ),
																				'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
																				'type'   => 'left',
																			),
																		array(
																				'from'   => array( 'profile_friends' => 'friend' ),
																				'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																				'type'   => 'left',
																			),
																		)													
										)  );
		
		/* Do the search */
		$this->DB->build( array( 
								'select'   => "l.*",
								'from'	   => array( 'form_logs' => 'l' ),
 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only ),
								'group'    => $group_by,
								'order'    => $sortKey . ' ' . $sort_order,
								'limit'    => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
								'add_join' => array(
													array(
															'select' => 'i.*',
															'from'   => array( 'permission_index' => 'i' ),
															'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
															'type'   => 'left',
														),
													array(
															'select' => 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
															'from'   => array( 'members' => 'mem' ),
															'where'  => "mem.member_id=l.member_id",
															'type'   => 'left',
														),
													array(
															'from'   => array( 'profile_friends' => 'friend' ),
															'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
															'type'   => 'left',
														),
													)													
									)		);
		$this->DB->execute();
		
		/* Sort */
		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;
		}
	
		/* Return it */
		return array( 'count' => $count['total_results'], 'resultSet' => $rows );
	}
	
		/**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewNewContent()
	{	
		$oldStamp	= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'form' );	
       
		/* Loop through the forums and build a list of forums we're allowed access to */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchTitleOnly' , true );
		IPSSearchRegistry::set('display.onlyTitles'  , true );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );
        
  		/* Finalize times */
		if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
		{
			$oldStamp = intval( $this->memberData['last_visit'] );
		}
		
		/* Start Where */
		$where		= array();
		$where[]	= $this->_buildWhereStatement( '' );

		/* Based on oldest timestamp */
		$where[] = "l.log_date > " . $oldStamp;       
  

		$where = implode( " AND ", $where ); 

		/* Fetch the count */
		$count = $this->DB->buildAndFetch(
											array( 
													'select'	=> 'COUNT(*) as count',
													'from'		=> array( 'form_logs' => 'l' ),
													'where'		=> $where,
													'add_join'	=> array(
																		array(
																				'from'   => array( 'permission_index' => 'i' ),
																				'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
																				'type'   => 'left',
																			),
																		array(
																				'from'   => array( 'profile_friends' => 'friend' ),
																				'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																				'type'   => 'left',
																			),
																		)													
										)  );
		
		/* Fetch the data */
		$logs = array();
		
		if( $count['count'] )
		{
			$this->DB->build( array( 
										'select'	=> "l.*",
										'from'		=> array( 'form_logs' => 'l' ),
										'where'		=> $where,
										'order'		=> 'l.log_date DESC',
										'limit'		=> array( $start, $perPage ),
										'add_join'	=> array(
															array(
																	'select' => 'i.*',
																	'from'   => array( 'permission_index' => 'i' ),
																	'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
																	'type'   => 'left',
																),
															array(
																	'select' => 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
																	'from'   => array( 'members' => 'mem' ),
																	'where'  => "mem.member_id=l.member_id",
																	'type'   => 'left',
																),
															array(
																	'from'   => array( 'profile_friends' => 'friend' ),
																	'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																	'type'   => 'left',
																),
															)													
							)		);
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$logs[] = $row;
			}
		}

		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );

		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $logs );
	}	
	
	/**
	 * Perform the viewUserContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewUserContent( $member )
	{
		/* Init */
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		IPSSearchRegistry::set( 'in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set( 'in.search_sort_order', 'desc' );
		IPSSearchRegistry::set( 'gallery.searchInKey', 'images' );
		
		/* Ensure we limit by date */
		$this->settings['search_ucontent_days'] = ( $this->settings['search_ucontent_days'] ) ? $this->settings['search_ucontent_days'] : 365;
		
		/* Start Where */
		$where	= array();
		$where[]	= $this->_buildWhereStatement( '' );
		
		/* Search by author */
		$where[] = "l.member_id=" . intval( $member['member_id'] );
	
		$where = implode( " AND ", $where );
		
		/* Fetch the count */
		$count = $this->DB->buildAndFetch(
											array( 
													'select'	=> 'COUNT(*) as count',
													'from'		=> array( 'form_logs' => 'l' ),
													'where'		=> $where,
													'add_join'	=> array(
																		array(
																				'from'   => array( 'permission_index' => 'i' ),
																				'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
																				'type'   => 'left',
																			),
																		array(
																				'from'   => array( 'profile_friends' => 'friend' ),
																				'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																				'type'   => 'left',
																			),
																		)
										)  );

		/* Fetch the data */
		$logs = array();
		
		if ( $count['count'] )
		{
			$this->DB->build( array( 
										'select'	=> "l.*",
										'from'		=> array( 'form_logs' => 'l' ),
										'where'		=> $where,
										'order'		=> 'l.log_date DESC',
										'limit'		=> array( $start, $perPage ),
										'add_join'	=> array(
															array(
																	'select' => 'i.*',
																	'from'   => array( 'permission_index' => 'i' ),
																	'where'  => "i.perm_type='form' AND i.perm_type_id=l.log_form_id AND i.app='form'",
																	'type'   => 'left',
																),
															array(
																	'select' => 'mem.members_display_name, mem.member_group_id, mem.mgroup_others',
																	'from'   => array( 'members' => 'mem' ),
																	'where'  => "mem.member_id=l.member_id",
																	'type'   => 'left',
																),
															array(
																	'from'   => array( 'profile_friends' => 'friend' ),
																	'where'  => 'friend.friends_member_id=l.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																	'type'   => 'left',
																),
															)
							)		);
			$this->DB->execute();

			while( $row = $this->DB->fetch() )
			{
				$logs[] = $row;
			}
		}

		/* Return it */
		return array( 'count' => $count['count'], 'resultSet' => $logs );
	}
		
	/**
	 * Builds the where portion of a search string
	 *
	 * @access	private
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @return	string
	 **/
	private function _buildWhereStatement( $search_term, $content_title_only=false )
	{		
		/* INI */
		$where_clause = array();
				
		if( $search_term )
		{
			if( $content_title_only )
			{
				$where_clause[] = "l.message LIKE '%{$search_term}%'";
			}
			else
			{
				$where_clause[] = "(l.message LIKE '%{$search_term}%' )";
			}
		}
		
		/* Exclude some items */
		if( !$this->memberData['g_is_supmod'] )
		{			
			/* Owner only */
			$where_clause[] = '(i.owner_only=0 OR l.member_id=' . $this->memberData['member_id'] . ')';
			
			/* Friend only */
			$where_clause[] = '(i.friend_only=0 OR friend.friends_id ' . $this->DB->buildIsNull( false ) . ')';
			
			/* Authorized users only */
			$where_clause[] = '(i.authorized_users ' . $this->DB->buildIsNull() . " OR i.authorized_users='' OR l.member_id=" . $this->memberData['member_id'] . " OR i.authorized_users LIKE '%," . $this->memberData['member_id'] . ",%')";
		}
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[] = $this->DB->buildBetween( "l.log_date", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = "l.log_date > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = "l.log_date < {$this->search_end_timestamp}";
			}
		}
		
		/* Add in AND where conditions */
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause = array_merge( $where_clause, $this->whereConditions['AND'] );
		}
		
		/* ADD in OR where conditions */
		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[] = '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		/* Permissions */
		$where_clause[] = $this->DB->buildRegexp( "i.perm_3", $this->member->perm_id_array );
			
		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id' ? 'l.member_id' : $column;

		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 **/
	public function buildFilterSQL( $data )
	{
		/* INIT */
		$return = array();
		
		/* Set up some defaults */
		IPSSearchRegistry::set( 'opt.noPostPreview'  , false );
		IPSSearchRegistry::set( 'opt.onlySearchPosts', false );
		
		return array();
	}

	/**
	 * Can handle boolean searching
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isBoolean()
	{
		return false;
	}
}