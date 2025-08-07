<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$PORTAL_CONFIG = array();
$PORTAL_CONFIG['pc_title'] = '(RC34) Testemunhos - Testemunho Randômico';
$PORTAL_CONFIG['pc_desc']  = "Mostra um testemunho postado no (SO30) Testemunhos de forma randômica";
$PORTAL_CONFIG['pc_settings_keyword'] = "";
$PORTAL_CONFIG['pc_exportable_tags']['testemunho_randomico'] = array( 'testemunho_randomico' , 'Mostra um testemunho postado no (SO30) Testemunhos de forma randômica' );