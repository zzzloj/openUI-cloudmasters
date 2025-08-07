<?php

/**
* Tracker 2.1.0
* 
* Core Extensions Extension
* Last Updated: $Date: 2012-07-01 02:57:53 +0100 (Sun, 01 Jul 2012) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Extensions
* @link			http://ipbtracker.com
* @version		$Revision: 1376 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

$_PERM_CONFIG = array( 'Project' );

// Load the module cache extensions
if ( IPS_APP_COMPONENT == 'tracker' && file_exists( DOC_IPS_ROOT_PATH . 'cache/tracker/coreExtensions.php' ) )
{
	require_once( DOC_IPS_ROOT_PATH . 'cache/tracker/coreExtensions.php' );
}

/**
 * Type: Extension
 * Projects permission mapping class
 * 
 * @package Tracker
 * @subpackage Extensions
 * @since 2.0.0
 */
class trackerPermMappingProject extends tracker_extension_perms_base
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	private
	 * @var		array
	 */
	private $mapping = array(
		'show'		=> 'perm_view',
		'read'		=> 'perm_2',
		'reply'		=> 'perm_3',
		'start'		=> 'perm_4',
		'upload'	=> 'perm_5',
		'download'	=> 'perm_6',
		'fields'	=> 'perm_7'
	);

	/**
	 * Mapping of keys to names
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_names = array(
		'show'     => 'Show Project',
		'read'     => 'Read Issues',
		'reply'    => 'Reply to Issues',
		'start'    => 'Create Issues',
		'upload'   => 'Upload',
		'download' => 'Download',
	);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_colors = array(
		'show'     => '#fff0f2',
		'read'     => '#effff6',
		'reply'    => '#edfaff',
		'start'    => '#f0f1ff',
		'upload'   => '#fffaee',
		'download' => '#ffeef9',
	);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the permission items
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		$this->checkForRegistry();

		$registry  = ipsRegistry::instance();
		$out       = array();
		$projects  = $registry->tracker->projects()->getCache();

		foreach( $projects as $r )
		{
			$perm = $r['perms'];

			$out[ $r['project_id'] ] = array(
				'title'     => $r['depthed_title'],
				'perm_view' => $perm['perm_view'],
				'perm_2'    => $perm['perm_2'],
				'perm_3'    => $perm['perm_3'],
				'perm_4'    => $perm['perm_4'],
				'perm_5'    => $perm['perm_5'],
				'perm_6'    => $perm['perm_6'],
				'perm_7'    => $perm['perm_7'],
				'restrict'  => $r['cat_only'] == 1 ? 'perm_view' : '',
			);
		}

		return $out;
	}
}

/**
 * Type: Extension
 * Permissions base class w/ function to check for Registry
 * 
 * @package Tracker
 * @subpackage Extensions
 * @since 2.0.0
 */
abstract class tracker_extension_perms_base
{
	/**
	 * Loads the tracker library if needed
	 *
	 * @return void
	 * @access protected
	 * @since 1.4.40
	 */
	protected function checkForRegistry()
	{
		if ( ! class_exists( 'app_class_tracker' ) )
		{
			$registry = ipsRegistry::instance();

			require_once( IPSLib::getAppDir('tracker') . '/app_class_tracker.php' );
			new app_class_tracker( $registry );
		}
	}
}

/**
 * Type: Extension
 * Public Sessions class
 * 
 * @package Tracker
 * @subpackage Extensions
 * @since 2.0.0
 */
class publicSessions__tracker
{
	public function getSessionVariables()
	{
		$return_array = array();
		$return_array['location_1_type'] = 'overview';

		if( intval( ipsRegistry::$request['showproject'] ) )
		{
			$return_array['location_1_type'] = 'project';
			$return_array['location_1_id'] = intval( ipsRegistry::$request['showproject'] );
		}

		if( intval( ipsRegistry::$request['showissue'] ) )
		{
			$return_array['location_2_type'] = 'topic';
			$return_array['location_2_id'] = intval( ipsRegistry::$request['showissue'] );
		}
		
		/** bug #18949 fix - Guests are not counted in the Memberlist */
		if( ipsRegistry::$request['section'] == 'projects' )
		{
			$return_array['location_1_type'] = 'project';
			$return_array['location_1_id'] = intval( ipsRegistry::$request['pid'] );
		}

		if( ipsRegistry::$request['section'] == 'issues' )
		{
			$return_array['location_2_type'] = 'topic';
			$return_array['location_2_id'] = intval( ipsRegistry::$request['iid'] );
		}
		/* bug #18949 fix - Guests are not counted in the Memberlist **/

		if( intval( ipsRegistry::$request['newissue'] ) )
		{
			$return_array['location_1_type'] = 'project';
			$return_array['location_1_id'] = intval( ipsRegistry::$request['newissue'] );
			$return_array['location_3_type'] = 'post';
		}

		if( ipsRegistry::$request['do'] == 'postreply' && intval( ipsRegistry::$request['iid'] ) )
		{
			$return_array['location_2_type'] = 'topic';	
			$return_array['location_2_id'] = intval( ipsRegistry::$request['iid'] );
			$return_array['location_3_type'] = 'post';	
		}

		return $return_array;
	}

