<?php

/**
* Tracker 2.1.0
* 
* RSS output plugin
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author 		$Author: stoo2000 $
* @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @since		6/24/2008
* @version		$Revision: 1363 $
*/

$_SEOTEMPLATES = array(
	'showissue' => array(
		'app'			=> 'tracker',
		'allowRedirect'	=> 1,
		'out'			=> array( '#app=tracker(&|&amp;)showissue=(.+?)(&|$)#i', 'tracker/issue-$2-#{__title__}/$3' ),
		'in'			=> array( 
			'regex'			=> "#/tracker/issue-(\d+?)-#i",
			'matches'		=> array( 
				array( 'app'		, 'tracker' ),
				array( 'module'		, 'projects' ),
				array( 'section'	, 'issues' ),
				array( 'iid'		, '$1' )
			)
		)	
	),
	
	'showproject' => array(
		'app'			=> 'tracker',
		'allowRedirect'	=> 1,
		'out'			=> array( '#app=tracker(&|&amp;)showproject=(.+?)(&|$)#i', 'tracker/project-$2-#{__title__}/$3' ),
		'in'			=> array( 
			'regex'			=> "#/tracker/project-(\d+?)-#i",
			'matches'		=> array( 
				array( 'app'		, 'tracker' ),
				array( 'module'		, 'projects' ),
				array( 'section'	, 'projects' ),
				array( 'pid'		, '$1' )
			)
		)	
	),
	
	'app=tracker'	=> array( 
		'app'			=> 'tracker',
		'allowRedirect' => 1,
		'out'			=> array( '#app=tracker$#i', 'tracker/' ),
		'in'			=> array( 
			'regex'			=> "#/tracker($|\/)#i",
			'matches'		=> array( array( 'app', 'tracker' ) )
		) 
	)
);