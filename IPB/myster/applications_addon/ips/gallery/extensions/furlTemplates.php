<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v5.0.5
 * Sets up SEO templates
 * Last Updated: $Date: 2012-09-19 18:02:05 -0400 (Wed, 19 Sep 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 11352 $
 *
 */
$_SEOTEMPLATES = array(
						
						'viewsizes' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))image=(.+?)(?:(?:&|&amp;))size=(.+?)(&|$)/i', 'gallery/sizes/$1-#{__title__}/$2/$3' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/sizes/(\d+?)-(.+?)/(?:(.+?)(/|$))?#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'images' ),
																								array( 'section'	, 'sizes' ),
																								array( 'image'		, '$1' ),
																								array( 'size'		, '$3' ),
																							)
																	) 
										),
						'viewimage' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))image=(.+?)(&|$)/i', 'gallery/image/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/image/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'image'		, '$1' )
																							)
																	) 
										),

						'slideshow' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))module=images(?:(?:&|&amp;))section=slideshow(?:(?:&|&amp;))type=(album|category)(?:(?:&|&amp;))typeid=(.+?)(&|$)/i', 'gallery/slideshow/$1-$2/$3' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/slideshow/(album|category)-(\d+?)(/|$)#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'images' ),
																								array( 'section'	, 'slideshow' ),
																								array( 'type'		, '$1' ),
																								array( 'typeid'		, '$2' )
																							)
																	) 
										),
										
						'editalbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))albumedit=(.+?)(&|$)/i', 'gallery/album/$1-#{__title__}/edit/$2' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/album/(\d+?)-(.+?)/edit(/|$)#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'images' ),
																								array( 'section'	, 'review' ),
																								array( 'album_id'	, '$1' )
																							)
																	) 
										),
														
						'viewalbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))(?:module=user(?:&|&amp;)user=\d+?(?:&|&amp;)do=view_album(?:&|&amp;))?album=(.+?)(&|$)/i', 'gallery/album/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/album/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'album'		, '$1' )
																							)
																	) 
										),

						'viewcategory' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 0,
											'out'			=> array( '/app=gallery(?:&|&amp;)category=(\d+?)(&|$)/i', 'gallery/category/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/category/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'category'	, '$1' )
																							)
																	)
										),

						'galleryrss' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))module=albums(?:(?:&|&amp;))section=rss(?:(?:&|&amp;))type=(album|category)(?:(?:&|&amp;))typeid=(\d+?)(&|$)/i', 'gallery/rssfeed/#{__title__}/$1-$2/$3' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/rssfeed/(.+?)/(album|category)-(\d+?)(/|$)#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'rss' ),
																								array( 'type'		, '$2' ),
																								array( 'typeid'		, '$3' )
																							)
																	) 
										),
										
						'useralbum' => array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery(?:(?:&|&amp;))user=(.+?)(&|$)/i', 'gallery/member/$1-#{__title__}/$2' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery/member/(\d+?)-#i',
																		'matches'	=> array( 
																								array( 'app'		, 'gallery' ),
																								array( 'module'		, 'albums' ),
																								array( 'section'	, 'user' ),
																								array( 'member_id'	, '$1' )
																							)
																	) 
										),
						
						'app=gallery'		=> array( 
											'app'			=> 'gallery',
											'allowRedirect' => 1,
											'out'			=> array( '/app=gallery/i', 'gallery/' ),
											'in'			=> array( 
																		'regex'		=> '#^/gallery(/|$|\?)#i',
																		'matches'	=> array( array( 'app', 'gallery' ) )
																	) 
														),
					);