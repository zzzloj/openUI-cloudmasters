<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Sets up SEO templates
 * Last Updated: $Date: 2013-04-17 10:31:02 -0400 (Wed, 17 Apr 2013) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 12183 $
 *
 */
$_SEOTEMPLATES = array(
						'blogcatview' => array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog(?:(?:&|&amp;))blogid=(.+?)(?:(?:&|&amp;))cat=(.+?)(&|$)/i', 'blog/blog-$1/cat-$2-#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/blog/blog-(\d+?)/cat-(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'blog' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'blog' ),
																								array( 'blogid'		, '$1' ),
																								array( 'cat'		, '$2' )
																							)
																	) 
										),
						'showentry' => array(
												'app'			=> 'blog',
												'allowRedirect'	=> 1,
												'out'			=> array( '/app=blog(?:(?:&|&amp;)module=display(?:&|&amp;)section=blog)?(?:&|&amp;)blogid=(.+?)(?:&|&amp;)showentry=(.+?)(&|$)/i', 'blog/$1/entry-$2-#{__title__}/$3' ),
												'in'			=> array( 
																			'regex'		=> "#/blog/(\d+?)/entry-(\d+?)-#i",
																			'matches'	=> array( 
																									array( 'app'		, 'blog' ),
																									array( 'module'		, 'display' ),
																									/**
																									 * @todo	section here needs to be changed to 'entry' for the next major version, for now we're leaving 'blog' or it will break all the existing urls. Section is properly reset to 'entry' in coreVariables for now.
																									 */
																									array( 'section'	, 'blog' ),
																									array( 'blogid'		, '$1' ),
																									array( 'showentry'	, '$2' )
																								)
																		)	
											),


						'showblog' => array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog(?:(?:(?:&|&amp;))module=display(?:(?:&|&amp;))section=blog)?(?:(?:&|&amp;))blogid=(.+?)(&|&amp;|$)/i', 'blog/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> "#/blog/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'blog' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'blog' ),
																								array( 'blogid'		, '$1' )
																							)
																	) 
										),

						'manageblog' => array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog(?:&|&amp;)module=manage(&|&amp;|$|#)/i', 'blog/manage/$1' ),
											'in'			=> array( 
																		'regex'		=> "#/blog/manage#i",
																		'matches'	=> array( 
																								array( 'app'		, 'blog' ),
																								array( 'module'		, 'manage' )
																							)
																	) 
										),
										
						'createblog' => array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog(?:&|&amp;)module=manage(?:(?:&|&amp;))section=dashboard(?:(?:&|&amp;))act=create(&|&amp;|$|#)/i', 'blog/create/$1' ),
											'in'			=> array( 
																		'regex'		=> "#/blog/create#i",
																		'matches'	=> array( 
																								array( 'app'		, 'blog' ),
																								array( 'module'		, 'manage' ),
																								array( 'section'	, 'dashboard' ),
																								array( 'act'		, 'create' )
																							)
																	) 
										),
						
						'blogarchive' => array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog(?:&|&amp;)module=display(?:&|&amp;)section=archive(?:&|&amp;)blogid=(.+?)(&|$)/i', 'blog/archive/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> "#/blog/archive/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'blog' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'archive' ),
																								array( 'blogid'		, '$1' )
																							)
																	) 
										),
						'blogrss' => array( 
												'app'			=> 'blog',
												'allowRedirect' => 1,
												'out'			=> array( '/app=core&amp;module=global&amp;section=rss&amp;type=blog&amp;blogid=(.+?)(&|$)/i', 'blog/rss/$1-#{__title__}/$2' ),
												'in'			=> array( 
																		'regex'		=> "#/blog/rss/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'core' ),
																								array( 'module'		, 'global' ),
																								array( 'section'	, 'rss' ),
																								array( 'type'		, 'blog' ),
																								array( 'blogid'		, '$1' )
																							)
																	) 
										),
						'app=blog'		=> array( 
											'app'			=> 'blog',
											'allowRedirect' => 1,
											'out'			=> array( '/app=blog/i', 'blogs/' ),
											'in'			=> array( 
																		'regex'		=> "#^/blogs(/|$|\?)#i",
																		'matches'	=> array( array( 'app', 'blog' ) )
																	) 
														),
					);
