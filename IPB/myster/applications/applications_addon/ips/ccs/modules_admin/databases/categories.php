<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS database category management
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_databases_categories extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	public
	 * @var		string			URL shortcut
	 */
	public $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	public
	 * @var		string			JS URL shortcut
	 */
	public $form_code_js;
	
	/**
	 * Skin object
	 *
	 * @access	public
	 * @var		object			Skin templates
	 */	
	public $html;

	/**
	 * Database info
	 *
	 * @access	public
	 * @var		array 			DB info
	 */	
	public $database			= array();

	/**
	 * Category lib
	 *
	 * @access	public
	 * @var		object
	 */
	public $categories;
	
	/**
	 * Current category (used by articles)
	 *
	 * @access	public
	 * @var		array
	 */
	public $_category			= array();

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_database_categories' );
		
		//-----------------------------------------
		// Need 'ID'
		//-----------------------------------------
		
		$_id	= intval($this->request['id']);
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_cats'], '11CCS27' );
		}
		
		$this->database	= $this->DB->buildAndFetch( array(
															'select'	=> 'd.*',
															'from'		=> array( 'ccs_databases' => 'd' ),
															'where'		=> 'd.database_id=' . $_id,
															'add_join'	=> array(
																				array(
																					'select'	=> 'i.*',
																					'from'		=> array( 'permission_index' => 'i' ),
																					'where'		=> "i.app='ccs' AND i.perm_type='databases' AND i.perm_type_id=d.database_id",
																					'type'		=> 'left',
																					),
																				),
													)		);
		
		if( !$this->database['database_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_db_id_cats'], '11CCS28' );
		}
		
		//-----------------------------------------
		// Category lib
		//-----------------------------------------
		
		$this->categories	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database, false );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=databases&amp;section=categories&amp;id=' . $this->request['id'];
		$this->form_code_js	= $this->html->form_code_js	= 'module=databases&section=categories&id=' . $this->request['id'];

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $_id );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );

		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code, $this->lang->words['dbcategoriess_title'] . ' ' . $this->database['database_name'] );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->_listCategories();
			break;
			
			case 'add':
			case 'edit':
				$this->_categoryForm( $this->request['do'] );
			break;
			
			case 'doAdd':
				$this->_categorySave( 'add' );
			break;
			
			case 'doEdit':
				$this->_categorySave( 'edit' );
			break;

			case 'delete':
				$this->_categoryDelete();
			break;
			
			case 'reorder':
				$this->_categoryReorder();
			break;
			
			case 'recache':
				$this->_categoryRecache();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Recache categories
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _categoryRecache()
	{
		$catid	= $this->request['category'] ? intval($this->request['category']) : 0;
		
		$this->categories->recache( $catid );
		
		$this->registry->output->setMessage( $this->lang->words['cat_recached_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Reorders categories
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _categoryReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax			= new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['category']) AND count($this->request['category']) )
 		{
 			foreach( $this->request['category'] as $this_id )
 			{
 				if( !$this_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_database_categories', array( 'category_position' => $position ), 'category_id=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcatreorder'], $this->database['database_name'] ) );

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Delete a category
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _categoryDelete()
	{
		$_id		= intval($this->request['category']);
		$_children	= 0;
		
		if( !$_id )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_cat_del'], '11CCS29' );
		}
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_categories', 'where' => 'category_database_id=' . $this->database['database_id'] ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[ $r['category_id'] ]	= $r;

			if( $r['category_parent_id'] == $_id )
			{
				$_children++;
			}
		}
		
		if( !$categories[ $_id ]['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_id_for_cat_del'], '11CCS30' );
		}
		
		//-----------------------------------------
		// Got a category to move records to?
		// Got more than one category?
		// @see	http://community.invisionpower.com/tracker/issue-19169-category-deletion/
		//-----------------------------------------
		
		if( count($categories) > 1 AND ( $categories[ $_id ]['category_records'] OR $categories[ $_id ]['category_records_queued'] OR $_children ) )
		{
			if( !$this->request['move_to'] )
			{
				$this->request['category']	= 0;
				$_menu						= $this->categories->getSelectMenu( array( $_id ) );
				$this->request['category']	= $_id;
				
				$this->registry->output->extra_nav[] = array( '', $this->lang->words['button__notd'] );
				
				$this->registry->output->html .= $this->html->categoryDeleteForm( $this->database, $categories[ $_id ], $categories, $_menu );
				return;
			}
			else if( !$categories[ $this->request['move_to'] ]['category_id'] OR $this->request['move_to'] == $_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_cat_move'], '11CCS31' );
			}
		}
		else
		{
			$this->request['move_to']	= 0;
		}

		//-----------------------------------------
		// Delete category
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_database_categories', 'category_id=' . $_id );
		$this->DB->delete( 'ccs_slug_memory', "memory_type='category' AND memory_type_id=" . $_id );
		
		//-----------------------------------------
		// Update records to new cat
		//-----------------------------------------
		
		$this->DB->update( $this->database['database_database'], array( 'category_id' => intval($this->request['move_to']) ), 'category_id=' . $_id );
		$this->DB->update( 'ccs_database_categories', array( 'category_parent_id' => intval($this->request['move_to']) ), 'category_parent_id=' . $_id );

		//-----------------------------------------
		// Move tags
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $this->database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $this->database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $this->database['database_id'] ) );
		}

		$this->registry->getClass('ccsTags-' . $this->database['database_id'] )->moveTagsByParentId( $_id, intval($this->request['move_to']) );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcatdeleted'], $categories[ $_id ]['category_name'], $this->database['database_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['cat_deleted_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save a new field/edited category
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _categorySave( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------

		if( $type == 'edit' )
		{
			$_id	= intval($this->request['category']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_cat_edit'], '11CCS32' );
			}
			
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_categories', 'where' => 'category_id=' . $_id ) );
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_cat_edit'], '11CCS33' );
			}
		}
		else
		{
			$category	= array( 'category_id' => 0 );
		}
		
		$_save	= array(
						'category_database_id'		=> $this->database['database_id'],
						'category_name'				=> trim($this->request['category_name']),
						'category_page_title'		=> $this->request['page_name_as_title'] ? '' : $this->request['category_page_title'],
						'category_parent_id'		=> intval($this->request['category_parent_id']),
						'category_description'		=> trim($this->request['category_description']),
						'category_show_records'		=> intval($this->request['category_show_records']),
						'category_has_perms'		=> intval($this->request['category_has_perms']),
						'category_furl_name'		=> IPSText::makeSeoTitle( $this->request['category_furl_name'] ? $this->request['category_furl_name'] : $this->request['category_name'] ),
						'category_meta_keywords'	=> trim($this->request['category_meta_keywords']),
						'category_meta_description'	=> trim($this->request['category_meta_description']),
						'category_template'			=> intval($this->request['category_template']),
						'category_rss'				=> intval($this->request['category_rss']),
						'category_rss_exclude'		=> intval($this->request['category_rss_exclude']),
						'category_rss_cache'		=> null,
						'category_rss_cached'		=> 0,
						'category_tags_override'	=> intval($this->request['category_tags_override']),
						'category_tags_enabled'		=> intval($this->request['category_tags_enabled']),
						'category_tags_noprefixes'	=> intval($this->request['category_tags_noprefixes']),
						'category_tags_predefined'	=> IPSText::stripslashes($_POST['category_tags_predefined']),
						);

		//-----------------------------------------
		// @link	http://community.invisionpower.com/tracker/issue-23658-can-create-blank-category/
		//-----------------------------------------
		
		if( !$_save['category_name'] )
		{
			$this->registry->output->showError( $this->lang->words['mustsetcatname'], '11CCS150' );
		}
		
		//-----------------------------------------
		// Forum posting options
		//-----------------------------------------
		
		$_save['category_forum_override']	= intval($this->request['category_forum_override']);
		$_save['category_forum_record']		= intval($this->request['category_forum_record']);
		$_save['category_forum_comments']	= intval($this->request['category_forum_comments']);
		$_save['category_forum_delete']		= intval($this->request['category_forum_delete']);
		$_save['category_forum_forum']		= intval($this->request['category_forum_forum']);
		$_save['category_forum_prefix']		= $this->request['category_forum_prefix'];
		$_save['category_forum_suffix']		= $this->request['category_forum_suffix'];
			
		if( $_save['category_forum_override'] )
		{
			//-----------------------------------------
			// We check differently in case you are trying
			// to shut off forum posting in a category, but
			// it's on in the DB globally
			//-----------------------------------------
			
			if( $_save['category_forum_record'] )
			{
				if( !$_save['category_forum_forum'] )
				{
					$this->registry->output->setMessage( $this->lang->words['db_error__selectforum'] );
					$this->_categoryForm( $type );
					return;
				}
				
				$forum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'forums', 'where' => 'id=' . $_save['category_forum_forum'] ) );
				
				if( !$forum['id'] )
				{
					$this->registry->output->global_error = $this->lang->words['db_error__noforum'];
					$this->_categoryForm( $type );
					return;
				}
	
				if( !$forum['sub_can_post'] )
				{
					$this->registry->output->global_error = $this->lang->words['db_error__canpost'];
					$this->_categoryForm( $type );
					return;
				}
				
				if( $forum['redirect_on'] )
				{
					$this->registry->output->global_error = $this->lang->words['db_error__redirect'];
					$this->_categoryForm( $type );
					return;
				}
			}
		}

		//-----------------------------------------
		// Verify furl name is not in use...
		//-----------------------------------------
		
		$_check	= $this->DB->buildAndFetch( array( 'select' => 'category_id', 'from' => 'ccs_database_categories', 'where' => "category_database_id={$this->database['database_id']} AND category_furl_name='{$_save['category_furl_name']}' AND category_id <> {$category['category_id']} AND category_parent_id={$_save['category_parent_id']}" ) );
		
		if( $_check['category_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['cat_furl_name_used'];
			$this->_categoryForm( $type );
			return;
		}

		//-----------------------------------------
		// Reset database RSS cache in case we excluded cat
		//-----------------------------------------
		
		$this->DB->update( 'ccs_databases', array( 'database_rss_cache' => null, 'database_rss_cached' => 0 ), 'database_id=' . $this->database['database_id'] );
		
		$this->cache->rebuildCache( 'rss_output_cache', 'global' );
		
		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		if( $type == 'add' )
		{
			//-----------------------------------------
			// Set position
			//-----------------------------------------
			
			$max	=  $this->DB->buildAndFetch( array( 'select' => 'MAX(category_position) as position', 'from' => 'ccs_database_categories', 'where' => "category_database_id={$_save['category_database_id']}" ) );
			
			$_save['category_position']	= $max['position'] + 1;
			
			$this->DB->insert( 'ccs_database_categories', $_save );
			
			$id	= $this->DB->getInsertId();
			
			//-----------------------------------------
			// Permission matrix
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
	   		$permissions	= new $classToLoad( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $id, 'categories' );

			$this->registry->output->setMessage( $this->lang->words['cat_add_success'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcatadded'], $_save['category_name'], $this->database['database_name'] ) );
		}
		else
		{
			//-----------------------------------------
			// Double check category...
			// 1) Don't set parent as ourself
			//-----------------------------------------
			
			if( $_save['category_parent_id'] == $category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['invalid_category_id'], '11CCS34' );
			}
			
			//-----------------------------------------
			// 2) Don't set parent as a child
			//-----------------------------------------
			
			$children	= $this->categories->getChildren( $category['category_id'] );
			
			if( in_array( $_save['category_parent_id'], $children ) )
			{
				$this->registry->output->showError( $this->lang->words['invalid_category_id'], '11CCS35' );
			}
			
			//-----------------------------------------
			// Remember FURL slug?
			//-----------------------------------------
			
			if( $_save['category_furl_name'] != $category['category_furl_name'] )
			{
				$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], $category['category_id'] );

				$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => rtrim( $url, '/?' ), 'memory_type' => 'category', 'memory_type_id' => $category['category_id'] ) );
			}
			
			$this->DB->update( 'ccs_database_categories', $_save, 'category_id=' . $category['category_id'] );
			
			//-----------------------------------------
			// Permission matrix
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
	   		$permissions	= new $classToLoad( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $category['category_id'], 'categories' );

			$this->registry->output->setMessage( $this->lang->words['cat_edit_success'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_dbcatedited'], $_save['category_name'], $this->database['database_name'] ) );
		}
		
		//-----------------------------------------
		// Delete URL memories of this URL
		//-----------------------------------------

		$this->registry->ccsFunctions->getCategoriesClass( $this->database['database_id'] )->init( $this->database );
		
		$this->registry->ccsFunctions->fetchFreshUrl	= true;
		
		$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], $category['category_id'] );

		$this->DB->delete( 'ccs_slug_memory', "memory_url='" . rtrim( $url, '/?' ) . "'" );

		//-----------------------------------------
		// And redirect
		//-----------------------------------------
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Form to add/edit a category
	 *
	 * @access	public
	 * @param	string		add|edit
	 * @return	@e void
	 */
	public function _categoryForm( $type='add' )
	{
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$_id	= intval($this->request['category']);
			
			if( !$_id )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_cat_edit'], '11CCS36' );
			}
			
			$category	= $this->DB->buildAndFetch( array( 
														'select'	=> 'c.*', 
														'from'		=> array( 'ccs_database_categories' => 'c' ), 
														'where'		=> 'c.category_id=' . $_id,
														'add_join'	=> array(
																			array(
																				'select'	=> 'p.*',
																				'from'		=> array( 'permission_index' => 'p' ),
																				'where'		=> "p.app='ccs' AND p.perm_type='categories' AND p.perm_type_id=c.category_id",
																				'type'		=> 'left',
																				)
																			)
												)		);
			
			if( !$category['category_id'] )
			{
				$this->registry->output->showError( $this->lang->words['no_id_for_cat_edit'], '11CCS37' );
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['editing_cat_title'] . ' ' . $category['category_name'] );
		}
		else
		{
			$_id		= 0;
			$category	= array( 'category_show_records' => 1, 'category_parent_id' => $this->request['category_parent_id'] ? $this->request['category_parent_id'] : 0, 'category_rss' => 20 );
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['adding_cat_title'] );
		}

		$this->_category	= $category;
		
		//-----------------------------------------
		// Get possible cats for parent cat
		//-----------------------------------------
		
		$categories	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_categories', 'where' => 'category_database_id=' . $this->database['database_id'] ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[ $r['category_id'] ]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->request['category']	= $category['category_parent_id'];
		
		$this->registry->output->html .= $this->html->categoryForm( $type, $this->database, $category, $categories, $this->categories->getSelectMenu() );
	}

	/**
	 * List all of the created categories
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _listCategories()
	{
		//-----------------------------------------
		// What parent category are we viewing?
		//-----------------------------------------
		
		$parent	= $this->request['parent'] ? intval($this->request['parent']) : 0;
		
		//-----------------------------------------
		// Get all categories
		//-----------------------------------------
		
		$categories	= $this->categories->catcache[ $parent ];
		$parent_cat	= array();
		
		if( count($categories) )
		{
			foreach( $categories as $id => $data )
			{
				$_children						= $this->categories->getChildren( $id );
				$categories[ $id ]['children']	= array();
				
				if( count($_children) )
				{
					foreach( $_children as $_child )
					{
						$categories[ $id ]['children'][]	= $this->categories->categories[ $_child ];
					}
				}
			}

			if( $parent )
			{
				$parent_cat	= $this->categories->getCategory( $parent );
			}
		}

		//-----------------------------------------
		// Navigation
		//-----------------------------------------
		
		$parents	= $this->categories->getParents( $parent );
		
		if( count($parents) )
		{
			foreach( $parents as $_id )
			{
				$data	= $this->categories->getCategory( $_id );
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code . '&amp;do=list&amp;parent=' . $data['category_id'], $data['category_name'] . ' ' );
			}
		}
		
		if( $parent )
		{
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['subcatviewing'] . ' ' . $parent_cat['category_name'] );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->categories( $this->database, $categories, $parent_cat );
	}
}
