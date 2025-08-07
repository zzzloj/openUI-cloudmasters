<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->_addFields();
		
		$this->request['workact']	= '';
		return true;
	}
	
	/**
	 * Add fields to existing custom databases
	 *
	 * @return	@e void
	 */
	protected function _addFields()
	{
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$this->DB->addField( $r['database_database'], "record_approved", "TINYINT", "'1' NOT NULL" );
			$this->DB->addField( $r['database_database'], "record_pinned", "TINYINT", "'1' NOT NULL" );
			$this->DB->addField( $r['database_database'], "record_views", "MEDIUMINT", "'0' NOT NULL" );
			
			$this->DB->addIndex( $r['database_database'], "record_approved", "record_approved" );
			$this->DB->addIndex( $r['database_database'], "category_id", "category_id" );
		}
	}
}