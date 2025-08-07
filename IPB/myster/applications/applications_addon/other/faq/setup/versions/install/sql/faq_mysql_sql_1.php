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

if(ipsRegistry::DB()->checkForTable('ccs_template_blocks'))
{
    $SQL[] = "INSERT INTO ccs_template_blocks (tpb_name, tpb_params, tpb_content) VALUES ('block__faq_collection', '\$content=\"\"', '{\$content}');";
}