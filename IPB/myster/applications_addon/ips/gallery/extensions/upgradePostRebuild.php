<?php
/**
 * @file		upgradePostRebuild.php 	Rebuilds post content in Gallery following an upgrade from 2.x.
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		6/24/2008
 * $LastChangedDate: 2013-04-30 21:56:42 -0400 (Tue, 30 Apr 2013) $
 * @version		v5.0.5
 * $Revision: 12209 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_gallery
{
	/**
	 * New content parser
	 *
	 * @var		object
	 */
	public $parser;

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
	protected $cache;
	/**#@-*/
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set registry objects
		//-----------------------------------------

		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		ipsRegistry::getAppClass('gallery');
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @return	@e array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		$return		= array( array( 'gal_images', ipsRegistry::getClass('class_localization')->words['rebuild_gal_images'] ) );
		$return[]	= array( 'gal_comments', ipsRegistry::getClass('class_localization')->words['rebuild_gal_comms'] );

	    return $return;
	}
	
	/**
	 * Find out if there are any more
	 *
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @return	@e integer
	 */
	public function getMax( $type, $dis )
	{
		switch( $type )
		{
			case 'gal_images':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'image_id as nextid', 'from' => 'gallery_images', 'where' => 'image_id > ' . $dis, 'order' => 'image_id ASC', 'limit' => array(1)  ) );
			break;
			
			case 'gal_comments':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'comment_id as nextid', 'from' => 'gallery_comments', 'where' => 'comment_id > ' . $dis, 'order' => 'comment_id ASC', 'limit' => array(1)  ) );
			break;
		}

		return intval( $tmp['nextid'] );
	}
	
	/**
	 * Execute the database query to return the results
	 *
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @param	integer		End point
	 * @return	@e integer
	 */
	public function executeQuery( $type, $start, $end )
	{
		switch( $type )
		{
			case 'gal_images':
				$this->DB->build( array(
										'select'	=> 'i.*',
										'from'		=> array( 'gallery_images' => 'i' ),
										'order'		=> 'i.image_id ASC',
										'where'		=> 'i.image_id > ' . $start,
										'limit'		=> array( $end ),
										'add_join'	=> array(
															array(
																'type'		=> 'left',
																'select'	=> 'm.member_group_id, m.mgroup_others',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_id=i.image_member_id"
																)
															)
								)		);
			break;
			
			case 'gal_comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'gallery_comments' => 'c' ),
										'order'		=> 'c.comment_id ASC',
										'where'		=> 'c.comment_id > ' . $start,
										'limit'		=> array( $end ),
										'add_join'	=> array(
															array(
																'type'		=> 'left',
																'select'	=> 'm.member_group_id, m.mgroup_others',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_id=c.comment_author_id"
																),
															)
								)		);
			break;
		}
	}
	
	/**
	 * Get preEditParse of the content
	 *
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @return	@e string	Content preEditParse
	 */
	public function getRawPost( $type, $r )
	{
		$this->parser->parse_smilies	= 1;
		$this->parser->parse_html		= 0;
		$this->parser->parse_bbcode		= 1;
		$this->parser->parse_nl2br		= 1;

		switch( $type )
		{
			case 'gal_images':
				$this->parser->parsing_section	= 'gallery_image';

				$rawpost = $this->parser->preEditParse( $r['image_description'] );
			break;
			
			case 'gal_comments':
				$this->parser->parsing_section	= 'gallery_comment';

				$rawpost = $this->parser->preEditParse( $r['comment_text'] );
			break;
		}

		return $rawpost;
	}
	
	/**
	 * Store the newly converted content
	 *
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @param	string		Newly parsed post
	 * @return	@e string	Content preEditParse
	 */
	public function storeNewPost( $type, $r, $newpost )
	{
		$lastId	= 0;
		
		switch( $type )
		{
			case 'gal_images':
				$this->DB->update( 'gallery_images', array( 'image_description' => $newpost ), 'image_id=' . $r['image_id'] );
				$lastId = $r['image_id'];
			break;
			
			case 'gal_comments':
				$this->DB->update( 'gallery_comments', array( 'comment_text' => $newpost ), 'comment_id=' . $r['comment_id'] );
				$lastId = $r['comment_id'];
			break;
		}

		return $lastId;
	}
}