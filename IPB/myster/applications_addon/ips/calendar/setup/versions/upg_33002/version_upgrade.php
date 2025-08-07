<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.4.5
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

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
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
			case 'minicaldel':
				$this->removeMinicalCaches();
			break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Remove excessive minical caches from earlier versions
	 * 
	 * @return	@e void
	 */
	public function removeMinicalCaches()
	{
		/* Init */
		$st		= intval($this->request['st']);
		$did	= 0;
		$each	= 500;
		
		/* Grab the minicals */
		$this->DB->build( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key LIKE 'minical_%'", 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$did++;
			
			$caches[] = $r['cs_key'];
		}
		
		if( count($caches) )
		{
			$this->cache->deleteCache( $caches );
		}

		/* Show message and redirect */
		if( $did > 0 )
		{
			$this->request['st']		= $st + $did;
			$this->request['workact']	= 'minicaldel';
			
			$this->registry->output->addMessage( "Up to {$this->request['st']} minical caches removed so far..." );
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= '';
			
			$this->registry->output->addMessage( "All minical caches removed..." );
		}

		/* Next Page */
		return;
	}
}