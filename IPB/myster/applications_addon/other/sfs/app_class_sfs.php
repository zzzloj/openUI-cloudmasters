<?php

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_sfs
{

	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
        
        if (IN_ACP) {
            $registry->getClass('class_localization')->loadLanguageFile(array('admin_sfs'), 'sfs');
        }
	}
}
?>