	public function parseOnlineEntries( $array=array() )
	{
		$return        = array();
		$project_cache = array();
		$topic_cache   = array();
		$topicp_cache  = array();
		$project_ids   = array();
		$topic_ids     = array();

		/* Language */
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_tracker' ), 'tracker' );

		require_once( IPSLib::getAppDir('tracker') . '/sources/classes/library.php' );
		ipsRegistry::setClass( 'tracker', new tracker_core_library( ipsRegistry::instance() ) );
		ipsRegistry::getClass('tracker')->doExecute( ipsRegistry::instance() );
		
		$this->tracker = ipsRegistry::getClass('tracker');
		
		//------------------------
		// LOOP
		//------------------------

		if( is_array( $array ) and count( $array ))
		{
			foreach( $array as $session_id => $session )
			{
				if( $session['current_appcomponent'] != 'tracker' )
				{
					continue;
				}

				if ( $session['location_1_type'] == 'project' && intval($session['location_1_id']) )
				{
					$project_ids[] = intval($session['location_1_id']);
				}

				if ( $session['location_2_type'] == 'topic' && intval($session['location_2_id']) )
				{
					$topic_ids[] = intval($session['location_2_id']);
				}
			}

			// load projects
			if ( count( $project_ids ) > 0 )
			{
				$query_string = "project_id in(".implode( ",", $project_ids ).")";

				ipsRegistry::DB()->build(
					array(
						'select' => 'project_id, title',
						'from'   => 'tracker_projects',
						'where'  => $query_string
					)
				);
				ipsRegistry::DB()->execute();

				while ($r = ipsRegistry::DB()->fetch() )
				{
					$project_cache[$r['project_id']] = $r;
				}
			}

			// load bugs
			if ( count( $topic_ids ) > 0 )
			{
				$extra = "e.issue_id in(".implode( ",", $topic_ids ).")";

				ipsRegistry::DB()->build(
					array(
						'select'   => "e.*, e.title as ititle",
						'from'     => array('tracker_issues' => 'e'),
						'add_join' => array(
							0 => array(
								'select' 	=> 'p.*',
								'from'  	=> array( 'tracker_projects' => 'p' ),
								'where' 	=> "p.project_id=e.project_id",
								'type'  	=> 'left'
							)
						),
						'where'    => $extra
					)
				);
				ipsRegistry::DB()->execute();

				while ( $row = ipsRegistry::DB()->fetch() )
				{
					$topic_cache[$row['issue_id']] = $row;
					$topicp_cache[ $row['project_id'] ] = $row;
				}
			}

			foreach( $array as $session_id => $session )
			{
				if ( $session['current_appcomponent'] == 'tracker' )
				{
					if ( $session['location_1_type'] == 'project' && $this->tracker->projects()->checkPermission( 'read', $session['location_1_id'] ) == TRUE )
					{
						$session['where_link']		= 'app=tracker&amp;showproject='.$session['location_1_id'];
						$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker&amp;showproject=".$session['location_1_id'], 'public' ), IPSText::makeSeoTitle( $project_cache[ $session['location_1_id'] ]['title'] ), 'showproject' );
						$session['where_line']		= ipsRegistry::getClass('class_localization')->words['bt_location_project'];
						$session['where_line_more']	= $project_cache[ $session['location_1_id'] ]['title'];
					}
					else if ( $session['location_2_type'] == 'topic' && $this->tracker->projects()->checkPermission( 'read', $topic_cache[$session['location_2_id']]['project_id'] ) )
					{
						// Private issue
						if ( $topic_cache[ $session['location_2_id'] ]['module_privacy'] == 1 )
						{
							$this->privacy 	= $this->tracker->fields()->getFieldByKeyword('privacy');
							$this->project	= $this->tracker->projects()->getProject( $topic_cache[ $session['location_2_id'] ]['project_id'] );
							
							// Is this field on in this project?
							if ( ! isset( $this->project['fields'][ $this->privacy['field_id'] ] ) )
							{
								$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker&amp;showissue=".$session['location_2_id'], 'public' ), IPSText::makeSeoTitle( $topic_cache[$session['location_2_id']]['ititle'] ), 'showissue' );
								$session['where_link']		= 'app=tracker&amp;showissue='.$session['location_2_id'];
								$session['where_line']		= ipsRegistry::getClass('class_localization')->words['bt_location_topic'];
								$session['where_line_more']	= $topic_cache[$session['location_2_id']]['ititle'];
							}
							else
							{
								// Do we have permission?
								if ( $this->tracker->fields()->checkPermission( $this->project, 'privacy', 'show' ) )
								{
									$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker&amp;showissue=".$session['location_2_id'], 'public' ), IPSText::makeSeoTitle( $topic_cache[$session['location_2_id']]['ititle'] ), 'showissue' );
									$session['where_link']		= 'app=tracker&amp;showissue='.$session['location_2_id'];
									$session['where_line']		= ipsRegistry::getClass('class_localization')->words['bt_location_topic'];
									$session['where_line_more']	= $topic_cache[$session['location_2_id']]['ititle'];
								}
								else
								{
									$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker", 'public' ), '', 'app=tracker' );
									$session['where_link'] = "app=tracker";
									$session['where_line'] = ipsRegistry::getClass('class_localization')->words['bt_location_overview'];
								}
							}
						}
						else
						{
							$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker&amp;showissue=".$session['location_2_id'], 'public' ), IPSText::makeSeoTitle( $topic_cache[$session['location_2_id']]['ititle'] ), 'showissue' );
							$session['where_link']		= 'app=tracker&amp;showissue='.$session['location_2_id'];
							$session['where_line']		= ipsRegistry::getClass('class_localization')->words['bt_location_topic'];
							$session['where_line_more']	= $topic_cache[$session['location_2_id']]['ititle'];
						}
					}
					else
					{
						$session['_whereLinkSeo']		= ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker", 'public' ), '', 'app=tracker' );
						$session['where_link'] = "app=tracker";
						$session['where_line'] = ipsRegistry::getClass('class_localization')->words['bt_location_overview'];
					}
				}

				$return[ $session_id ] = $session;
			}
		}

		return $return;
	}
}

