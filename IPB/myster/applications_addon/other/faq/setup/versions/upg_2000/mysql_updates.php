<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.2
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

$SQL[] = "ALTER TABLE faq_questions ADD (
    approved tinyint(1) default 0,
    KEY approved (approved)
    );";
    
$SQL[] = "UPDATE faq_questions SET approved=1;";