<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS field AJAX operations
 * Last Updated: $Date: 2011-11-23 17:54:52 -0500 (Wed, 23 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th Nov 2009
 * @version		$Revision: 9874 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_fields extends ipsAjaxCommand
{
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
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'fetchDatabases':
			default:
				$this->_fetchDatabases();
			break;
			
			case 'fetchFields':
				$this->_fetchFields();
			break;

			case 'checkKey':
				$this->_checkKey();
			break;
		}
	}
	
	/**
	 * Check a supplied key to make sure it's not in use
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _checkKey()
	{
		$value		= $this->request['value'];
		$database	= intval($this->request['database']);
		
		/* Let this one be empty, as the issue is likely an internal problem and not something the admin did */
		if( !$value )
		{
			$this->returnJsonArray( array( 'error' => '' ) );
		}
		
		$value	= strtolower( preg_replace( "/[^a-zA-Z0-9]/sm", '_', $value ) );
		
		if( !$this->registry->getClass('ccsFunctions')->getFieldsClass()->checkFieldKey( $database, $value ) )
		{
			$this->returnJsonArray( array( 'value' => $value . '_' . time() ) );
		}
		else
		{
			$this->returnJsonArray( array( 'value' => $value ) );
		}

 		exit();
	}
	
	/**
	 * Get databases
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _fetchDatabases()
	{
		$_field		= intval($this->request['field']);
		$_default	= 0;
		
		if( $_field )
		{
			$thisField	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . $_field ) );
			
			if( $thisField['field_id'] )
			{
				$_options	= explode( ',', $thisField['field_extra'] );
				
				$_default	= intval($_options[0]);
			}
		}
		
		//-----------------------------------------
		// Get all databases
		//-----------------------------------------
		
		$databases	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases', 'order' => "database_name" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$databases[]	= $r;
		}
		
 		//-----------------------------------------
 		// Set output
 		//-----------------------------------------

		$_output	= '';
		
		foreach( $databases as $database )
		{
			$_selected	= $database['database_id'] == $_default ? " selected='selected'" : '';
			
			$_output	.= "<option value='{$database['database_id']}'{$_selected}>{$database['database_name']}</option>\n";
		}

 		$this->returnHtml( $_output );
 		exit();
	}
	
	/**
	 * Fetch fields in a database
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _fetchFields()
	{
		$_field		= intval($this->request['field']);
		$_database	= intval($this->request['database']);
		$_default	= 0;
		
		if( $_field )
		{
			$thisField	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . $_field ) );
			
			if( $thisField['field_id'] )
			{
				$_options	= explode( ',', $thisField['field_extra'] );
				
				if( intval($_options[0]) == $_database )
				{
					$_default	= intval($_options[1]);
				}
			}
		}
		
		//-----------------------------------------
		// Get all fields in this db
		//-----------------------------------------
		
		$fields	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'order' => "field_position ASC", 'where' => 'field_id <> ' . $_field . ' AND field_database_id=' . $_database ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$fields[]	= $r;
		}
		
 		//-----------------------------------------
 		// Set output
 		//-----------------------------------------

		$_output	= '';
		
		foreach( $fields as $field )
		{
			$_selected	= $field['field_id'] == $_default ? " selected='selected'" : '';
			
			$_output	.= "<option value='{$field['field_id']}'{$_selected}>{$field['field_name']}</option>\n";
		}

 		$this->returnHtml( $_output );
 		exit();
	}
}
