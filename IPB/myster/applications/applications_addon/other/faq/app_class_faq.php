<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_faq
{
    public function __construct(ipsRegistry $registry)
    {
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('faq').'/sources/text.php', 'faq_text', 'faq');
        $registry->setClass('faqText', new $classToLoad());
        
        if(IN_ACP)
        {
            $registry->class_localization->loadLanguageFile(array('admin_faq'), 'faq');
        }
        else
        {
            $registry->class_localization->loadLanguageFile(array('public_faq'), 'faq');
        }        
        
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('faq') . '/sources/classes/questions.php', 'class_questions', 'faq');
        $registry->setClass('class_questions', new $classToLoad());
        
        $this->_loadMemberPermissions();
    }
    
    protected function _loadMemberPermissions()
    {
        $this->memberData =& ipsRegistry::member()->fetchMemberData();
        
        $perms = ipsRegistry::DB()->buildAndFetch(array('select' => '*', 'from' => 'permission_index', 'where' => "perm_type='question' and perm_type_id=1"));
        $this->memberData['faq']['view'] = ipsRegistry::getClass('permissions')->check('view', $perms);
        $this->memberData['faq']['add'] = ipsRegistry::getClass('permissions')->check('add', $perms);
        $this->memberData['faq']['moderate'] = ipsRegistry::getClass('permissions')->check('moderate', $perms);
        $this->memberData['faq']['approve'] = ipsRegistry::getClass('permissions')->check('approve', $perms);
    }
}