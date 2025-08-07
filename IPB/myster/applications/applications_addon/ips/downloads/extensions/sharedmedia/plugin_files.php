<?php
/**
 * @file		plugin_files.php 	Shared media plugin: download manager files
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		3/9/2011
 * $LastChangedDate: 2011-10-26 23:13:20 -0400 (Wed, 26 Oct 2011) $
 * @version		v2.5.4
 * $Revision: 9685 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_downloads_files
 * @brief		Provide ability to share download manager files via editor
 */
class plugin_downloads_files
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
		
		$this->lang->loadLanguageFile( array( 'public_downloads' ), 'downloads' );
		
		ipsRegistry::getAppClass( 'downloads' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTab()
	{
		if( $this->memberData['member_id'] )
		{
			return $this->lang->words['sharedmedia_downloads'];
		}
	}
	
	/**
	 * Return the HTML to display the tab
	 *
	 * @return	@e string
	 */
	public function showTab( $string )
	{
		//-----------------------------------------
		// Are we a member?
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		//-----------------------------------------
		// How many approved events do we have?
		//-----------------------------------------
		
		$st		= intval($this->request['st']);
		$each	= 30;
		$where	= '';
		
		if( $string )
		{
			$where	= " AND file_name LIKE '%{$string}%'";
		}

		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'downloads_files', 'where' => "file_open=1 AND file_submitter={$this->memberData['member_id']}" . $where ) );
		$rows	= array();

		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'seoTitle'			=> '',
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'ajaxLoad'			=> 'mymedia_content',
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=downloads&amp;tabplugin=files&amp;search=" . urlencode($string) )	);

		$this->DB->build( array( 'select' => '*', 'from' => 'downloads_files', 'where' => "file_open=1 AND file_submitter={$this->memberData['member_id']}" . $where, 'order' => 'file_updated DESC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$rows[]	= array(
							'image'		=> $this->registry->idmFunctions->returnScreenshotUrl( $r ),
							'width'		=> 0,
							'height'	=> 0,
							'title'		=> IPSText::truncate( $r['file_name'], 25 ),
							'desc'		=> IPSText::truncate( strip_tags( IPSText::stripAttachTag( IPSText::getTextClass('bbcode')->stripAllTags( $r['file_desc'] ) ), '<br>' ), 100 ),
							'insert'	=> "downloads:files:" . $r['file_id'],
							);
		}

		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'downloads', 'files' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$fileId		File ID to show
	 * @return	@e string
	 */
	public function getOutput( $fileId=0 )
	{
		$fileId	= intval($fileId);
		
		if( !$fileId )
		{
			return '';
		}

		$file	= $this->DB->buildAndFetch( array(
												'select'	=> 'f.*',
												'from'		=> array( 'downloads_files' => 'f' ),
												'where'		=> 'f.file_open=1 AND f.file_id=' . $fileId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'downloads_categories' => 'c' ),
																		'where'		=> 'f.file_cat=c.cid',
																		'type'		=> 'left',
																		)
																	)
										)		);

		return $this->registry->output->getTemplate('downloads_external')->bbCodeFile( $file );
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$fileId	File ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $fileId )
	{
		$fileId	= intval($fileId);
		
		if( !$fileId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$file	= $this->DB->buildAndFetch( array(
												'select'	=> 'f.*',
												'from'		=> array( 'downloads_files' => 'f' ),
												'where'		=> 'f.file_open=1 AND f.file_id=' . $fileId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'downloads_categories' => 'c' ),
																		'where'		=> 'f.file_cat=c.cid',
																		'type'		=> 'left',
																		)
																	)
										)		);
		
		if( $this->memberData['member_id'] AND $file['file_submitter'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}