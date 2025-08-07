<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article category management
 * Last Updated: $Date: 2011-11-22 21:35:28 -0500 (Tue, 22 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 9864 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_categories extends ipsCommand
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
	 * Skin object
	 *
	 * @access	protected
	 * @var		object			Skin templates
	 */	
	protected $html;
	
	/**
	 * Main category handler
	 *
	 * @access	protected
	 * @var		object			Category action file
	 */
	protected $categoryHandler;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_articles' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=categories';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=categories';

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
		// Get some libs we'll want
		//-----------------------------------------
		
		$this->database	= $this->DB->buildAndFetch( array(
															'select'	=> 'd.*',
															'from'		=> array( 'ccs_databases' => 'd' ),
															'where'		=> 'd.database_is_articles=1',
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
			$this->registry->output->showError( $this->lang->words['no_db_id_cats'], '11CCS6' );
		}
		
		$this->categories	= $this->registry->getClass('ccsFunctions')->getCategoriesClass( $this->database, false );
		
		//-----------------------------------------
		// Get main category handler
		//-----------------------------------------
		
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/modules_admin/databases/categories.php', 'admin_ccs_databases_categories', 'ccs' );
		$this->categoryHandler	= new $classToLoad( $this->registry );
		$this->categoryHandler->makeRegistryShortcuts( $this->registry );
		
		$this->categoryHandler->form_code		= $this->form_code;
		$this->categoryHandler->form_code_js	= $this->form_code_js;
		$this->categoryHandler->html			= $this->html;
		$this->categoryHandler->database		= $this->database;
		$this->categoryHandler->categories		= $this->categories;

		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
		
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
		$this->categoryHandler->_categoryRecache();
	}
	
	/**
	 * Reorders categories
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function _categoryReorder()
	{
		$this->categoryHandler->_categoryReorder();
	}
	
	/**
	 * Delete a category
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _categoryDelete()
	{
		$this->categoryHandler->_categoryDelete();
	}
	
	/**
	 * Save a new field/edited category
	 *
	 * @access	protected
	 * @param	string		add|edit
	 * @return	@e void
	 */
	protected function _categorySave( $type='add' )
	{
		$this->categoryHandler->_categorySave( $type );
	}
	
	/**
	 * Form to add/edit a category
	 *
	 * @access	protected
	 * @param	string		add|edit
	 * @return	@e void
	 */
	protected function _categoryForm( $type='add' )
	{
		$this->categoryHandler->_categoryForm( $type );
		
		//-----------------------------------------
		// And front page templates
		//-----------------------------------------

		$templates	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_database=4' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates[]	= array( $r['template_id'], $r['template_name'], $r['template_category'] );
		}

		$containers	= array();
		
		$this->DB->build( array( 'select' => 'container_id, container_name', 'from' => 'ccs_containers', 'where' => "container_type='arttemplate'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$containers[ $r['container_id'] ]	= $r['container_name'];
		}
		
		$this->registry->output->html	= str_replace( "<!--CAT_TEMPLATE-->", $this->html->categoryTemplate( $this->categoryHandler->_category, $templates, $containers ), $this->registry->output->html );
	}

	/**
	 * List all of the created categories
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _listCategories()
	{
		$this->categoryHandler->_listCategories();
		
		//-----------------------------------------
		// What parent category are we viewing?
		//-----------------------------------------
		
		$parent	= $this->request['parent'] ? intval($this->request['parent']) : 0;
		
		//-----------------------------------------
		// Get the template ids
		//-----------------------------------------
		
		$categories	= $this->categories->catcache[ $parent ];
		$templates	= array();
		
		if( count($categories) )
		{
			foreach( $categories as $id => $data )
			{
				if( $data['category_template'] )
				{
					$templates[ intval($data['category_template']) ]	= intval($data['category_template']);
				}
			}
		}
		
		if( count($templates) )
		{
			$this->DB->build( array( 'select' => 'template_id, template_name', 'from' => 'ccs_page_templates', 'where' => 'template_id IN(' . implode( ',', $templates ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$templates[ $r['template_id'] ]	= $r['template_name'];
			}
		}

		if( count($categories) )
		{
			foreach( $categories as $id => $data )
			{
				$_string						= $templates[ $data['category_template'] ] ? $templates[ $data['category_template'] ] : $this->lang->words['no_cp_tp_sel'];
				$this->registry->output->html	= str_replace( "<!--CAT_TEMPLATE_{$id}-->", $_string, $this->registry->output->html );
			}
		}
	}
}