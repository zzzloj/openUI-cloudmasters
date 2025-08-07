<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS page templates
 * Last Updated: $Date: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_templates_pages extends ipsCommand
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
	 * HTML library
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_templates' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=pages';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=pages';
		
		$this->request['type']	= $this->request['type'] ? $this->request['type'] : 'page';

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
			case 'add':
			case 'edit':
				$this->_form( $this->request['do'] );
			break;			

			case 'doAdd':
			case 'doEdit':
				$this->_save( strtolower( str_replace( 'do', '', $this->request['do'] ) ) );
			break;
			
			case 'delete':
				$this->_delete();
			break;
			
			case 'addCategory':
				$this->_categoryForm( 'add' );
			break;
			case 'editCategory':
				$this->_categoryForm( 'edit' );
			break;
			
			case 'doAddCategory':
				$this->_categorySave( 'add' );
			break;
			case 'doEditCategory':
				$this->_categorySave( 'edit' );
			break;
			
			case 'deleteCategory':
				$this->_deleteCategory();
			break;
			
			case 'exportCategory':
				$this->_exportTemplates( 'category' );
			break;
			
			case 'exportTemplate':
				$this->_exportTemplates( 'template' );
			break;
			
			case 'importTemplates':
				$this->_importTemplates();
			break;

			case 'revert':
				$this->_revertTemplate();
			break;

			default:
				$this->_list();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Revert a database/article template to default
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _revertTemplate()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['templaterevert_notemp'], '11CCS140.1' );
		}
		
		$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
		
		if( !$template['template_id'] )
		{
			$this->registry->output->showError( $this->lang->words['templaterevert_notemp'], '11CCS140.2' );
		}
		
		if( !$template['template_database'] )
		{
			$this->registry->output->showError( $this->lang->words['templaterevert_nokind'], '11CCS140.3' );
		}
		
		$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/demosite.xml' );
		$original	= '';
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
			
		foreach( $xml->fetchElements('template') as $dtemplate )
		{
			$_template	= $xml->fetchElementsFromRecord( $dtemplate );

			if( $_template['template_key'] == $template['template_key'] )
			{
				$original	= $_template['template_content'];
				break;
			}
		}

		$_revision	= array(
							'revision_type'		=> 'template',
							'revision_type_id'	=> $id,
							'revision_content'	=> $template['template_content'],
							'revision_date'		=> time(),
							'revision_member'	=> $this->memberData['member_id'],
							);

		$this->DB->insert( 'ccs_revisions', $_revision );
				
		$this->DB->update( 'ccs_page_templates', array( 'template_content' => $original ), 'template_id=' . $id );

		//-----------------------------------------
		// Recache the template
		//-----------------------------------------
		
		$cache	= array(
						'cache_type'	=> 'template',
						'cache_type_id'	=> $id,
						);

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $template['template_key'], $template['template_database'] ? '$data' : '', $original, '', false, true );
		
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$id}" ) );
		
		if( $hasIt['cache_id'] )
		{
			$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$id}" );
		}
		else
		{
			$this->DB->insert( 'ccs_template_cache', $cache );
		}

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templatereverted'], $template['template_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['template_revert_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=edit&amp;template=' . $id . '&amp;type=' . $this->request['type'] );
	}
	
	/**
	 * Import templates
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _importTemplates()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml();

		if( !$content )
		{
			$this->registry->output->showError( $this->lang->words['notemplate_to_import'], '11CCS144.8' );
		}

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		$_categoryId	= 0;
		$_category		= array();
		
		//-----------------------------------------
		// Do we have a category?
		//-----------------------------------------
		
		foreach( $xml->fetchElements('category') as $cat )
		{
			$_category	= $xml->fetchElementsFromRecord( $cat );
		}
		
		if( count($_category) )
		{
			//-----------------------------------------
			// Try to match on name and type - if exists, don't reinsert
			//-----------------------------------------
			
			$_check	= $this->DB->buildAndFetch( array( 'select' => 'container_id', 'from' => 'ccs_containers', 'where' => "container_type='{$_category['container_type']}' AND container_name='{$_category['container_name']}'" ) );
			
			if( $_check['container_id'] )
			{
				$_categoryId	= $_check['container_id'];
			}
			else
			{
				$this->DB->insert( "ccs_containers", $_category );
				
				$_categoryId	= $this->DB->getInsertId();
			}
		}
		
		//-----------------------------------------
		// Throw an error if there is nothing to import
		//-----------------------------------------

		if( !count($xml->fetchElements('template')) )
		{
			$this->registry->output->showError( $this->lang->words['notemplate_to_import'], '11CCS144.7' );
		}

		//-----------------------------------------
		// Now import templates
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );

		foreach( $xml->fetchElements('template') as $template )
		{
			$entry  = $xml->fetchElementsFromRecord( $template );
			$id		= 0;

			if( !$entry['template_key'] )
			{
				continue;
			}
			
			//-----------------------------------------
			// If template exists, update, otherwise insert
			//-----------------------------------------
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'template_id', 'from' => 'ccs_page_templates', 'where' => "template_key='{$entry['template_key']}'" ) );
			
			if( $check['template_id'] )
			{
				$_update	= array(
									'template_name'		=> $entry['template_name'],
									'template_desc'		=> $entry['template_desc'],
									'template_content'	=> $entry['template_content'],
									'template_database'	=> $entry['template_database'],
									'template_position'	=> $entry['template_position'],
									'template_category'	=> $_categoryId,
									);

				$this->DB->update( 'ccs_page_templates', $_update, 'template_id=' . $check['template_id'] );
				
				$id	= $check['template_id'];
			}
			else
			{
				$entry['template_category']	= $_categoryId;
				
				$this->DB->insert( 'ccs_page_templates', $entry );
				
				$id	= $this->DB->getInsertId();
			}

			//-----------------------------------------
			// Recache the template
			//-----------------------------------------

			if( $id )
			{
				$cache	= array(
								'cache_type'	=> 'template',
								'cache_type_id'	=> $id,
								'cache_content'	=> $engine->convertHtmlToPhp( 'template_' . $entry['template_key'], $entry['template_database'] ? '$data' : '', $entry['template_content'], '', false, true ),
								);

				$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$id}" ) );
				
				if( $hasIt['cache_id'] )
				{
					$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$id}" );
				}
				else
				{
					$this->DB->insert( 'ccs_template_cache', $cache );
				}
			}
		}
		
		//-----------------------------------------
		// Recache skin
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templateimported'], count($xml->fetchElements('template')) ) );
		
		$this->registry->output->setMessage( $this->lang->words['templateimport_success'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $this->request['type'] );
	}
	
	/**
	 * Export one or more templates
	 *
	 * @access	protected
	 * @param	string		Type to export (category or template)
	 * @return	@e void
	 */
	protected function _exportTemplates( $type='template' )
	{
		$templates	= array();
		$category	= array();
		
		if( $type == 'category' )
		{
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => 'container_id=' . intval($this->request['id']) ) );
			
			unset($category['container_id']);
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_category=' . intval($this->request['id']) ) );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . intval($this->request['id']) ) );
		}
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			unset($r['template_id']);
			unset($r['template_category']);
			
			$templates[]	= $r;
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'templateexport' );
		
		if( $type == 'category' )
		{
			$xml->addElement( 'categorydata', 'templateexport' );
			$xml->addElementAsRecord( 'categorydata', 'category', $category );
		}
		
		$xml->addElement( 'templatedata', 'templateexport' );
		
		if( is_array($templates) AND count($templates) )
		{
			foreach( $templates as $template )
			{
				$xml->addElementAsRecord( 'templatedata', 'template', $template );
			}
		}

		$this->registry->output->showDownload( $xml->fetchDocument(), 'template-export.xml', '', 0 );
	}

	/**
	 * Delete a category
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _deleteCategory()
	{
		$id	= intval($this->request['id']);
		
		$cat	= $this->DB->buildAndFetch( array( 'select' => 'container_name', 'from' => 'ccs_containers', 'where' => 'container_id=' . $id ) );

		$this->DB->update( 'ccs_page_templates', array( 'template_category' => 0 ), 'template_category=' . $id );
		$this->DB->delete( 'ccs_containers', 'container_id=' . $id );
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templatecatdel'], $cat['container_name'] ) );

		$this->registry->output->setMessage( $this->lang->words['template_cat_deleted'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $this->request['type'] );
	}

	/**
	 * Save a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	@e void
	 */
	protected function _categorySave( $type='add' )
	{
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type IN('template','dbtemplate','arttemplate') AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'], '11CCS144' );
			}
		}
		
		$save	= array( 'container_name' => $this->request['category_title'] );
		
		if( !$save['container_name'] )
		{
			$this->registry->output->showError( $this->lang->words['category__nonamegiven'], '11CCS144B' );
		}
		
		if( $type == 'add' )
		{
			$save['container_type']		= $this->request['type'] == 'database' ? 'dbtemplate' : ( $this->request['type'] == 'article' ? 'arttemplate' : 'template' );
			$save['container_order']	= 100;
			
			$this->DB->insert( 'ccs_containers', $save );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templatecatadd'], $save['container_name'] ) );
		}
		else
		{
			$this->DB->update( 'ccs_containers', $save, "container_id=" . $category['container_id'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templatecatedit'], $save['container_name'] ) );
		}
		
		$this->registry->output->setMessage( $this->lang->words['template_cat_save__good'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $this->request['type'] );
	}
	
	/**
	 * Form to add/edit a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	@e void
	 */
	protected function _categoryForm( $type='add' )
	{
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type IN('template','dbtemplate','arttemplate') AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'], '11CCS145' );
			}
		}
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['editing_cat_title'] . ' ' . $category['container_name'] );
		
		$this->registry->output->html .= $this->html->categoryForm( $type, $category );
	}

	/**
	 * List the current page templates
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _list()
	{
		//-----------------------------------------
		// Get current templates
		//-----------------------------------------
		
		$templateIds	= array( 0 );
		$templates		= array();

		if( $this->request['type'] == 'database' )
		{
			$this->DB->build( array(
									'select'	=> 't.template_id, t.template_category', 
									'from'		=> array( 'ccs_page_templates' => 't' ), 
									'order'		=> 't.template_position ASC',
									'where'		=> 't.template_database > 0',	// 1-3 is regular databases
									'group'		=> 't.template_id, t.template_category, t.template_position',
									'add_join'	=> array(
														array(
															'select'	=> 'COUNT(d.database_id) as dbs',
															'from'		=> array( 'ccs_databases' => 'd' ),
															'where'		=> 'd.database_template_categories=t.template_id OR d.database_template_listing=t.template_id OR d.database_template_display=t.template_id',
															'type'		=> 'left',
															)
														)
							)		);
		}
		else if( $this->request['type'] == 'article' )
		{
			$this->DB->build( array(
									'select'	=> 't.template_id, t.template_category', 
									'from'		=> array( 'ccs_page_templates' => 't' ), 
									'order'		=> 't.template_position ASC',
									'where'		=> 't.template_database > 3',	// 4-7 is for articles
							)		);
		}
		else
		{
			$this->DB->build( array(
									'select'	=> 't.template_id, t.template_category', 
									'from'		=> array( 'ccs_page_templates' => 't' ), 
									'order'		=> 't.template_position ASC',
									'where'		=> 't.template_database = 0',
									'group'		=> 't.template_id, t.template_category, t.template_position',
									'add_join'	=> array(
														array(
															'select'	=> 'COUNT(p.page_id) as pages',
															'from'		=> array( 'ccs_pages' => 'p' ),
															'where'		=> 'p.page_template_used=t.template_id',
															'type'		=> 'left',
															)
														)
							)		);
		}
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates[ intval($r['template_category']) ][ $r['template_id'] ]	= $r;
			
			$templateIds[]	= $r['template_id'];
		}

		$this->DB->build( array(
								'select'	=> 't.*', 
								'from'		=> array( 'ccs_page_templates' => 't' ), 
								'where'		=> 't.template_id IN(' . implode( ',', $templateIds ) . ')',
								'group'		=> 't.template_id',
								'add_join'	=> array(
													array(
														'select'	=> "COUNT(r.revision_id) as revisions",
														'from'		=> array( 'ccs_revisions' => 'r' ),
														'where'		=> "r.revision_type='template' AND r.revision_type_id=t.template_id",
														'type'		=> 'left', 
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['template_updated_formatted']	= $this->lang->getDate( $r['template_updated'], 'SHORT', 1 );
			
			$templates[ intval($r['template_category']) ][ $r['template_id'] ]	= array_merge( $templates[ intval($r['template_category']) ][ $r['template_id'] ], $r );
		}
		
		//-----------------------------------------
		// Get template categories
		//-----------------------------------------
		
		$categories	= array();

		if( $this->request['type'] == 'database' )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='dbtemplate'", 'order' => 'container_order ASC' ) );
		}
		else if( $this->request['type'] == 'article' )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='arttemplate'", 'order' => 'container_order ASC' ) );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'", 'order' => 'container_order ASC' ) );
		}
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		if( $this->request['type'] == 'database' )
		{
			$this->registry->output->html .= $this->html->listTemplatesDatabase( $templates, $categories );
		}
		else if( $this->request['type'] == 'article' )
		{
			$this->registry->output->html .= $this->html->listTemplatesArticles( $templates, $categories );
		}
		else
		{
			$this->registry->output->html .= $this->html->listTemplatesPage( $templates, $categories );
		}
	}
	
	/**
	 * Delete a template
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _delete()
	{
		$id	= intval($this->request['template']);
		
		//-----------------------------------------
		// If template is used, warn user
		//-----------------------------------------
		
		if( !$this->request['confirm'] )
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
			$check		= $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as total", 'from' => 'ccs_pages', 'where' => 'page_template_used=' . $id ) );
			
			if( $check['total'] )
			{
				$this->registry->output->html .= $this->html->confirmDelete( $id, $template, $check['total'] );
				return;
			}
		}

		$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );

		//-----------------------------------------
		// Deletes
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_page_templates', 'template_id=' . $id );
		$this->DB->delete( 'ccs_revisions', "revision_type='template' AND revision_type_id=" . $id );
		$this->DB->delete( 'ccs_template_cache', "cache_type='template' AND cache_type_id=" . $id );
		
		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache();
		
		//-----------------------------------------
		// Also clear any cache using this template..
		//-----------------------------------------
		
		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ), 'page_template_used=' . $id );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templatedeleted'], $template['template_name'] ) );
		
		$this->registry->output->setMessage( $this->lang->words['template_deleted'] );

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $this->request['type'] );
	}

	/**
	 * Show the form to add or edit a page template
	 *
	 * @access	protected
	 * @param	string		[$type]		Type of form (add|edit)
	 * @return	@e void
	 */
	protected function _form( $type='add' )
	{
		$defaults	= array( 'template_content' => '{ccs special_tag="page_content"}' );
		$form		= array();
		
		if( $type == 'edit' )
		{
			$id	= intval($this->request['template']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'], '11CCS146' );
			}
			
			$defaults	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['edittemplate_nav'] . ' ' . $defaults['template_name'] );
		}
		else
		{
			if( $this->request['database'] )
			{
				$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/demosite.xml' );
				
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
				$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
				$xml->loadXML( $content );
			
				foreach( $xml->fetchElements('template') as $template )
				{
					$_template	= $xml->fetchElementsFromRecord( $template );

					if( $_template['template_database'] == $this->request['database'] )
					{
						$defaults['template_content']	= $_template['template_content'];
						break;
					}
				}
				
				$defaults['template_database']	= $this->request['database'];
			}
			
			$this->registry->output->extra_nav[] = array( '', $this->lang->words['adding_a_template'] );
		}
		
		$form['name']			= $this->registry->output->formInput( 'template_name', $this->request['template_name'] ? $this->request['template_name'] : $defaults['template_name'] );
		$form['key']			= $this->registry->output->formInput( 'template_key', $this->request['template_key'] ? $this->request['template_key'] : $defaults['template_key'] );
		$form['description']	= $this->registry->output->formTextarea( 'template_desc', $this->request['template_desc'] ? $this->request['template_desc'] : $defaults['template_desc'], 75, 3 );
		$form['content']		= $this->registry->output->formTextarea( 'template_content', IPSText::htmlspecialchars( str_replace( '&#092;', '\\', $_POST['template_content'] ? $_POST['template_content'] : $defaults['template_content'] ) ), 75, 30, 'template_content', "style='width:100%;'" );
		
		//-----------------------------------------
		// Category, if available
		//-----------------------------------------
		
		$form['category']		= '';
		$categories				= array();
		
		if( $this->request['type'] == 'database' )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='dbtemplate'", 'order' => 'container_order ASC' ) );
		}
		else if( $this->request['type'] == 'article' )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='arttemplate'", 'order' => 'container_order ASC' ) );
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'", 'order' => 'container_order ASC' ) );
		}
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= array( $r['container_id'], $r['container_name'] );
		}
		
		if( count($categories) )
		{
			array_unshift( $categories, array( '0', $this->lang->words['no_selected_cat'] ) );
			
			$form['category']	= $this->registry->output->formDropdown( 'template_category', $categories, $this->request['template_category'] ? $this->request['template_category'] : $defaults['template_category'] );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->templateForm( $type, $defaults, $form );
		
		//-----------------------------------------
		// WYSIWYG editing
		//-----------------------------------------

		$_globalHtml	= $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
		
		$this->registry->output->html .= $_globalHtml->getWysiwyg( 'template_content' );
	}
	
	/**
	 * Save the edits to a template
	 *
	 * @access	protected
	 * @param	string		[$type]		Saving of form (add|edit)
	 * @return	@e void
	 */
	protected function _save( $type='add' )
	{
		//-----------------------------------------
		// Protected links from dashboard
		//-----------------------------------------
		
		if ( $this->request['request_method'] != 'post' )
		{
			return $this->_form( $type );
		}
		
		$id	= 0;

		if( $type == 'edit' )
		{
			$id	= intval($this->request['template']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'], '11CCS147' );
			}
		}
		
		$save	= array(
						'template_name'		=> trim($this->request['template_name']),
						'template_desc'		=> trim($this->request['template_desc']),
						'template_key'		=> $this->request['template_key'] ? trim( str_replace( '-', '_', IPSText::alphanumericalClean( $this->request['template_key'] ) ) ) : md5( uniqid( microtime() ) ),
						'template_content'	=> str_replace( '&#46;&#46;/', '../', str_replace( '&#092;', '\\', trim($_POST['template_content']) ) ),
						'template_updated'	=> time(),
						'template_category'	=> intval($this->request['template_category']),
						'template_database'	=> intval($this->request['template_database']),
						);

		if( !$save['template_name'] )
		{
			$this->registry->output->showError( $this->lang->words['template_name_missing'], '11CCS148' );
		}
		
		//-----------------------------------------
		// Make sure key is unique
		//-----------------------------------------
		
		if( !$save['template_key'] )
		{
			//$this->registry->output->showError( $this->lang->words['template_key_missing'], '11CCS148' );
			$save['template_key']	= md5( uniqid( microtime() ) );
		}
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'template_id', 'from' => 'ccs_page_templates', 'where' => "template_key='{$save['template_key']}' AND template_id<>{$id}" ) );
		
		if( $check['template_id'] )
		{
			$this->registry->output->showError( $this->lang->words['template_key_used'], '11CCS149' );
		}

		//-----------------------------------------
		// Test the syntax
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		
		$_TEST	= $engine->convertHtmlToPhp( 'test__' . $save['template_key'], $save['template_database'] ? '$data' : '', $save['template_content'], '', false, true );

		ob_start();
		eval( $_TEST );
		$_RETURN = ob_get_contents();
		ob_end_clean();

		if( $_RETURN )
		{
			$this->registry->output->global_error	= $this->lang->words['bad_template_syntax'];
			
			$this->_form( $type );
			return;
		}
		
		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			//-----------------------------------------
			// Store a revision first but only if content has changed
			//-----------------------------------------
			
			$_backUp	= $this->DB->buildAndFetch( array( 'select' => 'template_content', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );			

			if( $save['template_content'] != $_backUp['template_content'] )
			{
				$_revision	= array(
									'revision_type'		=> 'template',
									'revision_type_id'	=> $id,
									'revision_content'	=> $_backUp['template_content'],
									'revision_date'		=> time(),
									'revision_member'	=> $this->memberData['member_id'],
									);
	
				$this->DB->insert( 'ccs_revisions', $_revision );
			}

			$this->DB->update( 'ccs_page_templates', $save, 'template_id=' . $id );
			
			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ), 'page_template_used=' . $id );
			
			$this->registry->output->setMessage( $this->lang->words['template_edited'] );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templateedited'], $save['template_name'] ) );
		}
		else
		{
			$this->DB->insert( 'ccs_page_templates', $save );
			
			$id	= $this->DB->getInsertId();
			
			$this->registry->output->setMessage( $this->lang->words['template_added'] );
			
			$this->request['template']	= $id;

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['ccs_adminlog_templateadded'], $save['template_name'] ) );
		}
		
		//-----------------------------------------
		// Recache the template
		//-----------------------------------------
		
		$cache	= array(
						'cache_type'	=> 'template',
						'cache_type_id'	=> $id,
						);

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $save['template_key'], $save['template_database'] ? '$data' : '', $save['template_content'], '', false, true );
		
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$id}" ) );
		
		if( $hasIt['cache_id'] )
		{
			$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$id}" );
		}
		else
		{
			$this->DB->insert( 'ccs_template_cache', $cache );
		}

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$_pagesClass	= new $classToLoad( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );
		
		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->_form( 'edit' );
			return;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;type=' . $this->request['type'] );
	}
}
