<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Sets up SEO templates
 * Last Updated: $Date: 2011-06-10 10:07:06 -0400 (Fri, 10 Jun 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9023 $
 *
 */

$_SEOTEMPLATES	= array(
						'page' => array(
										'app'			=> 'ccs',
										'allowRedirect'	=> 1,
										'out'			=> array( '#app=ccs(?:&amp;|&)module=pages(?:&amp;|&)section=pages(?:&amp;|&)(?:folder=(.*?)(?:&amp;|&))(?:id|page)=(.+?)(&|$)#i', 'page/$1/#{__title__}' ),
										'in'			=> array( 
																	'regex'		=> "#/page(/.*?)?/([^/]+?)(\/|\?|$)#i",
																	'matches'	=> array( 
																							array( 'app'		, 'ccs' ),
																							array( 'module'		, 'pages' ),
																							array( 'section'	, 'pages' ),
																							array( 'folder'		, str_replace( '/page', '', '$1' ) ),
																							array( 'page'		, '$2' ),
																						)
																)	
									),
					);


//-----------------------------------------
// You must capture everything after the "marker"
//-----------------------------------------

$_PATHFIX		= "#.*?\/page\/(.+?)$#";