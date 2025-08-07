<?php
/**
 * @file		gallery.php 	IP.Gallery report center plugin
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		4.0.0
 * $LastChangedDate: 2012-06-25 20:53:17 -0400 (Mon, 25 Jun 2012) $
 * @version		v5.0.5
 * $Revision: 10984 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class gallery_plugin
{
	/**#@+
	 * Registry objects
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
	 * Holds extra data for the plugin
	 *
	 * @var		array
	 */
	public $_extra;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Set registry objects
		//-----------------------------------------

		$this->registry   = $registry;
		$this->DB	      = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->lang		  = $this->registry->class_localization;
		
		//-----------------------------------------
		// Set gallery objects
		//-----------------------------------------

		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Load the language file
		//-----------------------------------------

		$registry->class_localization->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	@e string	HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		$return = $html->addRow(
								$this->lang->words['r_supermod'],
								sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
								$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
								);
							
		$return .= $html->addRow(
								$this->lang->words['r_galmod'],
								"",
								$this->registry->output->formYesNo('report_bypass', (!isset( $plugin_data['report_bypass'] )) ? 1 : $plugin_data['report_bypass'] )
								);

		return $return;
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @param	array 		Plugin data for save
	 * @return	@e string	Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		$save_data_array['report_supermod']	= intval($this->request['report_supermod']);
		$save_data_array['report_bypass']	= intval($this->request['report_bypass']);

		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	@e boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		return true;
	}
	
	/**
	 * Get report permissions
	 *
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	@e boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if( $this->_extra['report_bypass'] == 0 || ( $this->memberData['g_is_supmod'] == 1 && ( !isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) ) )
		{
			return true;
		}
		else
		{
			//-----------------------------------------
			// Do we have any moderators?
			//-----------------------------------------

			$cache	= $this->cache->getCache('gallery_moderators');

			if( !count($cache) )
			{
				return false;
			}
			else
			{
				//-----------------------------------------
				// Loop over them
				//-----------------------------------------

				foreach( $cache as $k => $v )
				{
					//-----------------------------------------
					// Can we delete comments?
					//-----------------------------------------

					if( $v['mod_can_delete_comments'] )
					{
						//-----------------------------------------
						// Is this us as a member moderator?
						//-----------------------------------------

						if( $v['mod_type'] == 'member' AND $this->memberData['member_id'] == $v['mod_type_id'] )
						{
							return true;
						}

						//-----------------------------------------
						// Is this a group moderator for a group we're a member of?
						//-----------------------------------------

						else if( $v['mod_type'] == 'group' )
						{
							if( IPSMember::isInGroup( $this->memberData, $v['mod_type_id'] ) )
							{
								return true;
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Show the report form for this module
	 *
	 * @param	array		$com_dat		Report plugin data
	 * @return	@e string	HTML form information
	 */
	public function reportForm( $com_dat )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$image		= array();
		$comment	= array();
		
		//-----------------------------------------
		// If it's a comment, get our data
		//-----------------------------------------

		if ( $this->request['ctyp'] == 'comment' )
		{
			$comment	= $this->registry->gallery->helper('comments')->fetchById( $this->request['commentId'] );

			if ( !$comment['comment_id'] )
			{
				$this->registry->output->showError( 'reports_no_commentid', 10163.1 );
			}
			
			$image   = $this->registry->gallery->helper('image')->fetchImage( $comment['comment_img_id'] );
			
			$ex_form_data	= array(
									'imageId'	=> $image['image_id'],
									'commentId'	=> $comment['comment_id'],
									'ctyp'		=> 'comment',
									'title'		=> $image['image_caption'] . ' ' . $this->lang->words['report_gallery_comment_suffix'] . ' #' . $comment['comment_id']
									);
		}

		//-----------------------------------------
		// Must be an image - get it
		//-----------------------------------------

		else
		{
			$image	= $this->registry->gallery->helper('image')->fetchImage( $this->request['imageId'] );
			
			$ex_form_data	= array(
									'imageId'	=> $image['image_id'],
									'ctyp'		=> 'image',
									'title'		=> $image['image_caption'] . ' ' . $this->lang->words['report_gallery_image_suffix']
									);
		}
		
		//-----------------------------------------
		// If there's no image found, that's an issue
		//-----------------------------------------

		if ( !$image['image_id'] )
		{
			$this->registry->output->showError( 'reports_no_imageid', 10163 );
		}
		
		//-----------------------------------------
		// Verify comment is approved or that we can moderate
		//-----------------------------------------

		if ( $comment['comment_id'] && !$comment['comment_approved'] && !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_approve_comments' ) )
		{
			$this->registry->output->showError( 'reports_no_commentid', 10163.2 );
		}

		//-----------------------------------------
		// Fetch navigation
		//-----------------------------------------

		$nav	= array( array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );
		$cat	= $this->registry->gallery->helper('categories')->getNav( $image['image_category_id'] );

		foreach( $cat as $_categoryNav )
		{
			$nav[]	= array( $_categoryNav[0], $_categoryNav[1], $_categoryNav[2], 'viewcategory' );
		}

		if( $image['image_album_id'] )
		{
			$_album	= $this->registry->gallery->helper('albums')->fetchAlbum( $image['image_album_id'] );

			$nav[]	= array( $_album['album_name'], 'app=gallery&amp;album=' . $_album['album_id'], $_album['album_name_seo'], 'viewalbum' );
		}

		$nav[]	= array( $image['image_caption'], 'app=gallery&amp;image=' . $image['image_id'], $image['image_caption_seo'], 'viewimage' );

		if ( $comment['comment_id'] )
		{
			$url	= $this->registry->output->buildUrl( "app=core&module=global&section=comments&do=findComment&comment_id={$comment['comment_id']}&parentId={$image['image_id']}&fromApp=gallery-images", 'public' );
			$nav[]	= array( $this->lang->words['reports_comment_title'] );
		}
		else
		{
			$url	= $this->registry->output->buildSEOUrl( "app=gallery&amp;image={$image['image_id']}", 'public', $image['image_caption_seo'], 'viewimage' );
			$nav[]	= array( $this->lang->words['reports_image_title'] );
		}

		//-----------------------------------------
		// Set output elements
		//-----------------------------------------

		$title	= $image['image_caption'];
		
		if ( $comment['comment_id'] )
		{
			$title	.= " ({$this->lang->words['comment_ucfirst']} #{$comment['comment_id']})";
		}

		$this->registry->getClass('output')->setTitle( $this->lang->words['reporting_title'] . ' ' . $title );

		foreach( $nav as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
		}

		//-----------------------------------------
		// Return report center elements
		//-----------------------------------------

		$this->lang->words['report_basic_title']	= $comment['comment_id'] ? $this->lang->words['reports_comment_title'] : $this->lang->words['reports_image_title'];
		$this->lang->words['report_basic_enter']	.= '<br /><br />' . $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'thumb' ) );

		return $this->registry->getClass('reportLibrary')->showReportForm( $title, $url, $ex_form_data );
	}
	
	/**
	 * Get section and link
	 *
	 * @param	array		$report_row		Report data
	 * @return	@e array	Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
		//-----------------------------------------
		// Get the image
		//-----------------------------------------

		$image	= $this->registry->gallery->helper('image')->fetchImage( $report_row['exdat1'] );
		
		//-----------------------------------------
		// If we have the image...
		//-----------------------------------------

		if ( $image['image_id'] )
		{
			if( $image['image_album_id'] )
			{
				$album		= $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['image_album_id'] );

				return array(
							'title'			=> IPSLib::getAppTitle('gallery') . ': '. $album['album_name'],
							'url'			=> '/index.php?app=gallery&amp;album=' . $album['album_id'],
							'seo_title'		=> $album['album_name_seo'],
							'seo_template'	=> 'viewalbum'
							);
			}
			else
			{
				$category	= $this->registry->gallery->helper('categories')->fetchCategory( $image['image_category_id'] );

				return array(
							'title'			=> IPSLib::getAppTitle('gallery') . ': '. $category['category_name'],
							'url'			=> '/index.php?app=gallery&amp;category=' . $category['category_id'],
							'seo_title'		=> $category['category_name_seo'],
							'seo_template'	=> 'viewcategory'
							);
			}
		}
		
		//-----------------------------------------
		// Fallback
		//-----------------------------------------

		return array(
					'title'			=> $this->lang->words['report_section_title_site_gallery'],
					'url'			=> '/index.php?app=gallery',
					'seo_title'		=> 'false',
					'seo_template'	=> 'app=gallery'
					);
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @param	array		$com_dat		Report plugin data
	 * @return	@e array	Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		//-----------------------------------------
		// INIT vars
		//-----------------------------------------

		$image		= array();
		$comment	= array( 'comment_id' => 0 );
		$return		= array();
		$repUrl		= '';
		$status		= array();
		
		$this->request['ctyp']	= trim($this->request['ctyp']);

		//-----------------------------------------
		// This a comment?
		//-----------------------------------------

		if ( $this->request['ctyp'] == 'comment' )
		{
			$comment	= $this->registry->gallery->helper('comments')->fetchById( $this->request['commentId'] );
			
			if ( !$comment['comment_id'] )
			{
				$this->registry->output->showError( 'reports_no_commentid', 10163.3 );
			}
			
			$image		= $this->registry->gallery->helper('image')->fetchImage( $comment['comment_img_id'] );
			
			$repUrl		= "app=core&module=global&section=comments&do=findComment&comment_id={$comment['comment_id']}&parentId={$image['image_id']}&fromApp=gallery-images";
		}
		else
		{
			$image		= $this->registry->gallery->helper('image')->fetchImage( $this->request['imageId'] );
			
			$repUrl		= "app=gallery&amp;image={$image['image_id']}";
		}

		//-----------------------------------------
		// Got the image?
		//-----------------------------------------

		if ( !$image['image_id'] )
		{
			$this->registry->output->showError( 'reports_no_imageid', 10165 );
		}

		//-----------------------------------------
		// Verify comment is approved or that we can moderate
		//-----------------------------------------

		if ( $comment['comment_id'] && !$comment['comment_approved'] && !$this->registry->gallery->helper('categories')->checkIsModerator( $image['image_category_id'], null, 'mod_can_approve_comments' ) )
		{
			$this->registry->output->showError( 'reports_no_commentid', 10163.2 );
		}
		
		//-----------------------------------------
		// Build our unique id
		//-----------------------------------------

		$uid	= md5(  'gallery_' . $this->request['ctyp'] . '_' . $image['image_id'] . '_' . $comment['comment_id'] . '_' . $com_dat['com_id'] );
		
		//-----------------------------------------
		// Get statuses
		//-----------------------------------------

		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> 'is_new=1 OR is_complete=1'
						 )		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		//-----------------------------------------
		// Update existing report, or create new one
		//-----------------------------------------

		$_reportData = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		
		if( $_reportData['id'] )
		{
			$this->DB->update( 'rc_reports_index', 'num_reports=num_reports+1,date_updated=' . IPS_UNIX_TIME_NOW . ',status=' . intval($status['new']), "id={$_reportData['id']}", false, true );
		}
		else
		{	
			$_reportData = array(
								'uid'			=> $uid,
								'title'			=> $this->request['title'],
								'status'		=> $status['new'],
								'url'			=> '/index.php?' . $repUrl,
								'seoname'		=> $comment['comment_id'] ? '' : $image['image_caption_seo'],
								'seotemplate'	=> $comment['comment_id'] ? '' : 'viewimage',
								'rc_class'		=> $com_dat['com_id'],
								'updated_by'	=> $this->memberData['member_id'],
								'date_updated'	=> IPS_UNIX_TIME_NOW,
								'date_created'	=> IPS_UNIX_TIME_NOW,
								'img_preview'	=> '',
								'exdat1'		=> $image['image_id'],
								'exdat2'		=> $comment['comment_id'],
								'exdat3'		=> 0,
								'num_reports'	=> 1,
								);

			$this->DB->insert( 'rc_reports_index', $_reportData );
			
			$_reportData['id']	= $this->DB->getInsertId();
		}
		
		//-----------------------------------------
		// Insert the actual report
		//-----------------------------------------

		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
		
		$build_report	= array(
								'rid'			=> $_reportData['id'],
								'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
								'report_by'		=> $this->memberData['member_id'],
								'date_reported'	=> IPS_UNIX_TIME_NOW
								);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		//-----------------------------------------
		// Set the appropriate return data
		//-----------------------------------------

		$return = array(
						'REDIRECT_URL'	=> $repUrl,
						'REPORT_INDEX'	=> $_reportData['id'],
						'SAVED_URL'		=> $_reportData['url'],
						'REPORT'		=> $build_report['report'],
						'SEOTITLE'		=> $comment['comment_id'] ? '' : $image['image_caption_seo'],
						'TEMPLATE'		=> $comment['comment_id'] ? '' : 'viewimage'
						);
		
		return $return;
	}

	/**
	 * Accepts an array of data from rc_reports_index and returns an array formatted nearly identical to processReport()
	 *
	 * @param 	array 		Report data
	 * @return	array 		Formatted report data
	 */
	public function formatReportData( $report_data )
	{
		return array(
					'REDIRECT_URL'	=> $report_data['url'],
					'REPORT_INDEX'	=> $report_data['id'],
					'SAVED_URL'		=> str_replace( '&amp;', '&', $report_data['url'] ),
					'REPORT'		=> '',
					'SEOTITLE'		=> $report_data['seoname'],
					'TEMPLATE'		=> $report_data['viewimage'],
					);
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @param	array		$report_data		Report data
	 * @return	@e void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'], $report_data['SEOTITLE'], $report_data['TEMPLATE'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @param	string 		Group ids
	 * @param	array 		Report data
	 * @return	@e array 	Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		$notify	= array();
		
		$this->DB->build( array(
									'select'	=> 'noti.*',
									'from'		=> array( 'rc_modpref' => 'noti' ),
									'where'		=> 'mem.member_group_id IN(' . $group_ids . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
															'from'		=> array( 'members' => 'mem' ),
															'where'		=> 'mem.member_id=noti.mem_id',
															)
														)
							)		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() > 0 )
		{
			while( $row = $this->DB->fetch() )
			{
				$notify[] = $row;
			}	
		}
		
		return $notify;
	}
}