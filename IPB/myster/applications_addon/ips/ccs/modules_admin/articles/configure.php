<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS article configuration
 * Last Updated: $Date: 2011-11-22 21:35:28 -0500 (Tue, 22 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd February 2010
 * @version		$Revision: 9864 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_articles_configure extends ipsCommand
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
	 * Main database handler
	 *
	 * @access	protected
	 * @var		object			Database action file
	 */
	protected $databaseHandler;
	
	/**
	 * Database info
	 *
	 * @access	public
	 * @var		array 			DB info
	 */	
	public $database			= array();

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
		
		$this->form_code	= $this->html->form_code	= 'module=articles&amp;section=configure';
		$this->form_code_js	= $this->html->form_code_js	= 'module=articles&section=configure';

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
		// Get database data
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
			$this->registry->output->showError( $this->lang->words['no_db_id_cats'], '11CCS7' );
		}
		
		$this->request['id']	= $this->database['database_id'];
		
		//-----------------------------------------
		// Get main database handler
		//-----------------------------------------
		
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/modules_admin/databases/databases.php', 'admin_ccs_databases_databases', 'ccs' );
		$this->databaseHandler	= new $classToLoad( $this->registry );
		$this->databaseHandler->makeRegistryShortcuts( $this->registry );
		
		$this->databaseHandler->form_code		= $this->form_code;
		$this->databaseHandler->form_code_js	= $this->form_code_js;
		$this->databaseHandler->html			= $this->html;
		$this->databaseHandler->containerType	= 'arttemplate';

		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );
				
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'home':
			case 'add':
			case 'edit':
			case 'doAdd':
			case 'delete':
				$this->_databaseForm();
			break;

			case 'doEdit':
				$this->_databaseSave();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Save a new database/edited database
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _databaseSave()
	{
		$this->databaseHandler->_databaseSave( 'edit' );
	}
	
	/**
	 * Form to add/edit a database
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _databaseForm()
	{
		$this->databaseHandler->_databaseForm( 'edit' );
	}
}
