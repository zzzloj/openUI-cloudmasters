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
		
		$this->_fixTemplates();
		
		$this->request['workact']	= '';
		return true;
	}
	
	/**
	 * Run SQL files
	 * 
	 * @return	@e void
	 */
	public function _fixTemplates()
	{
		$templates	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates' ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$cache	= array(
							'cache_type'	=> 'template',
							'cache_type_id'	=> $r['template_id'],
							);
	
			require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );/*noLibHook*/
			$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
			$cache['cache_content']	= $engine->convertHtmlToPhp( 'template_' . $r['template_key'], '', $r['template_content'], '', false, true );
			
			$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$r['template_id']}" ) );
			
			if( $hasIt['cache_id'] )
			{
				$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$r['template_id']}" );
			}
			else
			{
				$this->DB->insert( 'ccs_template_cache', $cache );
			}
	
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$_pagesClass = new $classToLoad( $this->registry );
			$_pagesClass->recacheTemplateCache( $engine );
		}
	}	
}
