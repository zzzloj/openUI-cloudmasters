<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Formats IP.Content search results
 * Last Updated: $Date: 2012-02-09 11:20:16 -0500 (Thu, 09 Feb 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10284 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_ccs extends search_format
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}
		
		/* Language */
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		/* Load the caches */
		$this->cache->getCache( array( 'ccs_databases', 'ccs_fields' ) );
		
		/* Load tag class */
		$_key	= IPSSearchRegistry::get('ccs.searchInKey');
		
		if( $_key AND $_key != 'pages' AND strpos( $_key, '_comments' ) === false )
		{
			$_dbId	= intval( str_replace( 'database_', '', $_key ) );

			//-----------------------------------------
			// Tagging
			//-----------------------------------------
			
			if ( ! $this->registry->isClassLoaded('ccsTags-' . $_dbId ) )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
				$registry->setClass( 'ccsTags-' . $_dbId, classes_tags_bootstrap::run( 'ccs', 'records-' . $_dbId ) );
			}
		}
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
		$_key			= IPSSearchRegistry::get('in.followContentType');

		IPSSearchRegistry::set( 'ccs.searchInKey', $_key );

		$template		= strpos( $_key, '_comments' ) !== false ? 'databaseCommentSearchResult' : 'databaseSearchResult';
		$output			= '';
		$results		= $this->processResults( $results );

		/* Merge in follow data */
		if( is_array($followData) AND count($followData) )
		{
			foreach( $followData as $_follow )
			{
				$results[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
			}
		}

		return $this->registry->output->getTemplate('search')->searchResults( $this->parseAndFetchHtmlBlocks( $results ), IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false );
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 			Search result
	 * @return	array 			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		/* Go through and build HTML */
		foreach( $rows as $id => $data )
		{
			/* Format content */
			list( $html, $sub ) = $this->formatContent( $data );
			
			$results[ $id ] = array( 'html' => $html, 'app' => $data['app'], 'type' => $data['type'], 'sub' => $sub );
		}
		
		return $results;
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array 	Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		$data['misc']	= unserialize( $data['misc'] );
		$_key			= IPSSearchRegistry::get('ccs.searchInKey');
		$template		= ( $_key == 'pages' ) ? 'pageSearchResult' : ( strpos( $_key, '_comments' ) !== false ? 'databaseCommentSearchResult' : 'databaseSearchResult' );

		return array( ipsRegistry::getClass( 'output' )->getTemplate( 'ccs_global' )->$template( $data, IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false ), 0 );
	}

	/**
	 * Decides which type of search this was
	 *
	 * @access	public
	 * @param	array 	Ids
	 * @return	array 	Results
	 */
	public function processResults( $ids )
	{
		if ( IPSSearchRegistry::get('ccs.searchInKey') == 'pages' )
		{
			return $this->_processPageResults( $ids );
		}
		else if( strpos( IPSSearchRegistry::get('ccs.searchInKey'), '_comments' ) !== false )
		{
			return $this->_processCommentResults( $ids );
		}
		else
		{
			return $this->_processRecordResults( $ids );
		}
	}

	/**
	 * Returns an array of pages based on input array of ids
	 *
	 * @access	public
	 * @param	array 		Ids
	 * @return	array 		Page results
	 */
	public function _processPageResults( $ids )
	{
		/* INIT */
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$sortKey			= '';
		$sortType			= '';
		$_rows				= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
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

			/* Set vars */
			IPSSearch::$ask	= $sortKey;
			IPSSearch::$aso	= strtolower( $sort_order );
			IPSSearch::$ast	= $sortType;
			
			/* Fetch data */
			$this->DB->build( array( 
									'select'	=> '*',
									'from'		=> 'ccs_pages',
		 							'where'		=> 'page_id IN( ' . implode( ',', $ids ) . ')',
							)	);
	
			/* Grab data */
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
					$results[ $row['page_id'] ] = $this->genericizeResults( $row );
				}
			}
		}

		return $results;
	}
	
	/**
	 * Returns an array of database records from specified database based on input array of ids
	 *
	 * @access	public
	 * @param	array 		Ids
	 * @return	array 		Database record results
	 */
	public function _processRecordResults( $ids )
	{
		/* INIT */
		$databaseId			= intval( $this->request['contentType'] ? str_replace( array( 'ccs_custom_database_', '_records' ), '', $this->request['contentType'] ) : str_replace( 'database_', '', IPSSearchRegistry::get('ccs.searchInKey') ) );
		$database			= $this->caches['ccs_databases'][ $databaseId ];
		$categories			= $this->registry->ccsFunctions->getCategoriesClass( $database );
		
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$sortKey			= '';
		$sortType			= '';
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Grab custom fields class for formatting... */
			$_fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		
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
			}

			/* And deal with custom fields... */
			if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
			{
				foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
				{
					if( in_array( $_field['field_type'], array( 'input', 'textarea', 'radio', 'select' ) ) )
					{
						if( $sort_by == 'field_' . $_field['field_id'] )
						{
							$sortKey	= 'field_' . $_field['field_id'];
							$sortType	= 'string';

							break;
						}
					}
				}
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = $sortType;
			
			/* Query joins */
			$_joins	= array(
							'select'	=> 'm.members_display_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
							'from'		=> array( 'members' => 'm' ),
							'where'		=> "m.member_id=r.member_id",
							'type'		=> 'left',
							);

			if ( $this->registry->isClassLoaded('ccsTags-' . $database['database_id'] ) )
			{
				$_joins[]	= $this->registry->getClass('ccsTags-' . $database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) );
			}

			/* Fetch data */
			$this->DB->build( array( 
									'select'	=> "r.*",
									'from'		=> array( $database['database_database'] => 'r' ),
		 							'where'		=> 'r.primary_id_field IN( ' . implode( ',', $ids ) . ')',
									'add_join'	=> array( $_joins )
							)	);
			
			/* Grab data */
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
					if ( ! empty( $row['member_id'] ) )
					{
						$members[ $row['member_id'] ] = $row['member_id'];
					}

					/* Get tags */
					if ( ! empty( $row['tag_cache_key'] ) )
					{
						$row['tags'] = $this->registry->getClass('ccsTags-' . $database['database_id'] )->formatCacheJoinData( $row );
					}
					
					foreach( $row as $k => $v )
					{
						if( preg_match( '/^field_(\d+)$/', $k, $matches ) )
						{
							$row[ $k . '_value' ]	= $_fieldsClass->getFieldValue( $this->caches['ccs_fields'][ $databaseId ][ $matches[1] ], $row, $this->caches['ccs_fields'][ $databaseId ][ $matches[1] ]['field_truncate'] );
							$row[ $this->caches['ccs_fields'][ $databaseId ][ $matches[1] ]['field_key'] ]	= $row[ $k . '_value' ];
						}
					}
					
					$row['database_id']	= $databaseId;
		
					$results[ $row['primary_id_field'] ] = $this->genericizeResults( $row );
					
					$category	= $categories->categories[ $row['category_id'] ];
					
					if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
					{
						$results[ $row['primary_id_field'] ]['content']	= $this->lang->words['nosearchpermview'];
					}
					else if( $database['perm_2'] != '*' )
					{ 
						if ( $this->registry->permissions->check( 'show', $database ) != TRUE )
						{
							$results[ $row['primary_id_field'] ]['content']	= $this->lang->words['nosearchpermview'];
						}
					}
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$_mem = IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

						$results[ $id ] = array_merge( $results[ $id ], $_mem );
					}
					else
					{
						$results[ $id ] = array_merge( $results[ $id ], IPSMember::setUpGuest() );
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Returns an array of database comments from specified database based on input array of ids
	 *
	 * @access	public
	 * @param	array 		Ids
	 * @return	array 		Database record results
	 */
	public function _processCommentResults( $ids )
	{
		/* INIT */
		$databaseId			= intval( str_replace( array( 'database_', '_comments' ), '', IPSSearchRegistry::get('ccs.searchInKey') ) );
		$database			= $this->caches['ccs_databases'][ $databaseId ];
		$categories			= $this->registry->ccsFunctions->getCategoriesClass( $database );
		
		$sort_by			= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order			= IPSSearchRegistry::get('in.search_sort_order');
		$sortKey			= '';
		$sortType			= '';
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Grab custom fields class for formatting... */
			$_fieldsClass	= $this->registry->getClass('ccsFunctions')->getFieldsClass();
		
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey	= 'comment_date';
					$sortType	= 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = $sortType;
			
			/* Parser properties */
			IPSText::getTextClass( 'bbcode' )->parse_html		= 0;
			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'ccs_comment';
			
			/* Fetch data */
			$this->DB->build( array( 
									'select'	=> "c.*",
									'from'		=> array( 'ccs_database_comments' => 'c' ),
		 							'where'		=> 'c.comment_id IN( ' . implode( ',', $ids ) . ')',
									'add_join'	=> array(
														array(
																'select'	=> 'm.members_display_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_id=c.comment_user",
																'type'		=> 'left',
															),
														array(
																'select'	=> 'r.*',
																'from'		=> array( $database['database_database'] => 'r' ),
																'where'		=> "r.primary_id_field=c.comment_record_id",
																'type'		=> 'left',
															),
														)
							)	);
	
			/* Grab data */
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
					$row['member_id']	= $row['comment_user'];

					/* Got author but no member data? */
					if ( ! empty( $row['member_id'] ) )
					{
						$members[ $row['member_id'] ] = $row['member_id'];
					}
					else
					{
						$row['members_display_name']	= $row['comment_author'];
					}
					
					/* BBcode parsing */
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
					$row['comment_post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['comment_post'] );
					
					/* Format title */
					$row['record_title']	= $_fieldsClass->getFieldValue( $this->caches['ccs_fields'][ $databaseId ][ str_replace( 'field_', '', $database['database_field_title'] ) ], $row, $this->caches['ccs_fields'][ $databaseId ][ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] );

					$results[ $row['comment_id'] ] = $this->genericizeResults( $row );
					
					$category	= $categories->categories[ $row['category_id'] ];
					
					if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
					{
						$results[ $row['comment_id'] ]['content']	= $this->lang->words['nosearchpermview'];
					}
					else if( $database['perm_2'] != '*' )
					{ 
						if ( $this->registry->permissions->check( 'show', $database ) != TRUE )
						{
							$results[ $row['comment_id'] ]['content']	= $this->lang->words['nosearchpermview'];
						}
					}
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['member_id'] ) AND isset( $mems[ $r['member_id'] ] ) )
					{
						$_mem = IPSMember::buildDisplayData( $mems[ $r['member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );

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
		if ( IPSSearchRegistry::get('ccs.searchInKey') == 'pages' )
		{
			$r['app']					= 'ccs';
			$r['content']				= '';
			$r['content_title']			= $r['page_name'];
			$r['updated']				= $r['page_last_edited'];
			$r['type_2']				= 'page';
			$r['type_id_2']				= $r['page_id'];
			$r['url']					= $this->registry->ccsFunctions->returnPageUrl( $r );
			$r['misc']					= serialize( array(
													)		);
		}
		else if( strpos( IPSSearchRegistry::get('ccs.searchInKey'), '_comments' ) !== false )
		{
			$r['app']					= 'ccs';
			$r['content']				= $r['comment_post'];
			$r['content_title']			= $r['record_title'];
			$r['updated']				= $r['comment_date'];
			$r['type_2']				= 'comment';
			$r['type_id_2']				= $r['comment_id'];
			$r['url']					= $this->registry->ccsFunctions->returnDatabaseUrl( $r['comment_database_id'], 0, $r['comment_record_id'], $r['comment_id'] );
			$r['misc']					= serialize( array(
															'database_id'		=> $r['comment_database_id'],
															'record_id'			=> $r['comment_record_id'],
													)		);
		}
		else
		{
			//-----------------------------------------
			// We expect that _values values have been set
			// already in the actual search class
			//-----------------------------------------
			
			$_db						= $this->caches['ccs_databases'][ $r['database_id'] ];
			
			$r['app']					= 'ccs';
			$r['content']				= $r[ $_db['database_field_content'] . '_value' ];
			$r['content_title']			= $r[ $_db['database_field_title'] . '_value' ];
			$r['updated']				= $r['record_updated'];
			$r['type_2']				= 'record';
			$r['type_id_2']				= $r['primary_id_field'];
			$r['url']					= $this->registry->ccsFunctions->returnDatabaseUrl( $r['database_id'], 0, $r );
			$r['misc']					= serialize( array(
															'database_id'		=> $r['database_id'],
													)		);
		}

		return $r;
	}

}