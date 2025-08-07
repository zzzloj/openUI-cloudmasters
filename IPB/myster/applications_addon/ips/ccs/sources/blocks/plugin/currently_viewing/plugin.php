<?php

/**
 * Users currently viewing page plugin
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $ 
 * @since		1st March 2009
 */

class plugin_currently_viewing implements pluginBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $registry;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags()
	{
		return array(
					$this->lang->words['block_plugin__generic']	=> array( 
																		array( '&#36;title', $this->lang->words['block_custom__title'] ) ,
																		),	
						
					$this->lang->words['block_plugin_ou_active']	=> array(
																		array( '&#36;active', $this->lang->words['block_plugin__cvgroup'], 
																			array(
																				array( "&#36;active['stats']['bots']", $this->lang->words['col__special_cv_statsbots'] ),
																				array( "&#36;active['stats']['guests']", $this->lang->words['col__special_cv_statsguests'] ),
																				array( "&#36;active['stats']['members']", $this->lang->words['col__special_cv_statsmems'] ),
																				array( "&#36;active['stats']['anon']", $this->lang->words['col__special_cv_statsanon'] ),
																				array( "&#36;active['stats']['total']", $this->lang->words['col__special_cv_statstotal'] ),
																				array( "&#36;active['rows']['bots']", $this->lang->words['col__special_cv_rowsbots'] ),
																				array( "&#36;active['rows']['guests']", $this->lang->words['col__special_cv_rowsguests'] ),
																				array( "&#36;active['rows']['members']", $this->lang->words['col__special_cv_rowsmems'] ),
																				array( "&#36;active['rows']['anon']", $this->lang->words['col__special_cv_rowsanon'] ),
																				array( "&#36;active['names']", $this->lang->words['col__special_cv_allnames'] ),
																				)
																			),
																		),
					);
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'currently_viewing',
					'name'			=> $this->lang->words['plugin_name__currently_viewing'],
					'description'	=> $this->lang->words['plugin_description__currently_viewing'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__currently_viewing',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		return array();
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{
		return array( true, $data );
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
		//-----------------------------------------
		// If we are in ACP, just return
		//-----------------------------------------

		if( IN_ACP )
		{
			return '';
		}

		//-----------------------------------------
		// Determine filters
		//-----------------------------------------
		
		$_filters	= array();

		$session	= $this->registry->member()->sessionClass()->returnCurrentSession();
		
		if( count($session) )
		{
			/**
			 * @todo	in_error=0 check is now included by default in session_api, we should remove it at some point (IPB 3.4?)
			 */
			$_filters['addWhere'][]	= "s.in_error=0";
			$_filters['addWhere'][]	= "s.location_1_type='{$session['location_1_type']}'";
			$_filters['addWhere'][]	= "s.location_2_type='{$session['location_2_type']}'";
			$_filters['addWhere'][]	= "s.location_3_type='{$session['location_3_type']}'";
			$_filters['addWhere'][]	= "s.location_1_id='{$session['location_1_id']}'";
			$_filters['addWhere'][]	= "s.location_2_id='{$session['location_2_id']}'";
			$_filters['addWhere'][]	= "s.location_3_id='{$session['location_3_id']}'";
		}
		
		//-----------------------------------------
		// Get sessions
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
		$sessions    = new $classToLoad( $this->registry );
		
		$activeUsers = $sessions->getUsersIn( $this->registry->getCurrentApplication(), $_filters );

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		ob_start();
 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $activeUsers );
 		ob_end_clean();
 		return $_return;
	}
}