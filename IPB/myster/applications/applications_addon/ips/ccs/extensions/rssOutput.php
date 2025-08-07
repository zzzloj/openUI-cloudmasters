<?php
/**
 * <pre>
 * Invision Power Services
 * RSS output plugin :: ccs
 * Last Updated: $Date: 2011-11-29 12:01:59 -0500 (Tue, 29 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 9908 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_ccs
{
	/**
	 * Expiration date
	 * 
	 * @var		integer			Expiration timestamp
	 */
	protected $expires	= 0;

	/**
	 * Category lib
	 * 
	 * @var		object			Category library
	 */
	protected $categories;
	
	/**
	 * Constructor
	 * 
	 * @return	@e void
	 */
	public function __construct()
	{
		$registry = ipsRegistry::instance();
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}
	}

	/**
	 * Grab the RSS links
	 * 
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{
		$return = array();
		
		//-----------------------------------------
		// Database feeds
		//-----------------------------------------
		
		$databases = ipsRegistry::cache()->getCache('ccs_databases');
		
		if ( is_array( $databases ) )
		{
			foreach( $databases as $_dbId => $_db )
			{
				if ( ipsRegistry::getClass('permissions')->check( 'view', $_db ) != TRUE )
				{
					continue;
				}

				if( $_db['database_rss'] < 1 )
				{
					continue;
				}
				
				$return[] = array( 'title' => $_db['database_name'], 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=ccs&amp;id=" . $_db['database_id'], '%%' . $_db['database_name'] . '%%', 'section=rss2' ) );
			}
		}
		
		//-----------------------------------------
		// Category feeds
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/categories.php', 'ccs_categories', 'ccs' );
		$this->categories = new $classToLoad( ipsRegistry::instance() );
		
		if ( is_array( $databases ) )
		{
			foreach( $databases as $_dbId => $_db )
			{
				$this->categories->init( $_db );
				
				if( count($this->categories->categories) )
				{
					foreach( $this->categories->categories as $_catId => $_cat )
					{
						if ( ipsRegistry::getClass('permissions')->check( 'view', $databases[ $_cat['category_database_id'] ] ) != TRUE )
						{
							continue;
						}
						
						if ( $_cat['category_has_perms'] AND ipsRegistry::getClass('permissions')->check( 'view', $_cat ) != TRUE )
						{
							continue;
						}

						if( $_cat['category_rss'] < 1 )
						{
							continue;
						}
						
						$return[] = array( 'title' => $databases[ $_cat['category_database_id'] ]['database_name'] . ': ' . $_cat['category_name'], 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=ccs&amp;id=" . $_cat['category_database_id'] . 'c' . $_cat['category_id'], '%%' . $_cat['category_name'] . '%%', 'section=rss2' ) );
					}
				}
			}
		}

		return $return;
	}
	
	/**
	 * Grab the RSS document content and return it
	 * 
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$databases		= ipsRegistry::cache()->getCache('ccs_databases');
		$_input			= explode( 'c', ipsRegistry::$request['id'] );
		$database		= intval($_input[0]);
		$category		= intval($_input[1]);
		$rss_data		= array();
		$to_print		= '';
	
		//-----------------------------------------
		// Can view?
		//-----------------------------------------

		if ( ipsRegistry::getClass('permissions')->check( 'view', $databases[ $database ] ) != TRUE )
		{
			return '';
		}
		
		//-----------------------------------------
		// Category or db feed?
		//-----------------------------------------
		
		if( $category )
		{
			//-----------------------------------------
			// Get category handler
			//-----------------------------------------
			
			$this->categories	= ipsRegistry::instance()->getClass('ccsFunctions')->getCategoriesClass( $databases[ $database ] );
			
			$thisCategory	= $this->categories->categories[ $category ];
			
			//-----------------------------------------
			// Is RSS enabled?
			//-----------------------------------------
			
			if( $thisCategory['category_rss'] < 1 )
			{
				return '';
			}
			
			//-----------------------------------------
			// Permissions
			//-----------------------------------------

			if ( $thisCategory['category_has_perms'] AND ipsRegistry::getClass('permissions')->check( 'view', $thisCategory ) != TRUE )
			{
				return '';
			}

			//-----------------------------------------
			// Expiration (default to 24 hours)
			//-----------------------------------------
			
			$this->expires	= time() + ( 60 * 60 * 24 );
			$_expired		= time() - ( 60 * 60 * 24 );

			//-----------------------------------------
			// Get RSS export
			// We don't include RSS cache in normal db cache
			// for resource/efficiency reasons
			//-----------------------------------------
			
			$rss_data	= $thisCategory['category_rss_cache'];/*noLibHook*/

			//-----------------------------------------
			// Got a cache??
			//-----------------------------------------
	
			if ( $rss_data AND $thisCategory['category_rss_cached'] > $_expired  )
			{
				return $rss_data;
			}
			else
			{
				//-----------------------------------------
				// Recache
				//-----------------------------------------
				
				return $this->recacheCategoryRss( $database, $databases, $thisCategory );
			}
		}
		else
		{
			//-----------------------------------------
			// Is RSS enabled?
			//-----------------------------------------
			
			if( $databases[ $database ]['database_rss'] < 1 )
			{
				return '';
			}

			//-----------------------------------------
			// Expiration (default to 24 hours)
			//-----------------------------------------
			
			$this->expires	= time() + ( 60 * 60 * 24 );
			$_expired		= time() - ( 60 * 60 * 24 );

			//-----------------------------------------
			// Get RSS export
			// We don't include RSS cache in normal db cache
			// for resource/efficiency reasons
			//-----------------------------------------

			$rss_data = ipsRegistry::DB()->buildAndFetch( array( 'select'	=> 'database_rss_cache, database_rss_cached',
																'from'		=> 'ccs_databases',
																'where'		=> 'database_id=' . $database ) );

			//-----------------------------------------
			// Got a cache??
			//-----------------------------------------
	
			if ( $rss_data['database_rss_cache'] AND $rss_data['database_rss_cached'] > $_expired )
			{
				return $rss_data['database_rss_cache'];
			}
			else
			{
				//-----------------------------------------
				// Recache
				//-----------------------------------------
				
				return $this->recacheRss( $database, $databases );
			}
		}
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 * 
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		return $this->expires ? $this->expires : time();
	}
	
	/**
	 * Recache the RSS feed
	 * 
	 * @param	integer		Database ID 
	 * @param	array 		Databases cache
	 * @return	string
	 */
	public function recacheRss( $database, $databases )
	{
		//-----------------------------------------
		// Lang file
		//-----------------------------------------
		
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$settings	= ipsRegistry::instance()->fetchSettings();
		$articles	= $databases[ $database ]['database_is_articles'];
		$publish	= 0;
		$cache		= array();
		$where		= array();

		//-----------------------------------------
		// RSS class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$class_rss				= new $classToLoad();
		$class_rss->doc_type	= IPS_DOC_CHAR_SET;
		
		//-----------------------------------------
		// Field type handler
		//-----------------------------------------
		
		$fieldsClass	= ipsRegistry::instance()->getClass('ccsFunctions')->getFieldsClass();
		
		//-----------------------------------------
		// Get database class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
		$dbClass		= new $classToLoad( ipsRegistry::instance() );
		
		$fields		= array();
		$fieldCache	= ipsRegistry::cache()->getCache('ccs_fields');

		if( is_array($fieldCache[ $database ]) AND count($fieldCache[ $database ]) )
		{
			foreach( $fieldCache[ $database ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
				
				if( $articles AND $_field['field_key'] == 'article_date' )
				{
					$publish	= $_field['field_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Get category handler
		//-----------------------------------------
		
		$this->categories	= ipsRegistry::instance()->getClass('ccsFunctions')->getCategoriesClass( $databases[ $database ] );

		if( count($this->categories->categories) )
		{
			$_catIds	= array();
			
			foreach( $this->categories->categories as $_catId => $_cat )
			{
				//-----------------------------------------
				// Permissions
				//-----------------------------------------
				
				if( !$_cat['category_show_records'] )
				{
					continue;
				}
				
				if ( $_cat['category_has_perms'] AND ipsRegistry::getClass('permissions')->check( 'view', $_cat ) != TRUE )
				{
					continue;
				}
			
				//-----------------------------------------
				// Are we excluding category from feed?
				//-----------------------------------------
				
				$_catRss	= explode( ';', $_cat['category_rss'] );

				if( $_catRss[3] )
				{
					continue;
				}
				
				$_catIds[]	= $_catId;
			}
			
			if( count($_catIds) )
			{
				$where[]		= "r.category_id IN(" . implode( ',', $_catIds ) . ")";
			}
		}
		
		$where[]	= "r.record_approved=1";

		//-----------------------------------------
		// If this is for articles, we need to exclude non-published items
		//-----------------------------------------
		
		if( $articles )
		{
			$where[]	= "r.field_" . $publish . "+0<" . time();
		}

		//-----------------------------------------
		// Start RSS stuff
		//-----------------------------------------
		
		$cache[] = array( 'url' => $settings['board_url'] . '/index.php?app=core&amp;module=global&amp;section=rss&amp;type=ccs&amp;id='.$database, 'title' => $databases[ $database ]['database_name'] );

		/* Create the RSS Channel */
		$channel_id = $class_rss->createNewChannel( array(	'title'			=> $databases[ $database ]['database_name'],
															'link'			=> ipsRegistry::instance()->getClass('ccsFunctions')->returnDatabaseUrl( $database ),
															'pubDate'		=> $class_rss->formatDate( time() ),
															'ttl'			=> 24 * 60 * 60,
															'description'	=> $databases[ $database ]['database_description']
													)		);
														
		//-----------------------------------------
		// Get the entries to add to the RSS
		//-----------------------------------------

		ipsRegistry::DB()->build( array(
										'select'	=> 'r.*',
										'from'		=> array( $databases[ $database ]['database_database'] => 'r' ),
										'order'		=> 'r.record_saved DESC',
										'where'		=> implode( " AND ", $where ),
										'limit'		=> array( 0, $databases[ $database ]['database_rss'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=r.member_id',
																'type'		=> 'left',
																)
															)
								)		);
		$outer	= ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch($outer) )
		{
			foreach( $r as $k => $v )
			{
				if( preg_match( '/^field_(\d+)$/', $k, $matches ) )
				{
					$r[ $k ]			= str_replace( '[page]', '', $r[ $k ] );
					$r[ $k . '_value' ]	= $fieldsClass->getFieldValue( $fields[ $matches[1] ], $r, $fields[ $matches[1] ]['field_truncate'] );
				}
			}
			
			if ( $this->categories->categories[ $r['category_id'] ]['category_has_perms'] AND ipsRegistry::getClass('permissions')->check( 'show', $this->categories->categories[ $r['category_id'] ] ) != TRUE )
			{
				$r[ $databases[ $database ]['database_field_content'] . '_value' ] = ipsRegistry::getClass('class_localization')->words['nosearchpermview'];
			}
			else if( ipsRegistry::getClass('permissions')->check( 'show', $databases[ $database ] ) != TRUE )
			{
				$r[ $databases[ $database ]['database_field_content'] . '_value' ] = ipsRegistry::getClass('class_localization')->words['nosearchpermview'];
			}
			
			$r	= $dbClass->parseAttachments( $databases[ $database ], $fields, $r );

			$class_rss->addItemToChannel( $channel_id, array(	'title'			=> $r[ $databases[ $database ]['database_field_title'] . '_value' ],
																'link'			=> ipsRegistry::instance()->getClass('ccsFunctions')->returnDatabaseUrl( $database, 0, $r['primary_id_field'] ),
																'description'	=> $r[ $databases[ $database ]['database_field_content'] . '_value' ],
																'pubDate'		=> $class_rss->formatDate( $r['record_saved'] ),
																'guid'			=> md5( $database . $r['primary_id_field'] )
										)					);
		}

		//-----------------------------------------
		// Create feed
		//-----------------------------------------
		
		$class_rss->createRssDocument();
		
		//-----------------------------------------
		// Update cache?
		//-----------------------------------------

		$update		= array( 'database_rss_cached' => time(), 'database_rss_cache' => ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document ) );

		ipsRegistry::DB()->update( 'ccs_databases', $update, "database_id=" . $database );
		
		ipsRegistry::cache()->rebuildCache( 'ccs_databases' );
		
		return ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document );
	}
	
	/**
	 * Recache the RSS feed
	 * 
	 * @param	integer		Database ID 
	 * @param	array 		Databases cache
	 * @param	array 		Category data
	 * @return	string
	 */
	public function recacheCategoryRss( $database, $databases, $category )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$settings	= ipsRegistry::instance()->fetchSettings();
		$articles	= $databases[ $database ]['database_is_articles'];
		$publish	= 0;
		$cache		= array();
		$where		= array();

		//-----------------------------------------
		// RSS class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$class_rss				= new $classToLoad();
		$class_rss->doc_type	= IPS_DOC_CHAR_SET;
		
		//-----------------------------------------
		// Field type handler
		//-----------------------------------------
		
		$fieldsClass	= ipsRegistry::instance()->getClass('ccsFunctions')->getFieldsClass();
		
		//-----------------------------------------
		// Get database class
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
		$dbClass		= new $classToLoad( ipsRegistry::instance() );
		
		$fields		= array();
		$fieldCache	= ipsRegistry::cache()->getCache('ccs_fields');

		if( is_array($fieldCache[ $database ]) AND count($fieldCache[ $database ]) )
		{
			foreach( $fieldCache[ $database ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
				
				if( $articles AND $_field['field_key'] == 'article_date' )
				{
					$publish	= $_field['field_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Category where clause
		//-----------------------------------------

		$_children	= $this->categories->getChildren( $category['category_id'] );
		$_cats		= array( $category['category_id'] ) ;
		
		if( count($_children) )
		{
			foreach( $_children as $_child )
			{
				$_cats[]	= $_child;
			}
		}
		
		if( count($_cats) == 1 )
		{
			$where[]	= "r.category_id=" . $category['category_id'];
		}
		else
		{
			$where[]	= "r.category_id IN(" . implode( ',', $_cats ) . ")";
		}

		$where[]	= "r.record_approved=1";
		
		//-----------------------------------------
		// If this is for articles, we need to exclude non-published items
		//-----------------------------------------
		
		if( $articles )
		{
			$where[]	= "r.field_" . $publish . "+0<" . time();
		}

		//-----------------------------------------
		// Start RSS stuff
		//-----------------------------------------
		
		$cache[] = array( 'url' => $settings['board_url'] . '/index.php?app=core&amp;module=global&amp;section=rss&amp;type=ccs&amp;id=' . $database . 'c' . $category['category_id'], 'title' => $databases[ $database ]['database_name'] . ': ' . $category['category_name'] );


		/* Create the RSS Channel */
		$channel_id = $class_rss->createNewChannel( array(	'title'			=> $category['category_name'] . ' - ' . $databases[ $database ]['database_name'],
															'link'			=> ipsRegistry::instance()->getClass('ccsFunctions')->returnDatabaseUrl( $database, $category['category_id'] ),
															'pubDate'		=> $class_rss->formatDate( time() ),
															'ttl'			=> 24 * 60 * 60,
															'description'	=> $category['category_description']
													)		);
														
		//-----------------------------------------
		// Get the entries to add to the RSS
		//-----------------------------------------

		ipsRegistry::DB()->build( array(
										'select'	=> 'r.*',
										'from'		=> array( $databases[ $database ]['database_database'] => 'r' ),
										'order'		=> 'r.record_saved DESC',
										'where'		=> implode( " AND ", $where ),
										'limit'		=> array( 0, $category['category_rss'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=r.member_id',
																'type'		=> 'left',
																)
															)
								)		);
		$outer	= ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch($outer) )
		{
			foreach( $r as $k => $v )
			{
				if( preg_match( '/^field_(\d+)$/', $k, $matches ) )
				{
					$r[ $k ]			= str_replace( '[page]', '', $r[ $k ] );
					$r[ $k . '_value' ]	= $fieldsClass->getFieldValue( $fields[ $matches[1] ], $r, $fields[ $matches[1] ]['field_truncate'] );
				}
			}
			
			$r	= $dbClass->parseAttachments( $databases[ $database ], $fields, $r );

			$class_rss->addItemToChannel( $channel_id, array(	'title'			=> $r[ $databases[ $database ]['database_field_title'] . '_value' ],
																'link'			=> ipsRegistry::instance()->getClass('ccsFunctions')->returnDatabaseUrl( $database, 0, $r['primary_id_field'] ),
																'description'	=> $r[ $databases[ $database ]['database_field_content'] . '_value' ],
																'pubDate'		=> $class_rss->formatDate( $r['record_saved'] ),
																'guid'			=> md5( $database . $r['primary_id_field'] )
										)					);
		}

		//-----------------------------------------
		// Create feed
		//-----------------------------------------
		
		$class_rss->createRssDocument();
		
		//-----------------------------------------
		// Update cache?
		//-----------------------------------------

		ipsRegistry::DB()->update( 'ccs_database_categories', array( 'category_rss_cached' => time(), 'category_rss_cache' => ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document ) ), "category_id=" . $category['category_id'] );
		
		return ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document );
	}
}