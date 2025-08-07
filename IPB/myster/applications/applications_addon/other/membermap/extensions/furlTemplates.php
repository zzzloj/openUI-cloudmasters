<?php

/**
 * FURL Templates
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

$_SEOTEMPLATES = array(

	'friends' => array(
		'app'			=> 'membermap',
		'allowRedirect'	=> 1,
		'out'			=> array( '/app=membermap(?:&|&amp;)module=membermap(?:&|&amp;)do=friends/i', 'membermap/showfriends/' ),
		'in'			=> array(
			'regex'			=> "#/membermap/showfriends#i",
			'matches'		=> array(
				array( 'app'		, 'membermap' ),
				array( 'module'		, 'membermap' ),
				array( 'do'		, 'friends' )
			)
		)
	),

    	'removeMarker' => array(
		'app'			=> 'membermap',
		'allowRedirect'	=> 1,
		'out'			=> array( '/app=membermap(?:&|&amp;)module=membermap(?:&|&amp;)action=removeMarker(?:&|&amp;)do=(\d+?)/i', 'membermap/removeMarker/$1' ),
		'in'			=> array(
			'regex'			=> "#/membermap/removeMarker/(\d+?)$#i",
			'matches'		=> array(
				array( 'app'		, 'membermap' 	),
                                array( 'section'	, 'moderate' 	),
				array( 'action'		, 'removeMarker'),
				array( 'do'	, '$1'			)
			)
		)
	),

    	'ips'	=> array(
		'app'			=> 'membermap',
		'allowRedirect' => 1,
		'out'			=> array( '#app=membermap#i', 'membermap/ips' ),
		'in'			=> array(
			'regex'			=> "#/membermap/ips#i",
			'matches'		=> array( array( 'app', 'membermap' ),
                                                        array( 'do', 'ips' ) )
		)
	),

    	'membermap'	=> array(
		'app'			=> 'membermap',
		'allowRedirect' => 1,
		'out'			=> array( '#app=membermap#i', 'membermap/' ),
		'in'			=> array(
			'regex'			=> "#/membermap#i",
			'matches'		=> array( array( 'app', 'membermap' ) )
		)
	),
    );