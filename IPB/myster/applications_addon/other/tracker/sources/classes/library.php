<?php

/**
* Tracker 2.1.0
*
* Core functions library
* Last Updated: $Date: 2013-02-20 00:52:56 +0000 (Wed, 20 Feb 2013) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1407 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

/**
 * Type: Library
 * Main core class available at $this->registry->tracker
 * All other core classes are accessible through the function of the
 * same name, which will automatically load and create an
 * instance of the class if one isn't already created.
 *
 * @package Tracker
 * @subpackage Core
 * @since 1.0.0
 */
class tracker_core_library extends ipsCommand
{
	/**
	 * Version string for copyright printout
	 *
	 * @access private
	 * @var string
	 * @since 1.3.0
	 */
	private $version = '2.1.0';

	/**
	 * Email core class instance
	 *
	 * @access protected
	 * @var tracker_core_email
	 * @since 2.0.0
	 */
	protected $email;
	/**
	 * Facebook core class instance
	 *
	 * @access protected
	 * @var tracker_core_facebook
	 * @since 2.0.0
	 */
	protected $facebook;
	/**
	 * Fields core class instance
	 *
	 * @access protected
	 * @var tracker_core_fields
	 * @since 2.0.0
	 */
	protected $fields;
	/**
	 * Issues core class instance
	 *
	 * @access protected
	 * @var tracker_core_issues
	 * @since 2.0.0
	 */
	protected $issues;
	/**
	 * Moderators core class instance
	 *
	 * @access protected
	 * @var tracker_core_moderators
	 * @since 2.0.0
	 */
	protected $moderators;
	/**
	 * Modules core class instance
	 *
	 * @access protected
	 * @var tracker_core_modules
	 * @since 2.0.0
	 */
	protected $modules;
	/**
	 * IPS Core permissions system instance
	 *
	 * @access protected
	 * @var classPublicPermissions
	 * @since 2.0.0
	 */
	protected $perms;
	/**
	 * Post core class instance
	 *
	 * @access protected
	 * @var tracker_core_post
	 * @since 2.0.0
	 */
	protected $post;
	/**
	 * Projects core class instance
	 *
	 * @access protected
	 * @var tracker_core_projects
	 * @since 1.0.0
	 */
	protected $projects;

