<?php

/**
 * Basic Notifications Wrapper
 * @usage
 * $classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('quiz') . '/sources/classes/notifications.php', 'quiz__notifications_wrapper', 'quiz' );
 * $notifications  = new $classToLoad( $this->registry );
 * $notifications->buildAndSendNotification($title, $message, $member, $key, $url);
 */

class quiz__notifications_wrapper {
	
	protected $doDebug = false;
	
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
	
	//
	/**
	 * Build and Send the Notification. For cleaner code!
	 * @param string $title
	 * @param string $message
	 * @param string member_id $member
	 * @param string notification key $key
	 * @param string formatted URL $url
	 * 
	 * this function will always send from the currently logged in member 
	 * @todo - make $member an array() with 'to' and 'from'.
	 */
	public function buildAndSendNotification($title, $message, $member, $key, $url) 
	{
		//$member = array('to', 'false');
		if (($title) && ($message) && ($member) && ($key) && ($url)) {
			// load up the notifications lib (could we move to __construct??)
			$classToLoad    = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
			$notifyLibrary  = new $classToLoad( $this->registry );
			// who are we sending this to?
			if(!is_array($member))
			{
				$memberId = intval($member);
				$_member = IPSMember::load($memberId, 'all', 'id');
			} else {
				if (!$member['member_id']) {
					trigger_error('Whoops, no member id passed. Ensure that you\'re passing either an ARRAY of memberData, or just the MEMBER ID as a string.', E_USER_ERROR);
				}
			}
			// now we know who you are (evil laugh)
			$notifyLibrary->setMember( $_member );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( $key );
			$notifyLibrary->setNotificationUrl( $url );
			$notifyLibrary->setNotificationText( $message );
			$notifyLibrary->setNotificationTitle( $title );
			
			/* Send the notification */
			try
			{
				$notifyLibrary->sendNotification();
			}
			catch( Exception $e ){
				if ($this->doDebug == TRUE) {
					$this->debug($e);
				}
			}
		}
	}
	
	public function debug($e)
	{
		// what to do here.. email someone? log the error? print an error...?
	}
	
}