/**
 * Type: Extension
 * Item marking class
 * 
 * @package Tracker
 * @subpackage Extensions
 * @since 2.0.0
 */
class itemMarking__tracker
{
	/**
	* Field Convert Data Remap Array
	*
	* This is where you can map your app_key_# numbers to application savvy fields
	* 
	* @access	private
	* @var		array
	*/
	private $_convertData = array( 'forumID' => 'item_app_key_1' );

	/**#@+
	* Registry Object Shortcuts
	*
	* @access	protected
	* @var		object
	*/
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/

	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	* Convert Data
	* Takes an array of app specific data and remaps it to the DB table fields
	*
	* @access	public
	* @param	array
	* @return	array
	*/
	public function convertData( $data )
	{
		$_data = array();

		foreach( $data as $k => $v )
		{
			if ( isset($this->_convertData[$k]) )
			{
				# Make sure we use intval here as all 'forum' app fields
				# are integers.
				$_data[ $this->_convertData[ $k ] ] = intval( $v );
			}
			else
			{
				$_data[ $k ] = $v;
			}
		}

		return $_data;
	}

	/**
	* Fetch unread count
	*
	* Grab the number of items truly unread
	* This is called upon by 'markRead' when the number of items
	* left hits zero (or less).
	* 
	*
	* @access	public
	* @param	array 	Array of data
	* @param	array 	Array of read itemIDs
	* @param	int 	Last global reset
	* @return	integer	Last unread count
	*/
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{
		$count     = 0;
		$lastItem  = 0;
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );

		if ( $data['forumID'] )
		{
			$_count = $this->DB->buildAndFetch(
				array( 
					'select' => 'COUNT(*) as cnt, MIN(last_post) as lastItem',
					'from'   => 'tracker_issues',
					'where'  => "project_id=" . intval( $data['forumID'] ) . " AND issue_id NOT IN(".implode(",",array_keys($readItems)).") AND last_post > ".intval($lastReset)
				)
			);

			$count    = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );
		}

		return array(
			'count'    => $count,
			'lastItem' => $lastItem
		);
	}
}

?>