<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Sets up SEO templates
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

$_SEOTEMPLATES = array(
						'idmshowcat' => array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)showcat=(.+?)(&|$)/i', 'files/category/$2-#{__title__}/$3' ),
											'in'			=> array( 
																		'regex'		=> "#/files/category/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'showcat'	, '$1' )
																							)
																	) 
										),
						'idmshowfile' => array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)showfile=(.+?)(&|$)/i', 'files/file/$2-#{__title__}/$3' ),
											'in'			=> array( 
																		'regex'		=> "#/files/file/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'showfile'	, '$1' )
																							)
																	) 
										),
						'longwinded' => array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=file(&amp;|&)id=(.+?)(&|$)/i', 'files/file/$4-#{__title__}/$5' ),
											'newTemplate'	  => 'idmshowfile',
											'in'			=> array( 
																		'regex'		=> "#/xxxyyyzzz/file/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'showfile'	, '$1' )
																							)
																	) 
										),
						'idmdownload'		=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=confirm_download(&amp;|&)id=(.+?)(&|$)/i', 'files/download/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/files/download/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'confirm_download' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),
						'idmdodownload'	=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=do_download(&amp;|&)id=(.+?)(&|$)/i', 'files/getdownload/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/files/getdownload/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'do_download' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),
						'idmdd'		=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=confirm_download(&amp;|&)hash=(.+?)(&|$)/i', 'files/go/$5/#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/files/go/([a-zA-Z0-9]+?)/#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'confirm_download' ),
																								array( 'hash'		, '$1' )
																							)
																	) 
														),
						'idmdd2'	=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=do_download(&amp;|&)hash=(.+?)(&amp;|&)id=(\d+)(&|$)/i', 'files/get/$5/$7-#{__title__}' ),
											'in'			=> array( 
																		'regex'		=> "#/files/get/([a-zA-Z0-9]+?)/(\d+)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'do_download' ),
																								array( 'hash'		, '$1' ),
																								array( 'id'			, '$2' ),
																							)
																	) 
														),
						'idmbuy'	=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads(&amp;|&)module=display(&amp;|&)section=download(&amp;|&)do=buy(&amp;|&)id=(.+?)(&|$)/i', 'files/buy/$5-#{__title__}/$6' ),
											'in'			=> array( 
																		'regex'		=> "#/files/buy/(\d+?)-#i",
																		'matches'	=> array( 
																								array( 'app'		, 'downloads' ),
																								array( 'module'		, 'display' ),
																								array( 'section'	, 'download' ),
																								array( 'do'			, 'buy' ),
																								array( 'id'			, '$1' )
																							)
																	) 
														),
						'app=downloads'		=> array( 
											'app'			=> 'downloads',
											'allowRedirect' => 1,
											'out'			=> array( '/app=downloads$/i', 'files/' ),
											'in'			=> array( 
																		'regex'		=> "#^/files#i",
																		'matches'	=> array( array( 'app', 'downloads' ) )
																	) 
														),
					);