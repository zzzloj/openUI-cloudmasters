<?php

/**
 * Show welcome block widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8868 $ 
 * @since		1st March 2009
 */

class plugin_welcome_block implements pluginBlockInterface
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
						
					$this->lang->words['block_plugin_wb_user']	=> array(
																		array( '&#36;data', $this->lang->words['block_plugin__wbud'], 
																			array(
																				array( "&#36;data['topics']", $this->lang->words['col__special_topics'] ),
																				array( "&#36;data['posts']", $this->lang->words['col__special_posts'] ),
																				array( "&#36;data['avg_posts']", $this->lang->words['col__special_avg_posts'] ),
																				array( "&#36;data['new_topics']", $this->lang->words['col__special_new_topics'] ),
																				array( "&#36;data['new_posts']", $this->lang->words['col__special_new_posts'] ),
																				array( "&#36;data['new_friends']", $this->lang->words['col__special_new_friends'] ),
																				array( "&#36;data['new_comments']", $this->lang->words['col__special_new_comments'] ),
																				array( "&#36;data['group']", $this->lang->words['col__special_group'] ),
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
					'key'			=> 'welcome_block',
					'name'			=> $this->lang->words['plugin_name__welcome_block'],
					'description'	=> $this->lang->words['plugin_description__welcome_block'],
					'hasConfig'		=> false,
					'templateBit'	=> 'block__welcome_block',
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
		$data	= array();

		//-----------------------------------------
		// Reset login form lang if needed
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			$uses_name	= $uses_email	= false;
			
			foreach( $this->cache->getCache('login_methods') as $method )
			{
				if( $method['login_user_id'] == 'username' )
				{
					$uses_name	= true;
				}
				
				if( $method['login_user_id'] == 'email' )
				{
					$uses_email	= true;
				}
			}
		
			if( $uses_name AND $uses_email )
			{
				$this->lang->words['enter_name']	= $this->lang->words['welcome_name_and_email'];
			}
			else if( $uses_email )
			{
				$this->lang->words['enter_name']	= $this->lang->words['welcome_useremail'];
			}
		}
		else
		{
			$topics			= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'topics', 'where' => 'starter_id=' . $this->memberData['member_id'] ) );
			$posts			= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'posts', 'where' => 'author_id=' . $this->memberData['member_id'] ) );
			$newTopics		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'topics', 'where' => 'start_date > ' . $this->memberData['last_visit'] ) );
			$newPosts		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'posts', 'where' => 'post_date > ' . $this->memberData['last_visit'] ) );
			$newFriends		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'profile_friends', 'where' => 'friends_approved=0 AND friends_friend_id=' . $this->memberData['member_id'] ) );
			$newComments	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'member_status_updates', 'where' => 'status_approved=0 AND status_member_id=' . $this->memberData['member_id'] . ' AND status_author_id<>' . $this->memberData['member_id'] ) );

			$board_posts	= $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'];
			$_posts_day		= 0;
			
			if ( $posts['total'] and $board_posts  )
			{
				$_posts_day = round( $posts['total'] / ( ( time() - $this->memberData['joined']) / 86400 ), 2 );
		
				# Fix the issue when there is less than one day
				$_posts_day = ( $_posts_day > $posts['total'] ) ? $posts['total'] : $_posts_day;
			}
			
			$_posts_day = floatval($_posts_day);
			
			//-----------------------------------------
			// Get the data not already available...
			//-----------------------------------------
			
			$data	= array(
							'topics'		=> $topics['total'],		// Total topics count
							'posts'			=> $posts['total'],			// True total posts count
							'avg_posts'		=> $_posts_day,				// Average daily posts
							'new_topics'	=> $newTopics['total'],		// Topics since your last visit
							'new_posts'		=> $newPosts['total'],		// Posts since your last visit
							'new_friends'	=> $newFriends['total'],	// Pending friend requests
							'new_comments'	=> $newComments['total'],	// Pending comments
							);
		}
		
		$group_cache		= $this->cache->getCache('group_cache');
		$data['group']		= IPSMember::makeNameFormatted( $group_cache[ $this->memberData['member_group_id'] ]['g_title'], $this->memberData['member_group_id'] );
		
		//-----------------------------------------
		// Reputation
		//-----------------------------------------

		if( ! ipsRegistry::isClassLoaded( 'repCache' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', "classReputationCache" );
			ipsRegistry::setClass( 'repCache', new $classToLoad() );
		}

		$this->memberData['pp_reputation_points']	= $this->memberData['pp_reputation_points'] ? $this->memberData['pp_reputation_points'] : 0;
		$this->memberData['author_reputation']		= ipsRegistry::getClass( 'repCache' )->getReputation( $this->memberData['pp_reputation_points'] );

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		ob_start();
 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $data );
 		ob_end_clean();
 		return $_return;
	}
}