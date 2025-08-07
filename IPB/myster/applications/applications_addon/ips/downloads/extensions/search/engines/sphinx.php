<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Basic Downloads Search
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_downloads extends search_engine
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Hard limit */
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );

		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $registry->isClassLoaded('downloadsTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$registry->setClass( 'downloadsTags', classes_tags_bootstrap::run( 'downloads', 'files' ) );
		}
			
		parent::__construct( $registry );
	}
	
	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @return array
	 */
	public function search()
	{
		//-----------------------------------------
		// Run search
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
		{
			return $this->_filesSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Search files
	 *
	 * @return array
	 */
	public function _filesSearch()
	{
		/* Init */
		$start				= intval( IPSSearchRegistry::get('in.start') );
		$perPage			= IPSSearchRegistry::get('opt.search_per_page');
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$search_tags        = IPSSearchRegistry::get('in.raw_search_tags');
		$search_ids			= array();
		$groupby			= false;

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'file_submitted';
			break;
			case 'update':
				$sortKey	= 'file_updated';
			break;
			case 'title':
				$sortKey	= 'fordinal';
			break;
			case 'downloads':
				$sortKey	= 'file_downloads';
			break;
			case 'views':
				$sortKey	= 'file_views';
			break;
			case 'rating':
				$sortKey	= 'file_rating';
			break;
		}

		if ( $sort_order == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
		}
			
		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), intval($perPage) );

		/* Date limit */
		if ( $this->search_begin_timestamp )
		{
			if ( ! $this->search_end_timestamp )
			{
				$this->search_end_timestamp = time() + 100;
			}
			
			$this->sphinxClient->SetFilterRange( 'file_updated', $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		
		/* Permissions/restrictions */
		if( $this->memberData['g_is_supmod'] )
		{
			$this->sphinxClient->SetFilter( 'file_open', array( 0, 1 ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'file_open', array( 1 ) );
		}

		$this->_getDownloadsClasses();
		
		/* Generic category filtering */
		$_cats	= array( 0 );
		
		/* Did we search by category? - if so use those instead */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] ) AND count( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] ) )
		{
			foreach( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] as $cat )
			{
				if( $cat )
				{
					if( in_array( $cat, $this->registry->getClass('categories')->member_access['show'] ) )
					{
						$_cats[]	= intval($cat);
					}
				}
			}
		}
		else
		{
			if( count( $this->registry->getClass('categories')->cat_lookup ) > 0 )
			{
				foreach( $this->registry->getClass('categories')->cat_lookup as $cid => $cinfo )
				{
					if( in_array( $cid, $this->registry->getClass('categories')->member_access['show'] ) )
					{
						$_cats[]	= $cid;
					}
				}
			}
		}

		$this->sphinxClient->SetFilter( 'file_cat', $_cats );
		
		/* Filtering by paid or free? */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['downloads']['freepaid'] ) )
		{
			switch( ipsRegistry::$request['search_app_filters']['downloads']['freepaid'] )
			{
				case 'free':
					$this->sphinxClient->SetFilterFloatRange( 'file_cost', 0.00, 0.00 );
				break;
				
				case 'paid':
					$this->sphinxClient->SetFilterFloatRange( 'file_cat', 0.01, 999999999.99 );
				break;
			}
		}
		
		/* Check tags */
		$tagIds = array();
		
		if ( $search_tags && $this->settings['tags_enabled'] )
		{
			$search_tags = explode( ",", $search_tags );
			
			$this->DB->build( array( 'select' => 'tag_id',
									 'from'   => 'core_tags',
									 'where'  => "tag_meta_app='downloads' AND tag_meta_area='files' AND (" . $this->DB->buildLikeChain( 'tag_text', $search_tags, false ) . ')',
									 'limit'  => array( 0, 500 ) ) );
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				$tagIds[] = $row['tag_id'];
			}
			
			if ( count( $tagIds ) )
			{
				$this->sphinxClient->SetFilter( 'tag_id', $tagIds );
			}
		}

		/* Set search term */
		$_s		= '';
		
		if( $search_term )
		{
			switch( IPSSearchRegistry::get('opt.searchType') )
			{
				case 'both':
				default:
					$_s		= strstr( $search_term, '"' ) ? '@file_desc  ' . $search_term . ' | @file_name ' . $search_term : '@(file_desc,file_name) ' . $search_term;
				break;
				
				case 'titles':
					$_s		= '@file_name ' . $search_term;
				break;
				
				case 'content':
					$_s		= '@file_desc ' . $search_term;
				break;
			}
		}
		
		/* Exclude an individual file?  Useful for similar content */
		if( IPSSearchRegistry::get('downloads.excludeFileId') )
		{
			$this->sphinxClient->SetFilter( 'search_id', array( intval(IPSSearchRegistry::get('downloads.excludeFileId')) ), true );
		}
		
		/* Custom field? */
		$_s	= $this->_checkCustomFields( $_s );
		
		/* Run search and log warnings */
		$result	= $this->sphinxClient->Query( $_s, $this->settings['sphinx_prefix'] . 'downloads_search_main,' . $this->settings['sphinx_prefix'] . 'downloads_search_delta' );

		$this->logSphinxWarnings();
		
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = intval($res['attrs']['file_id']);
			}
		}

		/* Return it */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}
	
	/**
	 * Search comments
	 *
	 * @return array
	 */
	public function _commentsSearch()
	{
		/* Init */
		$start				= intval( IPSSearchRegistry::get('in.start') );
		$perPage			= IPSSearchRegistry::get('opt.search_per_page');
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$search_ids			= array();
		$groupby			= false;
		$sortKey			= 'comment_date';

		if ( $sort_order == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $sortKey );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $sortKey );
		}
			
		/* Limit Results */
		$this->sphinxClient->SetLimits( intval($start), intval($perPage) );

		/* Date limit */
		if ( $this->search_begin_timestamp )
		{
			if ( ! $this->search_end_timestamp )
			{
				$this->search_end_timestamp = time() + 100;
			}
			
			$this->sphinxClient->SetFilterRange( 'comment_date', $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		
		/* Permissions/restrictions */
		if( $this->memberData['g_is_supmod'] )
		{
			$this->sphinxClient->SetFilter( 'file_open', array( 0, 1 ) );
			$this->sphinxClient->SetFilter( 'comment_open', array( 0, 1 ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'file_open', array( 1 ) );
			$this->sphinxClient->SetFilter( 'comment_open', array( 1 ) );
		}

		$this->_getDownloadsClasses();
		
		/* Generic category filtering */
		$_cats	= array( 0 );
		
		/* Did we search by category? - if so use those instead */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] ) AND count( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] ) )
		{
			foreach( ipsRegistry::$request['search_app_filters']['downloads']['downloads'] as $cat )
			{
				if( $cat )
				{
					if( in_array( $cat, $this->registry->getClass('categories')->member_access['show'] ) )
					{
						$_cats[]	= intval($cat);
					}
				}
			}
		}
		else
		{
			if( count( $this->registry->getClass('categories')->cat_lookup ) > 0 )
			{
				foreach( $this->registry->getClass('categories')->cat_lookup as $cid => $cinfo )
				{
					if( in_array( $cid, $this->registry->getClass('categories')->member_access['show'] ) )
					{
						$_cats[]	= $cid;
					}
				}
			}
		}
		
		$this->sphinxClient->SetFilter( 'file_cat', $_cats );
		
		/* Filtering by paid or free? */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['downloads']['freepaid'] ) )
		{
			switch( ipsRegistry::$request['search_app_filters']['downloads']['freepaid'] )
			{
				case 'free':
					$this->sphinxClient->SetFilterFloatRange( 'file_cost', 0.00, 0.00 );
				break;
				
				case 'paid':
					$this->sphinxClient->SetFilterFloatRange( 'file_cat', 0.01, 999999999.99 );
				break;
			}
		}

		/* Set search term */
		$_s		= $search_term ? '@comment_text ' . $search_term : '';

		/* Custom field? */
		$_s	= $this->_checkCustomFields( $_s );

		/* Run search and log warnings */
		$result	= $this->sphinxClient->Query( $_s, $this->settings['sphinx_prefix'] . 'downloads_comments_main,' . $this->settings['sphinx_prefix'] . 'downloads_comments_delta' );
		
		$this->logSphinxWarnings();
		
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$search_ids[] = intval($res['attrs']['search_id']);
			}
		}

		/* Return it */
		return array( 'count' => intval( $result['total_found'] ) > 1000 ? 1000 : $result['total_found'], 'resultSet' => $search_ids );
	}
	
	/**
	 * Add custom field searching, if applicable
	 * 
	 * @param	string		Search string
	 * @return	string		Search string with custom fields
	 */
 	protected function _checkCustomFields( $_s )
 	{
 		$_fields	= array();
 		
 		if( is_array($this->request['search_app_filters']['downloads']) AND count($this->request['search_app_filters']['downloads']) )
 		{
 			$cfields	= $this->cache->getCache('idm_cfields');
 			
			foreach( $this->request['search_app_filters']['downloads'] as $k => $v )
			{
				if( $v AND preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					if( $cfields[ $matches[1] ]['cf_search'] )
					{
						$_fields[]	= "@field_{$matches[1]} {$v}";
					}
				}
			}
		}
		
		if( !count($_fields) )
		{
			return $_s;
		}
		else
		{
			return $_s . ' ' . implode( ' ', $_fields );
		}
 	}
	
	/**
	 * Perform the search.
	 * Returns an array with the results
	 *
	 * @param	array 		Member data for member we are searching
	 * @return	array
	 * @see		search_engine_downloads::search
	 */
	public function viewUserContent( $member )
	{
		/* Set filters for search() method */
		
		switch( IPSSearchRegistry::get('in.userMode') )
		{
			default:
			case 'all': 
				IPSSearchRegistry::set('opt.searchType', 'both' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;
			case 'title': 
				IPSSearchRegistry::set('opt.searchType', 'titles' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
			case 'content': 
				IPSSearchRegistry::set('opt.searchType', 'both' );
				IPSSearchRegistry::set('opt.noPostPreview'  , false );
			break;	
		}

		/* Filter by member? */
		if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
		{
			$this->sphinxClient->SetFilter( 'file_submitter', array( intval( $member['member_id'] ) ) );
		}
		else
		{
			$this->sphinxClient->SetFilter( 'comment_member_id', array( intval( $member['member_id'] ) ) );
		}
		
		/* Set timeframe cutoff */
		if ( $this->settings['search_ucontent_days'] )
		{
			$this->setDateRange( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );
		}
		
		/* Run search */
		return $this->search();
	}

	/**
	 * Perform the viewNewContent search.
	 * Returns an array with the results
	 *
	 * @return	array
	 * @see		search_engine_downloads::search
	 */
	public function viewNewContent()
	{
		$fids			= $this->registry->getClass('classItemMarking')->fetchReadIds( array(), 'downloads', true );
		$oldStamp		= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'downloads' );
		$followedOnly	= $this->memberData['member_id'] ? IPSSearchRegistry::get('in.vncFollowFilterOn' ) : false;
		$check			= IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchType', 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );
		
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$oldStamp	= IPSLib::fetchHighestNumber( array( intval( $this->memberData['_cache']['gb_mark__downloads'] ), intval( $this->memberData['last_visit'] ), $oldStamp ) );
			$fids		= array();
		}
		else
		{
			/**
			 * Check for additional read ids
			 */
			$this->_getDownloadsClasses();

			foreach( $this->registry->getClass('categories')->cat_lookup as $id => $data )
			{
				$lMarked	= $this->registry->getClass('classItemMarking')->fetchTimeLastMarked( array( 'forumID' => $id ), 'downloads' );
				
				if( $data['cfileinfo']['date'] > $lMarked )
				{
					$_fids = $this->registry->getClass('classItemMarking')->fetchReadIds( array( 'forumID' => $id ), 'downloads', false );
					
					if( is_array( $_fids ) )
					{
						foreach( $_fids as $k => $v )
						{
							$fids[ $k ]	= $v;
						}
					}
				}
			}

			if ( intval( $this->memberData['_cache']['gb_mark__downloads'] ) > $oldStamp  )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__downloads'];
			}
			
			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}
			
			/* Older than 3 months.. then limit */
			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}
		}
		
		/* Set the timestamps */
		$this->setDateRange( $oldStamp );
		
		//-----------------------------------------
		// Only content we are following?
		//-----------------------------------------
		
		if ( $followedOnly )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$like = classes_like::bootstrap( 'downloads', 'files' );
			
			$followedFiles	= $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedFiles = ( $followedFiles === null ) ? array() : array_keys( $followedFiles );
			
			if( !count($followedFiles) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			else
			{
				if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
				{
					$this->sphinxClient->SetFilter( 'search_id', $followedFiles );
				}
				else
				{
					$this->sphinxClient->SetFilter( 'file_id', $followedFiles );
				}
			}
		}

		/* Try and limit the files */
		if ( is_array( $fids ) AND count( $fids ) )
		{
			if ( count( $fids ) > 300 )
			{
				/* Sort by last read date */
				arsort( $fids, SORT_NUMERIC );
				$fids = array_slice( $fids, 0, 300 );
			}
							
			$this->DB->build( array( 'select' => 'file_id, file_updated',
							   		 'from'   => 'downloads_files',
							   	     'where'  => "file_id IN (" . implode( ",", array_keys( $fids ) ) . ')' ) );
							   
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				/* Posted in since last read? */
				if ( $row['file_updated'] > $fids[ $row['file_id'] ] )
				{
					unset( $fids[ $row['file_id'] ] );
				}
			}
			
			if ( count( $fids ) )
			{
				$fids = array_keys( $fids );
			}
		}
		
		/* Set read files */
		if ( count( $fids ) )
		{
			if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
			{
				$this->sphinxClient->SetFilter( 'search_id', $fids, TRUE );
			}
			else
			{
				$this->sphinxClient->SetFilter( 'file_id', $fids, TRUE );
			}
		}

		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------
		
		if( IPSSearchRegistry::get('in.userMode') AND IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
		{
			switch( IPSSearchRegistry::get('in.userMode') )
			{
				default:
				case 'all': 
					$_fileIds	= $this->_getFileIdsFromComments();
					
					if( count($_fileIds) )
					{
						$this->sphinxClient->SetFilter( 'file_submitter', $this->memberData['member_id'] );
						$this->sphinxClient->SetFilter( 'search_id', $_fileIds );
					}
					else
					{
						$this->sphinxClient->SetFilter( 'file_submitter', $this->memberData['member_id'] );
					}
				break;
				case 'title':
					$this->sphinxClient->SetFilter( 'file_submitter', $this->memberData['member_id'] );
				break;
			}
		}
		
		/* Set up some vars */
		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		return $this->search();
	}

	/**
	 * Find files we have commented on
	 *
	 * @return	array
	 */
	protected function _getFileIdsFromComments()
	{
		$ids	= array();
		
		$this->DB->build( array(
								'select'	=> $this->DB->buildDistinct('comment_fid'),
								'from'		=> 'downloads_comments',
								'where'		=> 'comment_open=1 AND comment_mid=' . $this->memberData['member_id'],
								'limit'		=> array( 0, 200 )
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ids[]	= $r['comment_fid'];
		}
		
		return $ids;
	}

	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		$column = $column == 'member_id' ? 'file_submitter' : $column;

		return $column;
	}
	
	/**
	 * Get our helper classes
	 *
	 * @return void
	 */
	protected function _getDownloadsClasses()
	{
		if( !$this->registry->isClassLoaded('categories') )
		{
			ipsRegistry::getAppClass( 'downloads' );
		}
	}

	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		return array();
	}
}