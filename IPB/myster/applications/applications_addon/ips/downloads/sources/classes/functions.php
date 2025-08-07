<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IDM miscellaneous functions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class downloadsFunctions
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Total active users
	 *
	 * @access	protected
	 * @var 	integer
	 */
	protected $total_active			= 0;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Return the screenshot URL.  Takes into account whether screenshots are web-accessible
	 * or need to be loaded through the PHP handler.
	 * 
	 * @param	array	Screenshot file information
	 * @param	bool	Show thumbnail?
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @return	string	URL to screenshot
	 * @note	When $thumb is false, we need to return full URL so the watermark/copyright stamping can be applied correctly
	 */
 	public function returnScreenshotUrl( $file, $thumb=true, $checked=false )
 	{
 		if( !is_array($file) AND intval($file) == $file )
 		{
 			$file	= array( 'file_id' => $file );
 		}

 		if( $this->settings['idm_screenshot_url'] AND $thumb AND $file['record_storagetype'] == 'disk' )
 		{
	 		if( $checked OR $file['record_id'] )
	 		{
	 			if( $file['record_type'] == 'sslink' )
	 			{
	 				return $file['record_location'];
	 			}

	 			$_fileName	= ( $thumb AND $file['record_thumb'] ) ? $file['record_thumb'] : $file['record_location'];
	 			
	 			if( !$_fileName OR !file_exists( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['idm_localsspath'] ) . '/' . $_fileName ) )
	 			{
	 				return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
	 			}
	 			
	 			return rtrim( $this->settings['idm_screenshot_url'], '/' ) . '/' . $_fileName;
	 		}
	 		else if( $file['file_id'] )
	 		{
	 			$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files_records', 'where' => "record_file_id={$file['file_id']} AND record_type IN('ssupload','sslink') AND record_backup=0", 'order' => 'record_default DESC', 'limit' => array( 1 ) ) );
	 			
	 			if( $_record['record_id'] )
	 			{
		 			if( $_record['record_type'] == 'sslink' )
		 			{
		 				return $_record['record_location'];
		 			}
	 			
		 			$_fileName	= ( $thumb AND $_record['record_thumb'] ) ? $_record['record_thumb'] : $_record['record_location'];
		 			
		 			if( !$_fileName OR !file_exists( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['idm_localsspath'] ) . '/' . $_fileName ) )
		 			{
		 				return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
		 			}
	 			
	 				return rtrim( $this->settings['idm_screenshot_url'], '/' ) . '/' . $_fileName;
 				}
 				else
 				{
 					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
 				}
	 		}
	 		else
	 		{
	 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
	 		}
 		}

 		/* If it is a remotely linked screenshot, just return the URL */
		if( $thumb AND $file['record_storagetype'] == 'disk' AND $file['record_id'] AND $file['record_type'] == 'sslink' )
		{
			return $file['record_location'];
		}

 		/* If this is an FTP-stored file and we have a remote URL, use that */
		if( $file['record_storagetype'] == 'ftp' AND $file['record_id'] AND $file['record_type'] == 'ssupload' )
		{
			if( $checked OR $file['record_id'] )
			{
				$_fileName	= ( $thumb AND $file['record_thumb'] ) ? $file['record_thumb'] : $file['record_location'];

				if( $_fileName )
				{
					return rtrim( $this->settings['idm_remotessurl'], '/' ) . '/' . $_fileName;
				}
				else
				{
					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
				}
			}
	 		else if( $file['file_id'] )
	 		{
	 			$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files_records', 'where' => "record_file_id={$file['file_id']} AND record_type IN('ssupload','sslink') AND record_backup=0", 'order' => 'record_default DESC', 'limit' => array( 1 ) ) );
	 			
	 			if( $_record['record_id'] )
	 			{
					$_fileName	= ( $thumb AND $_record['record_thumb'] ) ? $_record['record_thumb'] : $_record['record_location'];

					if( $_fileName )
					{
						return rtrim( $this->settings['idm_remotessurl'], '/' ) . '/' . $_fileName;
					}
					else
					{
						return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
					}
 				}
 				else
 				{
 					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
 				}
	 		}
	 		else
	 		{
	 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
	 		}
		}
 		
 		if( $file['record_id'] )
 		{
 			return $this->registry->output->buildSEOUrl( "app=downloads&amp;module=display&amp;section=screenshot&amp;record=" . $file['record_id'] . '&amp;id=' . $file['record_file_id'] . ( !$thumb ? "&amp;full=1" : '' ), 'public' );
 		}
 		else if( $file['file_id'] )
 		{
 			return $this->registry->output->buildSEOUrl( "app=downloads&amp;module=display&amp;section=screenshot&amp;id=" . $file['file_id'] . ( !$thumb ? "&amp;full=1" : '' ), 'public' );
 		}
 		else
 		{
 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/downloads/no_screenshot.png';
 		}
 	}
	
	/**
	 * Show error message if we're offline
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function checkOnline()
	{
		$groups		= array( $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = $omg;
			}
		}
		
		$offlineGroups	= explode( ',', $this->settings['idm_offline_groups'] );
		
		if( !$this->settings['idm_online'] )
		{
			$accessOffline	= false;
			
			foreach( $groups as $g )
			{
				if( in_array( $g, $offlineGroups ) )
				{
					$accessOffline	= true;
				}
			}
			
			if( !$accessOffline )
			{
				$this->registry->member()->finalizePublicMember();
				$this->registry->getClass('output')->showError( $this->settings['idm_offline_msg'], null, null, 403 );
			}
		}
	}

	/**
	 * Rebuild the pending comment count for a file
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return 	boolean
	 */
	public function rebuildPendingComments( $file_id=0 )
    {
	    if( !$file_id )
	    {
		    return false;
	    }
	    
	    $file_id = intval($file_id);
	    
	    $comments = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as coms',
	    															  'from'	=> 'downloads_comments',
	    															  'where'	=> 'comment_fid=' . $file_id . ' AND comment_open IN (0,-1)'
	    													)		);
	    
	    $comments['coms'] = $comments['coms'] <= 0 ? 0 : $comments['coms'];
	    
	    $this->DB->update( 'downloads_files', array( 'file_pendcomments' => $comments['coms'] ), 'file_id=' . $file_id );
	    
	    return true;
    }
    
	/**
	 * Rebuild the viewable comment count for a file
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return 	boolean
	 */
	public function rebuildComments( $file_id=0 )
    {
	    if( !$file_id )
	    {
		    return false;
	    }
	    
	    $file_id = intval($file_id);
	    
	    $comments = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as coms',
	    															  'from'	=> 'downloads_comments',
	    															  'where'	=> 'comment_fid=' . $file_id . ' AND comment_open=1'
	    													)		);
	    
	    $comments['coms'] = $comments['coms'] <= 0 ? 0 : $comments['coms'];
	    
	    $this->DB->update( 'downloads_files', array( 'file_comments' => $comments['coms'] ), 'file_id=' . $file_id );
	    
	    return true;
    }
    
	/**
	 * Check permissions to complete an action
	 *
	 * @access	public
	 * @param	array		File info
	 * @param	string		Moderator permission key to check
	 * @param	string		"User allowed" setting to check
	 * @return 	boolean		User can do action or not
	 */
	public function checkPerms( $file=array(), $modperm='modcanapp', $userperm='' )
    {
	    if( !is_array( $file ) OR !count( $file ) )
	    {
		    return false;
	    }
	    
		//-----------------------------------------
		// Got permission?
		//-----------------------------------------
		
		$moderator 	= $this->memberData['g_is_supmod'] ? true : false;
		
		$groups		= array( 'g' . $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = 'g' . $omg;
			}
		}

		if( !$moderator )		
		{
			if( is_array( $this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ] ) )
			{
				if( count($this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ]) )
				{
					foreach( $this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ] as $k => $v )
					{
						if( $k == "m".$this->memberData['member_id'] )
						{
							if( $v[ $modperm ] )
							{
								$moderator = true;
							}
						}
						else if( in_array( $k, $groups ) )
						{
							if( $v[ $modperm ] )
							{
								$moderator = true;
							}
						}
					}
				}
			}
		}
		
		if( $userperm )
		{
			if( $userperm == 'idm_comment_edit' OR $userperm == 'idm_comment_delete' )
			{
				$member_id	= $file['id'] ? $file['id'] : ( $file['comment_author_id'] ? $file['comment_author_id'] : $file['comment_mid'] );
			}
			else
			{
				$member_id	= $file['file_submitter'] ? $file['file_submitter'] : $file['member_id'];
			}
			
			if( $member_id == $this->memberData['member_id'] && $this->settings[ $userperm ] )
			{
				$moderator = true;
			}
		}
		
		return $moderator;
	}
	
	/**
	 * Is a moderator?
	 *
	 * @access	public
	 * @return 	boolean		User is a moderator
	 */
	public function isModerator()
    {
		$moderator 	= $this->memberData['g_is_supmod'] ? true : false;
		
		$groups		= array( $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = $omg;
			}
		}

		if( !$moderator )		
		{
			foreach( $groups as $groupId )
			{
				if( is_array( $this->registry->getClass('categories')->group_mods[ $groupId ] ) )
				{
					if( count($this->registry->getClass('categories')->group_mods[ $groupId ]) )
					{
						foreach( $this->registry->getClass('categories')->group_mods[ $groupId ] as $k => $v )
						{
							if( $v['modcanapp'] OR $v['modcanbrok'] )
							{
								$moderator	= true;
								break;
							}
						}
					}
				}
			}
		}

		if( !$moderator )
		{
			if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanapp'] OR $v['modcanbrok'] )
						{
							$moderator	= true;
							break;
						}
					}
				}
			}
		}
		
		return $moderator;
	}
	
	/**
	 * Return all moderators
	 *
	 * @access	public
	 * @return 	array 		Members who are moderators
	 */
	public function returnModerators()
    {
    	//-----------------------------------------
    	// Get supermod group ids
    	//-----------------------------------------
    	
    	$group_ids	= array();
    	$member_ids	= array();
		$members	= array();
		
		foreach( $this->cache->getCache('group_cache') as $i )
		{
			if ( $i['g_is_supmod'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
			
			if ( $i['g_access_cp'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
		}
		
		//-----------------------------------------
		// Get regular moderator group ids
		//-----------------------------------------
		
		if( is_array($this->registry->getClass('categories')->group_mods) AND count($this->registry->getClass('categories')->group_mods) )
		{
			foreach( $this->registry->getClass('categories')->group_mods as $groupId => $_data )
			{
				$group_ids[ $groupId ] = $groupId;
			}
		}
		
		//-----------------------------------------
		// Get members based on group id
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'members', 'where' => "member_group_id IN(" . implode( ',', $group_ids ) . ")" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$members[ $r['member_id'] ]	= $r;
		}
    	
		//-----------------------------------------
		// Any member mods?
		//-----------------------------------------
		
		if( is_array($this->registry->getClass('categories')->mem_mods) AND count($this->registry->getClass('categories')->mem_mods) )
		{
			foreach( $this->registry->getClass('categories')->mem_mods as $memberId => $_data )
			{
				$member_ids[ $memberId ]	= $memberId;
			}
		}
		
		//-----------------------------------------
		// Get members based on member id
		//-----------------------------------------
		
		if( count($member_ids) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'members', 'where' => "member_id IN(" . implode( ',', $member_ids ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$members[ $r['member_id'] ]	= $r;
			}
		}
    
		//-----------------------------------------
		// Return members
		//-----------------------------------------

		return $members;
	}
	
	/**
	 * Grab stats block and display
	 *
	 * @access	public
	 * @return	@e void
	 */	
	public function getStats()
	{
		/* Grab active users */
		$activeUsers = array();
		
		if( $this->settings['idm_displayactive'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
			$sessions    = new $classToLoad( $this->registry );
			
			$activeUsers = $sessions->getUsersIn('downloads');
		}
		
		//-------------------------------------------
		// Mini-stats
		//-------------------------------------------
				
		$show['mini_active']	= $this->total_active;
		$show['mini_files']		= intval($this->caches['idm_stats']['total_files']);
		$show['mini_downloads']	= intval($this->caches['idm_stats']['total_downloads']);
		$latest_files			= array();

		//-------------------------------------------
		// Find the latest file you can see
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->cat_lookup) )
		{
			foreach( $this->registry->getClass('categories')->cat_lookup as $k => $v )
			{
				if( in_array( $k, $this->registry->getClass('categories')->member_access['show'] ) )
				{
					if( $v['cfileinfo']['date'] > 0 )
					{
						$latest_files[ $v['cfileinfo']['date'] ] = $v['cfileinfo'];
					}
				}
			}
		}
		
		krsort($latest_files);

		$latest = count($latest_files) ? array_shift($latest_files) : array();

		//-------------------------------------------
		// Show random files?
		//-------------------------------------------
		
		if( $this->settings['idm_randomfiles'] AND count($this->registry->getClass('categories')->member_access['view']) )
		{
			$random			= array();
			$_randomIds		= array();
			$count			= $this->settings['idm_randomfiles'] > 0 ? $this->settings['idm_randomfiles'] : 8;

			$this->DB->build( array( 'select'	=> 'f.*',
									 'from'		=> array( 'downloads_files' => 'f' ),
									 'where' 	=> "f.file_open=1 AND c.copen=1 AND " . $this->DB->buildRegexp( "p.perm_view", $this->member->perm_id_array ),
									 'order' 	=> $this->DB->buildRandomOrder(),
									 'limit' 	=> array( 0, $count ),
									 'add_join'	=> array(
														array(
																'select'	=> 'c.cname as file_category, c.cname_furl',
																'from'		=> array( 'downloads_categories' => 'c' ),
																'where'		=> 'c.cid=f.file_cat',
																'type'		=> 'left'
															),
														array(
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.app='downloads' AND p.perm_type='cat' AND p.perm_type_id=c.cid",
																'type'		=> 'left'
															),
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_id=f.file_submitter",
																'type'		=> 'left'
															),
														array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> "pp.pp_member_id=m.member_id",
																'type'		=> 'left'
															),
														)
								)		);
			$this->DB->execute();

			while( $row = $this->DB->fetch() )
			{
				$row['members_display_name']	= $row['members_display_name'] ? $row['members_display_name'] : $this->lang->words['global_guestname'];
				$random[ $row['file_id'] ]		= $row;
				$_randomIds[]					= $row['file_id'];
			}
			
			if( count($_randomIds) )
			{
				$_recordIds	= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'downloads_files_records', 'where' => "record_file_id IN(" . implode( ',', $_randomIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
					{
						$_recordIds[ $r['record_file_id'] ]	= $r;
					}
				}
			}
		}
		
		//-------------------------------------------
		// Show stats
		//-------------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('downloads')->pageEnd( $show, $activeUsers, $latest, $random, $_recordIds );
	}
	
	/**
	 * Get the filename without an extension
	 *
	 * @access	public
	 * @param	string		Filename
	 * @return	string		Filename, no extension
	 */	
	public function getFileName($file)
	{
		return strtolower( str_replace( ".", "", substr( $file, 0, (strrpos( $file, '.' )) ) ) );
	}
	
	/**
	 * Return the allowed mime-types for the category
	 *
	 * @access	public
	 * @param	array		Category
	 * @return	array		Allowed file/screenshot types
	 */	
	public function getAllowedTypes( $category )
	{
		$types						= array(
											'files'	=> array(),
											'ss'	=> array() 
											);

		if( is_array($this->cache->getCache('idm_mimetypes')) AND count( $this->cache->getCache('idm_mimetypes') ) > 0 )
		{
			foreach( $this->cache->getCache('idm_mimetypes') as $k => $v )
			{
				$addfile	= explode( ",", $v['mime_file'] );
				$addss		= explode( ",", $v['mime_screenshot'] );

				if( in_array( $category['coptions']['opt_mimemask'], $addfile ) )
				{
					$types['files'][] = $v['mime_extension'];
				}

				if( in_array( $category['coptions']['opt_mimemask'], $addss ) )
				{
					$types['ss'][] = $v['mime_extension'];
				}
			}
		}
		
		return $types;
	}
	
	/**
	 * Can member submit links?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function canSubmitLinks()
	{
		//-----------------------------------------
		// Can we submit links?
		//-----------------------------------------
		
		if( $this->settings['idm_allow_urls'] )
		{
			$groups		= explode( ",", $this->settings['idm_groups_link'] );
			$my_groups	= array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroup_others'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}
			
			foreach( $my_groups as $groupid )
			{
				if( in_array( $groupid, $groups ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Can member import files (submit paths)?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function canSubmitPaths()
	{
		//-----------------------------------------
		// Can we import files?
		//-----------------------------------------
		
		if( $this->settings['idm_allow_path'] )
		{
			$groups		= explode( ",", $this->settings['idm_path_users'] );
			$my_groups	= array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroup_others'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}

			foreach( $my_groups as $groupid )
			{
				if( in_array( $groupid, $groups ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * (Attempt to) Retrieve the filesize of a remotely hosted file
	 *
	 * @access	public
	 * @param	string		URL to file
	 * @return	integer		File size
	 */	
	public function obtainRemoteFileSize( $url="" )
	{
		if( !$url )
		{
			return 0;
		}
		
		if( function_exists( 'curl_init' ) )
		{
			ob_start();
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			
			$ok = curl_exec($ch);
			curl_close($ch);
			
			$head = ob_get_contents();
			ob_end_clean();
			
			preg_match( '/Content-Length:\s([0-9].+?)\s/', $head, $matches );
			
			return isset($matches[1]) ? $matches[1] : 0;
		}
		else
		{
			if( !parse_url( $url ) )
			{
				return 0;
			}
			else
			{
				$url_bits = parse_url( $url );
			}
		
			if( $url_bits['scheme'] == 'https' )
			{
				$url_bits['host'] = "ssl://" . $url_bits['host'];
			}

			$socket_connection = @fsockopen( $url_bits['host'], 80 );
			
			if( !$socket_connection )
			{
				return 0;
			}
			
			$head = "HEAD $url HTTP/1.0\r\nConnection: Close\r\n\r\n";
			
			fwrite( $socket_connection, $head );
			
   			$i			= 0;
   			$results 	= "";
   			
   			while( true && $i<20 )
   			{
	   			if( $i >= 20 )
	   			{
		   			$results = "";
		   			break;
	   			}
	   			
       			$s = fgets( $socket_connection, 4096 );
       
       			$results .= $s;

       			if( strcmp( $s, "\r\n" ) == 0 || strcmp( $s, "\n" ) == 0 )
       			{
           			break;
       			}
       
       			$i++;
   			}
   
			fclose( $socket_connection );
			
			preg_match( '/Content-Length:\s([0-9].+?)\s/', $results, $matches );
			
			return isset($matches[1]) ? $matches[1] : 0;
		}
	}
	
	/**
	 * Check for monthly directory and create if necessary
	 *
	 * @access	public
	 * @param	string		Directory to check
	 * @return	string		Directory to use
	 */
	public function checkForMonthlyDirectory( $path, $time=0 )
	{
		if( @ini_get("safe_mode") OR $this->settings['safe_mode_skins'] )
		{
			return '';
		}
		
		if( $this->settings['idm_filestorage'] != 'disk' )
		{
			return '';
		}
		
		$time		= $time ? $time : time();
		$this_month	= "monthly_" . gmstrftime( "%m_%Y", $time );
		
		$_path = $path . '/' . $this_month;

		if( ! file_exists( $_path ) )
		{
			if( @mkdir( $_path, IPS_FOLDER_PERMISSION ) )
			{
				@file_put_contents( $_path . '/index.html', '' );
				@chmod( $_path, IPS_FOLDER_PERMISSION );
			}
			
			/* Was it really made or was it lying? */
			if( ! file_exists( $_path ) )
			{
				return '';
			}
			else
			{
				return $this_month . '/';
			}
		}
		else
		{
			return $this_month . '/';
		}

		return '';
	}
}