	/**
	 * Main output string for public application HTML output.
	 * It is set directly in the modules just before calling
	 * sendOutput()
	 *
	 * @access public
	 * @var string
	 * @since 1.1.0
	 */
	public $output = '';
	/**
	 * Main page title string for public application HTML output.
	 * It is set directly in the modules just before calling
	 * sendOutput()
	 *
	 * @access public
	 * @var string
	 * @since 1.1.0
	 */
	public $pageTitle = '';
	/**
	 * Array where active user data is stored for the skins to use
	 *
	 * @access public
	 * @var array
	 * @since 1.3.0
	 */
	public $totalOnline = array();
	/**
	 * Array where active cache classes are stored
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $cacheClasses = array();

	/**
	 * Initial function.  Called by execute function in ipsCommand
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 1.3.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Registry */
		$this->registry =& $registry;

		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_tracker' ) );
	}

	//-------------------------------------------------------------------------------------//
	//                             Library Loader Functions                                //
	//-------------------------------------------------------------------------------------//

	/**
	 * If needed, creates an instance of and return the core email library
	 *
	 * @return tracker_core_email the core email library
	 * @access public
	 * @since 2.0.0
	 */
	public function email()
	{
		$out = NULL;

		if ( ! is_object( $this->email ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/email.php', 'tracker_core_email', 'tracker' );

			$this->email = new $classToLoad();
			$this->email->execute( $this->registry );
		}

		$out = $this->email;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core facebook library
	 *
	 * @return tracker_core_facebook the core facebook library
	 * @access public
	 * @since 2.0.0
	 */
	public function facebook()
	{
		$out = NULL;

		if ( ! is_object( $this->facebook ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/facebook.php', 'tracker_core_facebook', 'tracker');

			$this->facebook = new $classToLoad();
			$this->facebook->execute( $this->registry );
		}

		$out = $this->facebook;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core fields library
	 *
	 * @return tracker_core_fields the core fields library
	 * @access public
	 * @since 2.0.0
	 */
	public function fields()
	{
		$out = NULL;

		$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('tracker') . '/sources/classes/fields.php', 'tracker_core_fields', 'tracker');

		$this->fields = call_user_func( array( $classToLoad, 'instance' ) );

		$out = $this->fields;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core issues library
	 *
	 * @return tracker_core_issues the core issues library
	 * @access public
	 * @since 2.0.0
	 */
	public function issues()
	{
		$out = NULL;

		if ( ! is_object( $this->issues ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/issues.php', 'tracker_core_issues', 'tracker');

			$this->issues = new $classToLoad();
			$this->issues->execute( $this->registry );
		}

		$out = $this->issues;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core moderators library
	 *
	 * @return tracker_core_moderators the core moderators library
	 * @access public
	 * @since 2.0.0
	 */
	public function moderators()
	{
		$out = NULL;

		if ( ! is_object( $this->moderators ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/moderators.php', 'tracker_core_moderators', 'tracker');

			$this->moderators = new $classToLoad();
			$this->moderators->execute( $this->registry );
		}

		$out = $this->moderators;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core modules library
	 *
	 * @return tracker_core_modules the core modules library
	 * @access public
	 * @since 2.0.0
	 */
	public function modules()
	{
		$out = NULL;

		if ( ! is_object( $this->modules ) )
		{
			$this->modules = new tracker_core_modules();
			$this->modules->execute( $this->registry );
		}

		$out = $this->modules;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the IPS permissions class
	 *
	 * @return classPublicPermission the IPS permissions class
	 * @access public
	 * @since 2.0.0
	 */
	public function perms()
	{
		$out = NULL;

		if ( ! is_object( $this->perms ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );

			$this->perms = new $classToLoad( $this->registry );
		}

		$out = $this->perms;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core post library
	 *
	 * @return tracker_core_post the core post library
	 * @access public
	 * @since 2.0.0
	 */
	public function post()
	{
		$out = NULL;

		if ( ! is_object( $this->post ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/post.php', 'tracker_core_post', 'tracker' );

			$this->post = new $classToLoad();
			$this->post->execute( $this->registry );
		}

		$out = $this->post;

		return $out;
	}

	/**
	 * If needed, creates an instance of and return the core projects library
	 *
	 * @return tracker_core_projects the core projects library
	 * @access public
	 * @since 2.0.0
	 */
	public function projects()
	{
		$out = NULL;

		if ( ! is_object( $this->projects ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/projects.php', 'tracker_core_projects', 'tracker');

			$this->projects = new $classToLoad();
			$this->projects->execute( $this->registry );
		}

		$out = $this->projects;

		return $out;
	}

	//-------------------------------------------------------------------------------------//
	//                                Other Core Functions                                 //
	//-------------------------------------------------------------------------------------//

	/**
	* Checks for offline status and adds Tracker to the breadcrumb
	*
	* @return void
	* @access public
	* @since 1.3.0
	*/
	public function addGlobalNavigation()
	{
		/* Tracker online? */
		if ( ! $this->settings['tracker_is_online'] && ! $this->memberData['g_tracker_view_offline'] )
		{
			$this->registry->output->showError( $this->settings['tracker_offline_message'] );
		}

		$this->registry->output->addNavigation( IPSLib::getAppTitle('tracker'), 'app=tracker', 'tracker', 'app=tracker' );
	}

	/**
	 * Maintains an array of the cache controller classes for the core and modules
	 *
	 * @param string $name the name of the cache
	 * @param string $module Optional: name of the module OR core by default
	 * @return object|null the cache controller class instance
	 * @access public
	 * @since 2.0.0
	 * @todo put a check in to make sure modules are installed, disabled modules are OK to execute
	 */
	public function cache( $name, $module = 'core' )
	{
		$className = 'tracker_';
		$fileName  = 'cache_';
		$out       = NULL;

		if ( $module != 'core' )
		{
			$className .= 'module_' . $module . '_cache_' . $name;
		}
		else
		{
			$className .= 'core_cache_' . $name;
		}

		$fileName  .= $name . '.php';

		if ( ! isset( $this->cacheClasses[ $className ] ) || ! is_object( $this->cacheClasses[ $className ] ) )
		{
			if ( $module != 'core' )
			{
				if ( $this->modules()->moduleIsInstalled( $module, FALSE ) )
				{
					require_once( $this->modules()->getModuleFolder( $module ) . 'sources/' . $fileName );
				}
			}
			else
			{
				if ( $name == 'projects' )
				{
					require_once( IPSLib::getAppDir('tracker') . '/sources/classes/projects.php' );
				}
				else if ( $name == 'moderators' )
				{
					require_once( IPSLib::getAppDir('tracker') . '/sources/classes/moderators.php' );
				}
				else if ( $name == 'field_templates' || $name == 'fields' )
				{
					require_once( IPSLib::getAppDir('tracker') . '/sources/classes/fields.php' );
				}
				else if ( $name == 'modules' ) {}
				else
				{
					require_once( IPSLib::getAppDir('tracker') . '/sources/classes/cache_' . $name . '.php' );
				}
			}

			if ( class_exists( $className ) )
			{
				$this->cacheClasses[ $className ] =  new $className();
				$this->cacheClasses[ $className ]->execute( $this->registry );
			}
		}

		$out = $this->cacheClasses[ $className ];

		return $out;
	}

	/**
	* Generate the active user data, storing the information in LANG or returning html
	*
	* @param string $type type of display: 'overview', 'project', or 'issue'
	* @param int $id project or issue ID if applicable
	* @return string the html code
	* @access public
	* @since 1.1.0
	*/
	public function generateActiveUsers( $type='overview', $id=0 )
	{
		$html = '';

		/* Show us the correct active users */
		switch( $type )
		{
			case 'overview':
				$check = $this->settings['tracker_show_active'];
				break;
			case 'project':
				$check = ! $this->settings['tracker_au_project_index'];
				break;
			case 'issue':
				$check = ! $this->settings['tracker_au_topic'];
				break;
		}

		$active = array(
			'TOTAL'   => 0,
			'NAMES'   => array(),
			'GUESTS'  => 0,
			'MEMBERS' => 0,
			'ANON'    => 0,
		);

		$statsHtml	= '';

		if ( $check && $this->settings['show_active'] && $this->memberData['gbw_view_online_lists'] )
		{
			/* Get the users from the database */
			$cutOff	= $this->settings['tracker_au_cutoff'] ? $this->settings[ 'tracker_au_cutoff' ] * 60 : 900;
			$time	= time() - $cutOff;
			$rows	= array();
			$arTime = time();

			if ( $this->memberData['member_id'] )
			{
				$rows = array(
					$arTime.'.'.md5( microtime() ) => array(
						'id'           => 0,
						'login_type'   => substr( $this->memberData['login_anonymous'], 0, 1),
						'running_time' => $arTime,
						'seo_name'     => $this->memberData['members_seo_name'],
						'member_id'    => $this->memberData['member_id'],
						'member_name'  => $this->memberData['members_display_name'],
						'member_group' => $this->memberData['member_group_id']
					)
				);
			}

			$queryFrom  = array( 'sessions' => 's' );
			$queryWhere = '';
			$queryAdd   = '';

			switch( $type )
			{
				case 'project':
					$queryWhere = " AND (s.location_1_type='project' OR s.location_2_type='topic') AND (s.location_1_id=" . $id . ' OR i.project_id=' . $id . ')';
					$queryAdd   = array(
						0 => array(
							'select'   => 'i.project_id',
							'from'     => array( 'tracker_issues' => 'i' ),
							'where'    => 's.location_2_id = i.issue_id',
							'type'     => 'left'
						)
					);
					break;
				case 'issue':
					$queryWhere = " AND s.location_2_type='topic' AND s.location_2_id=".$id;
					break;
			}

			$this->DB->build(
				array(
					'select'   => 's.id, s.member_id, s.member_name, s.seo_name, s.login_type, s.running_time, s.member_group, s.uagent_type',
					'from'     => array( 'sessions' => 's' ),
					'add_join' => $queryAdd,
					'where'    => "s.running_time > $time AND s.current_appcomponent='tracker'" . $queryWhere,
				)
			);
			$this->DB->execute();

			while ( $r = $this->DB->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}

			krsort( $rows );

			/* Cache the members to avoid duplicates */
			$cached = array();

			foreach( $rows as $result )
			{
				$last_date = $this->registry->getClass('class_localization')->getTime( $result['running_time'] );

				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				if ( isset( $result['uagent_type'] ) && $result['uagent_type'] == 'search' )
				{
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------

					if ( ! $cached[ $result['member_name'] ] )
					{
						if ( $this->settings['spider_anon'] )
						{
							if ( $this->memberData['g_access_cp'] )
							{
								$active['NAMES'][] = $result['member_name'];
							}
						}
						else
						{
							$active['NAMES'][] = $result['member_name'];
						}

						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------

						$active['GUESTS']++;
					}
				}
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				else if ( ! $result['member_id'] OR ! $result['member_name'] )
				{
					$active['GUESTS']++;
				}
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = IPSMember::makeNameFormatted( $result['member_name'], $result['member_group'] );

						$p_start = '';
						$p_end   = '';

						if ( $type != 'overview' AND strstr( $result['location_3_type'], 'post' ) )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
						}

						if ( ! $this->settings['disable_anonymous'] AND $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
							{
								$active['NAMES'][] = "$p_start" . IPSMember::makeProfileLink( $result['member_name'], $result['member_id'], $result['seo_name'], $last_date ) . "*$p_end";
							}
							$active['ANON']++;
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = "$p_start" . IPSMember::makeProfileLink( $result['member_name'], $result['member_id'], $result['seo_name'], $last_date ) . "$p_end";
						}
					}
				}
			}

			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			$this->totalOnline = $active;
		}

		return $active;
	}

	/**
	* Prototypes template() function doesn't like any whitespace,
	* this is fine when Alex is developing it but it makes for horrible HTML
	* that is all on one line.
	*
	* This function loads our standard templates (tabs/newlines/double-quotes etc.)
	* and strips them for use in javascript templates
	*
	* @param string $content the content to be fixed
	* @return string Output ready to use for javascript templates
	* @access public
	* @since 2.0.0
	*/
	public function parseJavascriptTemplate( $content='' )
	{
		$out = '';

		/* Make sure javascript doesn't complain! */
		$out = str_replace( '"', "'", $content );
		$out = str_replace( "\n", '', $out );
		$out = str_replace( "\t", '', $out );
		$out = str_replace( "\r", '', $out );

		/* Return the happy HTML */
		return $out;
	}

	/**
	* Generate copyright, load CSS, load JS, and print output to screen.
	* $output and $pageTitle must already be set before use
	*
	* @return void [HTML output sent to screen using registry output class]
	* @access public
	* @since 1.1.0
	*/
	public function sendOutput()
	{
		// Global function
		$this->pageHeaderHTML = $this->registry->output->getTemplate('tracker_global')->includeCSS() . "\n" . $this->pageHeaderHTML;

		// Send to screen
		$this->registry->output->addToDocumentHead( 'raw', $this->pageHeaderHTML );
		$this->registry->output->setTitle( $this->pageTitle );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

	/**
	* Sorts through the array to find the option that is set
	*
	* @param array $array the array to be sorted and looked through
	* @return object chosen value
	* @access public
	* @since 1.3.0
	*/
	public function selectSetVariable( $array )
	{
		if ( ! is_array( $array ) )
		{
			return -1;
		}

		ksort( $array );

		$chosen = -1;

		foreach ( $array as $v )
		{
			if ( isset( $v ) )
			{
				$chosen = $v;
				break;
			}
		}

		return $chosen;
	}
}

abstract class iptCommand extends ipsCommand
{
	protected $tracker;

	public function makeRegistryShortcuts( ipsRegistry $registry )
	{
		parent::makeRegistryShortcuts( $registry );

		$this->tracker = $this->registry->tracker;

		// Upgrader fix
		if ( IPS_IS_UPGRADER )
		{
			$this->lang =& $this->registry->class_localization;
		}
	}
}

/**
 * Type: Library
 * Core class all caches should extend
 *
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
abstract class tracker_cache extends ipsCommand
{
	/**
	 * Loads the tracker library if needed
	 * THIS MUST BE CALLED FIRST IN THE REBUILD FUNCTION
	 *
	 * @return void
	 * @access protected
	 * @since 2.0.0
	 */
	protected function checkForRegistry()
	{
		if ( ! class_exists( 'app_class_tracker' ) )
		{
			$registry = ipsRegistry::instance();

			require_once( IPSLib::getAppDir('tracker') . '/app_class_tracker.php' );
			new app_class_tracker( $registry );

			$this->execute( $registry );
		}
	}

	/**
	 * Returns the full cache
	 *
	 * @return array the cache
	 * @access public
	 * @since 2.0.0
	 */
	public abstract function getCache();
	/**
	 * Rebuild the entire cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public abstract function rebuild();
}

/**
 * Type: Library
 * Module system global functions
 *
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_modules extends iptCommand
{
	/**
	 * The processed modules cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $modules;

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
		$this->modules = $this->tracker->cache('modules')->getCache();
	}

	/**
	 * Get the full module cache
	 *
	 * @return array the cache
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		return $this->modules;
	}

	/**
	 * Get a module from the cache
	 *
	 * @param string $module the name of the module
	 * @return array the module
	 * @access public
	 * @since 2.0.0
	 */
	public function getModule( $module = NULL )
	{
		$out = NULL;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				if ( $mod['directory'] == $module )
				{
					$out = $mod;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Get the path to a module folder
	 *
	 * @param string $module the name of the module
	 * @return bool
	 * @access public
	 * @since 2.0.0
	 */
	public function getModuleFolder( $module = NULL )
	{
		$modCache = array();
		$out      = IPSLib::getAppDir('tracker') . '/modules/';

		if ( ! is_null( $module ) && is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				if ( $mod['directory'] == $module )
				{
					$modCache = $mod;
					break;
				}
			}

			if ( is_array( $modCache ) && count( $modCache ) > 0 )
			{
				$out .= $modCache['directory'] . '/';
			}
		}

		return $out;
	}

	/**
	 * Get a module from the cache
	 *
	 * @param int $module the id of the module
	 * @return array the module
	 * @access public
	 * @since 2.0.0
	 */
	public function getModuleByID( $module = NULL )
	{
		$out = NULL;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				if ( $mod['module_id'] == $module )
				{
					$out = $mod;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Get the URL to a module folder
	 *
	 * @param string $module the name of the module
	 * @return bool
	 * @access public
	 * @since 2.0.0
	 */
	public function getModuleURL( $module = NULL )
	{
		$modCache = array();
		$out      = $this->settings['board_url'] . '/' . CP_DIRECTORY . '/' . IPSLib::getAppFolder('tracker') . '/tracker/modules/';

		if ( ! is_null( $module ) && is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				if ( $mod['directory'] == $module )
				{
					$modCache = $mod;
					break;
				}
			}

			if ( is_array( $modCache ) && count( $modCache ) > 0 )
			{
				$out .= $modCache['directory'] . '/';
			}
		}

		return $out;
	}

	/**
	 * Loads in an interface file
	 *
	 * @param string $name the name of the interface
	 * @return bool the result
	 * @access public
	 * @since 2.0.0
	 */
	public function loadInterface( $name )
	{
		$out = FALSE;

		if ( $name != '' && file_exists( IPSLib::getAppDir('tracker') . "/sources/interfaces/{$name}" ) )
		{
			require_once( IPSLib::getAppDir('tracker') . "/sources/interfaces/{$name}" );

			$out = true;
		}

		return $out;
	}

	/**
	 * Checks to see if the given module is currently installed and enabled
	 *
	 * @param string $module the name of the module to check
	 * @param bool $checkEnabled will base the check on installation status if FALSE
	 * @return bool
	 * @access public
	 * @since 2.0.0
	 */
	public function moduleIsInstalled( $module, $checkEnabled = TRUE )
	{
		$modCache = array();
		$out      = FALSE;

		if ( is_array( $this->modules ) && count( $this->modules ) > 0 )
		{
			foreach( $this->modules as $id => $mod )
			{
				if ( $mod['directory'] == $module )
				{
					$modCache = $mod;
					break;
				}
			}

			if( is_array( $modCache ) && count( $modCache ) > 0 )
			{
				if ( $checkEnabled == TRUE )
				{
					if ( $modCache['enabled'] == 1 )
					{
						$out = TRUE;
					}
				}
				else
				{
					$out = TRUE;
				}
			}
		}

		return $out;
	}

	/**
	 * Loads in an ACP skin file for a module
	 *
	 * @param string $template template file name
	 * @param string $module Tracker module name
	 * @return object
	 * @access public
	 * @since 2.0.0
	 */
	public function loadTemplate( $template, $module )
	{
		$out  = '';
		$path = $this->getModuleFolder( $module ) . "skin_cp/" . $template . ".php";

		/* Skin file exists? */
		if ( file_exists( $path ) )
		{
			$_pre_load = IPSDebug::getMemoryDebugFlag();

			require_once( $path );

			IPSDebug::setMemoryDebugFlag( "Tracker: Module Template Loaded ($template)", $_pre_load );

			return new $template( $this->registry );
		}
		else
		{
			$this->showError( "Could not locate template: $template", 4100, true );
		}
	}

	/**
	 * Rebuild the module cache
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$this->tracker->cache('modules')->rebuild();
	}
}

/**
 * Type: Cache
 * Modules cache controller
 *
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_cache_modules extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_modules';
	/**
	 * The processed modules cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $moduleCache;

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
		$this->moduleCache = $this->caches[ $this->cacheName ];
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

		if ( is_array( $this->moduleCache ) && count( $this->moduleCache ) > 0 )
		{
			$out = $this->moduleCache;
		}

		return $out;
	}

	/**
	 * Returns requested module
	 *
	 * @param int $id the module_id
	 * @return array the module data
	 * @access public
	 * @since 2.0.0
	 */
	public function getModule( $id )
	{
		$out = array();

		if ( is_array( $this->moduleCache ) && isset( $this->moduleCache[ $id ] ) && is_array( $this->moduleCache[ $id ] ) )
		{
			$out = $this->moduleCache[ $id ];
		}

		return $out;
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
		$moduleCache = array();

		$this->checkForRegistry();

		// If the array is empty then get the projects from the database
		$this->DB->build(
			array(
				'select'  => '*',
				'from'    => 'tracker_module'
			)
		);
		$this->DB->execute();

		// Load the local cache from the database
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				// Setup modules in cache
				$moduleCache[ $r['module_id'] ] = $r;
			}
		}

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $moduleCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->moduleCache = $moduleCache;
	}
}

?>