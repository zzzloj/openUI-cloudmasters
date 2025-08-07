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
	 * @access	protected
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * Fetch output
	 * 
	 * @access	public
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
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
		
		//-----------------------------------------
		// Find out "content" fields for all databases
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			if( strpos( $r['database_field_content'], 'field_' ) === 0 )
			{
				$_id	= str_replace( 'field_', '', $r['database_field_content'] );
				
				$this->DB->update( 'ccs_database_fields', array( 'field_topic_format' => '{value}' ), 'field_id=' . $_id );
			}
		}
		
		//-----------------------------------------
		// Return false if there's more to do
		//-----------------------------------------

		return true;
	}
}
