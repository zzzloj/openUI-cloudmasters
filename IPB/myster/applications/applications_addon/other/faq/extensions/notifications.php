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

ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_faq' ), 'faq' );

class faq_notifications
{
    public function getConfiguration()
	{
		$_NOTIFY	= array(
							array( 'key' => 'question_pending', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => true ),
							);
							
		return $_NOTIFY;
	}
    
    public function question_pending()
    {
        $perms = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'permission_index', 'where' => "perm_type='question' and perm_type_id=1"));
        return ipsRegistry::getClass('permissions')->check('moderate', $perms);
    }
}