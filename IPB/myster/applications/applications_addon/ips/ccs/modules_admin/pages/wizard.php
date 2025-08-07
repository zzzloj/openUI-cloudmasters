<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS page creation wizard
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

class admin_ccs_pages_wizard extends ipsCommand
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
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_pages' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=wizard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=wizard';

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
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'quickEdit':
				$page	= $this->DB->buildAndFetch( array( 'select' => 'page_content_type', 'from' => 'ccs_pages', 'where' => 'page_id=' . intval($this->request['page']) ) );

				if( $page['page_content_type'] == 'js' )
				{
					$this->easyForm( 'edit', 'js' );
				}
				else if( $page['page_content_type'] == 'css' )
				{
					$this->easyForm( 'edit', 'css' );
				}
				else
				{
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=pages&section=wizard&do=editPage&amp;page=' . intval($this->request['page']) . '&amp;_jump=2' );
				}
			break;

			case 'editPage':
				$this->_preLaunchWizard();
			break;

			case 'saveEasyPage':
				$this->saveEasyForm();
			break;

			case 'continue':
			default:
				$this->_wizardProxy();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Wrapper function for the proxy method to load any necessary data
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _preLaunchWizard()
	{
		$id		= intval($this->request['page']);
		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );

		if( !$page['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['page_not_found_edit'], '11CCS138' );
		}

		$session	= array(
							'wizard_id'					=> md5( uniqid( microtime(), true ) ),
							'wizard_step'				=> 0,
							'wizard_name'				=> $page['page_name'],
							'wizard_folder'				=> $page['page_folder'],
							'wizard_type'				=> $page['page_type'],
							'wizard_template'			=> $page['page_template_used'],
							'wizard_content'			=> $page['page_content'],
							'wizard_cache_ttl'			=> $page['page_cache_ttl'],
							'wizard_perms'				=> $page['page_view_perms'],
							'wizard_seo_name'			=> $page['page_seo_name'],
							'wizard_content_only'		=> $page['page_content_only'],
							'wizard_edit_id'			=> $page['page_id'],
							'wizard_meta_keywords'		=> $page['page_meta_keywords'],
							'wizard_meta_description'	=> $page['page_meta_description'],
							'wizard_ipb_wrapper'		=> $page['page_ipb_wrapper'],
							'wizard_started'			=> time(),
							'wizard_omit_filename'		=> $page['page_omit_filename'],
							'wizard_page_title'			=> $page['page_title'],
							'wizard_page_quicknav'		=> $page['page_quicknav'],
							);
							
		$this->DB->insert( 'ccs_page_wizard', $session );
		
		$_step	= '';
		
		if( $this->request['_jump'] )
		{
			$_step	= '&step=' . ( $this->request['_jump'] - 1 );
		}
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . '&module=pages&section=wizard&continuing=1&wizard_session=' . $session['wizard_id'] . $_step );
	}
	
	/**
	 * Easy form - used for CSS and JS content types
	 *
	 * @access	protected
	 * @param	string		Type of form (add|edit)
	 * @param	string		Content type (css|js)
	 * @return	@e void
	 */
	protected function easyForm( $formType, $contentType )
	{
		$page	= array();

		if( $formType == 'edit' )
		{
			$id		= intval($this->request['page']);
			
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['pagefile_not_found_edit'], '11CCS140' );
			}

			//-----------------------------------------
			// Add navigation
			//-----------------------------------------

			$this->registry->ccsAcpFunctions->addNavigation( $page['page_folder'] );
			$this->registry->output->extra_nav[] = array( '', sprintf( $this->lang->words['edit_content_type_nav'], $page['page_seo_name'] ) );
		}
		else
		{
			$this->registry->ccsAcpFunctions->addNavigation( $this->request['in'] );
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['add_content_type_title'] );
		}
		
		//-----------------------------------------
		// Get folders
		//-----------------------------------------
		
		$folders		= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders', 'order' => 'folder_path ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$folders[]	= array( $r['folder_path'], $r['folder_path'] );
		}
		
		$this->registry->output->html	= $this->html->easyPageForm( $formType, $contentType, $page, $folders );
		
		//-----------------------------------------
		// WYSIWYG editing
		//-----------------------------------------

		$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->html .= $_globalHtml->getWysiwyg( 'content', $contentType );
	}
	
	/**
	 * Save the easy form (css or javascript)
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function saveEasyForm()
	{
		$page	= array();

		if( $this->request['type'] == 'edit' )
		{
			$id		= intval($this->request['page']);
			
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['pagefile_not_found_edit'], '11CCS141' );
			}
		}
		
		//-----------------------------------------
		// Prepare array for save
		//-----------------------------------------
		
		$this->request['page_seo_name']		= $this->_cleanSEOName( $this->request['page_seo_name'] );
		
		$_save	= array(
						'page_name'				=> $this->request['page_seo_name'] . '.' . $this->request['content_type'],
						'page_seo_name'			=> $this->request['page_seo_name'] . '.' . $this->request['content_type'],
						'page_folder'			=> $this->request['page_folder'],
						'page_view_perms'		=> '*',
						'page_cache_ttl'		=> '*',
						'page_content'			=> IPSText::formToText( trim($_POST['content']) ),
						'page_cache'			=> IPSText::formToText( trim($_POST['content']) ),
						'page_content_type'		=> $this->request['content_type'],
						'page_last_edited'		=> time(),
						);

		//-----------------------------------------
		// Have names?
		//-----------------------------------------
		
		if( !$_save['page_seo_name'] )
		{
			$this->registry->output->showError( $this->lang->words['missing_pagefile_details'], '11CCS142' );
		}

		//-----------------------------------------
		// Make sure name is unique
		//-----------------------------------------
		
		if( $page['page_id'] )
		{
			$_where	= " AND page_id<>{$page['page_id']}";
		}
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$_save['page_seo_name']}' AND page_folder='{$_save['page_folder']}'{$_where}" ) );
		
		if( $check['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wizard_pagefile_exists'], '11CCS143' );
		}

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		if( $this->request['type'] == 'edit' )
		{
			//-----------------------------------------
			// Store a revision first but only if content has changed
			//-----------------------------------------
			
			if( $page['page_content'] != $_save['page_content'] )
			{
				$_revision	= array(
									'revision_type'		=> 'page',
									'revision_type_id'	=> $page['page_id'],
									'revision_content'	=> $page['page_content'],
									'revision_date'		=> time(),
									'revision_member'	=> $this->memberData['member_id'],
									);
	
				$this->DB->insert( 'ccs_revisions', $_revision );
			}

			$this->DB->update( 'ccs_pages', $_save, 'page_id='. $page['page_id'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pageedited'], $_save['page_name'] ) );
		}
		else
		{
			$this->DB->insert( 'ccs_pages', $_save );
			
			$this->request['page']	= $this->DB->getInsertId();

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pageadded'], $_save['page_name'] ) );
		}

		$this->registry->output->setMessage( $this->lang->words['pagefile_saved'] );

		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->easyForm( 'edit', $this->request['content_type'] );
			return;
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode($_save['page_folder']) );
	}
	
	/**
	 * This is a proxy function.  It determines what step of the wizard we are on and acts appropriately
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _wizardProxy()
	{
		//-----------------------------------------
		// If it's a different type - proxy there
		//-----------------------------------------
		
		if( $this->request['fileType'] == 'css' OR $this->request['fileType'] == 'js' )
		{
			return $this->easyForm( 'add', $this->request['fileType'] );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : md5( uniqid( microtime(), true ) );
		
		$session	= array( 'wizard_step' => 0, 'wizard_id' => $sessionId, 'wizard_started' => time(), 'wizard_ipb_wrapper' => $this->settings['ccs_use_ipb_wrapper'] );
		
		if( $this->request['wizard_session'] )
		{
			$_session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
			
			if( $_session['wizard_id'] )
			{
				$session	= $_session;
			}
			else
			{
				$this->DB->insert( 'ccs_page_wizard', $session );
			}
		}
		else
		{
			$this->DB->insert( 'ccs_page_wizard', $session );
		}
		
		if( $this->request['_jump'] )
		{
			$this->request['step']	= $this->request['step'] - 1;
		}

		$session['wizard_step']	= $this->request['step'] ? $this->request['step'] : 0;

		//-----------------------------------------
		// Got stuff to save?
		//-----------------------------------------
		
		if( $session['wizard_step'] > 0 AND !$this->request['continuing'] AND !$this->request['_jump'] )
		{
			$session	= $this->_storeSubmittedData( $session );
		}

		//-----------------------------------------
		// Proxy off to appropriate function
		//-----------------------------------------
		
		$step		= $session['wizard_step'] + 1;
		$step		= $step > 0 ? $step : 1;
		$_func 		= "wizard_step_" . $step;
		$additional	= array();

		$this->registry->ccsAcpFunctions->addNavigation( $session['wizard_folder'] );
		
		if( $session['wizard_name'] )
		{
			$this->registry->output->extra_nav[] = array( '', sprintf( $this->lang->words['edit_page_type_nav'], $session['wizard_name'] ) );
		}
		else
		{
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['add_page_type_nav'] );
		}

		switch( $step )
		{
			//-----------------------------------------
			// Step 1: Grab folders and templates for form
			//-----------------------------------------
			
			case 1:
				if( !$this->_saveAndGo( $session, 1 ) )
				{
					$additional['folders']		= array();
					$additional['templates']	= array();
					$additional['categories']	= array();
					
					//-----------------------------------------
					// Get templates
					//-----------------------------------------
					
					$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_database=0', 'order' => 'template_name ASC' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$additional['templates'][]	= array( $r['template_id'], $r['template_name'], $r['template_category'] );
					}
					
					$this->DB->build( array( 'select' => 'container_id, container_name', 'from' => 'ccs_containers', 'where' => "container_type='template'", 'order' => 'container_order ASC' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$additional['categories'][ $r['container_id'] ]	= $r['container_name'];
					}
	
					//-----------------------------------------
					// Get folders
					//-----------------------------------------
					
					$folders		= array();
			
					$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders', 'order' => 'folder_path ASC' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$additional['folders'][]	= array( $r['folder_path'], $r['folder_path'] );
					}
					
					//-----------------------------------------
					// Permission masks
					//-----------------------------------------
					
					if( $session['wizard_perms'] == '*' OR !$session['wizard_edit_id'] )
					{
						$additional['all_masks']	= 1;
					}
					else
					{
						$additional['masks']		= explode( ',', $session['wizard_perms'] );
					}
					
					$additional['avail_masks']	= array();
					
					$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$additional['avail_masks'][]	= array( $r['perm_id'], $r['perm_name'] );
					}
					
					//-----------------------------------------
					// Edit content only and enable quick nav by default
					//-----------------------------------------
					
					if( !$session['wizard_edit_id'] )
					{
						$session['wizard_content_only']		= 1;
						$session['wizard_page_quicknav']	= 1;
					}
				}
			break;
			
			//-----------------------------------------
			// Step 2: Show the appropriate editor
			//-----------------------------------------
			
			case 2:
				if( !$this->_saveAndGo( $session, 2 ) )
				{
					//-----------------------------------------
					// If we are not editing content only, not
					//	editing an existing page, and have a 
					//	template id, get it as default content
					//-----------------------------------------
					
					if( !$session['wizard_content_only'] AND !$session['wizard_edit_id'] AND $session['wizard_template'] )
					{
						$template	= $this->DB->buildAndFetch( array( 'select' => 'template_content', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . intval($session['wizard_template']) ) );
	
						$session['wizard_content']	= $template['template_content'];
					}
	
					//-----------------------------------------
					// Sort parse for editor
					//-----------------------------------------
					
					if( $session['wizard_type'] == 'bbcode' )
					{
						$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
						$editor = new $classToLoad();

						if( $session['wizard_previous_type'] != 'bbcode' )
						{
							$editor->setAllowHtml( 1 );
						}
						
						$editor->setContent( $session['wizard_content'] );

						$editor_area	= $editor->show( 'content' );
					}
					else
					{
						if( $session['wizard_previous_type'] == 'bbcode' )
						{
							$session['wizard_content']	= html_entity_decode( $session['wizard_content'], ENT_QUOTES );
						}
						
						$editor_area	= $this->registry->output->formTextarea( "content", IPSText::htmlspecialchars( $session['wizard_content'] ), 100, 30, "content", "style='width:100%;'" );
						
						//-----------------------------------------
						// WYSIWYG editing
						//-----------------------------------------
		
						$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
						
						$this->registry->output->html .= $_globalHtml->getWysiwyg( 'content' );
					}
					
					$additional['editor']	= $editor_area;
					$additional['help']		= $this->registry->ccsAcpFunctions->getTemplateTags();
				}
			break;

			//-----------------------------------------
			// Step 3: Save to DB, destroy wizard session,
			//	show complete page
			//-----------------------------------------
			
			case 3:
				$page	= $this->_savePage( $session );

				if( $session['wizard_edit_id'] AND $this->request['save_button'] )
				{
					$this->DB->delete( 'ccs_page_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$this->registry->output->setMessage( $this->lang->words['page_q_edit'] );
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=pages&do=viewdir&dir=' . $session['wizard_folder'] );
				}
				else if( $session['wizard_edit_id'] AND $this->request['save_and_reload'] )
				{
					$this->registry->output->setMessage( $this->lang->words['page_q_edit'] );
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=pages&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=2&_jump=1' );
				}
				else
				{
					$this->DB->delete( 'ccs_page_wizard', "wizard_id='{$session['wizard_id']}'" );
					
					$session	= array_merge( $session, $page );
					$session['wizard_edit_id']	= $page['page_id'] ? $page['page_id'] : $session['wizard_edit_id'];
				}
			break;
		}
		
		$this->registry->output->html .= $this->html->$_func( $session, $additional );
	}
	
	/**
	 * Store the data submitted for the last step
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Session data (updated)
	 */
	protected function _storeSubmittedData( $session )
	{
		switch( $session['wizard_step'] )
		{
			case 1:
				//-----------------------------------------
				// Clean the SEO name...
				//-----------------------------------------
				
				$this->request['page_name']		= $this->_cleanSEOName( $this->request['page_name'] );

				$dataToSave	= array(
									'wizard_name'				=> $this->request['name'],
									'wizard_page_title'			=> $this->request['page_name_as_title'] ? '' : $this->request['title'],
									'wizard_seo_name'			=> $this->request['page_name'],
									'wizard_folder'				=> $this->request['folder'],
									'wizard_type'				=> ( $this->request['type'] AND in_array( $this->request['type'], array( 'bbcode', 'html', 'php' ) ) ) ? $this->request['type'] : 'bbcode',
									'wizard_template'			=> intval($this->request['template']),
									'wizard_content_only'		=> intval($this->request['content_only']),
									'wizard_meta_keywords'		=> $this->request['meta_keywords'],
									'wizard_meta_description'	=> $this->request['meta_description'],
									'wizard_previous_type'		=> $session['wizard_type'],
									'wizard_ipb_wrapper'		=> intval($this->request['ipb_wrapper']),
									'wizard_omit_filename'		=> intval($this->request['omit_pagename']),
									'wizard_cache_ttl'			=> $this->request['cache_ttl'],
									'wizard_page_quicknav'		=> intval($this->request['page_quicknav']),
									);

				if( $this->request['all_masks'] )
				{
					$dataToSave['wizard_perms']	= '*';
				}
				else if( is_array($this->request['masks']) )
				{
					$dataToSave['wizard_perms']	= ',' . implode( ',', $this->request['masks'] ) . ',';
				}

				//-----------------------------------------
				// Have names?
				//-----------------------------------------
				
				if( !$dataToSave['wizard_name'] OR !$dataToSave['wizard_seo_name'] )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['missing_page_details'];
					return array_merge( $session, $dataToSave );
				}

				//-----------------------------------------
				// Make sure name is unique
				//-----------------------------------------
				
				$_where	= $session['wizard_edit_id'] ? " AND page_id<>{$session['wizard_edit_id']}" : '';
				
				$check	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$dataToSave['wizard_seo_name']}' AND page_folder='{$dataToSave['wizard_folder']}'{$_where}" ) );
				
				if( $check['page_id'] )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['wizard_page_exists'];
					return array_merge( $session, $dataToSave );
				}

				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;
			
			case 2:
				if( $session['wizard_type'] == 'bbcode' )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
					$editor = new $classToLoad();
					$dataToSave['wizard_content']	= $editor->process( $_POST['content'] );
			
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$dataToSave['wizard_content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $dataToSave['wizard_content'] );
				}
				else
				{
					$dataToSave['wizard_content']	= $_POST['content'];
				}
				
				$dataToSave['wizard_content']	= IPSText::formToText( trim($dataToSave['wizard_content']) );
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $session['wizard_type'] == 'php' )
				{
					if( strpos( trim($dataToSave['wizard_content']), '<?php' ) === 0 OR strpos( trim($dataToSave['wizard_content']), '<?' ) === 0 )
					{
						//-----------------------------------------
						// Reset wizard step
						//-----------------------------------------
						
						$session['wizard_step']--;

						$this->registry->output->global_error	= $this->lang->words['php_page_php_tag'];
						return array_merge( $session, $dataToSave );
					}
				}
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;

		}
		
		return array_merge( $session, $dataToSave );
	}
	
	/**
	 * Run DB update query
	 *
	 * @access	protected
	 * @param	string		Session ID
	 * @param	integer		Current step
	 * @param	array 		Config data
	 * @return	bool
	 */
	protected function _saveToDb( $sessionId, $currentStep, $dataToSave )
	{
		$dataToSave['wizard_step']	= $currentStep + 1;
		
		$this->DB->update( 'ccs_page_wizard', $dataToSave, "wizard_id='{$sessionId}'" );
		return true;
	}
	
	/**
	 * Clean the SEO name
	 *
	 * @access	protected
	 * @param	string		SEO name
	 * @return	string		Cleaned SEO name
	 */
	protected function _cleanSEOName( $seo_title )
	{
		$seo_title	= str_replace( "/", '', $seo_title );
		$seo_title	= str_replace( "\\", '', $seo_title );
		$seo_title	= str_replace( "$", '', $seo_title );
		$seo_title	= str_replace( "..", '', $seo_title );
		$seo_title	= str_replace( "#", '', $seo_title );
		
		if( !$seo_title )
		{
			$this->registry->output->showError( $this->lang->words['mustprovidepagename'], '11CCS141.1' );
		}
		
		return $seo_title;
	}
	
	
	/**
	 * Save the page to the DB
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Page data
	 */
	protected function _savePage( $session )
	{
		$page	= array(
						'page_name'				=> $session['wizard_name'],
						'page_seo_name'			=> $session['wizard_seo_name'],
						'page_folder'			=> $session['wizard_folder'],
						'page_type'				=> $session['wizard_type'],
						'page_last_edited'		=> time(),
						'page_template_used'	=> $session['wizard_template'],
						'page_content'			=> $session['wizard_content'],
						'page_view_perms'		=> $session['wizard_perms'],
						'page_cache_ttl'		=> $session['wizard_cache_ttl'],
						'page_content_only'		=> $session['wizard_content_only'],
						'page_meta_keywords'	=> $session['wizard_meta_keywords'],
						'page_meta_description'	=> $session['wizard_meta_description'],
						'page_content_type'		=> 'page',
						'page_ipb_wrapper'		=> $session['wizard_ipb_wrapper'],
						'page_omit_filename'	=> $session['wizard_omit_filename'],
						'page_title'			=> $session['wizard_page_title'],
						'page_quicknav'			=> $session['wizard_page_quicknav'],
						'page_cache'			=> '',
						'page_cache_last'		=> 0,
						);

		/* If the user is outputting data using the output library methods, caching from ACP tries to do that at runtime and those outputs are not stored with the page content properly.
			@link	http://community.invisionpower.com/tracker/issue-36664-page-template-not-working-the-same-after-upgrade-to-231/ */
		/*if( $page['page_cache_ttl'] )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			
			$page['page_cache']			= $pageBuilder->recachePage( $page );
			$page['page_cache_last']	= time();
		}*/
		
		if( $session['wizard_edit_id'] )
		{
			//-----------------------------------------
			// Store a revision first, but only if content has changed
			//-----------------------------------------
			
			$_backUp	= $this->DB->buildAndFetch( array( 'select' => 'page_content, page_folder, page_seo_name, page_omit_filename, page_id', 'from' => 'ccs_pages', 'where' => 'page_id=' . $session['wizard_edit_id'] ) );
			
			if( $page['page_content'] != $_backUp['page_content'] )
			{
				$_revision	= array(
									'revision_type'		=> 'page',
									'revision_type_id'	=> $session['wizard_edit_id'],
									'revision_content'	=> $_backUp['page_content'],
									'revision_date'		=> time(),
									'revision_member'	=> $this->memberData['member_id'],
									);
	
				$this->DB->insert( 'ccs_revisions', $_revision );
			}
			
			//-----------------------------------------
			// Remember FURL?
			//-----------------------------------------
			
			if( $page['page_folder'] != $_backUp['page_folder'] OR $page['page_seo_name'] != $_backUp['page_seo_name'] )
			{
				$url	= $this->registry->ccsFunctions->returnPageUrl( $_backUp );

				$this->DB->insert( 'ccs_slug_memory', array( 'memory_url' => $url, 'memory_type' => 'page', 'memory_type_id' => $session['wizard_edit_id'] ) );
			}
			
			//-----------------------------------------
			// Then update live content
			//-----------------------------------------
			
			$this->DB->update( 'ccs_pages', $page, 'page_id=' . $session['wizard_edit_id'] );
			$page['page_id']	= $session['wizard_edit_id'];

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pageedited'], $page['page_name'] ) );
		}
		else
		{
			$this->DB->insert( 'ccs_pages', $page );
			$page['page_id']	= $this->DB->getInsertId();

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_pageadded'], $page['page_name'] ) );
		}
		
		//-----------------------------------------
		// Delete URL memories of this URL
		//-----------------------------------------

		$url	= $this->registry->ccsFunctions->returnPageUrl( $page );

		$this->DB->delete( 'ccs_slug_memory', "memory_url='" . $url . "'" );
				
		return $page;
	}
	
	/**
	 * Determine if we are saving and going elsewhere or not
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @param	int			Step number
	 * @return	mixed		void, or bool
	 */
	protected function _saveAndGo( $session, $step )
	{
		$step--;
		
		if( $session['wizard_edit_id'] AND $this->request['save_button'] )
		{
			$page	= $this->_savePage( $session );
			
			$this->DB->delete( 'ccs_page_wizard', "wizard_id='{$session['wizard_id']}'" );
			
			$this->registry->output->setMessage( $this->lang->words['page_q_edit'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=pages' );
		}
		else if( $session['wizard_edit_id'] AND $this->request['save_and_reload'] )
		{
			$page	= $this->_savePage( $session );

			$this->registry->output->setMessage( $this->lang->words['page_q_edit'] );
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=pages&section=wizard&do=continue&wizard_session=' . $session['wizard_id'] . '&step=' . $step . '&_jump=1' );
		}

		return false;
	}
}

