<?php
/**
 * @file		sql.php 	IP.Gallery MySQL search library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-12-03 21:50:28 -0500 (Mon, 03 Dec 2012) $
 * @version		v5.0.5
 * $Revision: 11675 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_gallery extends search_engine
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make sure we have our gallery objects
		//-----------------------------------------

		if ( ! ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$registry->setClass( 'gallery', new $classToLoad( $registry ) );
		}

		//-----------------------------------------
		// Make sure our results hard limit is set
		//-----------------------------------------

		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );

		//-----------------------------------------
		// Make sure 'search in' is set correctly
		//-----------------------------------------

		IPSSearchRegistry::set( 'gallery.searchInKey', in_array( ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'], array( 'images', 'comments', 'albums' ) ) ? ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'] : 'images' );
		
		ipsRegistry::$request['search_app_filters']['gallery']['searchInKey'] = IPSSearchRegistry::get('gallery.searchInKey');

		//-----------------------------------------
		// Pass back to parent to finish setting up
		//-----------------------------------------

		parent::__construct( $registry );
	}
	
	/**
	 * Decide what type of search we're using and return results
	 *
	 * @return	@e array
	 */
	public function search()
	{ 
		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{
			return $this->_imagesSearch();
		}
		else if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			return $this->_albumsSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Perform a comment search.
	 *
	 * @return	@e array
	 */
	public function _commentsSearch()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$count				= 0;
		$results			= array();
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= 'comment_post_date';
		$sortType			= 'numerical';
		$group_by			= 'c.comment_img_id';
		
		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchTitleOnly') )
		{
			$group_by = 'c.comment_id';
		}

		//-----------------------------------------
		// Fetch the comments
		//-----------------------------------------

		$this->DB->build( array( 
								'select'	=> "c.*",
								'from'		=> array( 'gallery_comments' => 'c' ),
								'where'		=> $this->_buildWhereStatement( $search_term, $content_title_only, 'comments' ),
								'limit'		=> array( 0, IPSSearchRegistry::get('set.hardLimit') + 1 ),
								'group'		=> $group_by,
								'add_join'	=> array(
													array(
														'from'	=> array( 'gallery_images' => 'g' ),
														'where'	=> "c.comment_img_id=g.image_id",
														'type'	=> 'left'
														),
													array(
														'from'	=> array( 'gallery_albums' => 'a' ),
														'where'	=> 'g.image_album_id=a.album_id',
														'type'	=> 'left'
														)
													)
						)		);
		$o = $this->DB->execute();
		
		//-----------------------------------------
		// Get the total count
		//-----------------------------------------

		$count = intval( $this->DB->getTotalRows( $o ) );

		//-----------------------------------------
		// Are there more than our limit?
		//-----------------------------------------

		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		//-----------------------------------------
		// Fetch the comments
		//-----------------------------------------

		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['comment_id'] ]	= $r;
		}
		
		//-----------------------------------------
		// Set search registry sort params
		//-----------------------------------------

		IPSSearch::$ask	= $sortKey;
		IPSSearch::$aso	= strtolower( $order_dir );
		IPSSearch::$ast	= $sortType;
		
		//-----------------------------------------
		// Sort
		//-----------------------------------------

		if ( count( $_rows ) )
		{
			usort( $_rows, array( "IPSSearch", "usort" ) );

			//-----------------------------------------
			// Loop through and build sort array
			//-----------------------------------------

			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ]	= $r['comment_id'];
							
				$got++;

				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		//-----------------------------------------
		// Return results
		//-----------------------------------------

		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform an image search.
	 *
	 * @return	@e array
	 */
	public function _imagesSearch()
	{ 
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$count				= 0;
		$results			= array();
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$search_tags		= IPSSearchRegistry::get('in.raw_search_tags');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= '';
		$sortType			= '';
		$_rows				= array();
		$group_by			= '';

		if ( IPSSearchRegistry::get('opt.noPostPreview') OR IPSSearchRegistry::get('opt.searchTitleOnly') )
		{
			$group_by = 'g.image_id';
		}

		//-----------------------------------------
		// Determine sorting
		//-----------------------------------------

		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'image_date';
				$sortType	= 'numerical';
			break;

			case 'title':
				$sortKey	= 'image_caption';
				$sortType	= 'string';
			break;

			case 'views':
				$sortKey	= 'image_views';
				$sortType	= 'numerical';
			break;

			case 'comments':
				$sortKey	= 'image_comments';
				$sortType	= 'numerical';
			break;
		}
		
		//-----------------------------------------
		// Got a search term?  Fetch results.
		//-----------------------------------------

		if ( $search_term || IPSSearchRegistry::get('in.search_author') )
		{
			$this->DB->build( array(
									'select'	=> "g.*",
									'from'		=> array( 'gallery_images' => 'g' ),
									'where'		=> $this->_buildWhereStatement( $search_term, $content_title_only, 'images' ),
									'limit'		=> array( 0, IPSSearchRegistry::get('set.hardLimit') + 1 ),
									'order'		=> 'g.' . $sortKey . ' ' . $order_dir,
									'group'		=> $group_by,
									'add_join'	=> array(
														array(
															'from'	=> array( 'gallery_albums' => 'a' ),
															'where'	=> 'g.image_album_id=a.album_id',
															'type'	=> 'left'
															)
														)
							)		);
			$o = $this->DB->execute();

			while ( $r = $this->DB->fetch() )
			{
				$_rows[ $r['image_id'] ]	= $r;
			}
		}
		
		//-----------------------------------------
		// Got tags to search?  Fetch those results
		//-----------------------------------------

		if ( $search_tags && $this->settings['tags_enabled'] )
		{
			//-----------------------------------------
			// Make sure we have our object
			//-----------------------------------------

			if ( ! $this->registry->isClassLoaded('galleryTags') )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
				$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
			}

			$tags	= $this->registry->galleryTags->search( $search_tags, array(
																				'meta_app'		 => 'gallery',
																				'meta_area'		 => 'images',
																				'meta_id'		 => array_keys( $_rows ),
																				'sortOrder'		 => $sort_order
																				)
														);

			if ( is_array( $tags ) And count( $tags ) )
			{
				$_tagIds	= array();
				$_rows		= array();
				
				foreach( $tags as $id => $data )
				{
					$_tagIds[]	= $data['tag_meta_id'];
				}
			
				//-----------------------------------------
				// Refetch results filtered by tag
				//-----------------------------------------

				$this->DB->build( array(
										'select'	=> "g.*",
										'from'		=> array( 'gallery_images' => 'g' ),
										'where'		=> 'g.image_id IN (' . implode( ",", $_tagIds ) . ')',
										'limit'		=> array( 0, IPSSearchRegistry::get('set.hardLimit') + 1 ),
										'order'		=> 'g.' . $sortKey . ' ' . $order_dir,
										'add_join'	=> array(
															array(
																'from'	=> array( 'gallery_albums' => 'a' ),
																'where'	=> 'g.image_album_id=a.album_id',
																'type'	=> 'left'
																)
															)
								)		);
				$this->DB->execute();

				while ( $r = $this->DB->fetch() )
				{
					$_rows[ $r['image_id'] ]	= $r;
				}
			}
		}
		
		//-----------------------------------------
		// Get our count
		//-----------------------------------------

		$count	= count( $_rows );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count	= IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}
		
		//-----------------------------------------
		// Set search registry sort params
		//-----------------------------------------

		IPSSearch::$ask	= $sortKey;
		IPSSearch::$aso	= strtolower( $order_dir );
		IPSSearch::$ast	= $sortType;
		
		//-----------------------------------------
		// Sort results
		//-----------------------------------------

		if ( count( $_rows ) )
		{
			usort( $_rows, array("IPSSearch", "usort") );

			//-----------------------------------------
			// Build our final sorted array
			//-----------------------------------------

			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $r['image_id'];
							
				$got++;

				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		//-----------------------------------------
		// Return results
		//-----------------------------------------

		return array( 'count' => $count, 'resultSet' => $rows );
	}
	

	/**
	 * Perform an albums search.
	 *
	 * @return	@e array
	 */
	public function _albumsSearch()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$count				= 0;
		$results			= array();
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= 'album_last_img_date';
		$sortType			= 'numerical';

		//-----------------------------------------
		// Fetch albums
		//-----------------------------------------

		$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																						'isViewable'		=> true,
																						'albumNameContains'	=> $search_term,
																						'album_owner_id'	=> IPSSearchRegistry::get('in.search_author_id'),
																						'sortKey'			=> $sortKey,
																						'sortOrder'			=> $sort_order,
																						'limit'				=> IPSSearchRegistry::get('set.hardLimit') + 1
																						)
																					);
	
		//-----------------------------------------
		// Fetch count
		//-----------------------------------------

		$count	= count( $albums );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}
				
		//-----------------------------------------
		// Set search registry sort params
		//-----------------------------------------

		IPSSearch::$ask	= $sortKey;
		IPSSearch::$aso	= strtolower( $order_dir );
		IPSSearch::$ast	= $sortType;
		
		//-----------------------------------------
		// Sort
		//-----------------------------------------

		if ( count( $albums ) )
		{
			//-----------------------------------------
			// Build our final results array
			//-----------------------------------------

			foreach( $albums as $id => $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ]	= $id;
							
				$got++;

				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		//-----------------------------------------
		// Return the results
		//-----------------------------------------

		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform the viewNewContent search
	 *
	 * @return	@e array
	 */
	public function viewNewContent()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$imgIds		= array();
		$oldStamp	= 0;
		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		$seconds	= IPSSearchRegistry::get('in.period_in_seconds');
		
		IPSSearchRegistry::set( 'opt.searchTitleOnly', true );
		
		//-----------------------------------------
		// Filtering based on time period?
		//-----------------------------------------

		if ( $seconds !== false )
		{
			$oldStamp = IPS_UNIX_TIME_NOW - $seconds;
		}
		else
		{
			//-----------------------------------------
			// Get topic marking library data
			//-----------------------------------------

			$check		= IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
			$imgIds		= $this->registry->getClass('classItemMarking')->fetchReadIds( array(), 'gallery', true );
			$oldStamp	= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'gallery' );
			
			//-----------------------------------------
			// Finalize our timestamp cutoff
			//-----------------------------------------

			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp	= intval( $this->memberData['last_visit'] );
			}
		
			if ( $this->memberData['_cache']['gb_mark__gallery'] && ( $this->memberData['_cache']['gb_mark__gallery'] < $oldStamp ) )
			{
				$oldStamp	= $this->memberData['_cache']['gb_mark__gallery'];
			}
			
			if ( ! $this->memberData['bw_vnc_type'] )
			{
				//$oldStamp	= IPSLib::fetchHighestNumber( array( intval( $this->memberData['_cache']['gb_mark__gallery'] ), intval( $this->memberData['last_visit'] ) ) );
			}
			
			//-----------------------------------------
			// Limit to topic marker cut off
			//-----------------------------------------

			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}
		}

		//-----------------------------------------
		// Build where clause
		//-----------------------------------------

		$where		= array();
		$where[]	= $this->_buildWhereStatement( '', false, IPSSearchRegistry::get('gallery.searchInKey') );

		//-----------------------------------------
		// Set timestamp cutoff
		//-----------------------------------------

		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'comments' )
		{
			$where[] = "c.comment_post_date > " . $oldStamp;
		}
		else
		{
			$where[] = "g.image_date > " . $oldStamp;
		}
		
		//-----------------------------------------
		// Filter out read ids
		//-----------------------------------------

		if ( count( $imgIds ) )
		{
			$where[]	= "g.image_id NOT IN (" . implode( ",", $imgIds ) . ')';
		}

		//-----------------------------------------
		// Set cut off date in search registry
		//-----------------------------------------

		IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );
		
		//-----------------------------------------
		// Return the results
		//-----------------------------------------

		return $this->_getNonSearchData( implode( " AND ", $where ), $oldStamp );
	}
	
	/**
	 * Perform the viewUserContent search
	 *
	 * @param	array 	Member data we are searching for
	 * @return	@e array
	 */
	public function viewUserContent( $member )
	{
		//-----------------------------------------
		// Ensure date cutoff is set
		//-----------------------------------------

		$this->settings['search_ucontent_days']	= ( $this->settings['search_ucontent_days'] ) ? $this->settings['search_ucontent_days'] : 365;
		
		//-----------------------------------------
		// Build where clause
		//-----------------------------------------

		$cutoff		= 0;
		$where		= array();
		$where[]	= $this->_buildWhereStatement( '', false, IPSSearchRegistry::get('gallery.searchInKey') );

		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'comments' )
		{
			$where[]    = "c.comment_author_id=" . intval( $member['member_id'] );
		}
		else
		{
			$where[]    = "g.image_member_id=" . intval( $member['member_id'] );
		}
	
		if ( $this->settings['search_ucontent_days'] )
		{
			$cutoff		= ( time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) ) );

			if ( IPSSearchRegistry::get('gallery.searchInKey') == 'comments' )
			{
				$where[]	= "c.comment_post_date > " . $cutoff;
			}
			else
			{
				$where[]	= "g.image_date > " . $cutoff;
			}
		}
		
		//-----------------------------------------
		// Return the results
		//-----------------------------------------

		return $this->_getNonSearchData( implode( " AND ", $where ), $cutoff, $member['member_id'] );
	}
	
	/**
	 * Perform the search for viewUserContent and viewNewContent
	 *
	 * @param	string	Where clause
	 * @param	int		Date cut off
	 * @param	int		Member ID filter
	 * @return	@e array
	 */
	public function _getNonSearchData( $where, $date=0, $member=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$start		= IPSSearchRegistry::get('in.start');
		$perPage	= IPSSearchRegistry::get('opt.search_per_page');
		$imgIds		= array();

		IPSSearchRegistry::set( 'in.search_sort_by'   , 'date' );

		//-----------------------------------------
		// Return albums
		//-----------------------------------------

		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																							'isViewable'		=> true,
																							'unixCutOff'		=> $date,
																							'album_owner_id'	=> $member,
																							'getTotalCount'		=> true,
																							'sortKey'			=> 'date',
																							'sortOrder'			=> 'desc',
																							'offset'			=> $start,
																							'limit'				=> $perPage
																					)		);
	
			$count	= array( 'count' => $this->registry->gallery->helper('albums')->getCount() );
			$imgIds	= array_keys( $albums );
		}
		else
		{
			//-----------------------------------------
			// Get the count first
			//-----------------------------------------

			if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
			{
				$count	= $this->DB->buildAndFetch( array(
														'select'	=> 'count(*) as count',
														'from'		=> array( 'gallery_images' => 'g' ),
														'where'		=> $where,
														'add_join'	=> array(
																			array(
																				'from'	=> array( 'gallery_albums' => 'a' ),
																				'where'	=> 'g.image_album_id=a.album_id',
																				'type'	=> 'left'
																				)
																			)
												)		);
			}
			else
			{
				$count	= $this->DB->buildAndFetch( array(
														'select'	=> 'count(*) as count',
														'from'		=> array( 'gallery_comments' => 'c' ),
														'where'		=> $where,
														'add_join'	=> array(
																			array(
																				'from'	=> array( 'gallery_images' => 'g' ),
																				'where'	=> 'c.comment_img_id=g.image_id',
																				'type'	=> 'left'
																				),
																			array(
																				'from'	=> array( 'gallery_albums' => 'a' ),
																				'where'	=> 'g.image_album_id=a.album_id',
																				'type'	=> 'left'
																				)
																			)
												)		);
			}
	
			//-----------------------------------------
			// Fetch the results
			//-----------------------------------------

			if ( $count['count'] )
			{
				if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
				{
					$this->DB->build( array(
											'select'	=> 'g.image_id as id',
											'from'		=> array( 'gallery_images' => 'g' ),
											'where'		=> $where,
											'order'		=> 'g.image_date DESC',
											'limit'		=> array( $start, $perPage ),
											'add_join'	=> array(
																array(
																	'from'	=> array( 'gallery_albums' => 'a' ),
																	'where'	=> 'g.image_album_id=a.album_id',
																	'type'	=> 'left'
																	)
																)
									)		);
				}
				else
				{
					$this->DB->build( array(
											'select'	=> 'c.comment_id as id',
											'from'		=> array( 'gallery_comments' => 'c' ),
											'where'		=> $where,
											'order'		=> 'c.comment_post_date DESC',
											'limit'		=> array( $start, $perPage ),
											'add_join'	=> array(
																array(
																	'from'	=> array( 'gallery_images' => 'g' ),
																	'where'	=> 'g.image_id=c.comment_img_id',
																	'type'	=> 'left'
																	),
																array(
																	'from'	=> array( 'gallery_albums' => 'a' ),
																	'where'	=> 'g.image_album_id=a.album_id',
																	'type'	=> 'left'
																	)
																)
									)		);
				}
				$this->DB->execute();
	
				while( $row = $this->DB->fetch() )
				{
					$imgIds[]	= $row['id'];
				}
			}
		}

		//-----------------------------------------
		// Return the results
		//-----------------------------------------

		return array( 'count' => $count['count'], 'resultSet' => $imgIds );
	}
	
	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @param	string	$type				images, comments, albums
	 * @return	@e string
	 */
	protected function _buildWhereStatement( $search_term, $content_title_only=false, $type='images' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$where_clause	= array();
		$searchInCats	= array();

		if( $search_term )
		{
			$search_term	= trim($search_term);
			
			if( $type == 'images' )
			{
				if( $content_title_only )
				{
					$where_clause[]	= "g.image_caption LIKE '%{$search_term}%'";
				}
				else
				{
					$where_clause[]	= "(g.image_caption LIKE '%{$search_term}%' OR g.image_description LIKE '%{$search_term}%')";
				}
			}
			else
			{
				$where_clause[]	= "c.comment_text LIKE '%{$search_term}%'";
			}
		}
		
		//-----------------------------------------
		// Exclude unapproved stuff
		//-----------------------------------------

		if ( ! $this->memberData['g_is_supmod'] )
		{
			if( $type == 'comments' )
			{
				$where_clause[]	= 'c.comment_approved=1';	
			}

			$where_clause[]	= 'g.image_approved=1';	
		}
		
		//-----------------------------------------
		// Date restriction
		//-----------------------------------------

		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			if ( $type == 'comments' )
			{
				$where_clause[]	= $this->DB->buildBetween( "c.comment_post_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
			else
			{
				$where_clause[]	= $this->DB->buildBetween( "g.image_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
		}
		else
		{
			if ( $type == 'comments' )
			{
				if ( $this->search_begin_timestamp )
				{
					$where_clause[]	= "c.comment_post_date > {$this->search_begin_timestamp}";
				}
				
				if ( $this->search_end_timestamp )
				{
					$where_clause[]	= "c.comment_post_date < {$this->search_end_timestamp}";
				}
			}
			else
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[]	= "g.image_date > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[]	= "g.image_date < {$this->search_end_timestamp}";
				}
			}
		}
		
		//-----------------------------------------
		// Add in 'and' and 'or' conditions
		//-----------------------------------------

		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause	= array_merge( $where_clause, $this->whereConditions['AND'] );
		}

		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[]	= '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}
	
		//-----------------------------------------
		// Filtering by category?
		// Note that we do not exclude album images here
		//-----------------------------------------

		if ( ! empty( ipsRegistry::$request['search_app_filters']['gallery']['categoryids'] ) AND count( ipsRegistry::$request['search_app_filters']['gallery']['categoryids'] ) )
		{
			$categories	= IPSLib::cleanIntArray( ipsRegistry::$request['search_app_filters']['gallery']['categoryids'] );
			
			if ( count( $categories ) )
			{
				if ( ipsRegistry::$request['search_app_filters']['gallery']['excludeCategories'] )
				{
					$where_clause[]	= "g.image_category_id NOT IN(" . implode( ',', $categories ) . ')';
				}
				else
				{
					$where_clause[]	= "g.image_category_id IN(" . implode( ',', $categories ) . ')';
				}
			}
		}
		
		//-----------------------------------------
		// Add permissions checks
		//-----------------------------------------

		if ( $this->memberData['member_id'] )
		{
			$_or[]	= "( g." . $this->registry->gallery->helper('image')->sqlWherePrivacy( array( 'friend', 'public', 'private', 'galbum' ) ) . ' AND g.image_member_id=' . $this->memberData['member_id'] . ' )';
			$_or[]	= "( g." . $this->registry->gallery->helper('image')->sqlWherePrivacy( array( 'public', 'galbum' ) ) . ' AND g.image_member_id !=' . $this->memberData['member_id'] . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'g.image_parent_permission', true ) . ') )';
		
			if ( is_array( $this->memberData['_cache']['friends'] ) AND count( $this->memberData['_cache']['friends'] ) )
			{
				$_or[]	= "( g." . $this->registry->gallery->helper('image')->sqlWherePrivacy( array( 'public', 'friend' ) ) . ' AND g.image_member_id IN(' . implode( ",", array_slice( array_keys( $this->memberData['_cache']['friends'] ), 0, 150 ) ) . ') )';
			}

			if ( count( $_or ) )
			{
				$where_clause[]	= '( ' . implode( " OR ", $_or ) . ' )';
			}
		}
		else
		{
			$where_clause[]	= "g." . $this->registry->gallery->helper('image')->sqlWherePrivacy( array( 'public', 'galbum' ) ) . ' AND ( ' .  $this->DB->buildWherePermission( $this->member->perm_id_array, 'g.image_parent_permission', true ) . ')';
		}
			
		//-----------------------------------------
		// Return where clause
		//-----------------------------------------

		return implode( " AND ", $where_clause );
	}
	
	/**
	 * Remap standard columns
	 *
	 * @param	string	$column		Sql table column for this condition
	 * @return	@e string			column
	 */
	public function remapColumn( $column )
	{
		if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{
			$column	= ( $column == 'member_id' ) ? 'g.image_member_id' : $column;
		}
		else
		{
			$column	= ( $column == 'member_id' ) ? 'c.comment_author_id' : $column;
		}

		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @param	array 	$data	Array of filter data
	 * @return	@e array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		//-----------------------------------------
		// Set some defaults
		//-----------------------------------------

		IPSSearchRegistry::set( 'opt.noPostPreview'  , false );
		IPSSearchRegistry::set( 'opt.onlySearchPosts', false );	
		
		return array();
	}

	/**
	 * Can handle boolean searching
	 *
	 * @return	@e boolean
	 */
	public function isBoolean()
	{
		return false;
	}
}