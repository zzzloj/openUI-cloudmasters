<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_external' ), 'form' );

$BBCODE	= array(
				'form_forms'	=> ipsRegistry::getClass('class_localization')->words['editor_form_forms'],				
				);