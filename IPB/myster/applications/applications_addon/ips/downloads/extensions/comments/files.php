<?php
/**
 * Downloads comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_downloads_files extends classes_comments_renderer
{
	/**
	 * Internal remap array
	 *
	 * @var	array
	 */
	protected $_remap = array(  'comment_id'			=> 'comment_id',
								'comment_author_id'		=> 'comment_mid',
								'comment_author_name'	=> 'comment_author',
								'comment_text'			=> 'comment_text',
								'comment_ip_address'	=> 'ip_address',
								'comment_edit_date'		=> 'comment_edit_time',
								'comment_date'			=> 'comment_date',
								'comment_approved'		=> 'comment_open',
								'comment_parent_id'		=> 'comment_fid' );
					 
	/**
	 * Internal parent remap array
	 *
	 * @var	array
	 */
	protected $_parentRemap = array( 'parent_id'		=> 'file_id',
							 		 'parent_owner_id'	=> 'file_submitter',
									 'parent_parent_id' => 'file_cat',
									 'parent_title'	    => 'file_name',
									 'parent_seo_title' => 'file_name_furl',
									 'parent_date'	    => 'file_submitted' );

	/**
	 * Stored files
	 *
	 * @var	array
	 */
	protected $_files	= array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		parent::__construct();

		//-----------------------------------------
		// Make sure we have categories and functions
		//-----------------------------------------
		
		if( !$this->registry->isClassLoaded('idmFunctions') )
		{
			ipsRegistry::getAppClass( 'downloads' );
		}
	}
	
	/**
	 * Parent SEO template
	 *
	 * @return	string
	 */
	public function seoTemplate()
	{
		return 'idmshowfile';
	}

	/**
	 * Who am I?
	 *
	 * @return	string
	 */
	public function whoAmI()
	{
		return 'downloads-files';
	}
	
	/**
	 * Comment table
	 *
	 * @return	string
	 */
	public function table()
	{
		return 'downloads_comments';
	}
	
	/**
	 * Fetch parent
	 *
	 * @return	array
	 */
	public function fetchParent( $id )
	{
		if( !isset($this->_files[ $id ]) )
		{
			if( $this->cache->getCache( 'idm_file_' . $id, false ) )
			{
				$this->_files[ $id ]	= $this->cache->getCache( 'idm_file_' . $id );
			}
			else
			{
				$this->_files[ $id ]	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => 'file_id=' . intval($id) ) );
			}
		}
		
		return $this->_files[ $id ];
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=downloads&showfile=%s",
					  'urls-report'		=> $this->getReportLibrary()->canReport( 'downloads' ) ? "app=core&amp;module=reports&amp;rcom=downloads&amp;comment=%s&amp;file=%s" : '' );
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		return $this->settings['idm_comments_num'];
	}
	
	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function preSave( $type, array $array )
	{
		if ( $type == 'add' )
		{
			$file	= $this->fetchParent( $array['comment_parent_id'] );

			/* Test approval */
			if ( $array['comment_approved'] )
			{
				$array['comment_approved'] = !$this->settings['idm_comment_approval'] ? 1 : ( $this->registry->getClass('idmFunctions')->checkPerms( $file, 'modcancomments' ) ? 1 : 0 );
			}
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'downloadAddFileComment' );
		}
		else
		{
			/* Data Hook Location */
			IPSLib::doDataHooks( $array, 'downloadEditFileComment' ); 
		}
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array )
	{
		$this->registry->getClass('idmFunctions')->rebuildPendingComments( $array['comment_parent_id'] );
		$this->registry->getClass('idmFunctions')->rebuildComments( $array['comment_parent_id'] );

		IPSLib::doDataHooks( $array, 'downloadComment' . ucfirst( $type ) . 'PostSave' );
		
		return $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		$this->registry->getClass('idmFunctions')->rebuildPendingComments( $parentId );
		$this->registry->getClass('idmFunctions')->rebuildComments( $parentId );
		
		$_dataHook	= array( 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
							 
		/* Data Hook Location */
		IPSLib::doDataHooks( $_dataHook, 'downloadCommentPostDelete' );
	}
	
	/**
	 * Toggles visibility
	 * 
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
		$this->registry->getClass('idmFunctions')->rebuildPendingComments( $parentId );
		$this->registry->getClass('idmFunctions')->rebuildComments( $parentId );
		
		$_dataHook	= array( 'toggle'		=> $toggle,
							 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );
							 
		/* Data Hook Location */
		IPSLib::doDataHooks( $_dataHook, 'downloadCommentToggleVisibility' );
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		/* Get parent */
		if ( is_numeric( $parent ) )
		{
			$parent	= $this->fetchParent( $parent );
		}
		
		$canMod		= $this->registry->getClass('idmFunctions')->checkPerms( $parent, 'modcancomments' );

		if( $canMod )
		{
			return ( intval($parent['file_comments']) + intval($parent['file_pendcomments']) );
		}
		
		return intval($parent['file_comments']);
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{ 
		/* Init */
		$comment = array();
		
		/* Got data? */
		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		/* Get the file */
		$file	= $this->fetchParent( $array['comment_parent_id'] );

		/* Fetch comment */
		if ( $array['comment_id'] )
		{ 
			$comment = $this->fetchById( $array['comment_id'] );
		}

		if( !$this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ]['coptions']['opt_comments'] )
		{
			return 'NO_PERMISSION';
		}

		/* Check permissions */
		switch( $type )
		{
			case 'view':
				if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
				{
					return 'NO_PERMISSION';
				}
				else if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			case 'edit':
				if( count($this->registry->getClass('categories')->member_access['comment']) == 0 OR !in_array($file['file_cat'], $this->registry->getClass('categories')->member_access['comment']) )
				{
					return 'NO_PERMISSION';
				}

				if( !$this->memberData['member_id'] OR !$this->registry->getClass('idmFunctions')->checkPerms( array_merge( $file, $comment), 'modcancomments', 'idm_comment_edit' ) )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			case 'add':
				if( count($this->registry->getClass('categories')->member_access['comment']) == 0 OR !in_array($file['file_cat'], $this->registry->getClass('categories')->member_access['comment']) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'delete':
				if ( !$this->memberData['member_id'] OR !$this->registry->getClass('idmFunctions')->checkPerms( array_merge( $file, $comment), 'modcancomments', 'idm_comment_delete' ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'visibility':
			case 'moderate':
				if ( !$this->registry->getClass('idmFunctions')->checkPerms( $file, 'modcancomments' ) )
				{
					return 'NO_PERMISSION';
				}
				
				return true;
			break;
			case 'hide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
			case 'unhide':
				return IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) ? TRUE : 'NO_PERMISSION';
				break;
		}
	}

	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	array
	 */
	public function remapKeys( $type='comment' )
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
}