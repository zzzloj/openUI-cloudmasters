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

$_SEOTEMPLATES = array( 'showtestimonial'       => array( 'app'		      => 'testimonials',
												  		  'allowRedirect' => 1,
												  		  'out'           => array( '#app=testimonials(?:&|&amp;)showtestimonial=(.+?)(&|$)#i', 'testimonials/showtestimonial/$1-#{__title__}/$2' ),
												  		  'in'            => array( 'regex'   => "#/testimonials/showtestimonial/(\d+?)-#i",
														  'matches' => array( array( 'app', 'testimonials' ), array( 'showtestimonial', '$1' ) ) ) ),

														  
                        'showlist'               => array( 'app'		      => 'testimonials',
												  		  'allowRedirect' => 1,
												  		  'out'           => array( '#app=testimonials(?:&|&amp;)showlist=(.+?)(&|$)#i', 'testimonials/showlist/$1-#{__title__}/$2' ),
												  		  'in'            => array( 'regex'   => "#/testimonials/showlist/(\d+?)-#i",
														  'matches' => array( array( 'app', 'testimonials' ), array( 'module'		, 'testemunhos' ), array( 'section'	, 'list' ), array( 'showcat', '$1' ) ) ) ),
									  
						'app=testimonials'		=> array( 'app'           => 'testimonials',
												  		  'allowRedirect' => 1,
												  		  'out'           => array( '#app=testimonials$#i', 'testimonials/' ),
													  	  'in'            => array( 'regex'   => "#/testimonials/?$#i",
												  		  'matches' 	  => array( array( 'app', 'testimonials' ) ) ) ),
);