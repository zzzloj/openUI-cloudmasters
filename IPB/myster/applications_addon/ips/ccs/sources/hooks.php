<?php
/**
 * @file		hooks.php 	IP.Content hook gateway file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27 Dec 2011
 * $LastChangedDate: 2010-10-14 13:11:17 -0400 (Thu, 14 Oct 2010) $
 * @version		v3.4.5
 * $Revision: 477 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * Ensure app class has been loaded
 */
if( !class_exists('app_class_ccs') )
{
	ipsRegistry::getAppClass( 'ccs' );
}


/**
 * @class		ccsHooks
 * @brief		IP.Content hook gateway file
 */
class ccsHooks
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry		= $registry;
		$this->lang			= $this->registry->getClass('class_localization');
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Menu module hook
	 * 
	 * @param	array		$applications	Applications
	 * @return	@e string	Global template output
	 */
	public function menuBar( $applications )
	{
		if( isset($applications['ccs']) )
		{
			$applications['ccs']['app_link']		= $this->registry->ccsFunctions->returnPageUrl( array( 'page_seo_name' => $this->settings['ccs_default_page'], 'page_id' => 0 ) );
			$applications['ccs']['app_seotitle']	= '';		// Has to be empty, or IP.Board tries to run its FURL routines causing index.php?//page/...
			$applications['ccs']['app_template']	= 'app=ccs';
			$applications['ccs']['app_base']		= 'none';
		}

		return $this->registry->output->getTemplate('ccs_global')->primary_navigation( $this->caches['ccs_menu'], $applications );
	}
}