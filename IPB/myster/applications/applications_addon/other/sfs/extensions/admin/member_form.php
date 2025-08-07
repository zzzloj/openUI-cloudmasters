<?php

if (!defined( 'IN_IPB'))
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_member_form__sfs implements admin_member_form
{

	public function __construct(ipsRegistry $registry)
	{
        $this->registry     =  ipsRegistry::instance();
        $this->DB           =  $this->registry->DB();
        $this->settings     =& $this->registry->fetchSettings();
        $this->request      =& $this->registry->fetchRequest();
        $this->lang         =  $this->registry->getClass('class_localization');
        $this->member       =  $this->registry->member();
        $this->memberData  	=& $this->registry->member()->fetchMemberData();
        $this->cache        =  $this->registry->cache();
        $this->caches       =& $this->registry->cache()->fetchCaches();
	}

	public $tab_name = "";
    
	public function getSidebarLinks($member=array())
	{
		#init
		$array = array();
		
		#load lang
		ipsRegistry::getClass('class_localization')->loadLanguageFile(array('admin_sfs'), 'sfs' );
			
		#build link
		$array[] = array('img' => $this->settings['acp_url'].'applications_addon/other/sfs/skin_cp/appIcon.png', 
  						'url' => 'module=members&amp;section=report&amp;do=report',
						'title' => ipsRegistry::getClass('class_localization')->words['sfs_reportSFS']);
	
		#return it
		return $array;
	}

	public function getDisplayContent($member=array())
	{
        $html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_sfs_member_form', 'sfs');
        ipsRegistry::getClass('class_localization')->loadLanguageFile(array('admin_sfs'), 'sfs');
        $member = IPSMember::load($member['member_id'], 'pfields_content');
        
		return array('tabs' => $html->sfsMemTab($member), 'content' => $html->sfsMemContent($member), 'tabsUsed' => 1);
	}

	public function getForSave()
	{
		return $return;
	}
	
}

?>