<?php

/**
* Tracker 2.1.0
* 
* Class setup file
* Last Updated: $Date: 2012-11-03 14:13:09 +0000 (Sat, 03 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1391 $
*/

class app_class_tracker
{
	/**
	* Constructor
	*
	* @access	public
	* @param	object	ipsRegistry
	* @return	void
	*/
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();

		if ( ! $this->registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $this->registry );
	
			$this->registry->setClass( 'tracker', $this->tracker );
		}
	}

	/**
	* Do some set up after ipsRegistry::init()
	*
	* @access	public
	*/
	public function afterOutputInit( ipsRegistry $registry )
	{
		/* Admin CSS for Alex */
		if ( IN_ACP )
		{
			$this->globalHtml				= $this->registry->output->loadTemplate('cp_skin_tracker_global');
			$this->registry->output->html  .= $this->globalHtml->addGlobalJavascriptAndCSS();
		}

		if( $_GET['autocom'] == 'tracker' or $_GET['automodule'] == 'tracker' )
		{
			$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=tracker", 'false', true, 'app=tracker' );
		}
	}
}
?>