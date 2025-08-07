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
$PORTAL_CONFIG['pc_title'] = '(SOS30) Testemunhos - Últimos 5 Testemunhos Postados';
$PORTAL_CONFIG['pc_desc']  = "Mostra os testemunhos mais novos postados no (SO30) Testemunhos";
$PORTAL_CONFIG['pc_settings_keyword'] = "";
$PORTAL_CONFIG['pc_exportable_tags']['testemunhos_recentes'] = array( 'testemunhos_recentes' , 'Mostra os testemunhos mais novos postados no (SO30) Testemunhos' );