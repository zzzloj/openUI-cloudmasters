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

$_SEOTEMPLATES = array(
	'form_view_form' => array(
		'app'			=> 'form',
		'allowRedirect'	=> 1,
		'out'			=> array( '#app=form(&amp;|&)do=view_form(&amp;|&)id=(.+?)(&|$)#i', 'form/form/$3-#{__title__}/$4' ),
		'in'			=> array( 
			'regex'			=> "#/form/form/(\d+?)-#i",
			'matches'		=> array( 
				array( 'app'		, 'form' ),
				array( 'module'		, 'display' ),
				array( 'section'	, 'index' ),
				array( 'do'	    , 'view_form' ),				
				array( 'id'		, '$1' )
			)
		)	
	),

	'app=form'	=> array( 
		'app'			=> 'form',
		'allowRedirect' => 1,
		'out'			=> array( '#app=form#i', 'forms/' ),
		'in'			=> array( 
			'regex'			=> "#/forms($|\/)#i",
			'matches'		=> array( array( 'app', 'form' ) )
		) 
	)
);