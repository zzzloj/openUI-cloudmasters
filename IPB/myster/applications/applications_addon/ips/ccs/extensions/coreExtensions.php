<?php
/**
 * <pre>
 * Invision Power Services
 * CCS
 * Core extensions
 * Last Updated: $Date: 2012-03-07 12:18:00 -0500 (Wed, 07 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 10408 $
 *
 */
 
$_PERM_CONFIG = array( 'Databases', 'Categories' );

/**
 * Database perm mapping
 */
class ccsPermMappingDatabases
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $mapping = array(
								'view'		=> 'perm_view',
								'show'		=> 'perm_2',
								'add'		=> 'perm_3',
								'edit'		=> 'perm_4',
								'comment'	=> 'perm_5',
								'rate'		=> 'perm_6',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_names = array(
								'view'		=> 'See Database',
								'show'		=> 'View Records',
								'add'		=> 'Add Records',
								'edit'		=> 'Edit Records',
								'comment'	=> 'Add Comments',
								'rate'		=> 'Rate Records',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_colors = array(
								'view'		=> '#fff0f2',
								'show'		=> '#effff6',
								'add'		=> '#edfaff',
								'edit'		=> '#f0f1ff',
								'comment'	=> '#fffaee',
								'rate'		=> '#ffeef9',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		$_return_arr = array();
		
		ipsRegistry::DB()->build( array(
										'select'	=> 'd.database_id, d.database_name',
										'from'		=> array( 'ccs_databases' => 'd' ),
										'add_join'	=> array(
															array(
																'select'	=> 'p.*',
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.perm_type='databases' AND p.perm_type_id=d.database_id",
																'type'		=> 'left',
																)
															)
								)		);
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$return_arr[ $r['database_id'] ] = array(
										'title'		=> $r['database_name'],
										'perm_view'	=> $r['perm_view'],
										'perm_2'	=> $r['perm_2'],
										'perm_3'	=> $r['perm_3'],
										'perm_4'	=> $r['perm_4'],
										'perm_5'	=> $r['perm_5'],
										'perm_6'	=> $r['perm_6'],
										'perm_7'	=> $r['perm_7'],
									);
		}
		
		return $return_arr;
	}	
}

/**
 * Category perm mapping
 */
class ccsPermMappingCategories
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $mapping = array(
								'view'		=> 'perm_view',
								'show'		=> 'perm_2',
								'add'		=> 'perm_3',
								'edit'		=> 'perm_4',
								'comment'	=> 'perm_5',
								'rate'		=> 'perm_6',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_names = array(
								'view'		=> 'See Database',
								'show'		=> 'View Records',
								'add'		=> 'Add Records',
								'edit'		=> 'Edit Records',
								'comment'	=> 'Add Comments',
								'rate'		=> 'Rate Records',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_colors = array(
								'view'		=> '#fff0f2',
								'show'		=> '#effff6',
								'add'		=> '#edfaff',
								'edit'		=> '#f0f1ff',
								'comment'	=> '#fffaee',
								'rate'		=> '#ffeef9',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ), 'ccs' );

		$_return_arr = array();
		
		ipsRegistry::DB()->build( array(
										'select'	=> 'c.category_id, c.category_name, c.category_has_perms',
										'from'		=> array( 'ccs_database_categories' => 'c' ),
										'order'		=> 'c.category_position ASC',
										'add_join'	=> array(
															array(
																'select'	=> 'p.*',
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.app='ccs' AND p.perm_type='categories' AND p.perm_type_id=c.category_id",
																'type'		=> 'left',
																)
															)
								)		);
		ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch() )
		{
			$return_arr[ $r['category_id'] ] = array(
										'title'		=> $r['category_name'],
										'perm_view'	=> $r['perm_view'],
										'perm_2'	=> $r['perm_2'],
										'perm_3'	=> $r['perm_3'],
										'perm_4'	=> $r['perm_4'],
										'perm_5'	=> $r['perm_5'],
										'perm_6'	=> $r['perm_6'],
										'perm_7'	=> $r['perm_7'],
										'_noconfig'	=> $r['category_has_perms'] ? null : ipsRegistry::getClass('class_localization')->words['ccs_per_db_permediting']
									);
		}

		return $return_arr;
	}	
}

