<?php
/**
 * @file		databases.php 	IP.CCS database AJAX operations
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		23 Nov 2011
 * $LastChangedDate: 2012-01-06 06:20:45 -0500 (Fri, 06 Jan 2012) $
 * @version		v3.4.5
 * $Revision: 10095 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_ccs_ajax_databases
 * @brief		IP.CCS database AJAX operations
 */
class admin_ccs_ajax_databases extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
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
			case 'checkKey':
			default:
				$this->_checkKey();
			break;

			case 'checkFurl':
				$this->_checkFurl();
			break;

			case 'checkCategoryKey':
			default:
				$this->_checkCategoryKey();
			break;
		}
	}

	/**
	 * Take a supplied value and return FURL for it
	 *
	 * @return	@e void
	 */
	protected function _checkFurl()
	{
		$this->returnJsonArray( array( 'value' => urldecode( IPSText::makeSeoTitle( $this->request['value'] ) ) ) );
	}
	
	/**
	 * Check a supplied category FURL key to make sure it's not in use
	 *
	 * @return	@e void
	 */
	protected function _checkCategoryKey()
	{
		$value		= IPSText::makeSeoTitle( $this->request['value'] );
		$database	= intval($this->request['database']);
		$parent		= intval($this->request['category_parent_id']);
		
		/* Let this one be empty, as the issue is likely an internal problem and not something the admin did */
		if( !$value )
		{
			$this->returnJsonError( '' );
		}

		$cat	= $this->DB->buildAndFetch( array( 'select' => 'category_id', 'from' => 'ccs_database_categories', 'where' => "category_database_id={$database} AND category_furl_name='{$value}' AND category_parent_id={$parent}" ) );
		
		if( $cat['category_id'] )
		{
			$this->returnJsonArray( array( 'value' => $value . '_' . time() ) );
		}
		else
		{
			$this->returnJsonArray( array( 'value' => $value ) );
		}
	}
	
	/**
	 * Check a supplied database key to make sure it's not in use
	 *
	 * @return	@e void
	 */
	protected function _checkKey()
	{
		$value		= $this->request['value'];
		
		/* Let this one be empty, as the issue is likely an internal problem and not something the admin did */
		if( !$value )
		{
			$this->returnJsonError( '' );
		}
		
		$value	= strtolower( preg_replace( "/[^a-zA-Z0-9]/sm", '_', $value ) );
		
		$db		= $this->DB->buildAndFetch( array( 'select' => 'database_id', 'from' => 'ccs_databases', 'where' => "database_key='{$value}'" ) );
		
		if( $db['database_id'] )
		{
			$this->returnJsonArray( array( 'value' => $value . '_' . time() ) );
		}
		else
		{
			$this->returnJsonArray( array( 'value' => $value ) );
		}
	}
}