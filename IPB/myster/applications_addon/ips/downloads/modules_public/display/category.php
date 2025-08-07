<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IDM category listing
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
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_downloads_display_category extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @access	protected
	 * @var 	string 				Page output
	 */
	protected $output		= "";

	/**
	 * Member can add to a category
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $canadd		= false;

	/**
	 * Member can moderate a category
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $canmod		= false;
	
	/**
	 * Got subcategories?
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $hascats		= false;

	/**
	 * Got files?
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $hasfiles		= false;

	/**
	 * Sorting limit options
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $sort_num		= array( 5, 10, 15, 20, 25 );
	
	/**
	 * Sorting by options
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $sort_by		= array( 'A-Z'	=> 'ASC',
									 'Z-A'	=> 'DESC' );
								 
	/**
	 * Sorting column options
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $sort_key		= array( 'file_downloads'	=> 'downloads',
									 'file_submitted'	=> 'submitted',
									 'file_name'		=> 'title',
									 'file_views'		=> 'views',
									 'file_rating'		=> 'rating',
									 'file_updated'		=> 'updated',
									 'file_comments'	=> 'comments' );

	/**
	 * Like object
	 *
	 * @var	object
	 */
	protected $_like;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$category_id	= intval($this->request['showcat']);
		
		//-------------------------------------------
		// Page title and navigation bar
		//-------------------------------------------
		
		$this->registry->output->addNavigation( IPSLib::getAppTitle('downloads'), 'app=downloads', 'false', 'app=downloads' );
	
		//-------------------------------------------
		// Files per page dropdown
		//-------------------------------------------
		
		if( $this->settings['idm_ddfilesperpage'] )
		{
			$this->sort_num = explode( ",", $this->settings['idm_ddfilesperpage'] );
		}
		
		//-------------------------------------------
		// Moderation ids
		//-------------------------------------------
		
		$this->request['selectedfileids'] = IPSCookie::get( 'modfileids' );

		$this->request['selectedfilecount'] = intval( count( preg_split( "/,/", $this->request['selectedfileids'], -1, PREG_SPLIT_NO_EMPTY ) ) );
		
		if( $this->request['selectedfilecount'] > 0 )
		{
			$this->lang->words['mod_button'] .= ' (' . $this->request['selectedfilecount'] . ')';
		}

		//-------------------------------------------
		// Check permissions
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_downloads_cats_created', 10838, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_downloads_permissions', 10839, null, null, 403 );
			}
		}
		else
		{
			if( isset( $this->registry->getClass('categories')->member_access['add'][ $category_id ] ) )
			{
				$this->canadd = true;
			}
			
			$this->canmod = $this->registry->getClass('idmFunctions')->isModerator();
		}
		
		//-----------------------------------------
		// Like class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'downloads', 'categories' );
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('downloadsTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'downloadsTags', classes_tags_bootstrap::run( 'downloads', 'files' ) );
		}

		//-------------------------------------------
		// Get the cat and loop to show subcats
		//-------------------------------------------
		
		$category		= $this->registry->getClass('categories')->cat_lookup[$category_id];
		$category_count	= 0;
		$category_rows	= array();
		$file_rows		= array();

		if( count( $this->registry->getClass('categories')->cat_cache[ $category_id ] ) > 0 )
		{
			foreach( $this->registry->getClass('categories')->cat_cache[ $category_id ] as $cid => $cinfo )
			{
				if( in_array( $cid, $this->registry->getClass('categories')->member_access['show'] ) )
				{
					$cinfo['can_approve']		= $this->registry->getClass('idmFunctions')->checkPerms( array( 'file_cat' => $cid ) );
					$cinfo['subcategories']		= "";
					
					$rtime						= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $cinfo['cid'] ), 'downloads' );
					
					if( !isset($cinfo['_has_unread']) )
					{
						$cinfo['_has_unread']	= ( $cinfo['cfileinfo']['date'] && $cinfo['cfileinfo']['date'] > $rtime ) ? 1 : 0;
					}					

					if( count($this->registry->getClass('categories')->subcat_lookup[$cid]) > 0 )
					{
						$sub_links = array();
						
						foreach( $this->registry->getClass('categories')->subcat_lookup[$cid] as $blank_key => $subcat_id )
						{
							if( in_array( $subcat_id, $this->registry->getClass('categories')->member_access['show'] ) )
							{
								$subcat_data = $this->registry->getClass('categories')->cat_lookup[ $subcat_id ];
							
								if ( is_array( $subcat_data ) )
								{
									$subcattime	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $subcat_data['cid'] ), 'downloads' );
									
									if( !isset($subcat_data['new']) )
									{
										$subcat_data['new']	= ( $subcat_data['cfileinfo']['date'] && $subcat_data['cfileinfo']['date'] > $subcattime ) ? 1 : 0;
									}
									
									$sub_links[] = $subcat_data;
								}
							}
						}
						
						$cinfo['subcategories'] = $sub_links;
					}

					$category_rows[] = $cinfo;
					$category_count++;
				}
			}

			if( $category_count > 0 )
			{
				$this->hascats	= true;
			}
			else
			{
				if( !in_array( $category_id, $this->registry->getClass('categories')->member_access['show'] ) )
				{
					if( $category['coptions']['opt_noperm_view'] )
					{
						$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 10840, null, null, 403 );
					}
					else
					{
						$this->registry->output->showError( 'no_permitted_categories', 10841, null, null, 403 );
					}
				}
			}
		}
		
		//-------------------------------------------
		// Now go for the files
		//-------------------------------------------

		if( in_array( $category_id, $this->registry->getClass('categories')->member_access['show'] ) )
		{
			$sort_by	= ( $this->request['sort_order'] && in_array( strtoupper($this->request['sort_order']), $this->sort_by ) ) ? $this->request['sort_order'] : ( $category['coptions']['opt_sortby'] ? $this->sort_by[$category['coptions']['opt_sortby']] : 'DESC' );
			$sort_key	= $this->request['sort_key'] = ( $this->request['sort_key'] && isset( $this->sort_key[ $this->request['sort_key'] ] ) ) ? $this->request['sort_key'] : ( $category['coptions']['opt_sortorder'] ? 'file_'.$category['coptions']['opt_sortorder'] : 'file_updated' );
			$num		= ( $this->request['num'] && in_array( $this->request['num'], $this->sort_num ) ) ? intval($this->request['num']) : $this->settings['idm_filesperpage'];

			$st			= $this->request['st'] ? intval($this->request['st']) : 0;
			$st			= $this->request['dosort'] ? 0 : $st;
			
			$canapp		= $this->registry->getClass('idmFunctions')->checkPerms( array( 'file_cat' => $category_id ) );
			$cancomment	= $this->registry->getClass('idmFunctions')->checkPerms( array( 'file_cat' => $category_id ), 'modcancomments' );
			$where		= array();
			
			if( !$canapp && $this->memberData['member_id'] )
			{
				$where[]		= "( f.file_open=1 OR f.file_submitter={$this->memberData['member_id']} )";
			}
			else if ( !$canapp )
			{
				$where[]		= "( f.file_open=1 )";
			}
			
			$extrasort		= $canapp ? "f.file_open ASC, " : '';
			$extrasort		= "f.file_pinned DESC, " . $extrasort;
			
			if( !$category['coptions']['opt_disfiles'] )
			{
				$_children	= $this->registry->getClass('categories')->getChildren( $category_id );
				$_chillen	= array();
				
				foreach( $_children as $_child )
				{
					if( in_array( $_child, $this->registry->getClass('categories')->member_access['show'] ) )
					{
						$_chillen[]	= $_child;
					}
				}
				
				if( count($_chillen) )
				{
					$where[]	= "f.file_cat IN(" . implode( ',', $_chillen ) . ")";
				}
				else
				{
					$where[]	= "f.file_cat=0";
				}
			}
			else
			{
				$where[]	= "f.file_cat=" . $category['cid'];
			}
			
			//-----------------------------------------
			// Filter free/paid?
			//-----------------------------------------

			$_filterKey = '';

			if( IPSLib::appIsInstalled('nexus') )
			{
				if( !empty($this->request['filter_key']) AND $this->request['filter_key'] != 'all' )
				{
					if( $this->request['filter_key'] == 'free' )
					{
						$where[] = "(f.file_cost=0 AND ( f.file_nexus='' OR f.file_nexus=0 ))";
					}
					else
					{
						$where[] = "(f.file_cost > 0 OR ( f.file_nexus != '' AND f.file_nexus != 0 ))";
					}
				}
				
				/* Need a def value there.. */
				$this->request['filter_key'] = $this->request['filter_key'] ? trim($this->request['filter_key']) : 'all';
				
				$_filterKey = '&amp;filter_key='.$this->request['filter_key'];
			}
			
			$file_count = $this->DB->buildAndFetch( array(	'select'	=> 'COUNT(*) as max',
															'from'		=> 'downloads_files f',
															'where'		=> implode( ' AND ', $where ),
												)		);
						
			$page_links = $this->registry->output->generatePagination( array(
																			'totalItems'		=> $file_count['max'],
																			'itemsPerPage'		=> $num,
																			'currentStartValue'	=> $st,
																			'baseUrl'			=> "app=downloads&amp;showcat=".$category['cid']."&amp;sort_order={$sort_by}&amp;sort_key={$sort_key}&amp;num={$num}{$_filterKey}" . ($this->request['filter'] ? "&amp;filter={$this->request['filter']}" : ''),
																			'seoTitle'			=> $category['cname_furl'],
																			'seoTemplate'		=> 'idmshowcat',
																	)		);
			
			if( $file_count['max'] )
			{			
				$_fileIds	= array();
				
				$_mySort	= "f.{$sort_key} {$sort_by}";
				
				if( $sort_key == 'file_rating' )
				{
					$_mySort	= "f.file_rating {$sort_by}, " . $this->DB->buildLength( "f.file_votes" ) . " {$sort_by}";
				}
				
				$this->DB->build( array(
											'select'	=> 'f.*',
											'from'		=> array( 'downloads_files' => 'f' ),
											'where'		=> implode( ' AND ', $where ),
											'order'		=> $extrasort . $_mySort,
											'limit'		=> array( $st, $num ),
											'add_join'	=> array(
																array(
																		'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name, m.member_group_id',
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_id=f.file_submitter',
																		'type'		=> 'left',
																	),
																$this->registry->downloadsTags->getCacheJoin( array( 'meta_id_field' => 'f.file_id' ) )
																)
									)		);
				$res	= $this->DB->execute();
				
				while( $row = $this->DB->fetch( $res ) )
				{
					$row['_isRead']				= $this->registry->classItemMarking->isRead( array( 'forumID' => $row['file_cat'], 'itemID' => $row['file_id'], 'itemLastUpdate' => $row['file_updated'] ), 'downloads' );
					$row['_has_screenshots']	= 0;
					$row['file_purchases']		= 0;
					
					if( !$category['coptions']['opt_disfiles'] )
					{
						$row['_breadcrumb'] = $this->registry->getClass('categories')->getNav( $row['file_cat'] );
					}
					
					//-------------------------------------------
					// Get tags
					//-------------------------------------------
					
					if ( ! empty( $row['tag_cache_key'] ) )
					{
						$row['tags'] = $this->registry->downloadsTags->formatCacheJoinData( $row );
					}
		
					$file_rows[ intval($row['file_id']) ] = $row;
					
					if( $row['file_cost'] )
					{
						$_fileIds[]	= intval($row['file_id']);
					}
				}
				
				//-----------------------------------------
				// Get purchase count
				//-----------------------------------------
				
				if( count($_fileIds) AND IPSLib::appIsInstalled('nexus') )
				{
					$this->DB->build( array( 'select' => "COUNT(*) as purchases, ps_item_id", 'from' => 'nexus_purchases', 'group' => 'ps_item_id', 'where' => "ps_app='downloads' AND ps_type='file' AND ps_item_id IN(" . implode( ',', $_fileIds ) . ")" ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$file_rows[ intval($r['ps_item_id']) ]['file_purchases'] = $r['purchases'];
					}
				}
				
				//-----------------------------------------
				// Grab screenshot info
				//-----------------------------------------
					
				$_screenshotFileIds	= array_unique( array_keys( $file_rows ) );
				
				if( count($_screenshotFileIds) )
				{
					$_recordIds	= array();
					
					$this->DB->build( array( 'select' => '*', 'from' => 'downloads_files_records', 'where' => "record_file_id IN(" . implode( ',', $_screenshotFileIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
						{
							$_recordIds[ $r['record_file_id'] ]	= $r;
						}
					}
				}
			}

			$this->hasfiles = true;
	 	}

		//-------------------------------------------
		// Got at least something?
		//-------------------------------------------
		
		if( !$this->hascats && !$this->hasfiles )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 10842, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_permitted_categories', 10843, null, null, 403 );
			}
		}

		//-------------------------------------------
		// Print
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('downloads')->categoryListing( 
																										array( 
																												'canadd'		=> $this->canadd, 
																												'canmod'		=> $this->canmod, 
																												'cancomments'	=> $cancomment, 
																												'canapp'		=> $canapp,
																											), 
																										$page_links, 
																										$category, 
																										$category_rows, 
																										$file_rows,
																										array(
																												 'current_num'	=> $num,
																												 'current_key'	=> $sort_key,
																												 'current_by'	=> $sort_by,
																												 'filter_key'	=> $this->request['filter_key'] ? $this->request['filter_key'] : 'all',
																												 'options_num'	=> $this->sort_num,
																												 'options_key'	=> $this->sort_key,
																												 'options_by'	=> $this->sort_by
																											),
																										$this->_like->render( 'summary', $category['cid'] ),
																										$_recordIds
																										);

		//-------------------------------------------
		// Grab stats
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('idmFunctions')->getStats();

		//-------------------------------------------
		// Output
		//-------------------------------------------
		
		foreach( $this->registry->getClass('categories')->getNav( $category['cid'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'idmshowcat' );
		}

		$this->registry->output->setTitle( $category['cname'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
}