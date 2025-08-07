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

if(!ipsRegistry::DB()->checkForField('source', 'faq_collections'))
{
    $SQL[] = "ALTER TABLE faq_collections_questions ADD (
    source varchar(10),
    KEY source (source)
    );";
    
    $SQL[] = "UPDATE faq_collections_questions SET source='HSC';";    
}