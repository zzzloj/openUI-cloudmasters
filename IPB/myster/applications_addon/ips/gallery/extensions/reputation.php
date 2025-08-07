<?php

/**
 * @file		reputation.php 	IP.Gallery reputation extension
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * $LastChangedDate: 2012-09-27 11:58:26 -0400 (Thu, 27 Sep 2012) $
 * @version		v5.0.5
 * $Revision: 11387 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class reputation_gallery
{
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//-----------------------------------------
		// We need the reputation cache object
		//-----------------------------------------

		if( !ipsRegistry::isClassLoaded('repCache') )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			ipsRegistry::setClass( 'repCache', new $classToLoad() );
		}

		//-----------------------------------------
		// And we'll need our images library
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			ipsRegistry::setClass( 'gallery', new $classToLoad( ipsRegistry::instance() ) );
		}
	}
	
	/**
	 * Database Query to get results
	 *
	 * @param	string		'given' if we want to return items as user has liked, 
	 *						'received' if we want to fetch items a user has posted that others have liked, 
	 *						NULL to get highest rated
	 * @param	array		Member this applies to, 
	 *						NULL to get highest rated
	 *
	 * @return	@e array	Parameters to pass to ipsRegistry::DB()->build. 
	 *						Must return at least the data from reputation_index. 
	 *						limit and order will be applied automatically
	 */
	public function fetch( $type=NULL, $member=NULL )
	{
		//-----------------------------------------
		// Start building the where clause
		//-----------------------------------------

		$where	= array(
						'image_privacy IN(0,1)',
						ipsRegistry::DB()->buildRegexp( "image_parent_permission", ipsRegistry::member()->perm_id_array ),
						'image_approved=1'
						);
	
		//-----------------------------------------
		// Return query
		//-----------------------------------------
		
		$userGiving	= 'r';
		$extraWhere	= '';
		$extraJoin	= array();
		
		if ( $type == 'most' )
		{
			return array(
						'type'	=> 'image_id',

						/* Used in first query to fetch type_ids */
						'inner'	=> array(
											'select'	=> 'i.image_id',
											'from'		=> array( 'gallery_images' => 'i' ),
											'where'		=> implode( " AND ", $where ),
										),

						/* Used in second query to fetch actual data */
						'joins'	=> array(
										array(
											'select'	=> 'rc.*',
						 					'from'		=> array( 'reputation_cache' => 'rc' ),
											'where'		=> "rc.app='gallery' AND rc.type='image_id' AND rc.type_id=r.type_id"
											),
										array(
											'select'	=> 'c.*',
											'from'		=> array( 'gallery_comments' => 'c' ),
											'where'		=> 'r.type="comment_id" AND r.type_id=c.comment_id',
											'type'		=> 'left'
											),
										array(
											'select'	=> 'i.*',
											'from'		=> array( 'gallery_images' => 'i' ),
											'where'		=> 'i.image_id = IFNULL(c.comment_img_id, r.type_id)'
											) 
										)
						);
		}
		else
		{
			if ( $type !== NULL )
			{
				$where[]	= ( ( $type == 'given' ) ? "r.member_id={$member['member_id']}" : "( ( r.type='image_id' AND i.image_member_id={$member['member_id']} ) OR ( r.type='comment_id' AND c.comment_author_id={$member['member_id']} ) )" );
			}
			else
			{
				$userGiving	= 'r2';
				$extraJoin	= array(
									array(
										'from'		=> array( 'reputation_index' => 'r2' ),
										'where'		=> "r2.app=r.app AND r2.type=r.type AND r2.type_id=r.type_id AND r2.member_id=" . ipsRegistry::member()->getProperty('member_id')
										)
									);
			}
			
			return array(
						'select'	=> "r.*, rc.*, {$userGiving}.member_id as repUserGiving, i.*, c.*", // we have to do aliases on some of them due to duplicate column names
						'from'		=> array( 'reputation_index' => 'r'),
						'add_join'	=> array_merge( $extraJoin, array(
											array(
												'from'		=> array( 'reputation_cache' => 'rc' ),
												'where'		=> "rc.app=r.app AND rc.type=r.type AND rc.type_id=r.type_id"
												),
											array(
												'from'		=> array( 'gallery_comments' => 'c' ),
												'where'		=> 'r.type="comment_id" AND r.type_id=c.comment_id',
												'type'		=> 'left'
												),
											array(
												'from'		=> array( 'gallery_images' => 'i' ),
												'where'		=> 'i.image_id = IFNULL(c.comment_img_id, r.type_id)',
												'type'		=> 'left'
												),
											)	),
						'where'		=>	"r.app='gallery' AND " . 				// belongs to this app
										implode( " AND ", $where ) . ' AND ' .	// is viewable to the member
										" i.image_id<>0"						// is valid (not a bad row due to complex query)
						);
		}
	}
	
	/**
	 * Process Results
	 *
	 * @param	array		Row from database using query specified in fetch()
	 * @return	@e array	Same data with any additional processing necessary
	 */
	public function process( $row )
	{
		//-----------------------------------------
		// Comment or image?
		//-----------------------------------------

		if ( empty($row['comment_id']) )
		{
			$idField      = 'image_id';
			$authorField  = 'image_member_id';
			$contentField = 'image_description';
		}
		else
		{
			$idField      = 'comment_id';
			$authorField  = 'comment_author_id';
			$contentField = 'comment_text';
		}
	
		//-----------------------------------------
		// Build author's display data
		//-----------------------------------------

		$member	= $row[ $authorField ] ? IPSMember::load( $row[ $authorField ], 'profile_portal,pfields_content,sessions,groups', 'id' ) : IPSMember::setUpGuest();
		$row	= array_merge( $row, IPSMember::buildDisplayData( $member, array( 'reputation' => 0, 'warn' => 0 ) ) );
		
		//-----------------------------------------
		// Parse bbcode
		//-----------------------------------------

		IPSText::getTextClass('bbcode')->parse_smilies			= 1;
		IPSText::getTextClass('bbcode')->parse_html				= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'gallery';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];

		$row[ $contentField ]	= IPSText::getTextClass('bbcode')->preDisplayParse( $row[ $contentField ] );
		
		//-----------------------------------------
		// Reputation
		//-----------------------------------------

		if ( $row['repUserGiving']	== ipsRegistry::member()->getProperty('member_id') )
		{
			$row['has_given_rep']	= $row['rep_rating'];
		}

		$row['repButtons']	= ipsRegistry::getClass('repCache')->getLikeFormatted( array( 'app' => 'gallery', 'type' => $idField, 'id' => $row[ $idField ], 'rep_like_cache' => $row['rep_like_cache'] ) );

		//-----------------------------------------
		// Thumbnail
		//-----------------------------------------

		$row['thumb']	= ipsRegistry::getClass('gallery')->helper('image')->makeImageLink( $row, array( 'type' => 'thumb', 'coverImg' => false, 'link-type' => 'page' ) );
		
		//-----------------------------------------
		// And return
		//-----------------------------------------

		return $row;
	}
	
	/**
	 * Display Results
	 *
	 * @param	array		Results after being processed
	 * @param	@e string	HTML
	 */
	public function display( $results )
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( 'public_gallery', 'gallery' );
		
		return ipsRegistry::getClass('output')->getTemplate('gallery_external')->tabReputation_gallery( $results );
	}
}

$rep_author_config = array( 
						'comment_id'	=> array( 'column' => 'comment_author_id', 'table'  => 'gallery_comments' ),
						'image_id'		=> array( 'column' => 'image_member_id', 'table'  => 'gallery_images' )
					);
					
/*
 * The following config items are for the log viewer in the ACP 
 */

$rep_log_joins = array(
						array(
								'from'   => array( 'gallery_comments' => 'p' ),
								'where'  => 'r.type="comment_id" AND r.type_id=p.comment_id AND r.app="gallery"',
								'type'   => 'left'
							),
						array(
								'select' => 't.image_caption as repContentTitle, t.image_id as repContentID',
								'from'   => array( 'gallery_images' => 't' ),
								'where'  => 't.image_id = IFNULL(p.comment_img_id, r.type_id)',
								'type'   => 'left'
							),
					);

$rep_log_where = "p.comment_author_id=%s";

$rep_log_link = 'app=gallery&amp;module=images&amp;section=viewimage&amp;img=%d#comment_%d';