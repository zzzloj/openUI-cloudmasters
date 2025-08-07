<?php

class like_tracker_issues_composite extends classes_like_composite 
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
		$this->registry->class_localization->loadLanguageFile( array( 'public_issue' ), 'tracker' );

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
		return 'track_issue';
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
		return array( 'issue_reply', $this->lang->words['bt_issue_like_type'] );
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
				'from'   => 'tracker_issues',
				'where'  => 'issue_id IN (' . implode( ',', $relId ) . ')'
			)
		);
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'title', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.title'] = $row['title'];
			} 
			
			/* URL */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'url', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.url'] = $this->registry->output->buildSEOUrl( 'app=tracker&showissue=' . $row['issue_id'], "public", $row['title_seo'], "showissue" );
			}
			
			/* Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'type', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.type'] = $this->lang->words['bt_issue_like_type'];
			} 
			
			/* Parent title */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.parentTitle'] = '';
			} 
			
			/* Parent url */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentTitle', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.parentUrl'] = '';
			} 
			
			/* Parent Type */
			if ( $selectType === null OR ( is_array( $selectType ) AND in_array( 'parentType', $selectType ) ) )
			{
				$return[ $row['issue_id'] ]['like.parentType'] = '';
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
		$issue = $this->registry->tracker->issues()->getIssueData();

		if(!$issue)
		{
			$issue = $this->DB->buildAndFetch(
				array(
					'select'   => 't.*',
					'from'     => 'tracker_issues t',
					'where'    => 't.issue_id=' . $metaData['like_rel_id'],
				)
			);
		}

		/* Permission ids */
		$permId		= $metaData['org_perm_id'] ? $metaData['org_perm_id'] : $metaData['g_perm_id'];
		$permArray	= explode( ',', $permId );

		/* Project Show/Read Permission? */
		if (	! $this->registry->tracker->projects()->checkPermission( 'show', $issue['project_id'], $permArray ) &&
				! $this->registry->tracker->projects()->checkPermission( 'read', $issue['project_id'], $permArray ) )
		{
			return FALSE;
		}

		return TRUE;
	}
}

?>