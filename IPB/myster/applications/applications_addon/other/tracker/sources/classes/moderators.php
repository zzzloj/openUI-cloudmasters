<?php

/**
* Tracker 2.1.0
* 
* Moderators controller class file
* Last Updated: $Date: 2012-10-31 23:00:03 +0000 (Wed, 31 Oct 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1390 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}
// Have to load the cache if it isn't already
if ( ! class_exists('tracker_cache') )
{
	require_once( IPSLib::getAppDir('tracker') . '/sources/classes/library.php' );
}

/**
 * Type: Library
 * Moderators controller class
 * 
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_moderators extends iptCommand
{
	/**
	 * Moderators cache controller reference
	 *
	 * @access protected
	 * @var tracker_core_cache_moderators
	 * @since 2.0.0
	 */
	protected $cache;

	/**
	 * Core module moderator keys
	 *
	 * @access protected
	 * @var array
	 * @static
	 * @since 2.0.0
	 */
	protected static $globalKeys = array(
		'can_edit_posts',
		'can_edit_titles',
		'can_del_posts',
		'can_del_issues',
		'can_lock',
		'can_unlock',
		'can_move',
		'can_merge',
		'can_massmoveprune',
		'is_super'
	);

	/**
	 * Module mod key values
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $keys;

	/**
	 * Permission mappings
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $mappings;

	/**
	 * Built moderators
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $moderators;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache = $this->tracker->cache('moderators');
		$this->loadKeysAndMaps();
	}

	/**
	 * Add an action to the IP.T moderator log
	 * 
	 * USAGE: Will add an entry to the moderator logs table
	 * 
	 * @param string The action performed in plain english (e.g. Closed Issue)
	 * @param string The title of the issue
	 * @param int The project ID the issue is in
	 * @param int The issue ID
	 * @param int The post ID
	 * @return void
	 * @access public
	 * @since 1.1.0
	 */
	public function addLog( $modTitle, $issueTitle, $projectID, $issueID, $postID )
	{
		$this->DB->insert(
			'tracker_logs',
			array(
				'member_id'    => $this->memberData['member_id'],
				'member_name'  => $this->memberData['members_display_name'],
				'ip_address'   => $this->member->ip_address,
				'http_referer' => $HTTP_REFERER,
				'ctime'        => time(),
				'project_id'   => $projectID,
				'issue_id'     => $issueID,
				'post_id'      => $postID,
				'action'       => $modTitle,
				'query_string' => $QUERY_STRING,
				'issue_name'   => $issueTitle
			),

			// Its a shutdown query, so set to true
			true
		);
	}

	/**
	 * Build moderators
	 * 
	 * USAGE: Will build moderator perms for a particular project.
	 * 
	 * @param int The project ID that moderators are needed for
	 * @return void
	 * @access public
	 * @since 1.1.0
	*/
	public function buildModerators( $pid = 0 )
	{
		$moderator = array();
		$project   = array();

		// Only run this if the project is real
		if ( $this->tracker->projects()->isProject( $pid ) && is_array( $this->keys ) && count( $this->keys ) > 0 )
		{
			// Setup the tracker perms set
			foreach( $this->keys as $key )
			{
				$moderator[ $key ] = 0;
			}

			// Get the projects
			$project = $this->tracker->projects()->getProject( $pid );

			// Supermods get full permission
			/*if ( $this->memberData['g_is_supmod'] )
			{
				foreach( $moderator as $key => $value )
				{
					$moderator[ $key ] = 1;
				}
			}
			// If not super then we need to loop through the moderators to build permissions
			else*/ if ( count( $this->cache->getModerators() ) > 0 )
			{
				$our_mgroups = array();

				// Check if we have other groups
				if ( $this->memberData['mgroup_others'] )
				{
					$our_mgroups = explode(",", $this->memberData['mgroup_others']);
				}

				// Add our main group
				$our_mgroups[] = $this->memberData['member_group_id'];

				// Loop through the moderators to find matches
				foreach( $templates = $this->cache->getModerators() as $i => $r )
				{
					if ( ( ( $r['type'] == 'member' && $r['mg_id'] == $this->memberData['member_id'] ) || ( $r['type'] == 'group' && in_array( $r['mg_id'], $our_mgroups ) ) ) && ( $r['is_super'] || $r['project_id'] == $pid ) )
					{
						// If this mod uses a template drop in the template
						if ( $r['mode'] == 'template' && $r['template_id'] > 0 )
						{
							$temp = $this->cache->getTemplate( $r['template_id'] );

							if ( is_array( $temp ) && count( $temp ) > 0 )
							{
								$r = $temp;
							}
						}

						// Loop through the keys to build the permissions
						if ( is_array( $r ) && count( $r ) > 0 )
						{
							foreach( $this->keys as $key )
							{
								if ( isset( $r[ $key ] ) && $r[ $key ] > 0 )
								{
									$moderator[ $key ] = $r[ $key ];
								}
							}
						}
					}
				}
			}

			// Save data locally
			$this->moderators[ $pid ] = $moderator;

			// Add member tracker shortcuts
			$this->member->tracker[ $pid ] = $moderator;

			// Create a mod flag for the user and set it in tracker perms and the global user
			if(count($moderator))
			{
				if(array_filter(array_values($moderator)))
				{
					$this->member->tracker['g_tracker_ismod'] = 1;
					$this->memberData['g_tracker_ismod']      = 1;

					foreach($moderator as $k => $v)
					{
						$this->member->tracker['g_tracker_'.$k]	= $v;
						$this->memberData['g_tracker_'.$k]		= $v;
					}
				}
			}
		}
	}

	/**
	 * Returns the moderator's access to a field feature
	 *
	 * @param int $projectID the project ID being moderated
	 * @param string $module the module keyword
	 * @param string $field the field keyword
	 * @param string $key the permission key to check
	 * @return bool access to feature
	 * @access public
	 * @since 2.0.0
	 */
	public function checkFieldPermission( $projectID, $module, $field, $key )
	{
		$mapKey = $module . '_field_' . $field;
		$out    = FALSE;


		if ( ! isset( $this->moderators[ $projectID ] ) )
		{
			$this->buildModerators( $projectID );
		}

		if ( isset( $this->mappings[ $mapKey ] ) && is_array( $this->mappings[ $mapKey ] ) && isset( $this->mappings[ $mapKey ][ $key ] ) )
		{
			$modKey = $this->mappings[ $mapKey ][ $key ];
			

			if ( isset( $this->moderators[ $projectID ] ) && is_array( $this->moderators[ $projectID ] ) && isset( $this->moderators[ $projectID ][ $modKey ] ) )
			{
				$check = $this->moderators[ $projectID ][ $modKey ];
				
				if ( $check == 1 )
				{
					$out = TRUE;
				}
			}
		}

		return $out;
	}

	/**
	 * Returns the full moderator cache
	 *
	 * @return array the cache
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		return $this->cache->getCache();
	}
	
	/**
	 * Returns requested moderator
	 *
	 * @param int $id the moderate_id
	 * @return array the mod data
	 * @access public
	 * @since 2.0.0
	 */
	public function getModerator( $id )
	{
		return $this->cache->getModerator($id);
	}

	/**
	 * Returns the moderators
	 *
	 * @return array the moderators
	 * @access public
	 * @since 2.0.0
	 */
	public function getModerators()
	{
		return $this->cache->getModerators();
	}
	
	/**
	 * Returns requested template
	 *
	 * @param int $id the moderate_id
	 * @return array the template data
	 * @access public
	 * @since 2.0.0
	 */
	public function getTemplate( $id )
	{
		return $this->cache->getTemplate( $id );
	}

	/**
	 * Returns the moderators
	 *
	 * @return array the moderators
	 * @access public
	 * @since 2.0.0
	 */
	public function getTemplates()
	{
		return $this->cache->getTemplates();
	}

	/**
	 * Loads in permission mappigs
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	protected function loadKeysAndMaps()
	{
		$MAPPING   = array();
		$MODERATOR = array();

		if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/tracker/moderatorKeys.php' ) )
		{
			require_once( DOC_IPS_ROOT_PATH . 'cache/tracker/moderatorKeys.php' );
		}

		if ( count( $MODERATOR ) > 0 )
		{
			$this->keys = array_merge( self::$globalKeys, $MODERATOR );
		}

		$this->mappings = $MAPPING;
	}

	/**
	 * Make dropdown of projects for ACP
	 *
	 * @return array of projects for dropdown
	 * @access public
	 * @since 2.0.0
	 */
	public function makeAdminTemplateDropdown()
	{
		$templates   = array();

		if ( is_array( $this->cache->getTemplates() ) && count( $this->cache->getTemplates() ) > 0 )
		{
			foreach( $temp = $this->cache->getTemplates() AS $id => $template )
			{
				$templates[] = array( $id, $template['name'] );
			}
		}

		return $templates;
	}

	/**
	 * Rebuild the cache fresh using the values from the database
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$this->cache->rebuild();
	}
}

/**
 * Type: Cache
 * Moderators cache controller
 * 
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_cache_moderators extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_mods';
	/**
	 * The processed moderator cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $moderatorCache;
	/**
	 * The moderators from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $moderators;
	/**
	 * The templates from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $templates;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache->getCache( $this->cacheName );
		$this->moderatorCache = $this->caches[ $this->cacheName ];

		$this->processForApplication();
	}

	/**
	 * Returns the processed cache
	 *
	 * @return array the cache with post-DB processing
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		$out = array();

		if ( is_array( $this->moderatorCache ) && count( $this->moderatorCache ) > 0 )
		{
			$out = $this->moderatorCache;
		}

		return $out;
	}

	/**
	 * Returns requested moderator
	 *
	 * @param int $id the moderate_id
	 * @return array the mod data
	 * @access public
	 * @since 2.0.0
	 */
	public function getModerator( $id )
	{
		$out = array();

		if ( is_array( $this->moderators ) && isset( $this->moderators[ $id ] ) && is_array( $this->moderators[ $id ] ) )
		{
			$out = $this->moderators[ $id ];
		}

		return $out;
	}

	/**
	 * Returns the moderators
	 *
	 * @return array the moderators
	 * @access public
	 * @since 2.0.0
	 */
	public function getModerators()
	{
		$out = array();

		if ( is_array( $this->moderators ) && count( $this->moderators ) > 0 )
		{
			$out = $this->moderators;
		}

		return $out;
	}

	/**
	 * Returns requested template
	 *
	 * @param int $id the template_id
	 * @return array the mod data
	 * @access public
	 * @since 2.0.0
	 */
	public function getTemplate( $id )
	{
		$out = array();

		if ( is_array( $this->templates ) && isset( $this->templates[ $id ] ) && is_array( $this->templates[ $id ] ) )
		{
			$out = $this->templates[ $id ];
		}

		return $out;
	}

	/**
	 * Returns moderator templates
	 *
	 * @return array the templates
	 * @access public
	 * @since 2.0.0
	 */
	public function getTemplates()
	{
		$out = array();

		if ( is_array( $this->templates ) && count( $this->templates ) > 0 )
		{
			$out = $this->templates;
		}

		return $out;
	}

	/**
	 * Processes the cache into separate arrays
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	protected function processForApplication()
	{
		$this->moderators = array();
		$this->templates  = array();

		if ( is_array( $this->moderatorCache ) && count( $this->moderatorCache ) > 0 )
		{
			foreach( $this->moderatorCache as $id => $data )
			{
				if ( $data['type'] == "template" )
				{
					unset( $data['template_id'] );
					unset( $data['project_id'] );
					unset( $data['mg_id'] );
					unset( $data['type'] );
					unset( $data['mode'] );

					$this->templates[ $id ]  = $data;
				}
				else
				{
					unset( $data['name'] );

					$this->moderators[ $id ] = $data;
				}
			}
		}
	}

	/**
	 * Rebuild the cache fresh using the values from the database
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$moderatorCache = array();

		$this->checkForRegistry();

		// If the array is empty then get the projects from the database
		$this->DB->build(
			array(
				'select'  => '*',
				'from'    => 'tracker_moderators',
				'order'   => 'type, mg_id'
			)
		);
		$this->DB->execute();

		// Load the local cache from the database
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				// Setup fields in cache
				$moderatorCache[ $r['moderate_id'] ] = $r;
			}
		}

		// Build template usage counts
		if ( count( $moderatorCache ) > 0 )
		{
			// Set base as 0
			foreach( $moderatorCache as $id => $mod )
			{
				if ( $mod['type'] == 'template' )
				{
					$moderatorCache[ $id ]['num_mods'] = 0;
				}
			}

			// Increment for each use
			foreach( $moderatorCache as $id => $mod )
			{
				if ( $mod['template_id'] > 0 )
				{
					$moderatorCache[ $mod['template_id'] ]['num_mods'] += 1;
				}
			}
		}

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $moderatorCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->moderatorCache = $moderatorCache;
		$this->processForApplication();
	}
}

?>