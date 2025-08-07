<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Rebuild post content plugin
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_downloads
{
	/**
	 * New content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $parser;

	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
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
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		ipsRegistry::getAppClass( 'downloads' );
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @access	public
	 * @return	array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );

		$return		= array( array( 'idm_files', ipsRegistry::getClass('class_localization')->words['rebuild_idm_files'] ) );
		$return[]	= array( 'idm_comments', ipsRegistry::getClass('class_localization')->words['rebuild_idm_comms'] );
	    return $return;
	}
	
	/**
	 * Find out if there are any more
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @return	integer
	 */
	public function getMax( $type, $dis )
	{
		switch( $type )
		{
			case 'idm_files':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'file_id as nextid', 'from' => 'downloads_files', 'where' => 'file_id > ' . $dis, 'order' => 'file_id ASC', 'limit' => array(1)  ) );
			break;
			
			case 'idm_comments':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'comment_id as nextid', 'from' => 'downloads_comments', 'where' => 'comment_id > ' . $dis, 'order' => 'comment_id ASC', 'limit' => array(1)  ) );
			break;
		}
		
		return intval( $tmp['nextid'] );
	}
	
	/**
	 * Execute the database query to return the results
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @param	integer		End point
	 * @return	integer
	 */
	public function executeQuery( $type, $start, $end )
	{
		switch( $type )
		{
			case 'idm_files':
				$this->DB->build( array( 'select' 	=> 'f.*',
														 'from' 	=> array( 'downloads_files' => 'f' ),
														 'order' 	=> 'f.file_id ASC',
														 'where'	=> 'f.file_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=f.file_submitter"
														  						)	)
												) 		);
			break;
			
			case 'idm_comments':
				$this->DB->build( array( 'select' 	=> 'c.*',
														 'from' 	=> array( 'downloads_comments' => 'c' ),
														 'order' 	=> 'c.comment_id ASC',
														 'where'	=> 'c.comment_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=c.comment_mid"
														  						),
														  						2 => array( 'type'		=> 'left',
														  									'select'	=> 'f.file_cat',
														  								  	'from'		=> array( 'downloads_files' => 'f' ),
														  								  	'where' 	=> "f.file_id=c.comment_fid"
														  						)	)
												) 		);
			break;
		}
	}
	
	/**
	 * Get preEditParse of the content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @return	string		Content preEditParse
	 */
	public function getRawPost( $type, $r )
	{
		$category	= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ];

		$this->parser->parse_smilies	= 1;
		$this->parser->parse_html		= $category['coptions']['opt_html'] ? 1 : 0;
		$this->parser->parse_bbcode		= $category['coptions']['opt_bbcode'] ? 1 : 0;
		$this->parser->parse_nl2br		= 1;

		switch( $type )
		{
			case 'idm_files':
				$this->parser->parsing_section	= 'idm_submit';

				$rawpost = $this->parser->preEditParse( $r['file_desc'] );
			break;
			
			case 'idm_comments':
				$this->parser->parsing_section	= 'idm_comment';

				$rawpost = $this->parser->preEditParse( $r['comment_text'] );
			break;
		}

		return $rawpost;
	}
	
	/**
	 * Store the newly converted content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @param	string		Newly parsed post
	 * @return	string		Content preEditParse
	 */
	public function storeNewPost( $type, $r, $newpost )
	{
		$lastId	= 0;
		
		switch( $type )
		{
			case 'idm_files':
				$this->DB->update( 'downloads_files', array( 'file_desc' => $newpost ), 'file_id=' . $r['file_id'] );
				$lastId = $r['file_id'];
			break;
			
			case 'idm_comments':
				$this->DB->update( 'downloads_comments', array( 'comment_text' => $newpost ), 'comment_id=' . $r['comment_id'] );
				$lastId = $r['comment_id'];
			break;
		}

		return $lastId;
	}
}