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
	 * Fetch output
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
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch( $this->request['workact'] )
		{
			default:
				$this->addFulltextIndexes();
			break;
		}
		
		//-----------------------------------------
		// Return false if there's more to do
		//-----------------------------------------

		$this->request['workact']	= '';
		
		return true;
	}

	/**
	 * Add fulltext indexes to database title and content fields
	 *
	 * @return	@e void
	 */
	public function addFulltextIndexes()
	{
		$this->DB->build( array( 'select' => 'database_id, database_database, database_field_title, database_field_content', 'from' => 'ccs_databases' ) );
		$outer	= $this->DB->execute();

		while( $r = $this->DB->fetch($outer) )
		{
			$title	= $this->DB->buildAndFetch( array( 'select' => 'field_id, field_type', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . intval( str_replace( 'field_', '', $r['database_field_title'] ) ) ) );

			if( $title['field_id'] AND in_array( $title['field_type'], array( 'input', 'textarea', 'editor' ) ) )
			{
				$this->DB->addFulltextIndex( $r['database_database'], 'field_' . $title['field_id'] );
			}

			$content	= $this->DB->buildAndFetch( array( 'select' => 'field_id, field_type', 'from' => 'ccs_database_fields', 'where' => 'field_id=' . intval( str_replace( 'field_', '', $r['database_field_content'] ) ) ) );

			if( $content['field_id'] AND in_array( $content['field_type'], array( 'input', 'textarea', 'editor' ) ) )
			{
				$this->DB->addFulltextIndex( $r['database_database'], 'field_' . $content['field_id'] );
			}
		}

		$this->registry->output->addMessage( "Fulltext search indexes added to databases" );
	}
}