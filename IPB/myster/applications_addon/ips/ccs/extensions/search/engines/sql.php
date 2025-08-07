<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Basic IP.Content Search
 * Last Updated: $Date: 2012-03-20 11:48:47 -0400 (Tue, 20 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10449 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_engine_ccs extends search_engine
{
	/**
	 * Category handler
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $categories;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{	
		//-----------------------------------------
		// Upper limit
		//-----------------------------------------
		
		IPSSearchRegistry::set( 'set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );

		//-----------------------------------------
		// Language
		//-----------------------------------------
		
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		//-----------------------------------------
		// Caches
		//-----------------------------------------
		
		ipsRegistry::cache()->getCache( array( 'ccs_databases', 'ccs_fields' ) );
		
		//-----------------------------------------
		// IP.Content functions
		//-----------------------------------------
		
		if( !$registry->isClassLoaded('ccsFunctions') )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}

		parent::__construct( $registry );
	}

	/**
	 * Decide what type of search we're using
	 *
	 * @access	public
	 * @return	array
	 */
	public function search()
	{
		if ( IPSSearchRegistry::get('contextual.type') == 'database' )
		{
			if ( strpos( $this->request['cId'], '-' ) )
			{
				$exploded = explode( '-', $this->request['cId'] );
				$_dbId = $exploded[0];
				$this->categoryID = $exploded[1];

				ipsRegistry::$request['category']	= $this->categoryID;
			}
			else
			{
				$_dbId = $this->request['cId'];
			}

			ipsRegistry::$request['database']	= $_dbId;
			
			IPSSearchRegistry::set('ccs.searchInKey', "database_{$_dbId}");
			$this->request['search_app_filters']['ccs']['searchInKey'] = "database_{$_dbId}";
		}
	
		$_key	= IPSSearchRegistry::get('ccs.searchInKey');
		
		if( $_key == 'pages' )
		{
			return $this->_searchPages();
		}
		else if( strpos( $_key, '_comments' ) )
		{
			$_dbId	= intval( str_replace( array( 'database_', '_comments' ), '', $_key ) );
			
			return $this->_searchComments( $_dbId );
		}
		else
		{
			$_dbId	= intval( str_replace( 'database_', '', $_key ) );
			
			if( $_dbId )
			{
				if( !$this->caches['ccs_databases'][ $_dbId ]['database_id'] )
				{
					return array( 'count' => 0, 'resultSet' => array() );
				}

				//-----------------------------------------
				// Tagging
				//-----------------------------------------
				
				if ( ! $this->registry->isClassLoaded('ccsTags-' . $_dbId ) )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
					$this->registry->setClass( 'ccsTags-' . $_dbId, classes_tags_bootstrap::run( 'ccs', 'records-' . $_dbId ) );
				}
			
				return $this->_searchDatabase( $_dbId );
			}
		}
	}

	/**
	 * Perform search against pages
	 *
	 * @access	public
	 * @return	array
	 */
	public function _searchPages()
	{
		/* INIT */ 
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= '';
		$sortType			= '';

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'page_last_edited';
				$sortType	= 'numerical';
			break;
			case 'title':
				$sortKey	= 'page_name';
				$sortType	= 'string';
			break;
		}

		/* Fetch data */
		$this->DB->build( array( 
								'select'   => "page_id, page_name, page_last_edited",
								'from'	   => 'ccs_pages',
								'where'	   => $this->_buildWhereStatement( $search_term, 'page' ),
								'limit'    => array( 0, IPSSearchRegistry::get('set.hardLimit') + 1 ),
								)	);
		$this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows() );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['page_id'] ] = $r;
		}
		
		/* Set vars */
		IPSSearch::$ask	= $sortKey;
		IPSSearch::$aso	= strtolower( $sort_order );
		IPSSearch::$ast	= $sortType;
		
		/* Sort */
		if ( count( $_rows ) )
		{
			usort( $_rows, array( "IPSSearch", "usort" ) );

			/* Build result array */
			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $r['page_id'];
							
				$got++;
				
				/* Done? */
				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform a search against comments
	 *
	 * @access	public
	 * @param	int			Database id
	 * @return	array
	 */
	public function _searchComments( $databaseId )
	{
		/* INIT */ 
		$database			= $this->caches['ccs_databases'][ $databaseId ];
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= '';
		$sortType			= '';

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey  = 'comment_date';
				$sortType = 'numerical';
			break;
		}

		/* Fetch data */
		$this->DB->build( array( 
									'select'	=> "c.comment_id, c.comment_user, c.comment_date",
									'from'		=> array( 'ccs_database_comments' => 'c' ),
									'where'		=> $this->_buildWhereStatement( $search_term, 'comment', $databaseId ),
									'limit'		=> array( 0, IPSSearchRegistry::get('set.hardLimit') + 1 ),
									'order'		=> 'c.' . $sortKey . ' DESC',
									'add_join'	=> array(
														array(
																'from'   => array( $database['database_database'] => 'r' ),
																'where'  => "r.primary_id_field=c.comment_record_id",
																'type'   => 'left',
															),
														array(
																'from'   => array( 'ccs_database_categories' => 'cat' ),
																'where'  => "r.category_id=cat.category_id",
																'type'   => 'left',
															),
														array(
																'from'   => array( 'permission_index' => 'p' ),
																'where'  => "p.app='ccs' AND p.perm_type='categories' AND p.perm_type_id=cat.category_id",
																'type'   => 'left',
															),
														)
						)	);
		$this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows() );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['comment_id'] ] = $r;
		}
		
		/* Set vars */
		IPSSearch::$ask	= $sortKey;
		IPSSearch::$aso	= strtolower( $sort_order );
		IPSSearch::$ast	= $sortType;
		
		/* Sort */
		if ( count( $_rows ) )
		{
			usort( $_rows, array( "IPSSearch", "usort" ) );

			/* Build result array */
			foreach( $_rows as $r )
			{
				$c++;
				
				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}
				
				$rows[ $got ] = $r['comment_id'];
							
				$got++;
				
				/* Done? */
				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform a search against database records
	 *
	 * @access	public
	 * @param	int			Database id
	 * @return	array
	 */
	public function _searchDatabase( $databaseId )
	{
		if( ! $this->request['search_app_filters']['ccs']['database_' . $databaseId ]['sortKey'] && ipsRegistry::$settings['use_fulltext'] && $this->request['do'] == 'search' && IPSSearchRegistry::get('in.clean_search_term') && strtolower( $this->DB->connect_vars['mysql_tbl_type'] ) == 'myisam' )
		{
			$sort_by = IPSSearchRegistry::set('in.search_sort_by', 'relevancy');

			$this->request['search_app_filters']['ccs']['database_' . $databaseId ]['sortKey']	= 'relevancy';
		}

		/* INIT */ 
		$database			= $this->caches['ccs_databases'][ $databaseId ];
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$search_term		= IPSSearchRegistry::get('in.clean_search_term');
		$search_tags		= IPSSearchRegistry::get('in.raw_search_tags');
		$rows				= array();
		$c					= 0;
		$got				= 0;
		$sortKey			= '';
		$sortType			= '';

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date_added':
				$sortKey	= 'record_saved';
				$sortType	= 'numerical';
			break;
			case 'date_updated':
				$sortKey	= 'record_updated';
				$sortType	= 'numerical';
			break;
			case 'rating':
				$sortKey	= 'rating_real';
				$sortType	= 'numerical';
			break;
			case 'views':
				$sortKey	= 'record_views';
				$sortType	= 'numerical';
			break;
			case 'relevancy':
				$sortKey	= 'ranking';
				$sortType	= 'numerical';
			break;
		}

		if( ( $this->request['do'] != 'search' OR !IPSSearchRegistry::get('in.clean_search_term')  OR !ipsRegistry::$settings['use_fulltext'] OR strtolower(ipsRegistry::$settings['mysql_tbl_type']) != 'myisam' ) AND $sortKey == 'ranking' )
		{
			$sortKey	= 'record_saved';
		}

		$title_field	= '';
		$content_field	= '';

		/* And deal with custom fields... */
		if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
			{
				if( 'field_' . $_field['field_id'] == $database['database_field_title'] AND in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) )
				{
					$title_field	= 'field_' . $_field['field_id'];
				}

				if( 'field_' . $_field['field_id'] == $database['database_field_content'] AND in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) )
				{
					$content_field	= 'field_' . $_field['field_id'];
				}

				if( in_array( $_field['field_type'], array( 'input', 'textarea', 'radio', 'select' ) ) )
				{
					if( $sort_by == 'field_' . $_field['field_id'] )
					{
						$sortKey	= 'field_' . $_field['field_id'];
						$sortType	= 'string';
					}
				}
			}
		}
		
		/* Specific category? */
		$extra = '';

		if ( isset( $this->categoryID ) )
		{
			$extra = " AND r.category_id={$this->categoryID}";
		}

		$ranking_select = "";
		
		if( ipsRegistry::$settings['use_fulltext'] && strtolower(ipsRegistry::$settings['mysql_tbl_type']) == 'myisam' && $sortKey == 'ranking' AND $search_term AND ( $title_field OR $content_field ) )
		{
			$_columns		= array();

			if( $title_field )
			{
				$_columns[]	= "r." . $title_field;
			}

			if( $content_field )
			{
				$_columns[]	= "r." . $content_field;
			}

			$ranking_select = ", " . $this->DB->buildSearchStatement( implode( ', ', $_columns ), $search_term, true, true, ipsRegistry::$settings['use_fulltext'] );
		}
		else if ( $sortKey == 'ranking' )
		{
			$sortKey	= 'record_saved';
			$sortType	= 'numerical';
		}

		$_tags	= $this->registry->getClass('ccsTags-' . $databaseId )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) );
		unset( $_tags['select'] );

		$count	= $this->DB->buildAndFetch( array(
												'select'	=> 'COUNT(*) as total',
												'from'		=> array( $database['database_database'] => 'r' ),
												'where'		=> $this->_buildWhereStatement( $search_term, 'record', $databaseId, $search_tags ) . $extra,
												'add_join'	=> array(
													array(
															'from'   => array( 'ccs_database_categories' => 'cat' ),
															'where'  => "r.category_id=cat.category_id",
															'type'   => 'left',
														),
													array(
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.app='ccs' AND p.perm_type='categories' AND p.perm_type_id=cat.category_id",
															'type'   => 'left',
														),
													$_tags
													)
										)		);
		$count	= $count['total'];

		/* Fetch data */
		$this->DB->build( array( 
								'select'	=> "r.*" . $ranking_select,
								'from'		=> array( $database['database_database'] => 'r' ),
								'where'		=> $this->_buildWhereStatement( $search_term, 'record', $databaseId, $search_tags ) . $extra,
								'limit'		=> array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
								'order'		=> $sortKey . ' ' . $sort_order,
								'add_join'	=> array(
													array(
															'from'   => array( 'ccs_database_categories' => 'cat' ),
															'where'  => "r.category_id=cat.category_id",
															'type'   => 'left',
														),
													array(
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.app='ccs' AND p.perm_type='categories' AND p.perm_type_id=cat.category_id",
															'type'   => 'left',
														),
													$this->registry->getClass('ccsTags-' . $databaseId )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) )
													)
								)	);
								
		$this->DB->execute();
		
		/* Fetch count */
		//$count = intval( $this->DB->getTotalRows() );

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$rows[ $r['primary_id_field'] ] = $r['primary_id_field'];
		}

		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}

	/**
	 * Builds the where portion of a search string
	 *
	 * @access	protected
	 * @param	string	$search_term		The string to use in the search
	 * @param	string	$type				Content type
	 * @param	int		$databaseId			Database id for record/comment searches
	 * @param	mixed	$search_tags		Whether to search tags or not
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term, $type='page', $databaseId=0, $search_tags=null )
	{
		/* INI */
		$where_clause = array();
		
		/* If we have a search term... */
		if( $search_term )
		{
			$search_term	= trim($search_term);
		}

		/* Set filters based on content type */
		switch( $type )
		{
			case 'page':
			default:
				$where_clause[]	= "page_content_type='page'";
				$where_clause[]	= "page_type IN('bbcode','html')";

				$where_clause[]	= $this->DB->buildRegexp( 'page_view_perms', $this->member->perm_id_array );
				
				if( $search_term )
				{
					switch( IPSSearchRegistry::get('opt.searchType') )
					{
						case 'both':
						default:
							$where_clause[] = "(page_title LIKE '%{$search_term}%' OR page_name LIKE '%{$search_term}%' OR page_meta_description LIKE '%{$search_term}%' OR page_meta_keywords LIKE '%{$search_term}%')";
						break;
						
						case 'titles':
							$where_clause[] = "(page_title LIKE '%{$search_term}%' OR page_name LIKE '%{$search_term}%')";
						break;
						
						case 'content':
							$where_clause[] = "(page_meta_description LIKE '%{$search_term}%' OR page_meta_keywords LIKE '%{$search_term}%')";
						break;
					}
				}

				/* Date Restrict */
				if( $this->search_begin_timestamp && $this->search_end_timestamp )
				{
					$where_clause[] = $this->DB->buildBetween( "page_last_edited", $this->search_begin_timestamp, $this->search_end_timestamp );
				}
				else
				{
					if( $this->search_begin_timestamp )
					{
						$where_clause[] = "page_last_edited > {$this->search_begin_timestamp}";
					}
					
					if( $this->search_end_timestamp )
					{
						$where_clause[] = "page_last_edited < {$this->search_end_timestamp}";
					}
				}
			break;
			
			case 'comment':
				$where_clause[]		= "c.comment_database_id=" . $databaseId;

				$this->categories	= ipsRegistry::getClass('ccsFunctions')->getCategoriesClass( $databaseId );
				
				if( count($this->categories->categories) )
				{
					$where_clause[]	= "r.category_id IN(" . implode( ',', array_keys( $this->categories->categories ) ) . ")";
					$where_clause[]	= "(cat.category_has_perms=0 OR " . $this->DB->buildRegexp( "p.perm_view", $this->member->perm_id_array ) . ")";
				}
				
				if( $search_term )
				{
					$where_clause[] = "c.comment_post LIKE '%{$search_term}%'";
				}
				
				if( !$this->memberData['g_is_supmod'] )
				{
					$where_clause[] = "c.comment_approved=1";
					$where_clause[] = "r.record_approved=1";
				}

				/* Date Restrict */
				if( $this->search_begin_timestamp && $this->search_end_timestamp )
				{
					$where_clause[] = $this->DB->buildBetween( "c.comment_date", $this->search_begin_timestamp, $this->search_end_timestamp );
				}
				else
				{
					if( $this->search_begin_timestamp )
					{
						$where_clause[] = "c.comment_date > {$this->search_begin_timestamp}";
					}
					
					if( $this->search_end_timestamp )
					{
						$where_clause[] = "c.comment_date < {$this->search_end_timestamp}";
					}
				}
			break;
			
			case 'record':
				$this->categories	= ipsRegistry::getClass('ccsFunctions')->getCategoriesClass( $databaseId );
				
				if( count($this->categories->categories) )
				{
					$where_clause[]	= "r.category_id IN(" . implode( ',', array_keys( $this->categories->categories ) ) . ")";
					$where_clause[]	= "(cat.category_has_perms=0 OR " . $this->DB->buildRegexp( "p.perm_view", $this->member->perm_id_array ) . ")";
				}
								
				if( $search_term and !empty( $this->caches['ccs_fields'][ $databaseId ] ) )
				{
					/* Get fields class */
					$_fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
					$_titleField	= $this->caches['ccs_databases'][ $databaseId ]['database_field_title'];
										
					switch( IPSSearchRegistry::get('opt.searchType') )
					{
						case 'both':
						default:
							$where_clause[] = '(' . $_fieldsClass->getSearchWhere( $this->caches['ccs_fields'][ $databaseId ], $search_term, $this->caches['ccs_databases'][ $databaseId ] ) . ')';
						break;
						
						case 'titles':
							$where_clause[] = "r.{$_titleField} LIKE '%{$search_term}%'";
						break;
						
						case 'content':
							$where_clause[] = '(' . $_fieldsClass->getSearchWhere( $this->caches['ccs_fields'][ $databaseId ], $search_term, $this->caches['ccs_databases'][ $databaseId ] ) . ')';
						break;
					}
				}
				
				if( !$this->memberData['g_is_supmod'] )
				{
					$where_clause[] = "r.record_approved=1";
				}

				/* Date Restrict */
				if( $this->search_begin_timestamp && $this->search_end_timestamp )
				{
					$where_clause[] = $this->DB->buildBetween( "r.record_updated", $this->search_begin_timestamp, $this->search_end_timestamp );
				}
				else
				{
					if( $this->search_begin_timestamp )
					{
						$where_clause[] = "r.record_updated > {$this->search_begin_timestamp}";
					}
					
					if( $this->search_end_timestamp )
					{
						$where_clause[] = "r.record_updated < {$this->search_end_timestamp}";
					}
				}

				/* Searching tags? */
				if ( $search_tags )
				{
					$_tagIds = array();

					$tags	= $this->registry->getClass('ccsTags-' . $databaseId )->search( $search_tags, array() );
					
					if( is_array($tags) AND count($tags) )
					{
						foreach( $tags as $id => $data )
						{
							$_tagIds[] = $data['tag_meta_id'];
						}
					}

					if( count($_tagIds) )
					{
						$where_clause[] = 'r.primary_id_field IN(' . implode( ',', $_tagIds ) . ')';
					}
					else
					{
						$where_clause[] = 'r.primary_id_field=0';
					}
				}
			break;
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

		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}

	/**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	array
	 */
	public function viewNewContent()
	{	
		//-----------------------------------------
		// Init
		//-----------------------------------------

		IPSSearchRegistry::set('in.search_sort_by', 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchType', 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview', true );
		
		//-----------------------------------------
		// Set time limit
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$this->search_begin_timestamp	= ( IPS_UNIX_TIME_NOW - IPSSearchRegistry::get('in.period_in_seconds') );
		}
		else
		{
			$this->search_begin_timestamp	= intval( $this->memberData['last_visit'] ) ? intval( $this->memberData['last_visit'] ) : IPS_UNIX_TIME_NOW;
		}

		$_key	= IPSSearchRegistry::get('ccs.searchInKey');

		if( $_key == 'pages' )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}

		//-----------------------------------------
		// Only content we are following?
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('in.vncFollowFilterOn' ) AND $this->memberData['member_id'] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/

			$_newKey = 'ccs_custom_' . $_key;

			if( strpos( $_newKey, '_comments' ) )
			{
				$like = classes_like::bootstrap( 'ccs', str_replace( '_comments', '_records', $_newKey ) );
			}
			else
			{
				$like = classes_like::bootstrap( 'ccs', $_newKey . '_categories' );
			}

			$followedContent	= $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedContent	= ( $followedContent === null ) ? array() : array_keys( $followedContent );
			
			if( !count($followedContent) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			else
			{
				$this->whereConditions['AND'][]	= ( strpos( $_key, '_comments' ) ) ? "r.primary_id_field IN(" . implode( ',', $followedContent ) . ")" : "r.category_id IN(" . implode( ',', $followedContent ) . ")";
			}
		}

		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------
		
		if( IPSSearchRegistry::get('in.userMode') )
		{
			switch( IPSSearchRegistry::get('in.userMode') )
			{
				default:
				case 'all': 
					$_recordIds	= $this->_getRecordIdsFromComments();
					
					if( count($_recordIds) )
					{
						$this->whereConditions['AND'][]	= "(r.member_id=" . $this->memberData['member_id'] . " OR r.primary_id_field IN(" . implode( ',', $_recordIds ) . "))";
					}
					else
					{
						$this->whereConditions['AND'][]	= "r.member_id=" . $this->memberData['member_id'];
					}
				break;
				case 'title': 
					$this->whereConditions['AND'][]	= "r.member_id=" . $this->memberData['member_id'];
				break;
			}
		}

		return $this->search();
	}

	/**
	 * Find records we have commented on
	 *
	 * @return	array
	 */
	protected function _getRecordIdsFromComments()
	{
		$ids	= array();
		$_key	= IPSSearchRegistry::get('ccs.searchInKey');

		if( strpos( $_key, '_comments' ) )
		{
			$dbId = intval( str_replace( 'database_', '', str_replace( '_comments', '', $_key ) ) );
		}
		else
		{
			$dbId = intval( str_replace( 'database_', '', $_key ) );
		}
		
		$this->DB->build( array(
								'select'	=> $this->DB->buildDistinct('comment_record_id'),
								'from'		=> 'ccs_database_comments',
								'where'		=> "comment_approved=1 AND comment_database_id={$dbId} AND comment_user=" . $this->memberData['member_id'],
								'limit'		=> array( 0, 200 )
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ids[]	= $r['comment_record_id'];
		}
		
		return $ids;
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
		//-----------------------------------------
		// Set limits
		//-----------------------------------------

		$this->search_begin_timestamp	= time() - ( 86400 * intval( $this->settings['search_ucontent_days'] ) );

		$_key	= IPSSearchRegistry::get('ccs.searchInKey');

		if( $_key == 'pages' )
		{
			return array( 'count' => 0, 'resultSet' => array() );
		}
		
		if( strpos( $_key, '_comments' ) )
		{
			$this->whereConditions['AND'][]	= "c.comment_user=" . intval( $member['member_id'] );
		}
		else
		{
			$this->whereConditions['AND'][]	= "r.member_id=" . intval( $member['member_id'] );
		}

		//-----------------------------------------
		// Search
		//-----------------------------------------
		
		return $this->search();
	}
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		if( $column == 'member_id' )
		{
			if( IPSSearchRegistry::get('ccs.searchInKey') == 'pages' )
			{
				return '999999999999';
			}
			else if( strpos( IPSSearchRegistry::get('ccs.searchInKey'), '_comments' ) !== false )
			{
				return 'comment_user';
			}
		}
		
		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
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