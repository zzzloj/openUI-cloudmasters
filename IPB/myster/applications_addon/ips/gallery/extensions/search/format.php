<?php
/**
 * @file		format.php 	IP.Gallery search result formatting plugin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-08-31 12:18:47 -0400 (Fri, 31 Aug 2012) $
 * @version		v5.0.5
 * $Revision: 11313 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_gallery extends search_format
{
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Let parent do its thing
		//-----------------------------------------

		parent::__construct( $registry );
		
		//-----------------------------------------
		// Get language file
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		//-----------------------------------------
		// Grab Gallery objects
		//-----------------------------------------

		if( !ipsRegistry::isClassLoaded( 'gallery' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Parse search results
	 *
	 * @param	array 	$r		Search result
	 * @return	@e array		Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$search_term	= IPSSearchRegistry::get('in.clean_search_term');
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------

		if  ( ! is_array( $rows ) OR ! count( $rows ) )
		{
			return array();
		}
		
		//-----------------------------------------
		// Build the HTML for each row
		//-----------------------------------------

		foreach( $rows as $id => $data )
		{
			//-----------------------------------------
			// Format the content
			//-----------------------------------------

			list( $html, $sub ) = $this->formatContent( $data );
			
			if( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
			{
				$data['content_title']	= IPSText::searchHighlight( $data['content_title'], $search_term );
			}
			else
			{
				$data['content']		= IPSText::searchHighlight( $data['content'], $search_term );
			}

			$results[ $id ] = array( 'html' => $html, 'app' => $data['app'], 'type' => $data['type'], 'sub' => $sub );
		}

		//-----------------------------------------
		// Return formatted results
		//-----------------------------------------

		return $results;
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @param	array   $search_row		Array of data
	 * @return	@e array 	(0 => HTML, 1 => flag if there is a subsection)
	 */
	public function formatContent( $data )
	{
		//-----------------------------------------
		// Format some data
		//-----------------------------------------

		$data['misc']	= unserialize( $data['misc'] );		
		$data['thumb']	= ( ! isset( $data['thumb'] ) ) ? $this->registry->gallery->helper('image')->makeImageLink( $data, array( 'type' => 'thumb', 'link-type' => 'page' ) ) : $data['thumb'];

		//-----------------------------------------
		// Determine appropriate skin template
		//-----------------------------------------

		switch ( IPSSearchRegistry::get('gallery.searchInKey') )
		{
			case 'images':
				$template	= 'galleryImageSearchResult';
			break;

			case 'albums':
				$template	= 'galleryAlbumSearchResult';
			break;

			default:
				$template	= 'galleryCommentSearchResult';
			break;
		}

		//-----------------------------------------
		// Return the results as an array
		//-----------------------------------------

		return array( ipsRegistry::getClass('output')->getTemplate('gallery_external')->$template( $data, IPSSearchRegistry::get('opt.searchTitleOnly') ), 0 );
	}

	/**
	 * Return the output for the followed content results
	 *
	 * @param	array		$results		Array of results to show
	 * @param	array		$followData		Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		//-----------------------------------------
		// Looking at images?
		//-----------------------------------------

		if( IPSSearchRegistry::get('in.followContentType') == 'images' )
		{
			IPSSearchRegistry::set('gallery.searchInKey', 'images');
			
			if( count($results) )
			{
				$results = $this->_processImageResults( $results );
				
				//-----------------------------------------
				// Merge in our followed content data
				//-----------------------------------------

				if ( count($followData) )
				{
					foreach( $followData as $_follow )
					{
						$results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
					}
				}
			}
			
			return $this->registry->output->getTemplate('gallery_external')->searchResultsAsGallery( $this->parseAndFetchHtmlBlocks( $results ) );
		}

		//-----------------------------------------
		// Else we're looking at albums
		//-----------------------------------------

		else
		{
			IPSSearchRegistry::set('gallery.searchInKey', 'albums');
			
			$results = $this->_processAlbumResults( $results );
			
			if( count($results) )
			{
				//-----------------------------------------
				// Merge in our followed content data
				//-----------------------------------------

				if ( count($followData) )
				{
					foreach( $followData as $_follow )
					{
						$results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
					}
				}
			}
            
			return $this->registry->output->getTemplate('gallery_external')->searchResultsAsGallery( $this->parseAndFetchHtmlBlocks( $results ) );
		}
	}

	/**
	 * Process our results
	 *
	 * @return	@e array
	 */
	public function processResults( $ids )
	{
		//-----------------------------------------
		// Set our template wrapper
		//-----------------------------------------

		$this->templates = array( 'group' => 'gallery_external', 'template' => 'searchResultsAsGallery' );

		//-----------------------------------------
		// Return appropriately processed results
		//-----------------------------------------

		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{	
			return $this->_processImageResults( $ids );
		}
		else if ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{	
			return $this->_processAlbumResults( $ids );
		}
		else
		{
			return $this->_processCommentResults( $ids );
		}
	}
	
	/**
	 * Formats / grabs extra data for results. 
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	Result ids
	 * @return	@e array
	 */
	public function _processAlbumResults( $ids )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts			= IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		//-----------------------------------------
		// Do we have any albums?
		//-----------------------------------------

		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Set some vars for the search registry
			//-----------------------------------------

			IPSSearch::$ask	= 'date';
			IPSSearch::$aso	= strtolower( $order_dir );
			IPSSearch::$ast	= 'numerical';
			
			//-----------------------------------------
			// Fetch our albums
			//-----------------------------------------

			$albums	= $this->registry->gallery->helper('albums')->fetchAlbumsByFilters( array(
																							'isViewable'	=> true,
																							'album_id'		=> $ids,
																							'sortKey'		=> 'date',
																							'sortOrder'		=> $sort_order,
																					)		);
			
			//-----------------------------------------
			// Get owner ids
			//-----------------------------------------

			if ( count( $albums ) )
			{
				foreach( $albums as $id => $row )
				{
					if ( ! empty( $row['album_owner_id'] ) )
					{
						$members[ $row['album_owner_id'] ]	= $row['album_owner_id'];
					}
				}
			}
			
			//-----------------------------------------
			// Load owners
			//-----------------------------------------

			if ( count( $members ) )
			{
				$mems	= IPSMember::load( $members, 'all' );
				
				foreach( $albums as $id => $r )
				{
					if ( ! empty( $r['album_owner_id'] ) AND isset( $mems[ $r['album_owner_id'] ] ) )
					{
						$albums[ $id ]	= array_merge( $albums[ $id ], IPSMember::buildDisplayData( $mems[ $r['album_owner_id'] ], array( 'reputation' => 0, 'warn' => 0 ) ) );
					}
				}
			}

			//-----------------------------------------
			// Extract and load latest images
			//-----------------------------------------

			$albums		= $this->registry->gallery->helper('albums')->extractLatestImages( $albums );
		}

		return $albums;
	}
	
	/**
	 * Formats / grabs extra data for results. 
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	Result ids
	 * @return	@e array
	 */
	public function _processCommentResults( $ids )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts			= IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		//-----------------------------------------
		// Do we have any comments?
		//-----------------------------------------

		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Set some vars for the search registry
			//-----------------------------------------

			IPSSearch::$ask	= 'comment_post_date';
			IPSSearch::$aso	= strtolower( $order_dir );
			IPSSearch::$ast	= 'numerical';
			
			//-----------------------------------------
			// Fetch the comments
			//-----------------------------------------

			$this->DB->build( array( 
									'select'	=> "c.*",
									'from'		=> array( 'gallery_comments' => 'c' ),
		 							'where'		=> 'c.comment_id IN( ' . implode( ',', $ids ) . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'g.*',
															'from'		=> array( 'gallery_images' => 'g' ),
															'where'		=> "g.image_id=c.comment_img_id",
															'type'		=> 'left'
															),
														array(
															'select'	=> 'a.*',
															'from'		=> array( 'gallery_albums' => 'a' ),
															'where'		=> "a.album_id=g.image_album_id",
															'type'		=> 'left'
															),
														array(
															'select'	=> 'm.members_display_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> "m.member_id=c.comment_author_id",
															'type'		=> 'left'
															)
														)
							)		);
			$this->DB->execute();
			
			//-----------------------------------------
			// Get results
			//-----------------------------------------

			while( $row = $this->DB->fetch() )
			{
				if ( ! empty( $row['comment_author_id'] ) )
				{
					$members[ $row['comment_author_id'] ]	= $row['comment_author_id'];
				}
				else
				{
					$row	= array_merge( $row, IPSMember::buildDisplayData( IPSMember::setUpGuest() ) );
				}

				if( !$row['image_album_id'] )
				{
					$row	= array_merge( $row, $this->registry->gallery->helper('categories')->fetchCategory( $row['image_category_id'] ) );
				}

				$row['comment_text']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_text'] );

				$_rows[] = $this->genericizeResults( $row );
			}

			//-----------------------------------------
			// Sort results
			//-----------------------------------------

			if ( count( $_rows ) )
			{
				usort( $_rows, array( "IPSSearch", "usort" ) );
			}

			//-----------------------------------------
			// Get members
			//-----------------------------------------

			if ( count( $members ) )
			{
				$mems	= IPSMember::load( $members, 'all' );
				
				foreach( $_rows as $r )
				{
					$results[ $r['comment_id'] ]	= $r;

					if ( ! empty( $r['comment_author_id'] ) AND isset( $mems[ $r['comment_author_id'] ] ) )
					{
						$mems[ $r['comment_author_id'] ]['m_posts'] = $mems[ $r['comment_author_id'] ]['posts'];
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['comment_author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$results[ $r['comment_id'] ]	= array_merge( $results[ $r['comment_id'] ], $_mem );
					}
				}
			}
		}

		return $results;
	}
	
	/**
	 * Formats / grabs extra data for results.
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	Result ids
	 * @return	@e array
	 */
	public function _processImageResults( $ids )
	{
		//-----------------------------------------
		// Make sure we have our object
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded('galleryTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'galleryTags', classes_tags_bootstrap::run( 'gallery', 'images' ) );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only	= IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts			= IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$sortKey			= '';
		$sortType			= '';
		$rows				= array();
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		//-----------------------------------------
		// Do we have any images?
		//-----------------------------------------

		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Fix sort key
			//-----------------------------------------

			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey  = 'image_date';
					$sortType = 'numerical';
				break;

				case 'title':
					$sortKey  = 'image_caption';
					$sortType = 'string';
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
			// Set search registry sort values
			//-----------------------------------------

			IPSSearch::$ask	= $sortKey;
			IPSSearch::$aso	= strtolower( $order_dir );
			IPSSearch::$ast	= $sortType;
			
			$_post_joins[] = $this->registry->galleryTags->getCacheJoin( array( 'meta_id_field' => 'g.id' ) );
			
			//-----------------------------------------
			// Set search registry sort values
			//-----------------------------------------

			$images	= $this->registry->gallery->helper('image')->fetchImages( $this->memberData['member_id'], array(
																													'imageIds'			=> $ids,
																													'isViewable'		=> true,
																													'sortKey'			=> $sortKey,
																													'sortOrder'			=> $order_dir,
																													'getTags'			=> true,
																													'parseImageOwner'	=> true,
																													)
																			);

			//-----------------------------------------
			// Extra formatting for our images
			//-----------------------------------------

			if ( count( $images ) )
			{
				foreach( $images as $id => $row )
				{
					$row['image_notes']				= unserialize( $row['image_notes'] );
					$row['_image_notes_count']		= is_array( $row['image_notes'] ) ? count( $row['image_notes'] ) : 0;

					if( !$row['member_id'] )
					{
						$row	= array_merge( $row, IPSMember::buildDisplayData( IPSMember::setUpGuest() ) );
					}

					$results[ $row['image_id'] ]	= $this->genericizeResults( $row );				
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
		//-----------------------------------------
		// Generic formatting
		//-----------------------------------------

		$r['app']		= 'gallery';
		$r['misc']		= serialize( array(
											'image_directory'			=> $r['image_directory'],
											'image_masked_file_name'	=> $r['image_masked_file_name'],
											'image_thumbnail'			=> $r['image_thumbnail'],
										)		);

		//-----------------------------------------
		// Format for images
		//-----------------------------------------

		if ( IPSSearchRegistry::get('gallery.searchInKey') == 'images' )
		{
			$r['content']		= $r['image_description'];
			$r['content_title']	= $r['image_caption'];
			$r['updated']		= $r['image_date'];
			$r['type_2']		= 'img';
			$r['type_id_2']		= $r['image_id'];
		}

		//-----------------------------------------
		// Format for albums
		//-----------------------------------------

		elseif ( IPSSearchRegistry::get('gallery.searchInKey') == 'albums' )
		{
			/* No formatting required now */
		}

		//-----------------------------------------
		// Format for comments
		//-----------------------------------------

		else
		{
			$r['content']		= $r['comment_text'];
			$r['content_title']	= $r['image_caption'];
			$r['updated']		= $r['comment_post_date'];
			$r['type_2']		= 'comment';
			$r['type_id_2']		= $r['image_id'];
		}

		return $r;
	}
}