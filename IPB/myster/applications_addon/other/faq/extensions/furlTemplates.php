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

$_SEOTEMPLATES = array(

                        'app=faq' => array(
                                            'app' => 'faq',
                                            'allow_redirect' => 1,
                                            'out'			=> array( '#app=faq#i', 'faq/' ),
                                            'in'			=> array( 
																		'regex'		=> "#^/faq(/$|$)#i",
																		'matches'	=> array( 
                                                                            array( 'app', 'faq' ),
                                                                            array( 'module', 'faq'),
                                                                            array( 'section', 'faq')
																	))
                                            ),
                                            
                        'faqcollection' => array(
                                            'app' => 'faq',
                                            'allow_redirect' => 1,
                                            'out'			=> array( '#faqcollection=(.+?)#i', 'faq/$1-#{__title__}' ),
                                            'in'			=> array( 
																		'regex'		=> "#^/faq/(\d+?)-#i",
																		'matches'	=> array( 
                                                                            array( 'app', 'faq' ),
                                                                            array( 'module', 'faq'),
                                                                            array( 'section', 'collection'),
                                                                            array( 'collection_id', '$1')
																	))
                                            ),
);