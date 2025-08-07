<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 2.0
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

class faqNotifications
{
    protected $perms;
    
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->DB = $this->registry->DB();
        $this->memberData =& $this->registry->member()->fetchMemberData();
        $this->lang = $this->registry->getClass('class_localization');
        $this->lang->loadLanguageFile(array('public_specs'), 'bible');
        
        $this->perms = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'permission_index', 'where' => "perm_type='question' and perm_type_id=1"));
        $this->perms = $this->registry->permissions->parse($this->perms);
    }
    
    public function notifyQuestionPending($question)
    {
        $members = $this->_loadMembers('moderate');
        if(!is_array($members) || !count($members))
            return;
            
        $url = $this->registry->output->buildUrl("app=faq&amp;module=manage&amp;section=questions", "public");
            
        foreach($members as $toMember)
        {
            $toMember['language'] = $toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $toMember['language'];
            
            IPSText::getTextClass('email')->getTemplate("question_pending", $toMember['language'], 'public_faq', 'faq');
			
            IPSText::getTextClass('email')->buildMessage( array(
			     					                    'NAME'		=> $toMember['members_display_name'],
                                                        'LINK'		=> $url
                                                    ) );
                                       
            $this->_sendNotification('question_pending', $toMember, $this->memberData, $url);
        }
    }
    
    protected function _sendNotification($key, $toMember, $fromMember, $url)
    {
        $classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
        $notifyLibrary = new $classToLoad( $this->registry );
        
        $notifyLibrary->setMember($toMember);
        $notifyLibrary->setFrom($fromMember);
        $notifyLibrary->setNotificationKey($key);
        $notifyLibrary->setNotificationUrl($url);
        $notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
        $notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
            
        try
        {
           $notifyLibrary->sendNotification();
        }
        catch( Exception $e ){}
    }
    
    protected function _loadMembers($permType)
    {
        // may as well get the entire record, we would have to load it at some point anyway
        $this->DB->build(array('select' => '*', 'from' => 'members', 'where' => 'member_group_id in ('.$this->perms['perm_'.$permType].')'));
        $mQuery = $this->DB->execute();
        while($m = $this->DB->fetch($mQuery))
            $members[] = $m;
        $this->DB->freeResult($mQuery);
        
        return $members;
    }
}