/**
 * <pre>
 * Invision Power Services
 * Library: Handle public session data
 * Last Updated: $Date: 2012-03-07 12:18:00 -0500 (Wed, 07 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10408 $
 *
 */

class publicSessions__ccs
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getSessionVariables()
	{
		//-----------------------------------------
		// Is application still installed/enabled?
		//-----------------------------------------
				
		if( !IPSLib::appIsInstalled( 'ccs' ) )
		{
			return array();
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$type	= '';
		$key	= '';
		
		//-----------------------------------------
		// Load CCS functions
		//-----------------------------------------
		
		$registry	= ipsRegistry::instance();
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}

		$folderName	= $registry->ccsFunctions->getFolder();
		$pageName	= $registry->ccsFunctions->getPageName();
						
		if( ipsRegistry::$request['id'] )
		{
			$type	= 'id';
			$key	= ipsRegistry::$request['id'];
		}
		else
		{
			$type	= 'page';
			$key	= $pageName ? $pageName : ipsRegistry::$settings['ccs_default_page'];
			$folder	= $folderName;
			
			//-----------------------------------------
			// We don't want to have to run an extra
			// query on every page, so let's just try
			// checking the URL...
			//-----------------------------------------
			
			if( strpos( $key, '.css' ) !== false OR strpos( $key, '.js' ) !== false )
			{
				define( 'NO_SESSION_UPDATE', true );
			}
		}

		$array = array( 'location_1_type'   => $type,
						'location_1_id'	 	=> 0,
						'location_2_type'   => $key,
						'location_2_id'	 	=> 0,
						'location_3_type'	=> $folder, );

		return $array;
	}
	
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		//-----------------------------------------
		// No rows
		//-----------------------------------------
		
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		//-----------------------------------------
		// Offline
		//-----------------------------------------
		
		if( !ipsRegistry::$settings['ccs_online'] )
		{
			return $rows;
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$final		= array();
		$pages		= array();
		$keys		= array();
		$ids		= array();
		$dbr		= array();
		$dbc		= array();
		$records	= array();
		$cats		= array();
		$registry	= ipsRegistry::instance();
		
		//-----------------------------------------
		// Extract the page data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'ccs' )
			{
				if( $row['location_1_type'] == 'page' )
				{
					$keys[ $row['location_2_type'] ]	= $row['location_2_type'];
				}
				else if( $row['location_1_type'] == 'id' )
				{
					$ids[ $row['location_2_type'] ]		= intval($row['location_2_type']);
				}
				else if( $row['location_3_type'] == 'record' )
				{
					$dbr[ $row['location_1_id'] ][]		= array( intval($row['location_1_id']), intval($row['location_2_id']) );
				}
				else if( $row['location_3_type'] == 'category' )
				{
					$dbc[ $row['location_1_id'] ][]		= array( intval($row['location_1_id']), intval($row['location_3_id']) );
				}
			}
		}
		
		//-----------------------------------------
		// Get page library
		//-----------------------------------------
		
		if( count($keys) OR count($ids) OR count($dbr) OR count($dbc) )
		{
			if( !$registry->isClassLoaded( 'ccsFunctions' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
				$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
			}
			
			if( count($dbr) OR count($dbc) )
			{
				$fieldsClass	= $registry->ccsFunctions->getFieldsClass();
				
				$fields			= $registry->cache()->getCache('ccs_fields');
			}
		}
		
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------

		if( count($keys) )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name IN('" . implode( "','", array_map( array( ipsRegistry::DB(), 'addSlashes' ), $keys ) ) . "')" ) );
			ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch() )
			{
				if( $registry->ccsFunctions->canView( $r ) )
				{
					$pages[ md5($r['page_seo_name'] . $r['page_folder']) ]	= $r;
				}
			}
		}
		
		if( count($ids) )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_id IN(" . implode( ",", $ids ) . ")" ) );
			ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch() )
			{
				if( $registry->ccsFunctions->canView( $r ) )
				{
					$pages[ md5($r['page_seo_name'] . $r['page_folder']) ]	= $r;
				}
			}
		}

		if( count($dbr) )
		{
			$database	= $registry->cache()->getCache('ccs_databases');

			foreach( $dbr as $dbId => $_records )
			{
				if( $database[ $dbId ] AND $registry->permissions->check( 'view', $database[ $dbId ] ) )
				{
					$_recordIds	= array();
					
					foreach( $_records as $record )
					{
						$_recordIds[] = $record[1];
					}
					
					if( count($_recordIds) )
					{
						ipsRegistry::DB()->build( array( 'select' => '*', 'from' => $database[ $dbId ]['database_database'], 'where' => "primary_id_field IN(" . implode( ",", $_recordIds ) . ") and record_approved=1" ) );
						ipsRegistry::DB()->execute();
						
						while( $r = ipsRegistry::DB()->fetch() )
						{
							if( $r['category_id'] )
							{
								$_thisCat	= $registry->ccsFunctions->getCategoriesClass( $database[ $dbId ] )->getCategory( $r['category_id'] );

								if( !$_thisCat['category_id'] OR ( $_thisCat['category_has_perms'] AND !$registry->permissions->check( 'view', $_thisCat ) ) )
								{
									continue;
								}
							}

							$r['title']	= $fieldsClass->getFieldValue( $fields[ $dbId ][ str_replace( 'field_', '', $database[ $dbId ]['database_field_title'] ) ], $r, $fields[ $dbId ][ str_replace( 'field_', '', $database[ $dbId ]['database_field_title'] ) ]['field_truncate'] );
							
							$records[ md5($dbId . $r['primary_id_field']) ]	= $r;
						}
					}
				}
			}
		}

		if( count($dbc) )
		{
			$database	= $registry->cache()->getCache('ccs_databases');

			foreach( $dbc as $dbId => $_cats )
			{
				if( $database[ $dbId ] AND $registry->permissions->check( 'view', $database[ $dbId ] ) )
				{
					$_catIds	= array();
					
					foreach( $_cats as $cat )
					{
						$_catIds[] = $cat[1];
					}
					
					if( count($_catIds) )
					{
						$_catClass	= $registry->ccsFunctions->getCategoriesClass( $database[ $dbId ] );
						
						foreach( $_catIds as $_catId )
						{
							$_thisCat	= $_catClass->getCategory( $_catId );

							if( !$_thisCat['category_has_perms'] OR ( $_thisCat['category_has_perms'] AND $registry->permissions->check( 'view', $_thisCat ) ) )
							{
								$categories[ md5($dbId . $_catId) ]	= $_thisCat;
							}
						}
					}
				}
			}
		}

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'ccs' )
			{
				if( $row['location_1_type'] == 'page' )
				{
					$_md5Key	= md5($row['location_2_type'] . $row['location_3_type']);
					
					if( $pages[ $_md5Key ] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewing'] . ' ' . $pages[ $_md5Key ]['page_name'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page=' . $pages[ $_md5Key ]['page_id'];
					}
				}
				else if( $row['location_1_type'] == 'id' )
				{
					$page	= array();
					
					foreach( $pages as $key => $pageData )
					{
						if( $pageData['page_id'] == $row['location_2_type'] )
						{
							$page	= $pageData;
							break;
						}
					}
					
					if( $page['page_id'] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewing'] . ' ' . $page['page_name'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page=' . $ids[ $row['location_2_type'] ];
					}
				}
				else if( $row['location_3_type'] == 'record' )
				{
					$_md5Key	= md5($row['location_1_id'] . $row['location_2_id']);
					
					if( $records[ $_md5Key ] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewingr'] . ' ' . $records[ $_md5Key ]['title'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;database=' . $row['location_1_id'] . '&amp;record=' . $row['location_2_id'];
					}
				}
				else if( $row['location_3_type'] == 'category' )
				{
					$_md5Key	= md5($row['location_1_id'] . $row['location_3_id']);
					
					if( $categories[ $_md5Key ] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewingc'] . ' ' . $categories[ $_md5Key ]['category_name'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;database=' . $row['location_1_id'] . '&amp;category=' . $row['location_3_id'];
					}
				}
			}
			
			$final[ $row['id'] ] = $row;
		}

		return $final;
	}
}

