<?php

class like_tracker_projects_composite extends classes_like_composite 
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
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
	/**#@-*/
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_project' ), 'tracker' );

		if ( ! $this->registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $this->registry );

			$this->registry->setClass( 'tracker', $this->tracker );
		}
	}
	
	public function skin()
	{
		return 'forum';
	}
	
	/**
	 * Gets the vernacular (like or follow)
	 *
	 * @return	@e string
	 */
	public function getVernacular()
	{
		return 'track_project';
	}
	
	/**
	 * Return an array of acceptable frequencies
	 * Possible: immediate, offline, daily, weekly
	 * 
	 * @return      array
	 */
	public function allowedFrequencies()
	{
		return array( 'immediate', 'offline' );
	}
	
	/**
	 * return type of notification available for this item
	 * 
	 * @return      array (key, readable name)
	 */
	public function getNotifyType()
	{
		return array( 'new_issues', $this->lang->words['bt_project_like_type'] );
	}
	
	/**
	 * Returns the type of item
	 * 
	 * @param       mixed   Relationship ID or array of
	 * @param       array   Array of meta to select (title, url, type, parentTitle, parentUrl, parentType) null fetches all
	 * @return      array   Meta data
	 */
	public function getMeta( $relId, $selectType=null )
	{
		$return    = array();
		$isNumeric = false;
		
		if ( is_numeric( $relId ) )
		{
			$relId     = array( intval($relId) );
			$isNumeric = true;
		}
		
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_projects',
				'where'  => 'project_id IN (' . implode( ',', $relId ) . ')'
			)
		);
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.title'] = $row['title'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.url'] = $this->registry->output->buildSEOUrl( 'app=tracker&showproject=' . $row['project_id'], "public", $row['title_seo'], "showproject" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.type'] = 'Project';
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.parentTitle'] = '';
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.parentUrl'] = '';
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['project_id'] ]['like.parentType'] = '';
			}
		}
		
		return ( $isNumeric === true ) ? array_pop( $return ) : $return;
	}

	/**
	 * Check notifications that are to be sent to make sure they're valid and that
	 *
	 * @param	array		$metaData		like_ DB data and like owner member data
	 * @return	@e boolean
	 */
	public function notificationCanSend( $metaData )
	{
		/* Get Issue Array */
		$issue = $this->cache->getCache( 'trackerCurrentIssue', FALSE );

		/* Permission ids */
		$permId		= $metaData['org_perm_id'] ? $metaData['org_perm_id'] : $metaData['g_perm_id'];
		$permArray	= explode( ',', $permId );

		/* Project Show/Read Permission? */
		if (	! $this->registry->tracker->projects()->checkPermission( 'show', $metaData['like_rel_id'], $permArray ) &&
				! $this->registry->tracker->projects()->checkPermission( 'read', $metaData['like_rel_id'], $permArray ) )
		{
			return FALSE;
		}

		/* Private Issues */
		if($this->registry->tracker->fields()->active('privacy', $metaData['like_rel_id']))
		{
			$field	= $this->registry->tracker->fields()->getFieldByKeyword('privacy');
			$fClass = $this->registry->tracker->fields()->getFieldClass($field['field_id']);

			/* For Now.. Suppress Notifications.. */
			if(isset($issue['module_privacy']) && $issue['module_privacy'] && is_object($fClass))
			{
	//			$x = $this->tracker->moderators()->checkFieldPermission( $metaData['like_rel_id'], 'privacy', 'privacy', 'show' );
	//
	//			if ( $issue['module_private'] == 1 && ! $x )
	//			{
	//				if ( $issue['starter_id'] == $metaData['member_id'] )
	//				{
	//					return TRUE;
	//				}
					return FALSE;
	//			}
			}
		}

		return TRUE;
	}
}

?>