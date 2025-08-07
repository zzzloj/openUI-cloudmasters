<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS manage primary navigation menu
 * Last Updated: $Date: 2011-11-28 21:04:21 -0500 (Mon, 28 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		21 Dec 2011
 * @version		$Revision: 9902 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_ccs_settings_menu extends ipsCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	protected
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	protected
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_menu' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=settings&amp;section=menu';
		$this->form_code_js	= $this->html->form_code_js	= 'module=settings&section=menu';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );
		
		$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->addToDocumentHead( 'inlinecss', $_global->getCss() );
		
		//-----------------------------------------
		// Verify hook is enabled, else the rest is moot
		//-----------------------------------------
		
		$_enabled	= false;
		
		if( isset($this->caches['hooks']['skinHooks']['skin_global']) )
		{
			foreach( $this->caches['hooks']['skinHooks']['skin_global'] as $_hook )
			{
				if( $_hook['className'] == 'contentMenuBar' )
				{
					$_enabled	= true;
					break;
				}
			}
		}
		
		if( !$_enabled )
		{
			$this->registry->output->global_error	= sprintf( $this->lang->words['menu_hook_not_enabled'], $this->settings['_base_url'] . 'app=core&amp;module=applications&amp;section=hooks' );
		}
		else
		{
			//-----------------------------------------
			// What to do?
			//-----------------------------------------
			
			switch( $this->request['do'] )
			{
				case 'delete':
					$this->_deleteMenuItem();
				break;
	
				case 'add':
				case 'edit':
					$this->_showForm( $this->request['do'] );
				break;
				
				case 'doAdd':
					$this->_saveForm( 'add' );
				break;
				
				case 'doEdit':
					$this->_saveForm( 'edit' );
				break;
				
				case 'editApp':
					$this->_showAppForm();
				break;
				
				case 'saveApp':
					$this->_saveAppForm();
				break;

				default:
					$this->_list();
				break;
			}
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * List current menu items
	 * 
	 * @return	@e void
	 * @note	We explicitly do not show the 'core' application, because IP.Board hides it by default.  Even if we let user override, the modules may not
	 * 	be in the 'right' order and the help files won't necessarily be displayed at app=core (login form displays for example)
	 */
 	protected function _list()
 	{
		//-----------------------------------------
		// Get public lang (for app titles/description)
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Get applications (including disabled)
		//-----------------------------------------
		
 		$applications	= array();
 		
 		foreach( ipsRegistry::$applications as $app_key => $app_data )
 		{
 			if( $app_key != 'core' AND IPSLib::appIsInstalled( $app_key, false ) )
 			{
 				$applications[ $app_key ]	= $app_data;
 			}
 		}

		//-----------------------------------------
		// Get menu items
		//-----------------------------------------
		
		$menu	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_menus', 'where' => 'menu_parent_id=0', 'order' => $this->DB->buildLength('menu_position') . ' ASC, menu_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$menu[]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->listMenuItems( $applications, $menu );
 	}

	/**
	 * Show the form to add a menu item
	 * 
	 * @param	string	add or edit
	 * @return	@e void
	 */
 	protected function _showForm( $type='add' )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$submenus	= array();
		$menu		= array();
 			
		//-----------------------------------------
		// Get our menu and submenu items from the DB
		//-----------------------------------------
		
 		if( $type == 'edit' )
 		{
 			$id	= intval($this->request['id']);
 			
 			if( !$id )
 			{
 				$this->registry->output->showError( $this->lang->words['menu_not_found'], '11CCS555.1' );
 			}
 			
 			$menu	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_menus', 'where' => 'menu_id=' . $id ) );
 			
 			if( !$menu['menu_id'] )
 			{
 				$this->registry->output->showError( $this->lang->words['menu_not_found'], '11CCS555.2' );
 			}
 			
 			$submenus	= array();
 			
 			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_menus', 'where' => 'menu_parent_id=' . $id, 'order' => $this->DB->buildLength('menu_position') . ' ASC, menu_position ASC' ) );
 			$this->DB->execute();
 			
 			while( $r = $this->DB->fetch() )
 			{
 				$submenus[]	= $r;
 			}
 		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->menuForm( $type, $menu, $submenus );
 	}

	/**
	 * Save a new or edited menu item
	 * 
	 * @param	string	add or edit
	 * @return	@e void
	 */
 	protected function _saveForm( $type='add' )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$submenus	= array();
		$menu		= array();
		$_save		= array();
		$_submenus	= array();
 			
		//-----------------------------------------
		// Get our menu and submenu items from the DB
		//-----------------------------------------
		
 		if( $type == 'edit' )
 		{
 			$id	= intval($this->request['id']);
 			
 			if( !$id )
 			{
 				$this->registry->output->showError( $this->lang->words['menu_not_found'], '11CCS555.3' );
 			}
 			
 			$menu	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_menus', 'where' => 'menu_id=' . $id ) );
 			
 			if( !$menu['menu_id'] )
 			{
 				$this->registry->output->showError( $this->lang->words['menu_not_found'], '11CCS555.4' );
 			}

 			$submenus	= array();
 			
 			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_menus', 'where' => 'menu_parent_id=' . $id, 'order' => $this->DB->buildLength('menu_position') . ' ASC, menu_position ASC' ) );
 			$this->DB->execute();

 			while( $r = $this->DB->fetch() )
 			{
 				$submenus[ $r['menu_id'] ]	= $r;
 			}
 		}

		//-----------------------------------------
		// Basic data
		//-----------------------------------------
		
		$_save['menu_parent_id']	= 0;
		$_save['menu_title']		= $this->request['menu_title'];
		$_save['menu_url']			= $this->request['menu_url'];
		$_save['menu_description']	= $this->request['menu_description'];
		$_save['menu_attributes']	= str_replace( '&#092;', '\\', $_POST['menu_attributes'] );
		$_save['menu_permissions']	= count($this->request['menu_permissions']) ? implode( ',', $this->request['menu_permissions'] ) : null;

		//-----------------------------------------
		// Figure out submenus...
		//-----------------------------------------

		$position	= 0;

		if( $this->request['submenu_items'] )
		{
			foreach( $this->request['edit_map'] as $_value )
			{
				//-----------------------------------------
				// Figure out ids and keys
				//-----------------------------------------

				$_bits	= explode( '=', $_value );
				$_key	= intval($_bits[0]);
				$_id	= intval($_bits[1]);
				
				if( !$this->request['submenu_title_' . $_key ] OR !$this->request['submenu_url_' . $_key ] )
				{
					continue;
				}

				$_submenus[]	= array(
									'menu_id'			=> $_id,
									'menu_title'		=> $this->request['submenu_title_' . $_key ],
									'menu_url'			=> $this->request['submenu_url_' . $_key ],
									'menu_description'	=> $this->request['submenu_description_' . $_key ],
									'menu_attributes'	=> str_replace( '&#092;', '\\', $_POST['submenu_attributes_' . $_key ] ),
									'menu_permissions'	=> count($this->request['submenu_permissions_' . $_key ]) ? implode( ',', $this->request['submenu_permissions_' . $_key ] ) : null,
									'menu_position'		=> $position,
									);

				$position++;
			}
		}

		$_save['menu_submenu']		= count($_submenus) ? intval($this->request['menu_submenu']) : 0;

		//-----------------------------------------
		// Check data
		//-----------------------------------------
		
		if( !$_save['menu_title'] OR ( !$_save['menu_submenu'] AND !$_save['menu_url'] ) )
		{
			$this->registry->output->showError( $this->lang->words['menu_missing_data'], '11CCS555.6' );
		}

		//-----------------------------------------
		// Save to DB
		//-----------------------------------------
		
		if( $type == 'add' )
		{
			$_max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(menu_position) as menu', 'from' => 'ccs_menus', 'where' => 'menu_parent_id=0' ) );
			
			$_save['menu_position']	= $_max['menu'] + 1;
			
			$this->DB->insert( 'ccs_menus', $_save );

			$_newId	= $this->DB->getInsertId();

			//-----------------------------------------
			// Insert submenus if we have any
			//-----------------------------------------
			
			if( count($_submenus) )
			{
				foreach( $_submenus as $_submenu )
				{
					unset($_submenu['menu_id']);

					$_submenu['menu_parent_id']	= $_newId;
					
					$this->DB->insert( 'ccs_menus', $_submenu );
				}
			}

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_menuadded'], $_save['menu_title'] ) );
		}
		else
		{
			$this->DB->update( 'ccs_menus', $_save, 'menu_id=' . $id );

			//-----------------------------------------
			// Insert/update submenus if we have any
			//-----------------------------------------
			
			$existing	= array_keys($submenus);
			$stillHere	= array();

			if( count($_submenus) )
			{
				foreach( $_submenus as $_submenu )
				{
					$_toUpdate	= 0;

					if( $_submenu['menu_id'] )
					{
						$stillHere[]	= $_submenu['menu_id'];
						$_toUpdate		= $_submenu['menu_id'];
						
						unset($_submenu['menu_id']);
						
						$this->DB->update( 'ccs_menus', $_submenu, 'menu_id=' . $_toUpdate );
					}
					else
					{
						unset($_submenu['menu_id']);

						$_submenu['menu_parent_id']	= $id;
						
						$this->DB->insert( 'ccs_menus', $_submenu );
					}
				}
			}
			
			//-----------------------------------------
			// Delete submenus that no longer exist
			//-----------------------------------------
			
			if( count($existing) )
			{
				$_gone	= array_diff( $existing, $stillHere );
				
				if( count($_gone) )
				{
					$this->DB->delete( 'ccs_menus', "menu_id IN(" . implode( ',', $_gone ) . ")" );
				}
			}

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_menuedited'], $_save['menu_title'] ) );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
 		$this->recacheMenu();

		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->output->setMessage( $this->lang->words['menu_item_saved'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
 	}

	/**
	 * Show a form to edit properties of application menu items
	 * 
	 * @return	@e void
	 */
 	protected function _showAppForm()
 	{
		//-----------------------------------------
		// Get application
		//-----------------------------------------
		
		if( !$this->request['appkey'] )
		{
			$this->registry->output->showError( $this->lang->words['app_not_found'], '11CCS555.7' );
		}
		
		$application	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => "app_directory='{$this->request['appkey']}'" ) );
		
		if( !$application['app_id'] )
		{
			$this->registry->output->showError( $this->lang->words['app_not_found'], '11CCS555.8' );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->applicationForm( $application );
 	}

	/**
	 * Save properties of an application
	 * 
	 * @return	@e void
	 * @note	This method actually updates the application record in core_applications and rebuilds the application cache
	 */
 	protected function _saveAppForm()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_save		= array();
 			
		//-----------------------------------------
		// Get our application data
		//-----------------------------------------

		if( !$this->request['appkey'] )
		{
			$this->registry->output->showError( $this->lang->words['app_not_found'], '11CCS555.9' );
		}
		
		$application	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => "app_directory='{$this->request['appkey']}'" ) );
		
		if( !$application['app_id'] )
		{
			$this->registry->output->showError( $this->lang->words['app_not_found'], '11CCS555.10' );
		}

		//-----------------------------------------
		// Basic data
		//-----------------------------------------
		
		$_save['app_public_title']		= $this->request['app_public_title'];
		$_save['app_tab_description']	= $this->request['app_tab_description'];
		$_save['app_tab_attributes']	= str_replace( '&#092;', '\\', $_POST['app_tab_attributes'] );
		$_save['app_hide_tab']			= intval($this->request['app_hide_tab']);
		$_save['app_tab_groups']		= count($this->request['app_permissions']) ? implode( ',', $this->request['app_permissions'] ) : null;

		//-----------------------------------------
		// Save to DB
		//-----------------------------------------
		
		$this->DB->update( 'core_applications', $_save, 'app_id=' . $application['app_id'] );

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
 		$this->cache->rebuildCache( 'app_cache', 'global' );

		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_menuappupdate'], $_save['app_public_title'] ) );

		$this->registry->output->setMessage( $this->lang->words['app_item_saved'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
 	}

	/**
	 * Delete a menu item
	 * 
	 * @return	@e void
	 */
 	protected function _deleteMenuItem()
 	{
		//-----------------------------------------
		// Delete and recache
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['menudel_not_found'], '11CCS555.5' );
		}
		
		$menu	= $this->DB->buildAndFetch( array( 'select' => 'menu_title', 'from' => 'ccs_menus', 'where' => 'menu_id=' . $id ) );

		$this->DB->delete( 'ccs_menus', "menu_id=" . $id );

 		$this->recacheMenu();

		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_menudeleted'], $menu['menu_title'] ) );

		$this->registry->output->setMessage( $this->lang->words['menu_item_removed'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
 	}

	/**
	 * Recache the menu
	 * 
	 * @return	@e void
	 */
 	public function recacheMenu()
 	{
		//-----------------------------------------
		// Get menu items
		//-----------------------------------------
		
		$menu	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_menus', 'order' => $this->DB->buildLength('menu_position') . ' ASC, menu_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$menu[ $r['menu_parent_id'] ][]	= $r;
		}
		
		$this->cache->setCache( 'ccs_menu', $menu, array( 'array' => 1 ) );
 	}
}