/**
 * <pre>
 * Invision Power Services
 * CCS Item Marking
 * Last Updated: $Date: 2012-03-07 12:18:00 -0500 (Wed, 07 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10408 $
 *
 */

class itemMarking__ccs
{
	/**
	 * Field Convert Data Remap Array
	 *
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_convertData = array( 'catID' => 'item_app_key_1', 'databaseID' => 'item_app_key_2' );
	
	/**#@+
	 * Registry Object Shortcuts
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
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Convert Data
	 * Takes an array of app specific data and remaps it to the DB table fields
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function convertData( $data )
	{
		$_data = array();

		if( is_array($data) AND count($data) )		
		{
			foreach( $data as $k => $v )
			{
				if ( isset($this->_convertData[$k]) )
				{
					$_data[ $this->_convertData[ $k ] ] = intval( $v );
				}
				else
				{
					$_data[ $k ] = $v;
				}
			}
		}
		
		return $_data;
	}
	
	/**
	 * Fetch unread count
	 *
	 * Grab the number of items truly unread
	 * This is called upon by 'markRead' when the number of items
	 * left hits zero (or less).
	 * 
	 *
	 * @access	public
	 * @param	array 	Array of data
	 * @param	array 	Array of read itemIDs
	 * @param	int 	Last global reset
	 * @return	integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{
		$lastItem  = 0;
		$count     = 0;
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );

		if( ipsRegistry::$settings['_currentDatabaseId'] )
		{
			$category	= array( 'category_database_id' => ipsRegistry::$settings['_currentDatabaseId'] );
		}
		else
		{
			$category	= $this->DB->buildAndFetch( array(
														'select'	=> 'category_database_id',
														'from'		=> 'ccs_database_categories',
														'where'		=> 'category_id=' . intval($data['catID'])
												)		);
		}

		$database	= array_merge( 
								( is_array($this->caches['ccs_databases'][ $category['category_database_id'] ]) AND count($this->caches['ccs_databases'][ $category['category_database_id'] ]) ) ? $this->caches['ccs_databases'][ $category['category_database_id'] ] : array(), 
								( is_array($category) AND count($category) ) ? $category : array()
								);

		if( $database['database_database'] )
		{
			//-----------------------------------------
			// Gotta check approved/unapproved status
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases.php', 'databaseBuilder', 'ccs' );
			$_databases		= new $classToLoad( $this->registry );
			$database		= $_databases->checkModerator( $database );
			
			if( $database['moderate_delete'] AND $database['moderate_approve'] )
			{
				$_approved	= '';
			}
			else if( $database['moderate_approve'] )
			{
				$_approved	= 'record_approved IN(1,0) AND ';
			}
			else if( $database['moderate_delete'] )
			{
				$_approved	= 'record_approved IN(1,-1) AND ';
			}
			else
			{
				$_approved	= 'record_approved=1 AND ';
			}
			
			$_count		= $this->DB->buildAndFetch( array( 
															'select' => 'COUNT(*) as cnt, MIN(record_updated) AS lastItem',
															'from'   => $database['database_database'],
															'where'  => $_approved . "category_id=" . intval( $data['catID'] ) . " AND primary_id_field NOT IN(".implode(",",array_keys($readItems)).") AND record_updated > ".intval($lastReset)
													)	);

			$count    = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );
		}

		return array( 'count'    => $count,
					  'lastItem' => $lastItem );
	}

	/**
	 * Determines whether to load all markers for this view or not
	 * 
	 * @return	bool
	 */
	public function loadAllMarkers()
	{
		/* We will always load our markers ourselves */
		if( $this->request['_isDatabase'] )
		{
			return false;
		}

		return true;
	}
}