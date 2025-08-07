<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS template help AJAX functions
 * Last Updated: $Date: 2012-01-06 06:20:45 -0500 (Fri, 06 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10095 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_help extends ipsAjaxCommand
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
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=help';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=help';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$this->showDatabases();
			break;			

			case 'tags':
				$this->_showTags();
			break;
		}
	}
	
	/**
	 * Show the tags available
	 *
	 * @access	protected
	 * @param	array 		Database info (already pulled elsewhere)
	 * @return	@e void
	 */
	protected function _showTags( $database=array() )
	{
		//-----------------------------------------
		// Get database
		//-----------------------------------------
		
		if( !count($database) )
		{
			$database	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_databases', 'where' => 'database_id=' . intval($this->request['id']) ) );
		}

		if( !$database['database_id'] )
		{
			$this->returnJsonError( $this->lang->words['dbhelp_no_db'] );
		}
		
		$this->registry->ccsFunctions->fixStrings( $database['database_id'] );
		
		//-----------------------------------------
		// Get fields
		//-----------------------------------------
		
		$fields	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=' . $database['database_id'], 'order' => 'field_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$fields[]	= $r;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->returnHtml( $this->html->listDatabaseTags( $database, $fields ) );
	}

	/**
	 * List the databases
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function showDatabases()
	{
		//-----------------------------------------
		// If it's articles, just show help
		//-----------------------------------------
		
		if( $this->request['type'] > 3 )
		{
			$database	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_databases', 'where' => 'database_is_articles=1' ) );
			
			return $this->_showTags( $database );
		}
		
		//-----------------------------------------
		// Get our databases
		//-----------------------------------------
		
		$databases	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases', 'where' => 'database_is_articles=0', 'order' => 'database_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$databases[]	= $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->returnHtml( $this->html->listDatabases( $databases ) );
	}
}