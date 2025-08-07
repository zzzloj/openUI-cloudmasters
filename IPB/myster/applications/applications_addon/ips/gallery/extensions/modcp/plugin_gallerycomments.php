<?php
/**
 * @file		plugin_gallerycomments.php 	IP.Gallery unapproved comments modcp plugin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		5th October 2011
 * $LastChangedDate: 2012-07-09 19:54:15 -0400 (Mon, 09 Jul 2012) $
 * @version		v5.0.5
 * $Revision: 11048 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_gallery_gallerycomments
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Cat permissions
	 *
	 * @var	string
	 */
	protected $_categories	= null;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;

		//-----------------------------------------
		// Get gallery objects
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded( 'gallery') )
		{ 
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Get language strings
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		if ( $this->_moderateCategories( $permissions ) )
		{ 
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{ 
		return 'gallerycomments';
	}
	
	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if ( ! $this->_moderateCategories( $permissions ) )
		{
			return '';
		}
				
		//----------------------------------
		// Get comments pending approval
		//----------------------------------
		
		IPSText::getTextClass('bbcode')->parsing_section		= 'gallery_submit';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];

		$limiter	= ( $this->_categories == '*' ) ? '' : " AND i.image_category_id IN({$this->_categories})";

		$this->DB->build( array(
								'select'	=> 'c.*',
								'from'		=> array( 'gallery_comments' => 'c' ),
								'where'		=> "c.comment_approved=0" . $limiter,
								'add_join'	=> array(
													array(
															'select'	=> 'm.*, m.member_id as my_member_id',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=c.comment_author_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'm.member_id=pp.pp_member_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'i.*',
															'from'		=> array( 'gallery_images' => 'i' ),
															'where'		=> 'i.image_id=c.comment_img_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 'a.*',
															'from'		=> array( 'gallery_albums' => 'a' ),
															'where'		=> 'i.image_album_id=a.album_id',
															'type'		=> 'left',
														),
													)
							)		);
		$e = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $e ) )
		{
			//----------------------------------
			// Reset member ID
			//----------------------------------

			$row['member_id'] = intval($row['my_member_id']);
			
			//----------------------------------
			// BBCode parsing
			//----------------------------------

			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'global_comments';
			
			$row['comment']   = IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment'] );

			//----------------------------------
			// Merge in category data
			//----------------------------------

			$row	= array_merge( $row, $this->registry->gallery->helper('categories')->fetchCategory( $row['image_category_id'] ) );
			
			$results[] = array_merge( $row, IPSMember::buildDisplayData( $row['member_id'] ? $row : IPSMember::setUpGuest( $comment['comment_author_name'] ) ) );
		}
						
		return $this->registry->getClass('output')->getTemplate('gallery_external')->unapprovedComments( $results );
	}

	/**
	 * Determine where we can approve comments in
	 *
	 * @return	@e string
	 */
	protected function _moderateCategories( $permissions='' )
	{
		//-----------------------------------------
		// If it's not null, we've checked
		//-----------------------------------------

		if ( $this->_categories !== null )
		{
			return $this->_categories;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$appcats = '';

		//-----------------------------------------
		// Supermods can moderate everywhere
		//-----------------------------------------

		if( $this->memberData['g_is_supmod'] )
		{
			$appcats = '*';
		}
		else
		{
			//-----------------------------------------
			// Are we a member moderator?
			//-----------------------------------------

			if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['mod_can_approve_comments'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['mod_categories'];
							}
							else
							{
								$appcats = $v['mod_categories'];
							}
						}
					}
				}
			}

			//-----------------------------------------
			// Are we a group moderator?
			//-----------------------------------------

			else if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['mod_can_approve_comments'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['mod_categories'];
							}
							else
							{
								$appcats = $v['mod_categories'];
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Save for later and return
		//-----------------------------------------

		$this->_categories	= $appcats;

		return $this->_categories;
	}
}