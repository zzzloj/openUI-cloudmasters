<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Formats downloads search results
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_downloads extends search_format
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		/* Set up wrapper */
		$this->templates = array( 'group' => 'downloads_external', 'template' => 'searchResultsWrapper' );
		
		ipsRegistry::getAppClass('downloads');
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('downloadsTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'downloadsTags', classes_tags_bootstrap::run( 'downloads', 'files' ) );
		}
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		/* Get some member data :O */
		if ( is_array($rows) && count($rows) )
		{
			$_members = array();
			$members  = array();
			
			foreach( $rows as $file )
			{
				$_tmp = IPSSearchRegistry::get('downloads.searchInKey') == 'files' ? intval($file['file_submitter']) : intval($file['comment_mid']);
				
				if ( $_tmp )
				{
					$_members[ $_tmp ] = $_tmp;
				}
			}
			
			/* Got any? */
			if ( count($_members) )
			{
				$members = IPSMember::load( $_members, 'extendedProfile', 'id' );
			}
			
			$members[0] = IPSMember::setUpGuest();
			
			foreach( $rows as $key => $data )
			{
				if( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
				{
					if ( isset($members[ $data['file_submitter'] ]) )
					{
						$rows[ $key ] = array_merge( $data, IPSMember::buildDisplayData( $members[ $data['file_submitter'] ] ) );
					}
				}
				else
				{
					if ( isset($members[ $data['comment_mid'] ]) )
					{
						$rows[ $key ] = array_merge( $data, IPSMember::buildDisplayData( $members[ $data['comment_mid'] ] ) );
					}
				}
			}
		}
		
		return parent::parseAndFetchHtmlBlocks( $rows );
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		$template = ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' ) ? 'fileSearchResult' : 'commentSearchResult';
		
		return array( ipsRegistry::getClass('output')->getTemplate('downloads_external')->$template( $data, IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false, 0 ) );
	}

	/**
	 * Return the output for the followed content results
	 *
	 * @param	array 	$results	Array of results to show
	 * @param	array 	$followData	Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		/* Files? */
		if( IPSSearchRegistry::get('in.followContentType') == 'files' )
		{
			return $this->registry->output->getTemplate('downloads_external')->searchResultsWrapper( $this->parseAndFetchHtmlBlocks( $this->processFollowedResults( $results, $followData ) ), true );
		}
		/* Or categories? */
		else
		{
			$categories	= array();

			if( count($results) )
			{
				/* Get category data */
				foreach( $results as $result )
				{
					$cinfo	= $this->registry->getClass('categories')->cat_lookup[ $result ];
					$cid	= $result;
					
					if( !count($cinfo) OR !in_array( $cinfo['cid'], $this->registry->getClass('categories')->member_access['show'] ) )
					{
						continue;
					}

					$cinfo['can_approve']	= $this->registry->getClass('idmFunctions')->checkPerms( array( 'file_cat' => $cid ) );
					$cinfo['subcategories']	= "";
					$rtime					= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $cinfo['cid'] ), 'downloads' );
					
					if( !isset($cinfo['_has_unread']) )
					{
						$cinfo['_has_unread']	= ( $cinfo['cfileinfo']['date'] && $cinfo['cfileinfo']['date'] > $rtime ) ? 1 : 0;
					}

					if( count($this->registry->getClass('categories')->subcat_lookup[$cid]) > 0 )
					{
						$sub_links = array();
						
						foreach( $this->registry->getClass('categories')->subcat_lookup[$cid] as $blank_key => $subcat_id )
						{
							if( in_array( $subcat_id, $this->registry->getClass('categories')->member_access['show'] ) )
							{
								$subcat_data = $this->registry->getClass('categories')->cat_lookup[ $subcat_id ];
							
								if ( is_array( $subcat_data ) )
								{
									$subcattime	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $subcat_data['cid'] ), 'downloads' );
									
									if( !isset($subcat_data['new']) )
									{
										$subcat_data['new']	= ( $subcat_data['cfileinfo']['date'] && $subcat_data['cfileinfo']['date'] > $subcattime ) ? 1 : 0;
									}

									$sub_links[] = $subcat_data;
								}
							}
						}
						
						$cinfo['subcategories'] = $sub_links;
					}

					$categories[ $result ]	= $cinfo;
				}
				
				/* Merge in follow data */
				foreach( $followData as $_follow )
				{
					$categories[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
				}
			}
			
			/* Get some member data :O */
			if ( is_array($categories) && count($categories) )
			{
				$_members = array();
				$members  = array();
				
				foreach( $categories as $cat )
				{
					$_tmp = intval($cat['cfileinfo']['mid']);
					
					if ( $_tmp )
					{
						$_members[ $_tmp ] = $_tmp;
					}
				}
				
				/* Got any? */
				if ( count($_members) )
				{
					$members = IPSMember::load( $_members, 'extendedProfile', 'id' );
				}
				
				$members[0] = IPSMember::setUpGuest();
				
				foreach( $categories as $key => $data )
				{
					if ( isset($members[ $data['cfileinfo']['mid'] ]) )
					{
						$categories[ $key ]['member'] = IPSMember::buildDisplayData( $members[ $data['cfileinfo']['mid'] ] );
					}
				}
			}
			
			return $this->registry->output->getTemplate('downloads_external')->followedCategoryResults( $categories );
		}
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return array
	 */
	public function processFollowedResults( $ids, $followData=array() )
	{
		/* Topics? */
		if( IPSSearchRegistry::get('in.followContentType') == 'files' )
		{
			return $this->processResults_files( $ids, $followData );
		}

		return $ids;
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return	@e array
	 */
	public function processResults( $ids, $followData=array() )
	{
		if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
		{
			/* Set up wrapper */		
			return $this->processResults_files( $ids, $followData );
		}
		else
		{
			return $this->processResults_comments( $ids );
		}
	}
	
	/**
	 * Formats data for files
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return	@e array
	 */
	public function processResults_files( $ids, $followData=array() )
	{
		$rows	= array();
		$_files	= array();

		/* Load the data if needed */
		if( $ids[0] AND intval($ids[0]) == $ids[0] )
		{
			$_fids	= implode( ',', $ids );
						
			if ( !empty( $_fids ) )
			{
				$this->DB->build( array( 
											'select'   => "f.*",
											'from'	   => array( 'downloads_files' => 'f' ),
											'where'	   => "f.file_id IN(" . $_fids . ")",
											'add_join' => array(
																	array(
																			'select' => 'i.*',
																			'from'   => array( 'permission_index' => 'i' ),
																			'where'  => "i.app='downloads' AND i.perm_type='cat' AND i.perm_type_id=f.file_cat",
																			'type'   => 'left',
																		),
																	array(
																			'select' => 'mem.*',
																			'from'   => array( 'members' => 'mem' ),
																			'where'  => 'mem.member_id=f.file_submitter',
																			'type'   => 'left',
																		),
																	array(
																			'select' => 'pp.*',
																			'from'   => array( 'profile_portal' => 'pp' ),
																			'where'  => 'mem.member_id=pp.pp_member_id',
																			'type'   => 'left',
																		),
																	$this->registry->downloadsTags->getCacheJoin( array( 'meta_id_field' => 'f.file_id' ) )
																	)													
																)	
								);
				$this->DB->execute();
				
				/* Sort */
				while( $r = $this->DB->fetch() )
				{
					$_files[ $r['file_id'] ] = IPSMember::buildDisplayData( $r, array( 'reputation' => 0, 'warn' => 0 ) );
				}
				
				/* Get the 'follow' meta data? */
				if( is_array($followData) && count($followData) )
				{
					$followData = classes_like_meta::get( $followData );
	
					/* Merge the data from the follow class into the results */
					foreach( $followData as $_formatted )
					{
						$_files[ $_formatted['like_rel_id'] ]['_followData']	= $_formatted;
					}
				}
			}
		}

		foreach( $ids as $i => $d )
		{
			if( intval($d) == $d )
			{
				if( !$_files[ $d ]['file_id'] )
				{
					continue;
				}
				
				//-------------------------------------------
				// Get tags
				//-------------------------------------------
				
				if ( ! empty( $_files[ $d ]['tag_cache_key'] ) )
				{
					$_files[ $d ]['tags'] = $this->registry->downloadsTags->formatCacheJoinData( $_files[ $d ] );
				}
		
				$rows[ $i ] = $this->genericizeResults( $_files[ $d ] );
			}
			else
			{
				if( !$d['file_id'] )
				{
					continue;
				}
				
				//-------------------------------------------
				// Get tags
				//-------------------------------------------
				
				if ( ! empty( $d['tag_cache_key'] ) )
				{
					$d['tags'] = $this->registry->downloadsTags->formatCacheJoinData( $d );
				}
				
				$rows[ $i ] = $this->genericizeResults( $d );
			}
		}
		
		return $rows;	
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @return array
	 */
	public function processResults_comments( $ids )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$sortKey			= 'comment_date';
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = 'numerical';
			
			$this->DB->build( array(
									'select'	=> 'c.*, c.comment_mid as comment_member_id',
									'from'		=> array( 'downloads_comments' => 'c' ),
		 							'where'		=> 'c.comment_id IN( ' . implode( ',', $ids ) . ')',
									'limit'		=> array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'add_join'	=> array(
											 				array(
												 					'select'	=> 'f.*',
																	'from'		=> array( 'downloads_files' => 'f' ),
																	'where'		=> 'c.comment_fid=f.file_id',
																	'type'		=> 'left'
											 					)
														)													
					)	);
			$this->DB->execute();	
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[] = $row;
			}

			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array( "IPSSearch", "usort" ) );
		
				foreach( $_rows as $id => $row )
				{
					/* Got author but no member data? */
					if ( ! empty( $row['comment_member_id'] ) )
					{
						$members[ $row['comment_member_id'] ] = $row['comment_member_id'];
					}
					
					$results[ $row['comment_id'] ] = $this->genericizeResults( $row );
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['comment_member_id'] ) AND isset( $mems[ $r['comment_member_id'] ] ) )
					{
						$_mem = IPSMember::buildDisplayData( $mems[ $r['comment_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
						$results[ $id ] = array_merge( $results[ $id ], $_mem );
					}
				}
			}
		}

		return $results;
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		$r['app']				= 'downloads';
		$r['content_title']		= $r['file_name'];
		$r['_isRead']			= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['file_cat'], 'itemID' => $r['file_id'], 'itemLastUpdate' => $r['file_updated'] ), 'downloads' );
		$r['_breadcrumb']		= $this->registry->getClass('categories')->getNav( $r['file_cat'] );
		
		if ( IPSSearchRegistry::get('downloads.searchInKey') == 'files' )
		{
			$r['content']			= $r['file_desc'];
			$r['updated']			= $r['file_submitted'];
			$r['type_2']			= 'file';
			$r['type_id_2']			= $r['file_id'];
		}
		else
		{
			$r['content']             = $r['comment_text'];
			$r['updated']             = $r['comment_date'];
			$r['type_2']              = 'comment';
			$r['type_id_2']           = $r['comment_id'];
		}

		return $r;
	}

}