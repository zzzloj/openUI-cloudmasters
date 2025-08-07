<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS front end database interface
 * Last Updated: $Date: 2012-03-20 11:48:47 -0400 (Tue, 20 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10449 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class databaseBuilder
{
	/**
	 * Page builder library
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $pages;
	
	/**
	 * Page data
	 * 
	 * @var	array
	 */
 	protected $page	= array();
	
	/**#@+
	 * Registry objects
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
	public $caches;	/* @link	http://community.invisionpower.com/tracker/issue-33939-protected-caches-variable-called-publicly */
	protected $cache;
	/**#@-*/
	
	/**
	 * Custom fields class
	 *
	 * @access	public
	 * @var		obj
	 */
	public $fieldsClass;
	
	/**
	 * Categories handler
	 *
	 * @access	public
	 * @var		obj
	 */
	public $categories;
	
	/**
	 * Plugin class
	 *
	 * @access	public
	 * @var		obj
	 */
	public $plugin;
	
	/**
	 * Array of information about the database
	 *
	 * @access	public
	 * @var		array
	 */
	public $database		= array();
	
	/**
	 * Array of information about the fields in this database
	 *
	 * @access	public
	 * @var		array
	 */
	public $fields			= array();
	
	/**
	 * Cached record data, if pulled for FURL parsing
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_record		= array();

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @param	mixed	pageBuilder object, or null (i.e. if using a utility function such as checkModerator())
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $pages=null )
	{
		/* Make object */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->pages		=  $pages;

		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
	}

	/**
	 * Get and return database HTML
	 *
	 * @access	public
	 * @param	string		Database key
	 * @param	array 		Page data
	 * @return	string		Database HTML
	 */
	public function getDatabase( $key, $page )
	{
		//-----------------------------------------
		// Load skin file if it hasn't been loaded yet
		//-----------------------------------------
		
		$this->pages->loadSkinFile();
		
		$this->page	= $page;
		
		//-----------------------------------------
		// Get the database info
		//-----------------------------------------
		
		$this->database	= $this->_loadDatabase( $key );
		$this->request['database'] = $this->database['database_id'];

		if( !$this->database['database_id'] OR !$this->database['database_database'] )
		{
			return '';
		}
		
		if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
		{
			return '';
		}
				
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );

		//-----------------------------------------
		// Basic perm checking and init
		//-----------------------------------------
		
		if( !$this->_initDatabase( $page ) )
		{
			return '';
		}

		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$this->fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$this->fields	= array();

		if( is_array($this->caches['ccs_fields'][ $this->database['database_id'] ]) AND count($this->caches['ccs_fields'][ $this->database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $this->database['database_id'] ] as $_field )
			{
				$this->fields[ $_field['field_id'] ]	= $_field;
			}
		}

		//-----------------------------------------
		// Get category handler
		//-----------------------------------------
		
		$this->categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->database );

		//-----------------------------------------
		// Fix database furls... sigh :(
		//-----------------------------------------
		
		$this->fixDatabaseFurls();
		
		//-----------------------------------------
		// What action to take
		//-----------------------------------------
				
		switch( $this->request['do'] )
		{
			case 'add':
				return $this->_actionAdd();
			break;
			
			case 'save':
				return $this->_actionSaveAdd();
			break;
			
			case 'edit':
				return $this->_actionEdit();
			break;
			
			case 'save_edit':
				return $this->_actionSaveEdit();
			break;

			case 'delete':
				return $this->_actionDelete();
			break;

			case 'lock':
			case 'unlock':
				return $this->_actionToggleLock();
			break;
			
			case 'pin':
			case 'unpin':
				return $this->_actionPin();
			break;
			
			case 'approve':
			case 'unapprove':
				return $this->_actionApprove();
			break;
			
			case 'revisions':
				return $this->_showRevisions();
			break;

			case 'deleteRevision':
				return $this->_deleteRevision();
			break;
			
			case 'restoreRevision':
				return $this->_restoreRevision();
			break;
			
			case 'editRevision':
				return $this->_editRevision();
			break;
			
			case 'doEditRevision':
				return $this->_saveRevision();
			break;
			
			case 'compareRevision':
				return $this->_compareRevisions();
			break;
			
			case 'search':
				$this->_loadTemplates( 'category' );

				return $this->_getDatabaseListing();
			break;
		}
		
		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$this->_loadTemplates();

		//-----------------------------------------
		// If none of the above, view cats, listing or record?
		//-----------------------------------------

		$_content	= '';
		
		if( $this->request['record'] )
		{
			$_content	.= $this->_getRecordDisplay();
		}
		else if( $this->request['category'] )
		{
			$_content	.= $this->_getDatabaseListing();
		}
		else
		{
			$_content	.= $this->_getDatabaseCategories();
		}

		return $_content;
	}
	
	/**
	 * Get listing of categories
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getDatabaseCategories()
	{
		$this->checkPermalink( $this->database['base_link'] );
		
		//-----------------------------------------
		// Get all categories
		//-----------------------------------------
		
		$categories	= $this->categories->catcache[0];
		
		if( !count($categories) )
		{
			//-----------------------------------------
			// We have categories, but none we can view
			//-----------------------------------------
			
			if( $this->categories->hasCategories )
			{
				$this->registry->output->showError( $this->lang->words['no_cats_can_view'], '10CCS26', false, null, 403 );
			}

			$this->_loadTemplates( 'category' );
			
			$_content	.= $this->_getDatabaseListing();
		}
		else
		{
			foreach( $categories as $id => $data )
			{
				$categories[ $id ]				= $this->categories->getCategory( $id );
				$_children						= $this->categories->getChildren( $id );
				$categories[ $id ]['children']	= array();
				
				if( count($_children) )
				{
					foreach( $_children as $_child )
					{
						$categories[ $id ]['children'][ $_child ]					= $this->categories->getCategory( $_child );
						$categories[ $id ]['children'][ $_child ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $_child );
					}
				}
				
				foreach( $categories[ $id ] as $k => $v )
				{
					if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
					{
						$categories[ $id ][ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $categories[ $id ], $this->fields[ $matches[1] ]['field_truncate'] );
						$categories[ $id ][ $this->fields[ $matches[1] ]['field_key'] ]	= $categories[ $id ][ $k . '_value' ];
					}
				}

				//-----------------------------------------
				// Latest record display
				//-----------------------------------------

				if( in_array( $this->database['database_field_title'], array( 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'rating_real' ) ) )
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= ( $this->database['database_field_title'] == 'record_saved' OR $this->database['database_field_title'] == 'record_updated' ) ? $this->registry->class_localization->getDate( $categories[ $id ][ $this->database['database_field_title'] ], 'LONG' ) : $categories[ $id ][ $this->database['database_field_title'] ];
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				else
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $categories[ $id ], $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				
				$categories[ $id ]['last_author'] = IPSMember::buildDisplayData( $categories[ $id ]['member_id'] ? $categories[ $id ]['member_id'] : IPSMember::setUpGuest() );
				
				$categories[ $id ]['record_link']	= $this->getRecordUrl( array( 'primary_id_field' => $categories[ $id ]['category_last_record_id'], 'category_id' => $categories[ $id ]['category_last_record_cat'] ? $categories[ $id ]['category_last_record_cat'] : $id, $this->database['database_field_title'] . '_value' => $categories[ $id ][ $this->database['database_field_title'] . '_value' ], '_skipUpdateDynamic' => true ) );
				$categories[ $id ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $id );
			}

			$this->pages->setCanonical( rtrim( $this->database['base_link'], '?' ) );

			$template	= 'template_' . $this->database['template_key'];
			$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'categories' => $categories ) );
		}

		$this->_setPageTitle( array( 'database_name' => $this->database['database_name'] ), 1 );
		
		return $_content;
	}
	
	/**
	 * Get listing of records in a database
	 *
	 * @access	protected
	 * @return	string		HTML
	 */
	protected function _getDatabaseListing()
	{
		//-----------------------------------------
		// Do we have children?
		//-----------------------------------------
		
		$category					= $this->categories->getCategory( $this->request['category'] );
		
		if( $category['category_id'] )
		{
			$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] );

			$this->checkPermalink( $category['category_link'] );
			
			$this->pages->setMetaKeywords( $category['category_meta_keywords'] );
			$this->pages->setMetaDescription( $category['category_meta_description'] );
			$this->pages->setCanonical( $category['category_link'] );
		}

		if( $this->request['category'] AND !$category['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_view_perm_cat'], '10CCS27', false, null, 403 );
		}
		else if( $this->request['category'] )
		{
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) != TRUE )
			{
				$this->database['_can_add']	= false;
			}
			else if( $category['category_has_perms'] AND $this->registry->permissions->check( 'add', $category ) == TRUE )
			{
				$this->database['_can_add']	= true;
			}
			
			$_parents	= $this->categories->getParents( $category['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS89.1', false, null, 403 );
					}
				}
			}
		}

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'catID' => $category['category_id'], 'itemLastUpdate' => $category['category_last_record_date'], 'databaseID' => $category['category_database_id'] ), 'ccs' );

		$category['_has_unread'] = ( $category['category_last_record_date'] && $category['category_last_record_date'] > $rtime ) ? 1 : 0;
		
		$categories	= $this->categories->catcache[ $this->request['category'] ];
		
		if( count($categories) )
		{
			foreach( $categories as $id => $data )
			{
				$categories[ $id ]					= $this->categories->getCategory( $id );
				$_children							= $this->categories->getChildren( $id );
				$categories[ $id ]['children']		= array();
				
				if( count($_children) )
				{
					foreach( $_children as $_child )
					{
						$categories[ $id ]['children'][ $_child ]					= $this->categories->getCategory( $_child );
						$categories[ $id ]['children'][ $_child ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $_child );
					}
				}

				//-----------------------------------------
				// Latest record display
				//-----------------------------------------

				if( in_array( $this->database['database_field_title'], array( 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'rating_real' ) ) )
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= ( $this->database['database_field_title'] == 'record_saved' OR $this->database['database_field_title'] == 'record_updated' ) ? $this->registry->class_localization->getDate( $categories[ $id ][ $this->database['database_field_title'] ], 'LONG' ) : $categories[ $id ][ $this->database['database_field_title'] ];
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				else
				{
					$categories[ $id ][ $this->database['database_field_title'] . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $categories[ $id ], $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_title'] . '_value' ];
				}
				
				if( in_array( $this->database['database_field_content'], array( 'primary_id_field', 'member_id', 'record_saved', 'record_updated', 'rating_real' ) ) )
				{
					$categories[ $id ][ $this->database['database_field_content'] . '_value' ]	= ( $this->database['database_field_content'] == 'record_saved' OR $this->database['database_field_content'] == 'record_updated' ) ? $this->registry->class_localization->getDate( $categories[ $id ][ $this->database['database_field_content'] ], 'LONG' ) : $categories[ $id ][ $this->database['database_field_content'] ];
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_content'] . '_value' ];
				}
				else
				{
					$categories[ $id ][ $this->database['database_field_content'] . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ], $categories[ $id ], $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_truncate'] );
					$categories[ $id ][ $this->fields[ str_replace( 'field_', '', $this->database['database_field_content'] ) ]['field_key'] ]	= $categories[ $id ][ $this->database['database_field_content'] . '_value' ];
				}
				
				$categories[ $id ]['last_author']	= IPSMember::buildDisplayData( $categories[ $id ]['member_id'] ? $categories[ $id ]['member_id'] : IPSMember::setUpGuest() );

				$categories[ $id ]['record_link']	= $this->getRecordUrl( array( 'primary_id_field' => $categories[ $id ]['category_last_record_id'], 'category_id' => $categories[ $id ]['category_last_record_cat'] ? $categories[ $id ]['category_last_record_cat'] : $id, $this->database['database_field_title'] . '_value' => $categories[ $id ][ $this->database['database_field_title'] . '_value' ], '_skipUpdateDynamic' => true ) );
				$categories[ $id ]['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $id );
			}
		}

		//-----------------------------------------
		// Breadcrumbs
		//-----------------------------------------

		if( count($categories) OR $this->request['category'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $this->request['category'], $this->database['base_link'] );
	
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}
		else if( !$this->request['category'] )
		{
			$crumbies	= array( array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
		}

		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );

		if( $category['category_name'] )
		{
			$this->_setPageTitle( array( 'category_name' => $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'], 'database_name' => $this->database['database_name'] ), 2 );
		}
		
		//-----------------------------------------
		// Using forums?
		//-----------------------------------------

		$_useForums	= false;

		if( $category['category_forum_override'] )
		{
			if( $category['category_forum_record'] )
			{
				$_useForums	= true;
			}
		}
		else
		{
			if( $this->database['database_forum_record'] )
			{
				$_useForums	= true;
			}
		}

		//-----------------------------------------
		// Showing records?
		//-----------------------------------------
		
		$_show	= true;
		
		if( $category['category_id'] )
		{
			if( !$category['category_show_records'] )
			{
				$_show	= false;
			}
		}

		$records	= array();
		$pages		= '';
		
		if( $_show )
		{
			//-----------------------------------------
			// Sorting and limiting
			//-----------------------------------------
			
			$perPage	= intval($this->request['per_page']) ? intval($this->request['per_page']) : $this->database['database_field_perpage'];
			$perPage	= intval($perPage) > 0 ? $perPage : 25;
			$start		= intval($this->request['st']) ? intval($this->request['st']) : 0;
			
			$_col		= $this->request['sort_col'];
			$_useCol	= $this->database['database_field_sort'];
			$_numeric	= false;
	
			foreach( $this->fields as $_field )
			{
				if( $_col == 'field_' . $_field['field_id'] )
				{
					$_useCol	= $_col;
				}
			}
			
			if( in_array( $_col, array( 'rating_real', 'record_views', 'primary_id_field', 'member_id', 'record_saved', 'record_updated' ) ) )
			{
				$_useCol	= $_col;
			}
			
			//-----------------------------------------
			// Need to loop a second time to set numeric
			//-----------------------------------------
			
			foreach( $this->fields as $_field )
			{
				if( $_useCol == 'field_' . $_field['field_id'] )
				{
					$_numeric	= $_field['field_is_numeric'];
				}
			}
	
			$_dir		= ( $this->request['sort_order'] && in_array( $this->request['sort_order'], array( 'asc', 'desc' ) ) ) ? $this->request['sort_order'] : $this->database['database_field_direction'];
			
			$this->request['sort_col']		= $_useCol;
			$this->request['sort_order']	= $_dir;
			$this->request['per_page']		= $perPage;
		
			//-----------------------------------------
			// Search
			//-----------------------------------------
	
			$_where	= '';
			
			if( $this->request['search_value'] )
			{
				$_where	= $this->fieldsClass->getSearchWhere( $this->fields, $this->request['search_value'] );
			}
			
			if( $category['category_id'] )
			{
				$_where		= $_where ? "category_id=" . intval($this->request['category']) . " AND (" . $_where . ")" : "category_id=" . intval($this->request['category']);
			}
			else
			{
				if( count($this->categories->categories) )
				{
					$_catIds	= array();
					
					foreach( $this->categories->categories as $_catId => $_cat )
					{
						$_catIds[]	= $_catId;
					}
					
					$_where		= $_where ? "category_id IN(" . implode( ',', $_catIds ) . ") AND (" . $_where . ")" : "category_id IN(" . implode( ',', $_catIds ) . ")";
				}
			}
			
			//-----------------------------------------
			// Filters
			//-----------------------------------------
						
			if ( isset( $this->request['filters'] ) )
			{
				foreach ( $this->request['filters'] as $field => $options )
				{
					if ( isset( $this->fields[ $field ] ) and $this->fields[ $field ]['field_filter'] )
					{
						$values = array();

						foreach ( $options as $k => $v )
						{
							if ( isset( $this->request['reverse_filters'][ $field ] ) )
							{
								$values[] = "'" . $this->DB->addSlashes( $v ) . "'";
							}
							else
							{
								if ( $v )
								{
									$values[] = $k ? "'" . $this->DB->addSlashes( $k ) . "'" : 0;
								}
							}
						}

						if ( !empty( $values ) )
						{
							$__where	= '';
							$_or		= array();

							/* Value may be a single value if it's a radio or dropdown field, or a comma-separated list of values for multi-select or checkbox fields */
							foreach( $values as $value )
							{
								$_or[] = "FIND_IN_SET(" . $value . ", field_{$this->fields[ $field ]['field_id']})";
							}

							$__where	= '( ' . implode( " OR ", $_or ) . ' )';
							$_where		= $_where ? "( " . $__where . " ) AND (" . $_where . ")" : $__where;
						}
					}
				}
			}
			
			//-----------------------------------------
			// Only show approved records if we can't mod
			//-----------------------------------------
			
			$_approval	= array( 1 );

			if( $this->database['moderate_approve'] )
			{
				$_approval[]	= 0;
			}

			if( $this->database['moderate_delete'] )
			{
				$_approval[]	= -1;
			}

			if( $_where )
			{
				$_where	= "record_approved IN(" . implode( ',', $_approval ) . ") AND " . $_where;
			}
			else
			{
				$_where	= "record_approved IN(" . implode( ',', $_approval ) . ")";
			}

			//-----------------------------------------
			// Only queued?
			//-----------------------------------------

			if( $this->database['moderate_approve'] AND $this->request['sort'] == 'queued' )
			{
				$_where .= " AND record_approved=0";
			}
			else if( $this->database['moderate_approvec'] AND $this->request['sort'] == 'queuedc' )
			{
				$_where .= " AND record_comments_queued > 0";
			}
			
			$count		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => $this->database['database_database'], 'where' => $_where ) );
			
			//-----------------------------------------
			// Filters can have 0=> 1, ''=> 2, and one will be ignored
			// after http_build_query generates end result
			//-----------------------------------------

			if( is_array($this->request['filters']) AND count($this->request['filters']) )
			{
				foreach( $this->request['filters'] as $k => $v )
				{
					if( is_array( $v ) AND count( $v ) )
					{
						foreach( $v as $_k => $_v )
						{
							if( $_k === '' )
							{
								unset( $this->request['filters'][ $k ][ $_k ] );
								unset( $v[ $_k ] );
								$this->request['filters'][ $k ][ count($v) + 1 ]	= $_v;
							}
						}
					}
				}
			}

			//-----------------------------------------
			// Query string
			//-----------------------------------------
			
			$baseUrl	= $this->request['category'] ? $this->categories->getCategoryUrl( $this->database['base_link'], $this->request['category'], true ) : $this->database['base_link'];
			$_qs		= http_build_query( array( 'search_value' => $this->request['search_value'], 'sort_col' => $_useCol, 'sort_order' => $_dir, 'per_page' => $perPage, 'filters' => $this->request['filters'], 'reverse_filters' => $this->request['reverse_filters'] ) );
			
			if( $this->request['do'] == 'search' )
			{
				$_qs	= "do=search&amp;" . $_qs;
			}

			if( $this->request['sort'] )
			{
				$_qs	.= "&amp;sort=" . $this->request['sort'];
			}
			
			//-----------------------------------------
			// Page links
			//-----------------------------------------
			
			$pages		= $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $count['total'],
																	'itemsPerPage'		=> $perPage,
																	'currentStartValue'	=> $start,
																	'baseUrl'			=> $baseUrl . $_qs,
																	'base'				=> 'none',
											 				)	);

			//-----------------------------------------
			// Count for search results
			//-----------------------------------------
			
			if( $this->request['search_value'] )
			{
				$this->database['keyword']		= $this->request['search_value'];
				$this->database['result_cnt']	= $count['total'];
			}
			
			//-----------------------------------------
			// Set final sorting
			//-----------------------------------------
			
			$_finalSort	= 'record_pinned DESC, ' . ( $_numeric ? $_useCol . '+0' : $_useCol ) . ' ' . $_dir;
			
			if( $this->database['moderate_approve'] AND $this->request['sort'] == 'queued' )
			{
				$_finalSort	= 'record_approved ASC, record_pinned DESC, ' . ( $_numeric ? $_useCol . '+0' : $_useCol ) . ' ' . $_dir;
			}

			//-----------------------------------------
			// Records
			//-----------------------------------------
			
			$_memberIds	= array();
			$recordIds	= array();
			
			$this->DB->build( array(
									'select'	=> 'r.*',
									'from'		=> array( $this->database['database_database'] => 'r' ),
									'order'		=> $_finalSort,
									'where'		=> $_where,
									'limit'		=> array( $start, $perPage ),
									'add_join'	=> array( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) ) ),
							)		);
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$r['_isRead']		= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'databaseID' => $this->database['database_id'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
				$r['_iPosted']		= ( $r['member_id'] == $this->memberData['member_id'] ) ? 1 : 0;
				$r['record_link']	= $this->getRecordUrl( $r );
				
				if ( ! empty( $r['tag_cache_key'] ) )
				{
					if( $category['category_id'] AND $category['category_tags_override'] )
					{
						if( $category['category_tags_enabled'] )
						{
							$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
						}
					}
					else
					{
						if( $this->database['database_tags_enabled'] )
						{
							$r['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $r );
						}
					}
				}
	
				$records[ $r['primary_id_field'] ]		= $r;
				$memberIds[ $r['member_id'] ]			= $r['member_id'];
				$recordIds[ $r['primary_id_field'] ]	= $_useForums ? $r['record_topicid'] : $r['primary_id_field'];
			}
			
			if( count($memberIds) )
			{
				$_members	= IPSMember::load( $memberIds );
				$_mems		= array();
				
				foreach( $_members as $k => $v )
				{
					foreach( $v as $_k => $_v )
					{
						if( strpos( $_k, 'field_' ) === 0 )
						{
							$_k	= str_replace( 'field_', 'user_field_', $_k );
						}
						
						$_mems[ $k ][ $_k ]	= $_v;
					}
				}
				
				$_members	= $_mems;
				
				foreach( $records as $key => $record )
				{
					$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['member_id'] ] ? $_members[ $record['member_id'] ] : IPSMember::setUpGuest() ) );
				}
			}

			foreach( $records as $key => $record )
			{
				foreach( $record as $k => $v )
				{
					if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
					{
						$records[ $key ][ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $record, $this->fields[ $matches[1] ]['field_truncate'] );
						$records[ $key ][ $this->fields[ $matches[1] ]['field_key'] ]	= $records[ $key ][ $k . '_value' ];
					}
				}
			}

			//-----------------------------------------
			// Are we dotty?
			//-----------------------------------------
			
			if( $this->settings['show_user_posted'] and $this->memberData['member_id'] and count($recordIds) )
			{
				if( $_useForums )
				{
					$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' );

					$this->DB->build( array( 'select' => 'author_id, topic_id',
											 'from'   => 'posts',
											 'where'  => $_queued . ' AND author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $recordIds ) . ')' )	);
					$this->DB->execute();
					
					while( $p = $this->DB->fetch() )
					{
						foreach( $records as $k => $v )
						{
							if( $v['record_topicid'] == $p['topic_id'] )
							{
								$records[ $k ]['_iPosted'] = 1;
								break;
							}
						}
					}
				}
				else
				{
					$this->DB->build( array( 'select' => 'comment_user, comment_record_id',
											 'from'   => 'ccs_database_comments',
											 'where'  => 'comment_approved=1 AND comment_user=' . $this->memberData['member_id'] . ' AND comment_database_id=' . $this->database['database_id'] . ' AND comment_record_id IN(' . implode( ',', $recordIds ) . ')' )	);
					$this->DB->execute();
					
					while( $p = $this->DB->fetch() )
					{
						if ( is_array( $records[ $p['comment_record_id'] ] ) )
						{
							$records[ $p['comment_record_id'] ]['_iPosted'] = 1;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		$filterFields = array();

		foreach( $this->fields as $field )
		{
			if( $field['field_display_listing'] )
			{
				$showFields[]	= $field;
			}

			if ( $field['field_filter'] )
			{
				$field['options']	= array();
				$_options			= explode( "\n", str_replace( "\r", '', $field['field_extra'] ) );

				foreach( $_options as $k => $v )
				{
					$exploded	= explode( '=', $v );
					$active		= 0;

					if( count($_options) < 15 AND $this->request['filters'][ $field['field_id'] ][ $exploded[0] ] )
					{
						$active	= 1;
					}

					if( count($_options) < 15 AND $exploded[0] == 0 AND $this->request['filters'][ $field['field_id'] ][''] )
					{
						$active	= 1;
					}

					if( count($_options) >= 15 AND isset( $this->request['filters'][ $field['field_id'] ] ) AND in_array( $exploded[0], $this->request['filters'][ $field['field_id'] ] ) )
					{
						$active	= 1;
					}
				
					$field['options'][ $exploded[0] ] = array( 'value' => $exploded[1], 'active' => $active );
				}
				
				if ( !empty( $field['options'] ) )
				{
					$filterFields[] = $field;
				}
			}
		}
		
		/* Followed stuffs */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'ccs', $this->database['database_database'].'_categories' );

		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'records' => $records, 'pages' => $pages, 'categories' => $categories, 'parent' => $category, 'show' => $_show, 'follow_data' => $category['category_id'] ? $this->_like->render( 'summary', $category['category_id'] ) : '', 'filter_box' => $filterFields, 'baseUrl' => $baseUrl . $_qs ) );
		
		return $_content;
	}
	
	/**
	 * Load the database
	 *
	 * @access	protected
	 * @param	string		Key
	 * @return	array 		Database info
	 */
	protected function _loadDatabase( $key )
	{
		if( !$key )
		{
			return array();
		}
		
		$database	= array();
		
		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $dbid => $_database )
			{
				if( $key == $_database['database_key'] )
				{
					$database	= $_database;
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Store globally for item marking lib
		//-----------------------------------------
		
		if( $database['database_id'] )
		{
			$this->settings['_currentDatabaseId']	= $database['database_id'];
		}
		
		return $database;
	}
	
	/**
	 * Load the appropriate template data
	 *
	 * @access	protected
	 * @param	string		Override to a specific template type
	 * @return	@e void
	 */
	protected function _loadTemplates( $override='' )
	{
		//-----------------------------------------
		// Which template we loading?
		//-----------------------------------------
		
		$templateType	= $override;
		
		if( $this->request['record'] )
		{
			$templateType	= 'record';
		}
		else if( $this->request['category'] )
		{
			$templateType	= 'category';
		}
		else if( !$override )
		{
			$templateType	= 'index';
		}
		
		//-----------------------------------------
		// Get it
		//-----------------------------------------
		
		if( $templateType == 'record' )
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $this->database['database_template_display'] ) );
		}
		else if( $templateType == 'category' )
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $this->database['database_template_listing'] ) );
		}
		else
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_id=" . $this->database['database_template_categories'] ) );
		}
		
		//-----------------------------------------
		// Merge into database data and return
		//-----------------------------------------
		
		if( !is_array($template) OR !count($template) )
		{
			$this->registry->output->showError( $this->lang->words['error_loading_db_template'], '10CCSDB.1' );
		}
		
		$this->database	= array_merge( $this->database, $template );
	}

	/**
	 * Checks permissions for request, and sets some basic parameters
	 *
	 * @param	array 		Page info
	 * @return	bool		Successfully init
	 */	
	public function _initDatabase( $page )
	{
		//-----------------------------------------
		// Check show and view permissions
		//-----------------------------------------

		if( $this->database['perm_view'] != '*' )
		{ 
			if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Need to set base link for this DB page
		//-----------------------------------------
		
		$this->database['base_link']		= $this->registry->ccsFunctions->returnPageUrl( $page );
		
		if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
		{
			$this->database['base_link']	.= '&amp;';
		}
		else
		{
			$this->database['base_link']	.= '?';
		}

		$this->settings['post_key']	= md5( uniqid( microtime() ) );
		
		//-----------------------------------------
		// Permissions
		//-----------------------------------------
		
		$this->database['_can_add']		= false;
		$this->database['_can_edit']	= false;
		$this->database['_can_rate']	= false;
		$this->database['_can_comment']	= false;

		if ( $this->registry->permissions->check( 'add', $this->database ) == TRUE )
		{
			$this->database['_can_add']			= true;
		}

		$this->database['database_rate']		= trim( $this->database['perm_6'], ' ,' ) ? 1 : 0;
		$this->database['database_comments']	= trim( $this->database['perm_5'], ' ,' ) ? 1 : 0;

		if ( $this->registry->permissions->check( 'rate', $this->database ) == TRUE )
		{
			$this->database['_can_rate']		= true;
		}

		if ( $this->registry->permissions->check( 'comment', $this->database ) == TRUE )
		{
			$this->database['_can_comment']		= true;
		}
		
		//-----------------------------------------
		// Can we report?
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
		$reports		= new $classToLoad( $this->registry );
		
		$this->database['_can_report']			= $reports->canReport( 'ccs' );
		
		//-----------------------------------------
		// Reputation
		//-----------------------------------------
		
		if ( $this->settings['reputation_enabled'] )
		{
			if( !$this->registry->isClassLoaded('repCache') )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
				$this->registry->setClass( 'repCache', new $classToLoad() );
			}
		}

		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $this->database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $this->database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $this->database['database_id'] ) );
		}
		
		//-----------------------------------------
		// Moderator permissions
		//-----------------------------------------
		
		$this->database	= $this->checkModerator( $this->database );

		return true;
	}
	
	/**
	 * Delete a record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionDelete()
	{
		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Can we add?
		//-----------------------------------------
		
		if( !$this->database['moderate_delete'] )
		{
			$this->registry->output->showError( $this->lang->words['noperm_del_record'], '10CCS30', false, null, 403 );
		}
		
		//-----------------------------------------
		// Get the data
		//-----------------------------------------
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval($this->request['record']) ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['nofind_del_record'], '10CCS31', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nofind_del_record'], '10CCS32', false, null, 404 );
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['nofind_del_record'], '10CCS33', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS33.1', false, null, 403 );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Have we confirmed?
		//-----------------------------------------
		
		if( !$this->request['confirm'] )
		{
			return $this->registry->output->getTemplate('ccs_global')->confirmDeleteRecord( $this->database, $record );
		}

		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.4', null, null, 403 );
		}
		
		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $record, 'ccsPreDelete' );

		//-----------------------------------------
		// Delete record
		//-----------------------------------------
		
		$this->DB->delete( $this->database['database_database'], 'primary_id_field=' . $record['primary_id_field'] );
		
		//-----------------------------------------
		// Delete revisions
		//-----------------------------------------

		$this->DB->delete( 'ccs_database_revisions', 'revision_database_id=' . $this->database['database_id'] . ' AND revision_record_id=' . $record['primary_id_field'] );
		
		//-----------------------------------------
		// Delete comments
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_comments', 'comment_database_id=' . $this->database['database_id'] . ' AND comment_record_id=' . $record['primary_id_field'] );

		//-----------------------------------------
		// Ratings
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_ratings', 'rating_database_id=' . $this->database['database_id'] . ' AND rating_record_id=' . $record['primary_id_field'] );
		
		//-----------------------------------------
		// Post process delete
		//-----------------------------------------

		foreach( $this->fields as $field )
		{
			$this->fieldsClass->postProcessDelete( $field, $record );
		}
		
		//-----------------------------------------
		// Topic
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
		$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
		$_topics->removeTopic( $record, $this->categories->categories[ $record['category_id'] ], $this->database );

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		if( $record['record_approved'] == 1 )
		{
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($this->database['database_record_count'] - 1) ), 'database_id=' . $this->database['database_id'] );
		}
		
		//-----------------------------------------
		// Remove tags
		//-----------------------------------------
		
		$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->deleteByMetaId( array( $record['primary_id_field'] ) );
		
		//-----------------------------------------
		// Remove likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'ccs', 'default' );
		$_like->remove( array( $record['primary_id_field'] ) );
		
		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $record, 'ccsPostDelete' );

		//-----------------------------------------
		// Recache category
		//-----------------------------------------

		$this->categories->recache( $record['category_id'] );

		if( strpos( $this->request['return'], 'modcp' ) === 0 )
		{
			$_bits	= explode( ':', $this->request['return'] );
			$this->registry->output->redirectScreen( $this->lang->words['record_deleted_success'], $this->registry->output->buildSEOUrl( "app=core&amp;module=modcp&amp;fromapp=ccs&amp;tab=" . ( $_bits[1] == 'deleted' ? "deletedrecords" : "unapprovedrecords" ) . "&amp;database={$this->database['database_id']}", 'public' ) );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['record_deleted_success'], $record['category_id'] ? $this->categories->getCategoryUrl( $this->database['base_link'], $record['category_id'] ) : $this->database['base_link'] );
		}
	}

	/**
	 * Lock or unlock record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionToggleLock()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.5', null, null, 403 );
		}

		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Get the data
		//-----------------------------------------
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval($this->request['record']) ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['nofind_lock_record'], '10CCS39', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nofind_lock_record'], '10CCS40', false, null, 404 );
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['nofind_lock_record'], '10CCS41', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS41.1', false, null, 403 );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Update record
		//-----------------------------------------
		
		if( $record['record_locked'] )
		{
			//-----------------------------------------
			// Permission
			//-----------------------------------------
			
			if( !$this->database['moderate_unlock'] )
			{
				$this->registry->output->showError( $this->lang->words['noperm_lock_record'], '10CCS42', false, null, 403 );
			}
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreUnlock' );

			$this->DB->update( $this->database['database_database'], array( 'record_locked' => 0 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostUnlock' );
			
			$this->registry->output->redirectScreen( $this->lang->words['record_unlocked_success'], $this->getRecordUrl( $record ) );
		}
		else
		{
			//-----------------------------------------
			// Permission
			//-----------------------------------------
			
			if( !$this->database['moderate_lock'] )
			{
				$this->registry->output->showError( $this->lang->words['noperm_lock_record'], '10CCS43', false, null, 403 );
			}
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreLock' );
			
			$this->DB->update( $this->database['database_database'], array( 'record_locked' => 1 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostLock' );
			
			$this->registry->output->redirectScreen( $this->lang->words['record_locked_success'], $this->getRecordUrl( $record ) );
		}
	}
	
	/**
	 * Approve or unapprove record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionApprove( )
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.7', null, null, 403 );
		}

		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Permission
		//-----------------------------------------
		
		if( !$this->database['moderate_approve'] )
		{
			$this->registry->output->showError( $this->lang->words['noperm_app_record'], '10CCS44', false, null, 403 );
		}
		
		//-----------------------------------------
		// Get the data
		//-----------------------------------------
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval($this->request['record']) ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['nofind_app_record'], '10CCS45', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nofind_app_record'], '10CCS46', false, null, 404 );
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['nofind_app_record'], '10CCS47', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS47.1', false, null, 403 );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Update record
		//-----------------------------------------
		
		if( $record['record_approved'] == 1 )
		{
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreUnapprove' );
			
			$this->DB->update( $this->database['database_database'], array( 'record_approved' => -1 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count-1", 'database_id=' . $this->database['database_id'], false, true );
			
			if( $record['category_id'] )
			{
				$this->categories->recache( $record['category_id'] );
			}
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostUnapprove' );
			
			//-----------------------------------------
			// Tags
			//-----------------------------------------
			
			$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->updateVisibilityByMetaId( $record['primary_id_field'], 0 );
			
			//-----------------------------------------
			// Hide likes
			//-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'ccs', 'default' );
			$_like->toggleVisibility( array( $record['primary_id_field'] ), false );
			
			$this->registry->output->redirectScreen( $this->lang->words['record_unapprove_success'], $this->getRecordUrl( $record ) );
		}
		else
		{
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreApprove' );
			
			$this->DB->update( $this->database['database_database'], array( 'record_approved' => 1 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			$this->DB->update( 'ccs_databases', "database_record_count=database_record_count+1", 'database_id=' . $this->database['database_id'], false, true );
			
			if( $record['category_id'] )
			{
				$this->categories->recache( $record['category_id'] );
			}
			
			//-----------------------------------------
			// Tags
			//-----------------------------------------
			
			$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->updateVisibilityByMetaId( $record['primary_id_field'], 1 );
			
			//-----------------------------------------
			// Hide likes
			//-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'ccs', 'default' );
			$_like->toggleVisibility( array( $record['primary_id_field'] ), true );
			
			//-----------------------------------------
			// If this is first time approval, send notifications
			//-----------------------------------------
			
			$_modQueue	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_modqueue', 'where' => "mod_database={$this->database['database_id']} AND mod_record={$record['primary_id_field']} AND mod_comment=0" ) );
			
			if( $_modQueue['mod_id'] )
			{
		    	//-----------------------------------------
		    	// Post topic if configured to do so
		    	//-----------------------------------------
		    	
		    	$record['record_approved']	= 1;
		    	
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
		    	
		    	if( $_topics->checkForTopicSupport( $this->database, $this->categories->categories[ $record['category_id'] ] ) )
		    	{
			    	if ( $_topics->postTopic( $record, $this->categories->categories[ $record['category_id'] ], $this->database ) )
			    	{
						$_member					= IPSMember::load( $record['member_id'], 'core' );
						$record						= array_merge( $_member, $record );
						$record['poster_name']		= $record['members_display_name'];
						$record['poster_seo_name']	= $record['members_seo_name'];
				    	
					    $this->sendRecordNotification( $this->database, $this->categories->categories[ $record['category_id'] ], $record );
					}
				}
				else
				{
					$_member					= IPSMember::load( $record['member_id'], 'core' );
					$record						= array_merge( $_member, $record );
					$record['poster_name']		= $record['members_display_name'];
					$record['poster_seo_name']	= $record['members_seo_name'];

					$this->sendRecordNotification( $this->database, $this->categories->categories[ $record['category_id'] ], $record );
				}
			    
			    $this->DB->delete( 'ccs_database_modqueue', "mod_database={$this->database['database_id']} AND mod_record={$record['primary_id_field']} AND mod_comment=0" );
			}
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostApprove' );
			
			if( strpos( $this->request['return'], 'modcp' ) === 0 )
			{
				$_bits	= explode( ':', $this->request['return'] );
				$this->registry->output->redirectScreen( $this->lang->words['record_approve_success'], $this->registry->output->buildSEOUrl( "app=core&amp;module=modcp&amp;fromapp=ccs&amp;tab=" . ( $_bits[1] == 'deleted' ? "deletedrecords" : "unapprovedrecords" ) . "&amp;database={$this->database['database_id']}", 'public' ) );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['record_approve_success'], $this->getRecordUrl( $record ) );
			}
		}
	}

	/**
	 * Pin or unpin record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionPin()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.6', null, null, 403 );
		}

		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Get the data
		//-----------------------------------------
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval($this->request['record']) ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['nofind_pin_record'], '10CCS53', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nofind_pin_record'], '10CCS54', false, null, 404 );
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['nofind_pin_record'], '10CCS55', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS55.1', false, null, 403 );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Permission
		//-----------------------------------------
		
		if( !$this->database['moderate_pin'] )
		{
			$this->registry->output->showError( $this->lang->words['noperm_pin_record'], '10CCS56', false, null, 403 );
		}
		
		//-----------------------------------------
		// Update record
		//-----------------------------------------
		
		if( $record['record_pinned'] )
		{
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreUnpin' );
			
			$this->DB->update( $this->database['database_database'], array( 'record_pinned' => 0 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostUnpin' );
			
			$this->registry->output->redirectScreen( $this->lang->words['record_unpinned_success'], $this->getRecordUrl( $record ) );
		}
		else
		{
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPrePin' );
			
			$this->DB->update( $this->database['database_database'], array( 'record_pinned' => 1 ), 'primary_id_field=' . $record['primary_id_field'] );
			
			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPostPin' );
			
			$this->registry->output->redirectScreen( $this->lang->words['record_pinned_success'], $this->getRecordUrl( $record ) );
		}
	}
	
	/**
	 * Show form to add record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionAdd()
	{
		//-----------------------------------------
		// Can we add?
		//-----------------------------------------
		
		if( $this->request['category'] )
		{
			$_cat	= $this->categories->categories[ $this->request['category'] ];
			
			if ( $_cat['category_has_perms'] AND $this->registry->permissions->check( 'add', $_cat ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['noperm_add_category'], '10CCS58', false, null, 403 );
			}
			else if( $_cat['category_has_perms'] AND $this->registry->permissions->check( 'add', $_cat ) == TRUE )
			{
				$this->database['_can_add']	= true;
			}
		}
		
		if( !$this->database['_can_add'] )
		{
			$this->registry->output->showError( $this->lang->words['noperm_add_database'], '10CCS57', false, null, 403 );
		}
		
		//-----------------------------------------
		// Remove non-user-editable fields
		// Also sort form defaults
		//-----------------------------------------
		
		$_formDefaults	= array( 'post_key' => $this->settings['post_key'] );
		
		foreach( $this->fields as $_field )
		{
			if( !$_field['field_user_editable'] )
			{
				unset($this->fields[$_field['field_id']]);
			}
			else if( $_field['field_default_value'] !== '' )
			{
				$_formDefaults['field_' . $_field['field_id'] ]	= $_field['field_default_value'];
			}
		}

		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		$_formDefaults['_tagBox']	= '';

		$where = array( 'meta_parent_id'	=> $this->request['category'],
						'member_id'			=> $this->memberData['member_id'],
						'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
						);

		if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
		{
			$_formDefaults['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
		}

		//-----------------------------------------
		// Get category select list
		//-----------------------------------------

		$categories	= $this->categories->getSelectMenu( array(), 'add' );
		
		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $_formDefaults, 'ccsPreAdd' );
			
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_formDefaults, $categories );
	}
	
	/**
	 * Save the newly added record
	 *
	 * @access	protected
	 * @param	bool		Return instead of redirect (returns an array of data it would have used for redirect)
	 * @return	mixed		void (output an error) or HTML to print, or an array of data if $return is true
	 */
	protected function _actionSaveAdd( $return=false )
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.2', null, null, 403 );
		}

		//-----------------------------------------
		// Can we add?
		//-----------------------------------------
		
		if( $this->request['category_id'] )
		{
			$_cat	= $this->categories->categories[ $this->request['category_id'] ];

			if ( $_cat['category_has_perms'] AND $this->registry->permissions->check( 'add', $_cat ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['noperm_add_category'], '10CCS60', false, null, 403 );
			}
			else if( $_cat['category_has_perms'] AND $this->registry->permissions->check( 'add', $_cat ) == TRUE )
			{
				$this->database['_can_add']	= true;
			}
		}

		if( !$this->database['_can_add'] )
		{
			$this->registry->output->showError( $this->lang->words['noperm_add_database'], '10CCS59', false, null, 403 );
		}
		
		$this->settings['post_key']	= $this->request['post_key'];
		
		$_save	= array( 'post_key' => $this->request['post_key'] );
		
		//-----------------------------------------
		// Remove non-user-editable fields
		//-----------------------------------------
		
		foreach( $this->fields as $_field )
		{
			if( !$_field['field_user_editable'] )
			{
				$_save['field_' . $_field['field_id'] ] = $_field['field_default_value'];
			}
			else
			{
				$_save['field_' . $_field['field_id'] ]	= $this->fieldsClass->processInput( $_field );
			}
		}

		//-----------------------------------------
		// Any errors?
		//-----------------------------------------
		
		if( $error = $this->fieldsClass->getError() )
		{
			foreach( $this->fields as $_field )
			{
				$this->fieldsClass->onErrorCallback( $_field );

				if( !$_field['field_user_editable'] )
				{
					unset($this->fields[$_field['field_id']]);
				}
			}

			//-----------------------------------------
			// Cat lib
			//-----------------------------------------
			
			$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

			$categories	= $this->categories->getSelectMenu( array(), 'add' );

			//-----------------------------------------
			// Tagging
			//-----------------------------------------

			$_save['_tagBox']	= '';

			$where = array( 'meta_parent_id'	=> $this->request['category'],
							'member_id'			=> $this->memberData['member_id'],
							'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
							);

			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
			{
				$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
			}
	
			return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, $error );
		}
		
		foreach( $this->fields as $field )
		{
			$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
		}
		
		//-----------------------------------------
		// Some other data for add
		//-----------------------------------------
		
		$_save['member_id']				= $this->memberData['member_id'];
		$_save['record_saved']			= time();
		$_save['record_updated']		= time();
		$_save['category_id']			= intval($this->request['category_id']);
		$_save['record_dynamic_furl']	= IPSText::makeSeoTitle( $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $_save, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] ) );

		if( $this->database['database_record_approve'] AND !$this->database['moderate_approve'] )
		{
			$_save['record_approved']	= 0;
		}
		else
		{
			$_save['record_approved']	= 1;
		}

		if( $this->database['moderate_delete'] AND $this->request['hide_on_submit'] )
		{
			$_save['record_approved']	= -1;
		}

		if( $this->database['moderate_pin'] AND $this->request['pin_on_submit'] )
		{
			$_save['record_pinned']		= 1;
		}

		if( $this->database['moderate_lock'] AND $this->request['lock_on_submit'] )
		{
			$_save['record_locked']		= 1;
		}
		
		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------
		
		$where		= array( 'meta_parent_id'	=> $this->request['category_id'],
							 'member_id'		=> $this->memberData['member_id'],
							 'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_POST['ipsTags'] ) ) );
									  
		if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $this->request['category_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => ( $_save['record_approved'] == 1 ) ? 1 : 0 ) );

			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getErrorMsg() )
			{
				foreach( $this->fields as $_field )
				{
					$this->fieldsClass->onErrorCallback( $_field );

					if( !$_field['field_user_editable'] )
					{
						unset($this->fields[$_field['field_id']]);
					}
				}
	
				//-----------------------------------------
				// Cat lib
				//-----------------------------------------
				
				$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;
	
				$categories	= $this->categories->getSelectMenu( array(), 'add' );

				//-----------------------------------------
				// Tagging
				//-----------------------------------------

				$_save['_tagBox']	= '';

				$where = array( 'meta_parent_id'	=> $this->request['category'],
								'member_id'			=> $this->memberData['member_id'],
								'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
								);

				if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
				{
					$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
				}
		
				return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getFormattedError() );
			}
		}
		
		//-----------------------------------------
		// Extra details
		//-----------------------------------------
		
		if( $this->database['moderate_extras'] )
		{
			$_save['record_static_furl']		= IPSText::makeSeoTitle( trim($this->request['record_static_furl']) );
			$_save['record_meta_keywords']		= trim($this->request['record_meta_keywords']);
			$_save['record_meta_description']	= trim($this->request['record_meta_description']);
			
			if( $this->database['database_is_articles'] )
			{
				$_save['record_template']		= intval($this->request['article_template']);
			}

			if( $this->request['record_authorname'] )
			{
				$member					= IPSMember::load( $this->request['record_authorname'], 'members', 'displayname' );
				$_save['member_id']		= $member['member_id'] ? $member['member_id'] : $_save['member_id'];
			}
			
			if( $_save['record_static_furl'] )
			{
				$_check	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $this->database['database_database'], 'where' => "record_static_furl='{$_save['record_static_furl']}'" ) );
				
				if( $_check['primary_id_field'] )
				{
					foreach( $this->fields as $_field )
					{
						if( !$_field['field_user_editable'] )
						{
							unset($this->fields[$_field['field_id']]);
						}
				
						$this->fieldsClass->onErrorCallback( $_field );
					}

					//-----------------------------------------
					// Cat lib
					//-----------------------------------------
					
					$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

					$categories	= $this->categories->getSelectMenu( array(), 'add' );

					//-----------------------------------------
					// Tagging
					//-----------------------------------------

					$_save['_tagBox']	= '';

					$where = array( 'meta_parent_id'	=> $this->request['category'],
									'member_id'			=> $this->memberData['member_id'],
									'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
									);

					if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
					{
						$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
					}

					return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, $this->lang->words['static_furl_in_use'] );
				}
			}
		}
		
		//-----------------------------------------
		// Got a category?
		//-----------------------------------------
		
		if( ( !$_save['category_id'] OR !$this->categories->categories[ $_save['category_id'] ] ) AND count($this->categories->catcache) )
		{
			foreach( $this->fields as $_field )
			{
				$this->fieldsClass->onErrorCallback( $_field );

				if( !$_field['field_user_editable'] )
				{
					unset($this->fields[$_field['field_id']]);
				}
			}
			
			$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

			$categories	= $this->categories->getSelectMenu( array(), 'add' );

			//-----------------------------------------
			// Tagging
			//-----------------------------------------

			$_save['_tagBox']	= '';

			$where = array( 'meta_parent_id'	=> $this->request['category'],
							'member_id'			=> $this->memberData['member_id'],
							'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
							);

			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
			{
				$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
			}

			return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, $this->lang->words['must_select_cat'] );
		}
		
		//-----------------------------------------
		// Previewing?
		//-----------------------------------------

		if( $this->request['preview'] )
		{
			foreach( $this->fields as $_field )
			{
				$this->fieldsClass->onPreviewCallback( $_field );

				if( !$_field['field_user_editable'] )
				{
					unset($this->fields[$_field['field_id']]);
				}
			}
			
			$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

			$categories	= $this->categories->getSelectMenu( array(), 'add' );

			//-----------------------------------------
			// Tagging
			//-----------------------------------------

			$_save['_tagBox']	= '';

			$where = array( 'meta_parent_id'	=> $this->request['category'],
							'member_id'			=> $this->memberData['member_id'],
							'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
							);

			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
			{
				$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
			}

			return $this->registry->output->getTemplate('ccs_global')->recordForm( 'add', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, '', true );
		}

		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $_save, 'ccsPreSave' );
		
		//-----------------------------------------
		// Insert and recount
		//-----------------------------------------
		
		$this->DB->insert( $this->database['database_database'], $_save );
		
		$id	= $this->DB->getInsertId();
		
		$_save['primary_id_field']	= $id;
		
		if( $_save['record_approved'] == 1 )
		{
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($this->database['database_record_count'] + 1) ), 'database_id=' . $this->database['database_id'] );
		}
		
		//-----------------------------------------
		// If it's not approved, flag
		//-----------------------------------------
		
		if( !$_save['record_approved'] )
		{
			$modqueue	= array(
								'mod_database'		=> $this->database['database_id'],
								'mod_record'		=> $_save['primary_id_field'],
								'mod_comment'		=> 0,
								'mod_poster'		=> $this->memberData['member_id'],
								);
		
			$this->DB->insert( 'ccs_database_modqueue', $modqueue );
		}

		//-----------------------------------------
		// Post process
		//-----------------------------------------
		
		foreach( $this->fields as $field )
		{
			if( !$field['field_user_editable'] )
			{
				continue;
			}

			$this->fieldsClass->postProcessInput( $field, $id );
		}
		
		//-----------------------------------------
		// Store tags
		//-----------------------------------------
		
		if ( ! empty( $_POST['ipsTags'] ) )
		{
			$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->add( $_POST['ipsTags'], array( 'meta_id'			=> $_save['primary_id_field'],
												      				 'meta_parent_id'	=> $_save['category_id'],
												      				 'member_id'		=> $this->memberData['member_id'],
												      				 'meta_visible'		=> ( $_save['record_approved'] == 1 ) ? 1 : 0 ) );
		}

		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $_save, 'ccsPostSave' );

		//-----------------------------------------
		// Update category/cache
		//-----------------------------------------
		
		$this->categories->recache( $_save['category_id'] );
		$this->cache->rebuildCache( 'ccs_databases', 'ccs' );

		//-----------------------------------------
		// Delete URL memories of this URL
		//-----------------------------------------
	
		if( $this->database['moderate_extras'] )
		{
			$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $_save );
	
			$this->DB->delete( 'ccs_slug_memory', "memory_url='" . rtrim( $url, '/?' ) . "'" );
		}
		
		//-----------------------------------------
		// Send out appropriate notifications
		//-----------------------------------------
		
		if( $_save['record_approved'] == 1 )
		{
			//-----------------------------------------
			// Post topic if configured to do so
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
			
			if( $_topics->checkForTopicSupport( $this->database, $this->categories->categories[ $_save['category_id'] ] ) )
			{
				if ( $_topics->postTopic( $_save, $this->categories->categories[ $_save['category_id'] ], $this->database ) )
				{
					$_save['poster_name']		= $this->memberData['members_display_name'];
					$_save['poster_seo_name']	= $this->memberData['members_seo_name'];
					
					$this->sendRecordNotification( $this->database, $this->categories->categories[ $_save['category_id'] ], $_save );
				}
			}
			else
			{
				$_save['poster_name']		= $this->memberData['members_display_name'];
				$_save['poster_seo_name']	= $this->memberData['members_seo_name'];

				$this->sendRecordNotification( $this->database, $this->categories->categories[ $_save['category_id'] ], $_save );
			}
	    }
	    else
	    {
			$_save['poster_name']		= $this->memberData['members_display_name'];
			$_save['poster_seo_name']	= $this->memberData['members_seo_name'];

			$this->sendRecordPendingNotification( $this->database, $this->categories->categories[ $_save['category_id'] ], $_save );
	    }

		//-----------------------------------------
		// Social sharing
		//-----------------------------------------

		if( $_save['record_approved'] == 1 )
		{
			IPSMember::sendSocialShares( array( 'title' => $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $_save, $fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] ), 'url' => $this->getRecordUrl( $_save ) ) );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		if( $_save['record_approved'] == 1 )
		{
			if( $return )
			{
				return array( $this->lang->words['record_saved_success'], $this->getRecordUrl( $_save ), $_save );
			}
			
			$this->registry->output->redirectScreen( $this->lang->words['record_saved_success'], $this->getRecordUrl( $_save ) );
		}
		else
		{
			if( $_save['category_id'] )
			{
				if( $return )
				{
					return array( $this->lang->words['record_saved_success1'], $this->categories->getCategoryUrl( $this->database['base_link'], $_save['category_id'] ), $_save );
				}
			
				$this->registry->output->redirectScreen( $this->lang->words['record_saved_success1'], $this->categories->getCategoryUrl( $this->database['base_link'], $_save['category_id'] ) );
			}
			else
			{
				if( $return )
				{
					return array( $this->lang->words['record_saved_success1'], $this->database['base_link'], $_save );
				}
				
				$this->registry->output->redirectScreen( $this->lang->words['record_saved_success1'], $this->database['base_link'] );
			}
		}
	}
	
	/**
	 * Show form to edit a record
	 *
	 * @access	protected
	 * @return	mixed		void (output an error) or HTML to print
	 */
	protected function _actionEdit()
	{
		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		
		//-----------------------------------------
		// Got our id?
		//-----------------------------------------
		
		if( $this->request['record'] )
		{
			//-----------------------------------------
			// Get the data
			//-----------------------------------------

			$_approval	= array( 1 );

			if( $this->database['moderate_approve'] )
			{
				$_approval[]	= 0;
			}

			if( $this->database['moderate_delete'] )
			{
				$_approval[]	= -1;
			}

			$_approved	= "record_approved IN(" . implode( ',', $_approval ) . ") AND ";
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => $_approved . 'primary_id_field=' . intval($this->request['record']) ) );

			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS61', false, null, 404 );
			}

			$member	= IPSMember::load( $record['member_id'] );
			$_mem	= array();
			
			if( count($member) AND is_array($member) )
			{
				foreach( $member as $k => $v )
				{
					if( strpos( $k, 'field_' ) === 0 )
					{
						$k	= str_replace( 'field_', 'user_field_', $k );
					}
					
					$_mem[ $k ]	= $v;
				}
			}
			
			$member	= $_mem;

			$record	= array_merge( $member, $record );

			if( $record['category_id'] )
			{
				$category	= $this->categories->categories[ $record['category_id'] ];
				
				if( !$category['category_id'] )
				{
					$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS62', false, null, 404 );
				}
				
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
				{
					$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS63', false, null, 404 );
				}
				
				$_parents	= $this->categories->getParents( $record['category_id'] );
				
				if( count($_parents) )
				{
					foreach( $_parents as $_parent )
					{
						if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
						{
							$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS63.1', false, null, 403 );
						}
					}
				}
			}
			
			$this->settings['post_key']	= $record['post_key'];
			
			//-----------------------------------------
			// Can we edit this record?
			//-----------------------------------------

			if ( $this->registry->permissions->check( 'edit', $this->database ) == TRUE OR $this->registry->permissions->check( 'edit', $category ) == TRUE )
			{
				if( $this->database['database_all_editable'] OR ( $this->memberData['member_id'] AND $record['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
				{
					$this->database['_can_edit']		= true;
				}
			}
			
			if( $record['category_id'] )
			{
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $category ) != TRUE )
				{
					$this->database['_can_edit']		= false;
				}
			}
			
			if( $this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= true;
			}
			
			if( $record['record_locked'] AND !$this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= false;
			}
			
			//-----------------------------------------
			// Remove non-user-editable fields
			//-----------------------------------------
			
			foreach( $this->fields as $_field )
			{
				if( !$_field['field_user_editable'] )
				{
					unset($this->fields[$_field['field_id']]);
				}
			}
			
			//-----------------------------------------
			// Get cat selector
			//-----------------------------------------
			
			$this->request['category']	= $record['category_id'];

			$categories	= $this->categories->getSelectMenu( array(), 'add' );

			//-----------------------------------------
			// Tagging
			//-----------------------------------------
	
			$record['_tagBox']	= '';
	
			$where = array( 'meta_id'		 => $record['primary_id_field'],
						    'meta_parent_id' => $record['category_id'],
						    'member_id'	     => $this->memberData['member_id']
							);

			if ( $_REQUEST['ipsTags'] )
			{
				$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) );
			}
		
			if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'edit', $where ) )
			{
				$record['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
			}

			//-----------------------------------------
			// Data hook
			//-----------------------------------------
			
			IPSLib::doDataHooks( $record, 'ccsPreEdit' );
			
			//-----------------------------------------
			// Output or error
			//-----------------------------------------
			
			if( $this->database['_can_edit'] )
			{
				return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, $record, $categories );
			}
			
			$this->registry->output->showError( $this->lang->words['record_edit_no_perm'], '10CCS64', false, null, 403 );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS65', false, null, 403 );
		}
	}
	
	/**
	 * Save updated record
	 *
	 * @access	protected
	 * @param	bool		Return instead of redirect (returns an array of data it would have used for redirect)
	 * @return	mixed		void (output an error) or HTML to print, or an array of data if $return is true
	 */
	protected function _actionSaveEdit( $return=false )
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.3', null, null, 403 );
		}

		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();

		//-----------------------------------------
		// Got our id?
		//-----------------------------------------
		
		if( $this->request['record'] )
		{
			//-----------------------------------------
			// Get record data
			//-----------------------------------------
			
			$_approval	= array( 1 );

			if( $this->database['moderate_approve'] )
			{
				$_approval[]	= 0;
			}

			if( $this->database['moderate_delete'] )
			{
				$_approval[]	= -1;
			}
			
			$_approved	= "record_approved IN(" . implode( ',', $_approval ) . ") AND ";
			
			$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => $_approved . 'primary_id_field=' . intval($this->request['record']) ) );

			if( !$record['primary_id_field'] )
			{
				$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS66', false, null, 404 );
			}

			// Member data is not used anywhere here.  Leaving this in place, but commented out, for now.
			// $member	= IPSMember::load( $record['member_id'] );
			
			// Merging the data causes problems with revisions
			// $record	= array_merge( $member, $record );
			
			if( $record['category_id'] )
			{
				$category	= $this->categories->categories[ $record['category_id'] ];
				
				if( !$category['category_id'] )
				{
					$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS67', false, null, 404 );
				}
				
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
				{
					$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS68', false, null, 404 );
				}
				
				$_parents	= $this->categories->getParents( $record['category_id'] );
				
				if( count($_parents) )
				{
					foreach( $_parents as $_parent )
					{
						if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
						{
							$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS68.1', false, null, 403 );
						}
					}
				}
			}
			
			$this->settings['post_key']	= $record['post_key'];
			
			//-----------------------------------------
			// Can we edit this record?
			//-----------------------------------------

			if ( $this->registry->permissions->check( 'edit', $this->database ) == TRUE OR $this->registry->permissions->check( 'edit', $category ) == TRUE )
			{
				if( $this->database['database_all_editable'] OR ( $this->memberData['member_id'] AND $record['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
				{
					$this->database['_can_edit']		= true;
				}
			}
			
			if( $record['category_id'] )
			{
				if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $category ) != TRUE )
				{
					$this->database['_can_edit']		= false;
				}
			}
			
			if( $this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= true;
			}
			
			if( $record['record_locked'] AND !$this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= false;
			}
			
			if( $this->database['_can_edit'] )
			{
				$_save	= array();
				
				//-----------------------------------------
				// Loop over fields and check input
				//-----------------------------------------

				foreach( $this->fields as $field )
				{
					if( !$field['field_user_editable'] )
					{
						continue;
					}
			
					$_save['field_' . $field['field_id'] ]	= $this->fieldsClass->processInput( $field );

					if( $error = $this->fieldsClass->getError() )
					{
						foreach( $this->fields as $_field )
						{
							$this->fieldsClass->onErrorCallback( $_field );

							if( !$_field['field_user_editable'] )
							{
								unset($this->fields[$_field['field_id']]);
							}
						}

						//-----------------------------------------
						// Cat lib
						//-----------------------------------------
						
						$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];

						$categories	= $this->categories->getSelectMenu( array(), 'add' );

						//-----------------------------------------
						// Tagging
						//-----------------------------------------

						$_save['_tagBox']	= '';

						$where = array( 'meta_parent_id'	=> $this->request['category'],
										'member_id'			=> $this->memberData['member_id'],
										'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
										);

						if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
						{
							$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
						}

						return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, array_merge( $record, $_save ), $categories, $error );
					}
				}
				
				foreach( $this->fields as $field )
				{
					$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
				}
				
				//-----------------------------------------
				// Some other needed data
				//-----------------------------------------
				
				$_save['record_updated']		= time();
				$_save['category_id']			= intval($this->request['category_id']);
				$_save['record_dynamic_furl']	= IPSText::makeSeoTitle( $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $_save, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] ) );
				
				//-----------------------------------------
				// Extra details
				//-----------------------------------------
				
				if( $this->database['moderate_extras'] )
				{
					$_save['record_static_furl']		= IPSText::makeSeoTitle( trim($this->request['record_static_furl']) );
					$_save['record_meta_keywords']		= trim($this->request['record_meta_keywords']);
					$_save['record_meta_description']	= trim($this->request['record_meta_description']);
					
					if( $this->database['database_is_articles'] )
					{
						$_save['record_template']		= intval($this->request['article_template']);
					}
		
					if( $this->request['record_authorname'] )
					{
						$member					= IPSMember::load( $this->request['record_authorname'], 'members', 'displayname' );
						$_save['member_id']		= $member['member_id'] ? $member['member_id'] : $record['member_id'];
					}
					
					if( $_save['record_static_furl'] )
					{
						$_check	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $this->database['database_database'], 'where' => "record_static_furl='{$_save['record_static_furl']}' AND primary_id_field<>" . $record['primary_id_field'] ) );
						
						if( $_check['primary_id_field'] )
						{
							foreach( $this->fields as $_field )
							{
								$this->fieldsClass->onErrorCallback( $_field );

								if( !$_field['field_user_editable'] )
								{
									unset($this->fields[$_field['field_id']]);
								}
							}
	
							//-----------------------------------------
							// Cat lib
							//-----------------------------------------
							
							$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];
	
							$categories	= $this->categories->getSelectMenu( array(), 'add' );

							//-----------------------------------------
							// Tagging
							//-----------------------------------------

							$_save['_tagBox']	= '';

							$where = array( 'meta_parent_id'	=> $this->request['category'],
											'member_id'			=> $this->memberData['member_id'],
											'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
											);

							if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
							{
								$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
							}

							return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, array_merge( $record, $_save ), $categories, $this->lang->words['static_furl_in_use'] );
						}
					}
				}

				if( $this->database['moderate_delete'] )
				{
					$_save['record_approved']	= $this->request['hide_on_submit'] ? -1 : ( ( $record['record_approved'] == -1 ) ? 1 : $record['record_approved'] );
				}

				if( $this->database['moderate_pin'] )
				{
					$_save['record_pinned']		= $this->request['pin_on_submit'] ? 1 : 0;
				}

				if( $this->database['moderate_lock'] )
				{
					$_save['record_locked']		= $this->request['lock_on_submit'] ? 1 : 0;
				}

				//-----------------------------------------
				// Got category?
				//-----------------------------------------
				
				if( ( !$_save['category_id'] OR !$this->categories->categories[ $_save['category_id'] ] ) AND count($this->categories->catcache) )
				{
					foreach( $this->fields as $_field )
					{
						$this->fieldsClass->onErrorCallback( $_field );

						if( !$_field['field_user_editable'] )
						{
							unset($this->fields[$_field['field_id']]);
						}
					}
			
					$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];

					$categories	= $this->categories->getSelectMenu( array(), 'add' );

					//-----------------------------------------
					// Tagging
					//-----------------------------------------

					$_save['_tagBox']	= '';

					$where = array( 'meta_parent_id'	=> $this->request['category'],
									'member_id'			=> $this->memberData['member_id'],
									'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
									);

					if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
					{
						$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
					}

					return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, $_save, $categories, $this->lang->words['must_select_cat'] );
				}
				
				//-----------------------------------------
				// Check if we're ok with tags
				//-----------------------------------------
				
				$where		= array( 'meta_parent_id'	=> $_save['category_id'],
									 'member_id'		=> $this->memberData['member_id'],
									 'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_POST['ipsTags'] ) ) );
											  
				if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'edit', $where ) AND $this->settings['tags_enabled'] )
				{
					$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->checkAdd( $_POST['ipsTags'], array(
																		  'meta_parent_id' => $_save['category_id'],
																		  'member_id'	   => $this->memberData['member_id'],
																		  'meta_visible'   => ( $record['record_approved'] == 1 ) ? 1 : 0 ) );
		
					if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getErrorMsg() )
					{
						foreach( $this->fields as $_field )
						{
							$this->fieldsClass->onErrorCallback( $_field );

							if( !$_field['field_user_editable'] )
							{
								unset($this->fields[$_field['field_id']]);
							}
						}
			
						//-----------------------------------------
						// Cat lib
						//-----------------------------------------
						
						$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];
			
						$categories	= $this->categories->getSelectMenu( array(), 'add' );

						//-----------------------------------------
						// Tagging
						//-----------------------------------------

						$_save['_tagBox']	= '';

						$where = array( 'meta_parent_id'	=> $this->request['category'],
										'member_id'			=> $this->memberData['member_id'],
										'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
										);

						if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
						{
							$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
						}

						return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, array_merge( $record, $_save ), $categories, $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getFormattedError() );
					}
				}

				//-----------------------------------------
				// Previewing?
				//-----------------------------------------
		
				if( $this->request['preview'] )
				{
					foreach( $this->fields as $_field )
					{
						$this->fieldsClass->onPreviewCallback( $_field );

						if( !$_field['field_user_editable'] )
						{
							unset($this->fields[$_field['field_id']]);
						}
					}
			
					$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : $record['category_id'];
		
					$categories	= $this->categories->getSelectMenu( array(), 'add' );

					//-----------------------------------------
					// Tagging
					//-----------------------------------------

					$_save['_tagBox']	= '';

					$where = array( 'meta_parent_id'	=> $this->request['category'],
									'member_id'			=> $this->memberData['member_id'],
									'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
									);

					if ( $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->can( 'add', $where ) )
					{
						$_save['_tagBox'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->render( 'entryBox', $where );
					}

					return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, array_merge( $record, $_save ), $categories, '', true );
				}
				
				//-----------------------------------------
				// Data hook
				//-----------------------------------------
				
				$_merged	= array_merge( $record, $_save );
				IPSLib::doDataHooks( $_merged, 'ccsPreEditSave' );

				//-----------------------------------------
				// Update db
				//-----------------------------------------
				
				$this->DB->update( $this->database['database_database'], $_save, 'primary_id_field=' . $record['primary_id_field'] );

				//-----------------------------------------
				// Post process input
				//-----------------------------------------

				foreach( $this->fields as $field )
				{
					if( !$field['field_user_editable'] )
					{
						continue;
					}
					
					$this->fieldsClass->postProcessInput( $field, $record['primary_id_field'] );
				}

				//-----------------------------------------
				// Store tags
				//-----------------------------------------

				$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->replace( $_POST['ipsTags'], array( 
																			 'meta_id'			=> $record['primary_id_field'],
														      				 'meta_parent_id'	=> $_save['category_id'],
														      				 'member_id'		=> $this->memberData['member_id'],
														      				 'meta_visible'		=> ( $record['record_approved'] == 1 ) ? 1 : 0 ) );

				//-----------------------------------------
				// Data hook
				//-----------------------------------------

				IPSLib::doDataHooks( $_merged, 'ccsPostEditSave' );

				//-----------------------------------------
				// Revisions?
				//-----------------------------------------
				
				if( $this->database['database_revisions'] )
				{
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
					$revisions		= new $classToLoad( $this->registry );
					$revisions->storeRevision( $this->database, $record );
				}
				
		    	//-----------------------------------------
		    	// Post topic if configured to do so
		    	//-----------------------------------------
		    	
		    	if( $this->request['update_topic'] )
		    	{
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
					$_topics		= new $classToLoad( $this->registry, $this->fieldsClass, $this->categories, $this->fields );
					$_topics->postTopic( array_merge( $record, $_save ), $this->categories->categories[ $_save['category_id'] ], $this->database, 'edit' );
				}
				
				//-----------------------------------------
				// Update categor(y|ies)/cache
				//-----------------------------------------
				
				$this->categories->recache( $_save['category_id'] );
				
				if( $record['category_id'] != $_save['category_id'] )
				{
					$this->categories->recache( $record['category_id'] );
				}
				
				$this->cache->rebuildCache( 'ccs_databases', 'ccs' );
				
				//-----------------------------------------
				// Remember past URL?
				//-----------------------------------------
	
				if( $this->database['moderate_extras'] )
				{
					if( $_save['record_static_furl'] != $record['record_static_furl'] )
					{
						$this->registry->ccsFunctions->fetchFreshUrl	= true;
						
						$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $record );

						$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => rtrim( $url, '/?' ), 'memory_type' => 'record', 'memory_type_id' => $record['primary_id_field'], 'memory_type_id_2' => $this->database['database_id'] ) );
					}

					//-----------------------------------------
					// Delete URL memories of this URL
					//-----------------------------------------
			
					$this->registry->ccsFunctions->fetchFreshUrl	= true;
					
					$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, array_merge( $record, $_save ) );
			
					$this->DB->delete( 'ccs_slug_memory', "memory_url='" . rtrim( $url, '/?' ) . "'" );
				}
				
				//-----------------------------------------
				// Redirect
				//-----------------------------------------
				
				if( $return )
				{
					return array( $this->lang->words['record_edited_success'], $this->getRecordUrl( array_merge( $record, $_save ) ), $_save );
				}

				$this->registry->output->redirectScreen( $this->lang->words['record_edited_success'], $this->getRecordUrl( $record ) );
			}
			
			$this->registry->output->showError( $this->lang->words['record_edit_no_perm'], '10CCS69', false, null, 403 );
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['record_edit_not_found'], '10CCS70', false, null, 403 );
		}
	}

	/**
	 * Make sure we can view the record
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _checkRecordPermissions()
	{
		if( $this->database['perm_view'] != '*' )
		{ 
			if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['noperm_list_database'], '10CCS28', false, null, 403 );
			}
		}
		
		if( $this->database['perm_2'] != '*' )
		{
			if ( $this->registry->permissions->check( 'show', $this->database ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['noperm_view_database'], '10CCS29', false, null, 403 );
			}
		}
	}
	
	/**
	 * Show a record
	 *
	 * @access	protected
	 * @return	string		HTML output
	 */
	protected function _getRecordDisplay()
	{
		//-----------------------------------------
		// Database permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
			
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();

		if( !$record['primary_id_field'] )
		{
			$this->checkPermalink( '' );

			$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS87', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] );
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS88', false, null, 404 );
			}

			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS89', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS89.1', false, null, 403 );
					}
				}
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'rate', $category ) != TRUE )
			{
				$this->database['_can_rate']	= false;
			}
			
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'comment', $category ) != TRUE )
			{
				$this->database['_can_comment']	= false;
			}
		}

		if( $record['record_locked'] )
		{
			$this->database['_can_comment']		= false;
		}

		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member	= IPSMember::load( $record['member_id'] );

		$_mem	= array();
		
		if( count($member) AND is_array($member) )
		{
			foreach( $member as $k => $v )
			{
				if( strpos( $k, 'field_' ) === 0 )
				{
					$k	= str_replace( 'field_', 'user_field_', $k );
				}
				
				$_mem[ $k ]	= $v;
			}
		}
		
		$member	= $_mem;
		
		$record	= array_merge( $member ? $member : IPSMember::setUpGuest(), $record );
		
		//-----------------------------------------
		// Format fields
		//-----------------------------------------
		
		foreach( $record as $k => $v )
		{
			if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
			{
				$record[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], $record );
				$record[ $this->fields[ $matches[1] ]['field_key'] ]	= $record[ $k . '_value' ];
			}
		}
		
		$record	= $this->parseAttachments( $this->database, $this->fields, $record );
		
		$record['record_link']	= $this->getRecordUrl( $record, true );
		
		$this->checkPermalink( $record['record_link'] );
		
		if ( $this->settings['reputation_enabled'] )
		{
			$record['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'ccs', 'type' => 'record_id_' . $this->database['database_id'], 'id' => $record['primary_id_field'], 'rep_like_cache' => $record['rep_like_cache'] ) );
		}
		else
		{
			$record['like']	= null;
		}
		
		//-----------------------------------------
		// Now the fun part - look for cross-linked relational fields
		//-----------------------------------------

		foreach( $this->caches['ccs_fields'] as $_dbId => $_dbFields )
		{
			foreach( $_dbFields as $_fieldId => $_fieldData )
			{
				if( $_fieldData['field_type'] == 'relational' )
				{
					$_fieldConfig	= explode( ',', $_fieldData['field_extra'] );

					if( $_fieldConfig[4] AND $_fieldConfig[0] == $this->database['database_id'] )
					{
						//-----------------------------------------
						// Relational field set to crosslink
						//-----------------------------------------

						$this->fields[ $_fieldId ]	= array( '_isRelated' => 1, 'field_id' => $_fieldId, 'field_type' => 'relational', 'field_name' => $this->lang->words['rel_related_ft'], 'field_display_display' => 1 );
						$_remoteDb					= $this->caches['ccs_databases'][ $_dbId ];
						$_fieldValues				= array();

						if( $record[ 'field_' . $_fieldId ] )
						{
							$_fieldValues[ $record[ 'field_' . $_fieldId . '_value' ] ]	= $record[ 'field_' . $_fieldId ];
						}

						$this->DB->build( array( 'select' => '*', 'from' => $_remoteDb['database_database'], 'where' => 'field_' . $_fieldId . '=' . $record['primary_id_field'] . ' OR field_' . $_fieldId . " LIKE '%,{$record['primary_id_field']},%'" ) );
						$outer	= $this->DB->execute();

						while( $r = $this->DB->fetch($outer) )
						{
							$_fieldValues[ $this->registry->output->getTemplate('ccs_global')->auto_crosslink_html( $_remoteDb, $r ) ]	= $r[ $_remoteDb['database_field_title'] ];
						}

						natcasesort( $_fieldValues );

						$record[ 'field_' . $_fieldId ]				= array_keys( $_fieldValues ); /* Leave this unformatted so raw value is there if they want it */
						$record[ 'field_' . $_fieldId . '_value' ]	= implode( ', ', array_keys( $_fieldValues ) );
					}
				}
			}
		}
		
		//-----------------------------------------
		// User editable
		//-----------------------------------------

		if ( $this->registry->permissions->check( 'edit', $this->database ) == TRUE OR $this->registry->permissions->check( 'edit', $category ) == TRUE )
		{
			if( $this->database['database_all_editable'] OR ( $this->memberData['member_id'] AND $record['member_id'] == $this->memberData['member_id'] ) OR $this->database['moderate_edit'] )
			{
				$this->database['_can_edit']		= true;
			}
		}
		
		if( $record['category_id'] )
		{
			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'edit', $category ) != TRUE )
			{
				$this->database['_can_edit']	= false;
			}
		}
		
		if( $this->database['moderate_edit'] )
		{
			$this->database['_can_edit']		= true;
		}
		
		if( $record['record_locked'] AND !$this->database['moderate_edit'] )
		{
			$this->database['_can_edit']		= false;
		}
		
		//-----------------------------------------
		// Breadcrumbs
		//-----------------------------------------

		$crumbies	= array();
		
		if( $record['category_id'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $record['category_id'], $this->database['base_link'] );
	
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}
		else
		{
			$crumbies[]	= array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) );
		}

		array_push( $crumbies, array( 0 => $this->lang->words['navigation_prefix'] . ' ' . $record[ $this->database['database_field_title'] . '_value' ], 1 => '' ) );
		
		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );
		
		if( $category['category_id'] )
		{
			$this->_setPageTitle( array( 'record_name' => $record[ $this->database['database_field_title'] . '_value' ], 'category_name' => $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'], 'database_name' => $this->database['database_name'] ), 3 );
		}
		else
		{
			$this->_setPageTitle( array( 'record_name' => $record[ $this->database['database_field_title'] . '_value' ], 'database_name' => $this->database['database_name'] ), 3 );
		}
		
		//-----------------------------------------
		// Mark read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'catID' => $record['category_id'], 'itemID' => $record['primary_id_field'], 'databaseID' => $this->database['database_id'] ), 'ccs' );
		
		//-----------------------------------------
		// Meta tags
		//-----------------------------------------
		
		if( $record['record_meta_keywords'] )
		{
			$this->pages->setMetaKeywords( $record['record_meta_keywords'] );
		}
		else
		{
			$text	= $record[ $this->database['database_field_title'] . '_value' ];
			$text	.= "\n " . $record[ $this->database['database_field_content'] . '_value' ];
			
			//-----------------------------------------
			// Mostly copied from output class
			//-----------------------------------------
			
			$text	= str_replace( array( '.', ',', '!', ':', ';', "'", "'", '@', '%', '*', '(', ')' ), '', preg_replace( "/&([^;]+?);/", "", strip_tags($text) ) );
			$_vals	= preg_split( "/\s+?/", $text, -1, PREG_SPLIT_NO_EMPTY );
			$_sw	= explode( ',', $this->lang->words['_stopwords_'] );
			$_fvals	= array();
			$_limit	= 30;
			$_c		= 0;
			
			if ( is_array( $_vals ) )
			{
				foreach( $_vals as $_v )
				{
					if ( strlen( $_v ) >= 3 AND ! in_array( $_v, array_values( $_fvals ) ) AND ! in_array( $_v, $_sw ) )
					{
						$_fvals[] = $_v;
					}
					
					if ( $_c >= $_limit )
					{
						break;
					}
					
					$_c++;
				}
			}

			$this->pages->setMetaKeywords( implode( ',', $_fvals ) );
		}
		
		if( $record['record_meta_description'] )
		{
			$this->pages->setMetaDescription( $record['record_meta_description'] );
		}
		else
		{
			$text	= $record[ $this->database['database_field_title'] . '_value' ];
			$text	.= "\n " . $record[ $this->database['database_field_content'] . '_value' ];
			$text	= preg_replace( "/\<p class='citation'\>.+?\<\/p\>/ims", '', $text );
			
			$this->pages->setMetaDescription( IPSText::truncate( strip_tags($text), 247 ) );
		}
		
		$this->pages->setCanonical( $this->getRecordUrl( $record ) );

		//-----------------------------------------
		// Comments class
		//-----------------------------------------
		
		if( $this->database['database_comments'] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
			$this->_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $this->database, 'category' => $this->categories->categories[ $record['category_id'] ], 'record' => $record ) );
			
			$comments	= array(
								'html'  => $this->_comments->fetchFormatted( $record, array( 'offset' => intval( $this->request['st'] ) ) ),
								'count' => $this->_comments->count( $record ),
								);
  		}
  		else
  		{
  			$comments	= array( 'count' => 0 );
  		}

		//-----------------------------------------
		// Get contributer/revision data
		//-----------------------------------------
		
		$record['contributers']		= array();
		
		if( $this->database['database_revisions'] )
		{
			$contributors	= array();
			$contributions	= array();
			
			$this->DB->build( array( 'select' => 'revision_date, revision_member_id', 'from' => 'ccs_database_revisions', 'where' => 'revision_database_id=' . $this->database['database_id'] . ' AND revision_record_id=' . $record['primary_id_field'] . ' AND revision_member_id <> ' . $record['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$contributions[ $r['revision_member_id'] ]	= ( isset($contributions[ $r['revision_member_id'] ]) AND $contributions[ $r['revision_member_id'] ] > $r['revision_date'] ) ? $contributions[ $r['revision_member_id'] ] : $r['revision_date'];
				$contributors[ $r['revision_member_id'] ]	= $r['revision_member_id'];
			}
			
			if( count($contributors) )
			{
				$members	= IPSMember::load( $contributors );
				
				foreach( $contributions as $mid => $date )
				{
					$member	= IPSMember::buildDisplayData( $members[ $mid ] );
					
					$contributions[ $mid ]	= array( 'date' => $date, 'member' => $member );
				}
			}
			
			$record['contributers']	= $contributions;
		}

		//-----------------------------------------
		// Update views count
		//-----------------------------------------
		
		$this->DB->update( $this->database['database_database'], "record_views=record_views+1", "primary_id_field={$record['primary_id_field']}", true, true );
		
		//-----------------------------------------
		// Hide fields
		//-----------------------------------------
		
		$showFields	= array();
		
		foreach( $this->fields as $field )
		{
			if( $field['field_display_display'] )
			{
				$showFields[]	= $field;
			}
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'ccs', $this->database['database_database'].'_records' );

		$template	= 'template_' . $this->database['template_key'];
		$_content	.= $this->registry->output->getTemplate('ccs')->$template( array( 'database' => $this->database, 'fields' => $showFields, 'record' => $record, 'comments' => $comments, 'category' => $category, 'follow_data' => $this->_like->render( 'summary', $record['primary_id_field'] ) ) );
		
		return $_content;
	}
	
	/**
	 * Parse attachments
	 *
	 * @access	public
	 * @param	array 	Database data
	 * @param	array	Field data (all fields)
	 * @param	array 	Record data
	 * @return	array 	Reparsed records
	 */
	public function parseAttachments( $database, $fields, $record )
	{
		//-----------------------------------------
		// Verify we have an attachments field,
		// otherwise there are no uploads to parse
		//-----------------------------------------
		
		$_hasAttachments	= false;
		
		foreach( $fields as $k => $v )
		{
			if( $v['field_type'] == 'attachments' )
			{
				$_hasAttachments	= true;
				break;
			}
		}
		
		if( !$_hasAttachments )
		{
			return $record;
		}

		//-----------------------------------------
		// Get attachment map ids
		//-----------------------------------------
		
		$mapIds	= array();
		
		if( isset($this->caches['ccs_attachments_data'][ $record['primary_id_field'] ]) )
		{
			$mapIds	= ( is_array($this->caches['ccs_attachments_data'][ $record['primary_id_field'] ]) AND count($this->caches['ccs_attachments_data'][ $record['primary_id_field'] ]) ) ? array_keys( $this->caches['ccs_attachments_data'][ $record['primary_id_field'] ] ) : array();
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_attachments_map', 'where' => "map_database_id={$database['database_id']} AND map_record_id={$record['primary_id_field']}" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$mapIds[]	= $r['map_id'];
			}
		}
		
		if( !count($mapIds) )
		{
			return $record;
		}
			
		//-----------------------------------------
		// Get attachments library
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
		
		if ( ! is_object( $this->class_attach ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$class_attach	= new $classToLoad( $this->registry );
		}
	
		$class_attach->type  = 'ccs';
		$class_attach->init();
		
		//-----------------------------------------
		// Loop over each field in the record
		//-----------------------------------------

		foreach( $record as $k => $v )
		{
			//-----------------------------------------
			// If it's a custom field...
			//-----------------------------------------
			
			if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
			{
				//-----------------------------------------
				// If the field type is attachments, just skip
				//-----------------------------------------
				
				if( $fields[ $matches[1] ]['field_type'] == 'attachments' )
				{
					continue;
				}

				$value	= $record[ $k . '_value' ];
				
				//-----------------------------------------
				// Do we have a tag in this field?
				//-----------------------------------------
				
				if( !stristr( $value, "[attachment=" ) )
				{
					continue;
				}

				//-----------------------------------------
				// Get HTML
				//-----------------------------------------

				$attachHTML = $class_attach->renderAttachments( $value, $mapIds, 'ccs_global' );

				/* Now parse back in the rendered posts */
				if( is_array($attachHTML) AND count($attachHTML) )
				{
					foreach( $attachHTML as $id => $data )
					{
						if( !$data['html'] )
						{
							continue;
						}
						
						/* Get rid of any lingering attachment tags */
						if ( stristr( $data['html'], "[attachment=" ) )
						{
							$data['html'] = IPSText::stripAttachTag( $data['html'] );
						}

						$record[ $k . '_value' ]				= $data['html'];
						$record[ $k . '_value_attachmentHtml']	= $data['attachmentHtml'];
						
						$record[ $fields[ $matches[1] ]['field_key'] ]						= $data['html'];
						$record[ $fields[ $matches[1] ]['field_key'] . '_attachmentHtml']	= $data['attachmentHtml'];
					}
				}
			}
		}

		return $record;
	}
	
	/**
	 * Get/check moderator permissions
	 *
	 * @access	public
	 * @param	array 		Database info
	 * @return	array 		Modified database info array
	 */
	public function checkModerator( $database=array() )
	{
		//-----------------------------------------
		// Supermods can moderate
		//-----------------------------------------
		
		if( $this->memberData['g_is_supmod'] )
		{
			$database['_can_add']			= true;
			$database['moderate_edit']		= 1;
			$database['moderate_delete']	= 1;
			$database['moderate_editc']		= 1;
			$database['moderate_deletec']	= 1;
			$database['moderate_lock']		= 1;
			$database['moderate_pin']		= 1;
			$database['moderate_unlock']	= 1;
			$database['moderate_approve']	= 1;
			$database['moderate_approvec']	= 1;
			$database['moderate_restorer']	= 1;
			$database['moderate_extras']	= 1;
			
			return $database;
		}
		
		//-----------------------------------------
		// Cache, in case we get called more than once
		//-----------------------------------------
		
		static $moderators	= array();
		static $modChecked	= false;
		
		if( !$modChecked )
		{
			$modChecked	= true;
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id=' . $database['database_id'] ) );
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
					$database['_can_add']			= $_moderator['moderator_add_record'] ? true : $database['_can_add'];
					$database['moderate_edit']		= $_moderator['moderator_edit_record'];
					$database['moderate_delete']	= $_moderator['moderator_delete_record'];
					$database['moderate_editc']		= $_moderator['moderator_edit_comment'];
					$database['moderate_deletec']	= $_moderator['moderator_delete_comment'];
					$database['moderate_lock']		= $_moderator['moderator_lock_record'];
					$database['moderate_unlock']	= $_moderator['moderator_unlock_record'];
					$database['moderate_approve']	= $_moderator['moderator_approve_record'];
					$database['moderate_approvec']	= $_moderator['moderator_approve_comment'];
					$database['moderate_pin']		= $_moderator['moderator_pin_record'];
					$database['moderate_restorer']	= $_moderator['moderator_restore_revision'];
					$database['moderate_extras']	= $_moderator['moderator_extras'];
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
					$database['_can_add']			= $_moderator['moderator_add_record'] ? true : $database['_can_add'];
					$database['moderate_edit']		= $_moderator['moderator_edit_record'];
					$database['moderate_delete']	= $_moderator['moderator_delete_record'];
					$database['moderate_editc']		= $_moderator['moderator_edit_comment'];
					$database['moderate_deletec']	= $_moderator['moderator_delete_comment'];
					$database['moderate_lock']		= $_moderator['moderator_lock_record'];
					$database['moderate_unlock']	= $_moderator['moderator_unlock_record'];
					$database['moderate_approve']	= $_moderator['moderator_approve_record'];
					$database['moderate_approvec']	= $_moderator['moderator_approve_comment'];
					$database['moderate_pin']		= $_moderator['moderator_pin_record'];
					$database['moderate_restorer']	= $_moderator['moderator_restore_revision'];
					$database['moderate_extras']	= $_moderator['moderator_extras'];
				}
			}
		}
		
		return $database;
	}

	/**
	 * Send a notification to members watching a category
	 *
	 * @access	public
	 * @param	array 		Database data
	 * @param	array 		Category data
	 * @param	array 		Record data
	 * @return	@e void
	 */
	public function sendRecordNotification( $database, $category, $record )
	{
		if( !$category['category_id'] )
		{
			return;
		}
		
		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		//-----------------------------------------
		// Format title
		//-----------------------------------------

		$this->fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields	= array();

		if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
			}
		}
		
		$_title	= $this->fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ], $record, $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] );
		$_url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $record );

		//-----------------------------------------
		// Send
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'ccs', $database['database_database'].'_categories' );
		$this->_like->sendNotifications( $category['category_id'], array( 'immediate', 'offline' ), array(
																								'notification_key'		=> 'ccs_notifications',
																								'notification_url'		=> $_url,
																								'email_template'		=> 'ccs_record_notification',
																								'email_subject'			=> sprintf(
																																	$this->lang->words['notify_record_title'], 
																																	$this->registry->output->buildSEOUrl( 'showuser=' . $record['member_id'], 'public', $record['poster_seo_name'], 'showuser' ),
																																	$record['poster_name'],
																																	$_url,
																																	$_title
																																	),
																								'build_message_array'	=> array(
																															'NAME'		=> '-member:members_display_name-',
																															'POSTER'	=> $record['poster_name'],
																															'LINK'		=> $_url,
																															'TITLE'		=> $_title,
																															'CATEGORY'	=> $category['category_name']
																																)
																						) 		);
	}

	/**
	 * Send notifications to moderators that a record is pending approval
	 *
	 * @access	public
	 * @param	array 		Database data
	 * @param	array 		Category data
	 * @param	array 		Record data
	 * @return	@e void
	 */
	public function sendRecordPendingNotification( $database, $category, $record )
	{
		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		//-----------------------------------------
		// Format title
		//-----------------------------------------

		$this->fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields	= array();

		if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
			}
		}
		
		$_title	= $this->fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ], $record, $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] );
		$_url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $record );

		//-----------------------------------------
		// Get people to notify
		//-----------------------------------------

		$moderatorGroups	= array();
		$moderators			= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id=' . $database['database_id'] . " AND moderator_type='group' AND moderator_approve_record=1" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$moderatorGroups[]	= $r['moderator_type_id'];
		}

		foreach( $this->caches['group_cache'] as $cache )
		{
			if( $cache['g_is_supmod'] )
			{
				$moderatorGroups[]	= $cache['g_id'];
			}
		}

		$this->DB->build( array( 'select'	=> 'c.*',
								 'from'		=> array( 'ccs_database_moderators' => 'c' ),
								 'where'	=> 'c.moderator_database_id=' . $database['database_id'] . " AND c.moderator_type='member' AND c.moderator_approve_record=1",
								 'add_join'	=> array(
								 					array(
								 						'select'	=> 'm.*',
								 						'from'		=> array( 'members' => 'm' ),
								 						'where'		=> 'm.member_id=c.moderator_type_id',
								 						)
								 					)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$moderators[ $r['member_id'] ]	= $r;
		}

		if( count($moderatorGroups) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'members', 'where' => 'member_group_id IN(' . implode( ',', $moderatorGroups ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$moderators[]	= $r;
			}
		}

		if( !count($moderators) )
		{
			return;
		}

		//-----------------------------------------
		// Send
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary	= new $classToLoad( $this->registry );

		foreach( $moderators as $row )
		{
			/* We don't need to notify ourself */
			if( $this->memberData['member_id'] == $row['member_id'] )
			{
				continue;
			}
			
			$buildMessage = array(
								'NAME'		=> $row['members_display_name'],
								'POSTER'	=> $record['poster_name'],
								'LINK'		=> $_url,
								'TITLE'		=> $_title
								);

			$emailer	= IPSText::getTextClass('email');

			$emailer->setPlainTextTemplate( IPSText::getTextClass('email')->getTemplate( 'ccs_record_pend_notification', $row['language'] ) );

			if( method_exists( $emailer, 'buildPlainTextContent' ) )
			{
				IPSText::getTextClass('email')->buildPlainTextContent( $buildMessage );
			}
			else
			{
				IPSText::getTextClass('email')->buildMessage( $buildMessage );
			}
			
			$notifyLibrary->setMember( $row );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'ccs_approval_notifications' );
			$notifyLibrary->setNotificationUrl( $_url );
			$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->getPlainTextContent() );
			$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['subject__ccs_record_pend_notification'], $_url, $_title ) );

			if( method_exists( $notifyLibrary, 'setNotificationHtml' ) )
			{
				$notifyLibrary->setNotificationHtml( IPSText::getTextClass('email')->buildHtmlContent( $buildMessage ) );
			}

			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){ }
		}
	}
	
	/**
	 * Check permalink
	 *
	 * @access	public
	 * @param	string	Correct URL for page
	 * @return	@e void	(May redirect to correct URL)
	 */
	public function checkPermalink( $url )
	{
		if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
		{
			return;
		}

		$databaseString	= $this->registry->ccsFunctions->getDatabaseFurlString();

		if( ( !$databaseString AND strpos( $_SERVER['REQUEST_URI'], 'record=' ) === false AND ( strpos( $_SERVER['REQUEST_URI'], 'category=' ) === false ) 
				AND strpos( $_SERVER['REQUEST_URI'], 'do=' ) !== false ) )
		{
			return;
		}
		
		$requestedUrl	= rtrim( $this->database['base_link'], '?' ) . '/' . DATABASE_FURL_MARKER . '/' . $databaseString;
		$requestedUrl	= str_replace( '//' . DATABASE_FURL_MARKER . '/', '/' . DATABASE_FURL_MARKER . '/', $requestedUrl );
		$url			= rtrim( $url, '/?' );
		$_len			= strlen(DATABASE_FURL_MARKER);

		if( substr( $requestedUrl, 0 - ( $_len + 1 ) ) == DATABASE_FURL_MARKER . '/' )
		{
			$requestedUrl	= substr( $requestedUrl, 0, 0 - ( $_len + 2 ) );
		}

		if( substr( $url, 0 - ( $_len + 1 ) ) == '/' . DATABASE_FURL_MARKER )
		{
			$url			= substr( $url, 0, 0 - ( $_len + 1 ) );
		}

		// Here to easily debug bad redirects
		// print $requestedUrl.'<br>'.$url;
		// exit;

		if( strtolower($requestedUrl) != strtolower($url) )
		{
			//-----------------------------------------
			// Check for a stored URL...
			//-----------------------------------------
			
			$_old	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_slug_memory', 'where' => "memory_url='" . $this->DB->addSlashes( rtrim( str_replace( "'", "&#039;", $requestedUrl ), '/?' ) ) . "'" ) );
			
			if( $_old['memory_id'] )
			{
				if( $_old['memory_type'] == 'category' )
				{
					$url	= rtrim( $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], $_old['memory_type_id'] ), '/?' );

					if( substr( $url, 0 - ( $_len + 1 ) ) == '/' . DATABASE_FURL_MARKER )
					{
						$url			= substr( $url, 0, 0 - ( $_len + 1 ) );
					}
				}
				else if( $_old['memory_type'] == 'record' )
				{
					$url	= rtrim( $this->registry->ccsFunctions->returnDatabaseUrl( $_old['memory_type_id_2'], 0, $_old['memory_type_id'] ), '/?' );

					if( substr( $url, 0 - ( $_len + 1 ) ) == '/' . DATABASE_FURL_MARKER )
					{
						$url			= substr( $url, 0, 0 - ( $_len + 1 ) );
					}
				}
			}

			if( $url )
			{
				if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
				{
					header("HTTP/1.0 301 Moved Permanently" );
				}
				else
				{
					header("HTTP/1.1 301 Moved Permanently" );
				}			

				header( "Location: " . str_replace( '&amp;', '&', $url ) );
				exit;
			}
		}
	}
	
	/**
	 * Fix the incoming data into a normalized version for furls
	 *
	 * @access	public
	 * @param	object	Category library
	 * @return	@e void
	 */
	public function fixDatabaseFurls()
	{
		//-----------------------------------------
		// Got a string to parse?
		//-----------------------------------------
		
		$databaseString	= $this->registry->ccsFunctions->getDatabaseFurlString();

		if( !$databaseString )
		{
			return;
		}

		//-----------------------------------------
		// Break string up
		//-----------------------------------------
		
		$_bits			= explode( '/', $databaseString );

		if( count($_bits) )
		{
			$_last	= array_pop($_bits);
			
			//-----------------------------------------
			// Does last element appear to be a record?
			//-----------------------------------------
			
			if( preg_match( "/.*?\-r(\d+)($|#|&|\?)/", $_last, $matches ) )
			{
				$this->request['record']	= $matches[1];
			}
			else
			{
				$_parent	= count($_bits) ? array_pop($_bits) : '';

				//-----------------------------------------
				// Not sure?  Check categories first, since they
				//	are cached.
				//-----------------------------------------
				
				foreach( $this->categories->categories as $id => $data )
				{
					if( $_last == $data['category_furl_name'] )
					{
						//-----------------------------------------
						// Make sure parent is correct...
						//-----------------------------------------

						if( ( !$_parent AND $data['category_parent_id'] ) OR ( $_parent AND !$data['category_parent_id'] ) )
						{
							continue;
						}
						else
						{
							foreach( $this->categories->categories as $_data )
							{
								if( $_parent == $_data['category_furl_name'] )
								{
									$_theParent	= $_data;
									
									break;
								}
							}
							
							if( $_theParent['category_id'] != $data['category_parent_id'] )
							{
								continue;
							}
						}

						$this->request['category']	= $id;
						
						break;
					}
				}
			}
			
			if( ! $this->request['record'] AND ! $this->request['category'] )
			{
				//-----------------------------------------
				// No category found, possibly a static record furl
				//-----------------------------------------
				
				$_approval	= array( 1 );

				if( $this->database['moderate_approve'] )
				{
					$_approval[]	= 0;
				}

				if( $this->database['moderate_delete'] )
				{
					$_approval[]	= -1;
				}
			
				$_approved	= "r.record_approved IN(" . implode( ',', $_approval ) . ") AND ";
				
				$_joins		= array(
									array(
										'select'	=> 'c.*',
										'from'		=> array( 'ccs_database_categories' => 'c' ),
										'where'		=> 'c.category_id=r.category_id',
										'type'		=> 'left',
										),
									$this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => $this->DB->buildCoalesce( array( 'c.category_id', '0' ) ), 'item_app_key_2' => $this->database['database_id'] ), 'ccs' ),
									$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) )
									);

				/* Reputation system enabled? */
				if( $this->settings['reputation_enabled'] )
				{
					/* Add the join to figure out if the user has already rated the post */
					$_joins[] = $this->registry->repCache->getUserHasRatedJoin( 'record_id_' . $this->database['database_id'], 'r.primary_id_field', 'ccs' );
					
					/* Add the join to figure out the total ratings for each post */
					if( $this->settings['reputation_show_content'] )
					{
						$_joins[] = $this->registry->repCache->getTotalRatingJoin( 'record_id_' . $this->database['database_id'], 'r.primary_id_field', 'ccs' );
					}
				}
				
				$record		= $this->DB->buildAndFetch( array( 
															'select'	=> 'r.*', 
															'from'		=> array( $this->database['database_database'] => 'r' ), 
															'where'		=> $_approved . "r.record_static_furl='" . $this->DB->addSlashes( $_last ) . "'",
															'add_join'	=> $_joins
													)		);
	
				if( $record['primary_id_field'] )
				{
					$record			= $this->registry->classItemMarking->setFromSqlJoin( $record, 'ccs' );

					if ( ! empty( $record['tag_cache_key'] ) )
					{
						if( $record['category_id'] AND $record['category_tags_override'] )
						{
							if( $record['category_tags_enabled'] )
							{
								$record['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $record );
							}
						}
						else
						{
							if( $this->database['database_tags_enabled'] )
							{
								$record['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $record );
							}
						}
					}

					$this->_record	= $record;
					
					$this->request['record']	= $record['primary_id_field'];
				}
			}
		}
		
		//-----------------------------------------
		// Update session...
		//-----------------------------------------

		$this->member->sessionClass()->addQueryKey( 'location_1_type', 'database' );
		$this->member->sessionClass()->addQueryKey( 'location_1_id', $this->database['database_id'] );
		$this->member->sessionClass()->addQueryKey( 'location_2_id', $this->request['record'] );
		$this->member->sessionClass()->addQueryKey( 'location_3_id', $this->request['category'] );
		$this->member->sessionClass()->addQueryKey( 'location_3_type', $this->request['record'] ? 'record' : ( $this->request['category'] ? 'category' : '' ) );
	}
	
	/**
	 * Get record url (handles furls)
	 *
	 * @access	public
	 * @param	array 	Record data
	 * @param	bool	Prepare URL to add more params
	 * @return	string	Record url
	 */
	public function getRecordUrl( $record, $more=false )
	{
		if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
		{
			return $this->database['base_link'] . 'record=' . $record['primary_id_field'] . ( $more ? '&amp;' : '' );
		}
		else
		{
			if( $record['category_id'] )
			{
				$_url	= $this->categories->getCategoryUrl( $this->database['base_link'], $record['category_id'] );
			}
			else
			{
				$_url	= substr( $this->database['base_link'], 0, -1 ) . '/' . DATABASE_FURL_MARKER . '/';
				$_url	= str_replace( '//' . DATABASE_FURL_MARKER . '/', '/' . DATABASE_FURL_MARKER . '/', $_url );
			}

			$_value	= '';
			
			if( $record['record_static_furl'] )
			{
				$_url	.= $record['record_static_furl'];
			}
			else
			{
				if( $record['record_dynamic_furl'] )
				{
					$_url	.= $record['record_dynamic_furl'] . '-r' . $record['primary_id_field'];
				}
				else
				{
					if( $record[ $this->database['database_field_title'] . '_value' ] )
					{
						$_value	= $record[ $this->database['database_field_title'] . '_value' ];
					}
					else
					{
						$_value	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
					}
					
					$_seo	= IPSText::makeSeoTitle( $_value );
					$_url	.= $_seo . '-r' . $record['primary_id_field'];
					
					if( !$record['_skipUpdateDynamic'] )
					{
						$this->DB->update( $this->database['database_database'], array( 'record_dynamic_furl' => $_seo ), 'primary_id_field=' . $record['primary_id_field'], true );
					}
				}
			}
			
			return $_url . ( $more ? '?' : '' );
		}
	}
	
	/**
	 * Get the record
	 *
	 * @access	protected
	 * @return	array 		Record data
	 */
	protected function _getRecord()
	{
		if( $this->_record )
		{
			$record		= $this->_record;
		}
		else
		{
			$_approval	= array( 1 );

			if( $this->database['moderate_approve'] )
			{
				$_approval[]	= 0;
			}

			if( $this->database['moderate_delete'] )
			{
				$_approval[]	= -1;
			}
			
			$_approved	= "r.record_approved IN(" . implode( ',', $_approval ) . ") AND ";

			$_joins		= array(
								array(
									'select'	=> 'c.*',
									'from'		=> array( 'ccs_database_categories' => 'c' ),
									'where'		=> 'c.category_id=r.category_id',
									'type'		=> 'left',
									),
								$this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => $this->DB->buildCoalesce( array( 'c.category_id', '0' ) ), 'item_app_key_2' => $this->database['database_id'] ), 'ccs' ),
								$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->getCacheJoin( array( 'meta_id_field' => 'r.primary_id_field' ) )
								);

			/* Reputation system enabled? */
			if( $this->settings['reputation_enabled'] )
			{
				/* Add the join to figure out if the user has already rated the post */
				$_joins[] = $this->registry->repCache->getUserHasRatedJoin( 'record_id_' . $this->database['database_id'], 'r.primary_id_field', 'ccs' );
				
				/* Add the join to figure out the total ratings for each post */
				if( $this->settings['reputation_show_content'] )
				{
					$_joins[] = $this->registry->repCache->getTotalRatingJoin( 'record_id_' . $this->database['database_id'], 'r.primary_id_field', 'ccs' );
				}
			}
		
			$record		= $this->DB->buildAndFetch( array( 
														'select'	=> 'r.*', 
														'from'		=> array( $this->database['database_database'] => 'r' ), 
														'where'		=> $_approved . 'r.primary_id_field=' . intval($this->request['record']),
														'add_join'	=> $_joins
												)		);

			$record		= $this->registry->classItemMarking->setFromSqlJoin( $record, 'ccs' );

			if ( ! empty( $record['tag_cache_key'] ) )
			{
				if( $record['category_id'] AND $record['category_tags_override'] )
				{
					if( $record['category_tags_enabled'] )
					{
						$record['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $record );
					}
				}
				else
				{
					if( $this->database['database_tags_enabled'] )
					{
						$record['tags'] = $this->registry->getClass('ccsTags-' . $this->database['database_id'] )->formatCacheJoinData( $record );
					}
				}
			}
		}
		
		return $record;
	}
	
	/**
	 * Set the page title.  Abstracted as a new function to allow easy override via hooks. 
	 * Users can also define the following constants to force a specific format:
	 * 
	 * <code>
	 * define( 'CCS_PAGE_TITLE_HOME', '' );
	 * define( 'CCS_PAGE_TITLE_CAT', '' );
	 * define( 'CCS_PAGE_TITLE_RECORD', '' );
	 * define( 'CCS_PAGE_TITLE_LIST', '' );
	 * // Following variables will be replaced:
	 * // {page_name} = Page title (if not supplied, page name instead)
	 * // {database_name} = Database name
	 * // {category_name} = Category page title (if not supplied, category name) (only available for CCS_PAGE_TITLE_CAT and CCS_PAGE_TITLE_RECORD)
	 * // {record_name} = Record name (only available for CCS_PAGE_TITLE_RECORD)
	 * // {website_name} = Website name as configured in ACP
	 * // {board_name} = Board name as configured in ACP
	 * </code>
	 * 
	 * @param	array	Page title pieces (in order they should be displayed)
	 * @param	int		Area (used for constant lookup, 1=home, 2=cat, 3=record)
	 * @return	@e void
	 */
 	protected function _setPageTitle( $slugs=array(), $area=1 )
 	{
 		$title	= '';
 		
 		if( $area )
 		{
 			switch( $area )
 			{
 				case 1:
 					if( defined( 'CCS_PAGE_TITLE_HOME' ) )
 					{
 						$title	= CCS_PAGE_TITLE_HOME;
 					}
				break;
				
 				case 2:
 					if( defined( 'CCS_PAGE_TITLE_CAT' ) )
 					{
 						$title	= CCS_PAGE_TITLE_CAT;
 					}
				break;
				
 				case 3:
 					if( defined( 'CCS_PAGE_TITLE_RECORD' ) )
 					{
 						$title	= CCS_PAGE_TITLE_RECORD;
 					}
				break;

 				case 4:
 					if( defined( 'CCS_PAGE_TITLE_LIST' ) )
 					{
 						$title	= CCS_PAGE_TITLE_LIST;
 					}
				break;
 			}
 		}
 		
		//-----------------------------------------
		// Did we get a title from the constant?
		//-----------------------------------------

		if( $title )
		{
			$title	= str_replace( '{board_name}', 		$this->settings['board_name'], 		$title );
			$title	= str_replace( '{website_name}', 	$this->settings['home_name'], 		$title );
			$title	= str_replace( '{page_name}', 		$this->page['page_title'] ? $this->page['page_title'] : $this->page['page_name'], 			$title );
			$title	= str_replace( '{database_name}', 	$this->database['database_name'], 	$title );
			$title	= str_replace( '{category_name}', 	$slugs['category_name'], 			$title );
			$title	= str_replace( '{record_name}', 	$slugs['record_name'], 				$title );
		}
		else
		{
			$title	= count($slugs) ? implode( ' - ', $slugs ) . ' - ' : '';
			$title	.= ( $this->page['page_title'] ? $this->page['page_title'] : $this->page['page_name'] ) . ' - ' . ( $this->settings['home_name'] ? $this->settings['home_name'] : $this->settings['board_name'] );
		}

 		$this->registry->output->setTitle( $title );
 	}
 	
 	/**
 	 * List revisions for this record
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _showRevisions()
	{
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();

		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );
		
		//-----------------------------------------
		// Get all revisions for this record
		//-----------------------------------------
		
		$revisions	= array();
		
		$this->DB->build( array( 
								'select'	=> 'r.*', 
								'from'		=> array( 'ccs_database_revisions' => 'r' ), 
								'where'		=> 'r.revision_database_id=' . $this->database['database_id'] . ' AND r.revision_record_id=' . $record['primary_id_field'], 
								'order'		=> 'r.revision_date DESC',
								'add_join'	=> array(
												array(
													'select'	=> 'm.*',
													'from'		=> array( 'members' => 'm' ),
													'where'		=> 'm.member_id=r.revision_member_id',
													'type'		=> 'left'
													)
												)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_revision_data']	= unserialize($r['revision_data']);

			if( !$r['member_id'] )
			{
				$r	= array_merge( IPSMember::setUpGuest(), $r );
			}

			$revisions[]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		return $this->registry->output->getTemplate('ccs_global')->manageRevisions( $this->database, $this->fields, $record, $revisions );
	}

 	/**
 	 * Delete a revision
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _deleteRevision()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.8', null, null, 403 );
		}

		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();
		
		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );
		
		//-----------------------------------------
		// Check revision
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_delete'], '10CCS89.88', false, null, 404 );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_delete'], '10CCS89.87', false, null, 404 );
		}
		
		//-----------------------------------------
		// Get library and delete
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
		$revisions		= new $classToLoad( $this->registry );
		$revisions->deleteRevision( $id );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['revision_deleted_success'], $record['url'] . 'do=revisions' );
	}

 	/**
 	 * Restore a revision
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _restoreRevision()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.9', null, null, 403 );
		}

		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();
		
		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );
		
		//-----------------------------------------
		// Check revision
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_restore'], '10CCS89.86', false, null, 404 );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_restore'], '10CCS89.85', false, null, 404 );
		}
		
		//-----------------------------------------
		// Get library and restore
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/databases/revisions.php', 'ccs_database_revisions', 'ccs' );
		$revisions		= new $classToLoad( $this->registry );
		$revisions->restoreRevision( $this->database, $id );
		
		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['revision_restored_success'], $record['url'] . 'do=revisions' );
	}

 	/**
 	 * Compare two revisions
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _compareRevisions()
	{
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();
		
		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_compare'], '10CCS89.84', false, null, 404 );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_compare'], '10CCS89.68', false, null, 404 );
		}
		
		$revisionData	= unserialize($revision['revision_data']);

		//-----------------------------------------
		// Loop through all the fields and run through
		// plugin functionality to determine if there are
		// any differences
		//-----------------------------------------
		
		$differences	= array();
		
		foreach( $this->fields as $_field )
		{
			$differences[ $_field['field_id'] ]	= $this->fieldsClass->compareRevision( $_field, $record['field_' . $_field['field_id'] ], $revisionData['field_' . $_field['field_id'] ] );
		}
		
		return $this->registry->output->getTemplate('ccs_global')->compareRevisions( $this->database, $this->fields, $record, $differences );
	}

 	/**
 	 * Show form to edit a revision
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _editRevision()
	{
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$record	= $this->_getRecord();
		
		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['revision']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_edit'], '10CCS89.67', false, null, 404 );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_edit'], '10CCS89.66', false, null, 404 );
		}
		
		//-----------------------------------------
		// Sort for the form
		//-----------------------------------------
		
		$revisionData	= unserialize($revision['revision_data']);
		$revisionData['primary_id_field']	= $id;

		//-----------------------------------------
		// Categories
		//-----------------------------------------

		$this->request['category']	= $revisionData['category_id'];
		$categories					= $this->categories->getSelectMenu( array(), 'add' );

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, $revisionData, $categories, '', false, true );
	}

 	/**
 	 * Save edits to a revision
 	 * 
 	 * @return	string
 	 * @todo	Whole function
 	 */
	protected function _saveRevision()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.10', null, null, 403 );
		}

		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$id	= intval($this->request['record']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_edit'], '10CCS89.63', false, null, 404 );
		}
		
		$revision	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_revisions', 'where' => 'revision_id=' . $id ) );
		
		if( !$revision['revision_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_revision_to_edit'], '10CCS89.62', false, null, 404 );
		}
		
		//-----------------------------------------
		// Get record
		//-----------------------------------------
		
		$this->request['record']	= $revision['revision_record_id'];
		$record	= $this->_getRecord();

		$record['title']	= $this->fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $record, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
		$record['url']		= $this->getRecordUrl( $record, true );

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->_checkRecordPermissions();
		$this->_checkRevisionPermissions( $record );

		//-----------------------------------------
		// Process the input
		//-----------------------------------------
		
		$_save	= array();

		foreach( $this->fields as $field )
		{
			$_save['field_' . $field['field_id'] ]	= $this->fieldsClass->processInput( $field );
			
			if( $error = $this->fieldsClass->getError() )
			{
				return $this->registry->output->getTemplate('ccs_global')->recordForm( 'edit', $this->database, $this->fields, $this->fieldsClass, $revisionData, $categories, $error, false, true );
			}
		}
		
		foreach( $this->fields as $field )
		{
			$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
		}

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		$this->DB->update( 'ccs_database_revisions', array( 'revision_data' => serialize($_save) ), 'revision_id=' . $id );

		//-----------------------------------------
		// Send back to listing
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['revision_edit_success'], $record['url'] . 'do=revisions' );
	}
	
	/**
	 * Check permissions to access revision
	 * 
	 * @param	array	Record data
	 * @return	@e void
	 */
 	protected function _checkRevisionPermissions( $record )
 	{
 		if( !$this->database['moderate_restorer'] )
 		{
 			$this->registry->output->showError( $this->lang->words['no_revision_access'], '10CCS87.9', false, null, 403 );
 		}
 		
		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS87.1', false, null, 404 );
		}
		
		if( $record['category_id'] )
		{
			$category	= $this->categories->categories[ $record['category_id'] ];
			
			$category['category_link']	= $this->categories->getCategoryUrl( $this->database['base_link'], $category['category_id'] );
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['couldnot_find_record'], '10CCS88.1', false, null, 404 );
			}

			if ( $category['category_has_perms'] AND $this->registry->permissions->check( 'show', $category ) != TRUE )
			{
				$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS89.1', false, null, 404 );
			}
			
			$_parents	= $this->categories->getParents( $record['category_id'] );
			
			if( count($_parents) )
			{
				foreach( $_parents as $_parent )
				{
					if ( !$this->categories->categories[ $_parent ]['category_id'] OR ( $this->categories->categories[ $_parent ]['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->categories->categories[ $_parent ] ) != TRUE ) )
					{
						$this->registry->output->showError( $this->lang->words['no_view_perm_rec'], '10CCS89.11', false, null, 403 );
					}
				}
			}
		}

		//-----------------------------------------
		// Breadcrumbs and page title
		//-----------------------------------------

		$crumbies	= array();
		
		if( $record['category_id'] )
		{
			$crumbies	= $this->categories->getBreadcrumbs( $record['category_id'], $this->database['base_link'] );
	
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
			else
			{
				array_unshift( $crumbies, array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) ) );
			}
		}
		else
		{
			$crumbies[]	= array( 0 => $this->page['page_name'], 1 => $this->registry->ccsFunctions->returnPageUrl( $this->page ) );
		}

		array_push( $crumbies, array( 0 => $this->lang->words['navigation_prefix'] . ' ' . $record['title'], 1 => $this->getRecordUrl( $record ) ) );
		array_push( $crumbies, array( 0 => $this->lang->words['revisions_navigation'] ) );
		
		$this->pages->addNavigation( $crumbies, $this->database['database_key'] );
		
		if( $category['category_id'] )
		{
			$this->_setPageTitle( array( 'record_name' => $this->lang->words['revisions_pagetitle_pre'] . ' ' . $record['title'], 'category_name' => $category['category_page_title'] ? $category['category_page_title'] : $category['category_name'], 'database_name' => $this->database['database_name'] ), 3 );
		}
		else
		{
			$this->_setPageTitle( array( 'record_name' => $this->lang->words['revisions_pagetitle_pre'] . ' ' . $record['title'], 'database_name' => $this->database['database_name'] ), 3 );
		}
 	}
}