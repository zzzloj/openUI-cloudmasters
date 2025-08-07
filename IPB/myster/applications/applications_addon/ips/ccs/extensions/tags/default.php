<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Tagging: Content
 * Last Updated: $Date: 2011-09-13 01:59:05 +0100 (Tue, 13 Sep 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		25 Feb 2011
 * @version		$Revision: 9483 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tags_ccs_default extends classes_tag_abstract
{
	/**
	 * Cache of records
	 * 
	 * @var	array
	 */
	protected $recordCache	= array();
	
	/**
	 * Database data
	 * 
	 * @var	array
	 */
 	protected $database		= array();
		
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Set area call - allow us to sniff out DB id
	 * 
	 * @param	string	Area
	 * @return	@e void
	 */
	public function setArea( $area )
	{
		$_bits	= explode( '-', $area );
		
		$cache			= $this->cache->getCache('ccs_databases');
		
		if( intval($_bits[1]) )
		{
			$this->database	= $cache[ intval($_bits[1]) ];
		}
		else
		{
			if( $this->request['module'] == 'search' )
			{
				if( $this->request['search_app_filters']['ccs']['searchInKey'] )
				{
					$this->database	= $cache[ str_replace( 'database_', '', $this->request['search_app_filters']['ccs']['searchInKey'] ) ];
				}
				else if( $this->settings['ccs_default_search'] )
				{
					$this->database	= $cache[ str_replace( 'database_', '', $this->settings['ccs_default_search'] ) ];
				}
				else
				{
					$this->database	= array_shift( $cache );
				}
			}
		}
		
		if( !$this->database['database_id'] )
		{
			throw new Exception( "No tags class available for " . $this->getApp() . " - {$area}" );
		}
		
		return parent::setArea( $_bits[0] . '-' . $this->database['database_id'] );
	}

	/**
	 * @return the search section
	 */
	public function getSearchSection()
	{
		return 'database_' . $this->database['database_id'];
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		//-----------------------------------------
		// Load caches - uses external lib if avail
		//-----------------------------------------	
		
		if( !$this->registry->isClassLoaded('ccsFunctions') )
		{
			ipsRegistry::getAppClass( 'ccs' );
		}
		
		return parent::init();
	}
	
	/**
	 * Force preset tags
	 *
	 * @param	string	view to show
	 * @param	array	Where data to show
	 * @return	string
	 */
	public function render( $what, $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $where['meta_parent_id'] ];
		}
		
		if( $category['category_tags_override'] )
		{
			if ( ! empty( $category['category_tags_predefined'] ) )
			{
				/* Turn off open system */
				$this->settings['tags_open_system'] = false;
			}
		}
		else
		{
			if( !empty( $this->database['database_tags_predefined'] ) )
			{
				/* Turn off open system */
				$this->settings['tags_open_system'] = false;
			}
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 * 
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$record	= $this->_getRecord( $where['meta_id'] );
		
		return intval( $record['category_id'] );
	}
	
	/**
	 * Fetches permission data
	 * 
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $where['meta_parent_id'] ];
		}
		else if ( ! empty( $where['meta_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $this->getParentId( $where ) ];
		}
		
		if( $category['category_id'] AND $category['category_has_perms'] )
		{
			return $category['perm_view'];
		}
		else
		{
			return $this->database['perm_view'];
		}
	}
	
	/**
	 * Basic permission check
	 * 
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		/* Check parent */
		$return = parent::can( $what, $where );

		if ( $return === false  )
		{
			return $return;
		}
		
		if ( !empty( $where['meta_id'] ) )
		{
			$record		= $this->_getRecord( $where['meta_id'] );
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $record['category_id'] ];
		}
		else if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $where['meta_parent_id'] ];
		}
		
		/* Category disabled */
		if ( $category['category_tags_override'] )
		{
			if( !$category['category_tags_enabled'] )
			{
				return false;
			}
		}
		else if( !$this->database['database_tags_enabled'] )
		{
			return false;
		}
		
		if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
		{
			return false;
		}
		
		if( $category['category_id'] AND $category['category_has_perms'] )
		{
			if ( $this->registry->permissions->check( 'view', $category ) != TRUE )
			{
				return false;
			}
		}

		switch ( $what )
		{
			case 'create':
				if ( ! $this->_isOpenSystem() )
				{
					return false;
				}
				
				return true;
			break;
			case 'add':
			case 'prefix':
				if( $category['category_id'] )
				{
					if( $category['category_has_perms'] )
					{
						return $this->registry->permissions->check( 'add', $category );
					}
					else
					{
						return $this->registry->permissions->check( 'add', $this->database );
					}
				}
				
				return $this->registry->permissions->check( 'add', $this->database );
			break;
			case 'edit':
			case 'remove':
				/* If we can't 'show' records, can't edit */
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
				{
					return false;
				}
				else if( !$category['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->database ) != TRUE )
				{
					return false;
				}
				
				/* Super mods can edit */
				if( $this->memberData['g_is_supmod'] )
				{
					return true;
				}
				
				/* Get moderator permissions */
				static $moderators	= array();
				static $modChecked	= false;
				$modCanEdit			= false;
				$modCanApprove		= false;
				$modCanDelete		= false;
				
				if( !$modChecked )
				{
					$modChecked	= true;
					
					$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id=' . $this->database['database_id'] ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$moderators[ $r['moderator_type'] ][]	= $r;
					}
				}

				//-----------------------------------------
				// Check group perms first
				//-----------------------------------------
				
				if( is_array($moderators['group']) AND count($moderators['group']) )
				{
					$_myGroups	= array( $this->memberData['member_group_id'] );
					
					if( $this->memberData['mgroup_others'] )
					{
						$_others	= explode( ',', IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
						$_myGroups	= array_merge( $_myGroups, $_others );
					}
					
					foreach( $moderators['group'] as $_moderator )
					{
						if( in_array( $_moderator['moderator_type_id'], $_myGroups ) )
						{
							if( $_moderator['moderator_edit_record'] )
							{
								$modCanEdit	= true;
							}

							if( $_moderator['moderator_approve_record'] )
							{
								$modCanApprove	= true;
							}

							if( $_moderator['moderator_delete_record'] )
							{
								$modCanDelete	= true;
							}
						}
					}
				}
				
				//-----------------------------------------
				// Then individual member mod perms
				//-----------------------------------------
				
				if( is_array($moderators['member']) AND count($moderators['member']) )
				{
					foreach( $moderators['member'] as $_moderator )
					{
						if( $_moderator['moderator_type_id'] == $this->memberData['member_id'] )
						{
							if( $_moderator['moderator_edit_record'] )
							{
								$modCanEdit	= true;
							}

							if( $_moderator['moderator_approve_record'] )
							{
								$modCanApprove	= true;
							}

							if( $_moderator['moderator_delete_record'] )
							{
								$modCanDelete	= true;
							}
						}
					}
				}

				/* If it's not approved, only moderators who can approve can access */
				if( $record['record_approved'] == 0 AND !$modCanApprove )
				{
					return false;
				}

				/* If it's hidden, only moderators who can delete can access */
				if( $record['record_approved'] == -1 AND !$modCanDelete )
				{
					return false;
				}
				
				/* If we cannot edit records, only moderators can edit */
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $category ) != TRUE )
				{
					return $modCanEdit;
				}
				else if( !$category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $this->database ) != TRUE )
				{
					return $modCanEdit;
				}
				
				/* If record is locked, only moderators can edit */
				if( $record['record_locked'] )
				{
					return $modCanEdit;
				}
				
				/* Wiki-editable? */
				if( $this->database['database_all_editable'] )
				{
					return true;
				}
				
				/* Our record? */
				if( $this->memberData['member_id'] AND $record['member_id'] == $this->memberData['member_id'] )
				{
					return true;
				}
				
				/* Only moderators at this point */
				return $modCanEdit;
			break;
		}
		
		return false;
	}
	
	/**
	 * Is the record visible?
	 * 
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 * @todo	Whole function
	 */
	public function getIsVisible( $where )
	{
		$record	= $this->_getRecord( $where['meta_id'] );
		
		return $record['record_approved'] > 0 ? 1 : 0;
	}
	
	/**
	 * Search for tags
	 * 
	 * @param	mixed $tags	Array or string
	 * @param	array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 * @return	array
	 */
	public function search( $tags, $options )
	{
		$ok = array();
		
		/* Fix up category IDs */
		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->_canSearchCategory( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->_canSearchCategory( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}
		else
		{
			$ok	= array_keys( $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories );
		}
		
		$options['meta_parent_id']	= $ok;
		$options['meta_area']		= $this->getArea();
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	mixed
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $where['meta_parent_id'] ];
		}
		
		if( $category['category_tags_override'] )
		{
			if ( ! empty( $category['category_tags_predefined'] ) )
			{
				/* Turn off open system */
				$this->settings['tags_predefined']	= $category['category_tags_predefined'];
			}
		}
		else
		{
			if( !empty( $this->database['database_tags_predefined'] ) )
			{
				/* Turn off open system */
				$this->settings['tags_predefined']	= $this->database['database_tags_predefined'];
			}
		}

		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Are prefixes enabled in this cat?
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e boolean
	 */
	protected function _prefixesEnabled( $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $where['meta_parent_id'] ];
		}
		
		if( $category['category_tags_override'] )
		{
			if ( $category['category_tags_noprefixes'] )
			{
				return false;
			}
		}
		else
		{
			if( $this->database['database_tags_noprefixes'] )
			{
				return false;
			}
		}

		return parent::_prefixesEnabled( $where );
	}
	
	/**
	 * Check a category for tag searching
	 * 
	 * @param	id		$id		Category ID
	 * @return	@e boolean
	 */
	protected function _canSearchCategory( $id )
	{
		/* Can't find category - probably due to no permission */
		if( !isset( $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $id ] ) )
		{
			return false;
		}
		
		if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
		{
			return false;
		}
		
		$category	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database )->categories[ $id ];
		
		if( $category['category_tags_override'] )
		{
			if ( !$category['category_tags_enabled'] )
			{
				return false;
			}
		}
		else
		{
			if( !$this->database['database_tags_enabled'] )
			{
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Fetch a database record
	 * 
	 * @param	integer
	 * @return	@e array
	 */
	protected function _getRecord( $id )
	{
		if ( ! isset( $this->recordCache[ $id ] ) )
		{
			$this->recordCache[ $id ] = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval($id) ) );
		}
		
		return $this->recordCache[ $id ];
	}
}