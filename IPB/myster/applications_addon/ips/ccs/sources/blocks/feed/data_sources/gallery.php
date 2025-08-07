<?php
/**
 * @file		gallery.php 	IP.Gallery feed block plugin file for IP.Content
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		1st March 2009
 * $LastChangedDate: 2013-02-20 14:47:43 -0500 (Wed, 20 Feb 2013) $
 * @version		v3.4.5
 * $Revision: 12006 $
 */

class feed_gallery implements feedBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;

		//-----------------------------------------
		// Get gallery objects
		//-----------------------------------------

		if ( IPSLib::appIsInstalled('gallery') AND ! $this->registry->isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}

		$this->lang->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @param	string		Additional info
	 * @return	array
	 */
	public function getTags( $info='' )
	{
		$_return			= array();
		$_noinfoColumns		= array();

		//-----------------------------------------
		// Switch on type
		//-----------------------------------------
		
		switch( $info )
		{
			case 'images':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__imageurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__imagedate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__imagetitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__imagecontent'] ),
											'thumbnail'	=> array( "&#36;r['thumbnail']", $this->lang->words['block_feed__imagethumb'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'gallery_images' ) as $_column )
				{
					if( $this->lang->words['col__gallery_images_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_images_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'gallery_albums' ) as $_column )
				{
					if( $this->lang->words['col__gallery_albums_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_albums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_galimages']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__images'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'albums':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__galalbumurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__alalbumdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__alalbumtitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__alalbumcontent'] ),
											'thumbnail'	=> array( "&#36;r['thumbnail']", $this->lang->words['block_feed__alalbumthumb'] ),
											);

				foreach( $this->DB->getFieldNames( 'gallery_albums' ) as $_column )
				{
					if( $this->lang->words['col__gallery_albums_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_albums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'gallery_images' ) as $_column )
				{
					if( $this->lang->words['col__gallery_images_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_images_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_galalbums']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__albums'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'categories':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__galcaturl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__galcatdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__galcattitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__galcatcontent'] ),
											'thumbnail'	=> array( "&#36;r['thumbnail']", $this->lang->words['block_feed__galcatthumb'] ),
											);

				foreach( $this->DB->getFieldNames( 'gallery_categories' ) as $_column )
				{
					if( $this->lang->words['col__gallery_categories_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_categories_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'gallery_images' ) as $_column )
				{
					if( $this->lang->words['col__gallery_images_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_images_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_galcats']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__galcats'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'comments':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__galcommurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__galcommdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__galcommtitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__galcommcontent'] ),
											'thumbnail'	=> array( "&#36;r['thumbnail']", $this->lang->words['block_feed__galcommthumb'] ),
											);

				foreach( $this->DB->getFieldNames( 'gallery_comments' ) as $_column )
				{
					if( $this->lang->words['col__gallery_comments_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_comments_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'gallery_images' ) as $_column )
				{
					if( $this->lang->words['col__gallery_images_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_images_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'gallery_albums' ) as $_column )
				{
					if( $this->lang->words['col__gallery_albums_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_albums_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'gallery_categories' ) as $_column )
				{
					if( $this->lang->words['col__gallery_categories_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__gallery_categories_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_galcomms']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__galcomms'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
		}

		return $_return;
	}
	
	/**
	 * Appends member columns to existing arrays
	 *
	 * @param	array 		Columns that have descriptions
	 * @param	array 		Columns that do not have descriptions
	 * @return	@e void		[Params are passed by reference and modified]
	 */
	protected function _addMemberColumns( &$_finalColumns, &$_noinfoColumns )
	{
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
		{
			if( $this->lang->words['col__sessions_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__sessions_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		foreach( $this->DB->getFieldNames( 'members' ) as $_column )
		{
			if( $this->lang->words['col__members_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__members_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		$_fieldInfo	= array();
		
		$this->DB->buildAndFetch( array( 'select' => 'pf_id,pf_title,pf_desc', 'from' => 'pfields_data' ) );
		$this->DB->execute();
		
		while( $r= $this->DB->fetch() )
		{
			$_fieldInfo[ $r['pf_id'] ]	= $r;
		}

		foreach( $this->DB->getFieldNames( 'pfields_content' ) as $_column )
		{
			if( $this->lang->words['col__pfields_content_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
			}
			else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
			{
				unset($_finalColumns[ $_column ]);
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
			}
			else
			{
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		foreach( $this->DB->getFieldNames( 'profile_portal' ) as $_column )
		{
			if( $this->lang->words['col__profile_portal_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__profile_portal_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		$_finalColumns['pp_main_photo']		= array( "&#36;r['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
		$_finalColumns['_has_photo']		= array( "&#36;r['_has_photo']", $this->lang->words['col__special__has_photo'] );
		$_finalColumns['pp_small_photo']	= array( "&#36;r['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
		$_finalColumns['pp_mini_photo']		= array( "&#36;r['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
		$_finalColumns['member_rank_img_i']	= array( "&#36;r['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
		$_finalColumns['member_rank_img']	= array( "&#36;r['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );
	}

	/**
	 * Provides the ability to modify the feed type or content type values
	 * before they are passed into the gallery template search query
	 *
	 * @param 	string 		Current feed type 
	 * @param 	string 		Current content type
	 * @return 	array 		Array with two keys: feed_type and content_type
	 */
	public function returnTemplateGalleryKeys( $feed_type, $content_type )
	{		
		return array( 'feed_type' => $feed_type, 'content_type' => $content_type );
	}

	/**
	 * Return the plugin meta data
	 *
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		return array(
					'key'			=> 'gallery',
					'app'			=> 'gallery',
					'name'			=> $this->lang->words['feed_name__gallery'],
					'description'	=> $this->lang->words['feed_description__gallery'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic',
					'inactiveSteps'	=> array( ),
					);
	}
	
	/**
	 * Get the feed's available content types.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @param	array 			true: Return an HTML radio list; false: return an array of types
	 * @return	array 			Form data
	 */
	public function returnContentTypes( $session = array(), $asHTML = true )
	{
		$_types		= array(
							array( 'images', $this->lang->words['ct_gal_images'] ),
							array( 'comments', $this->lang->words['ct_gal_comments'] ),
							array( 'albums', $this->lang->words['ct_gal_albums'] ),
							array( 'categories', $this->lang->words['ct_gal_cats'] ),
							);

		if( !$asHTML )
		{
			return $_types;
		}

		$_html		= array();
		
		foreach( $_types as $_type )
		{
			$_html[]	= "<input type='radio' name='content_type' id='content_type_{$_type[0]}' value='{$_type[0]}'" . ( $session['config_data']['content_type'] == $_type[0] ? " checked='checked'" : '' ) . " /> <label for='content_type_{$_type[0]}'>{$_type[1]}</label>"; 
		}
		
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_contenttype'],
						'description'	=> '',
						'field'			=> '<ul style="line-height: 1.6"><li>' . implode( '</li><li>', $_html ) . '</ul>',
						)
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'images', 'comments', 'albums', 'categories' ) ) )
		{
			$data['content_type']	= 'images';
		}

		return array( true, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();

		$album_cache	= array();
		$category_cache	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_albums' ) );
		$this->DB->execute();
			
		while( $data = $this->DB->fetch() )
		{
			$album_cache[]		= array( $data['album_id'], $data['album_name'] );
		}

		foreach( $this->registry->gallery->helper('categories')->fetchCategories() as $data )
		{
			$category_cache[]	= array( $data['category_id'], $data['category_name'] );
		}

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_gal__cats'],
							'description'	=> $this->lang->words['feed_gal__cats_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_categories[]', $category_cache, explode( ',', $session['config_data']['filters']['filter_categories'] ), 10 ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__albums'],
									'description'	=> $this->lang->words['feed_gal__albums_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_albums[]', $album_cache, explode( ',', $session['config_data']['filters']['filter_albums'] ), 10 ),
									);

				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_media']		= $session['config_data']['filters']['filter_media'] ? $session['config_data']['filters']['filter_media'] : 0;
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';
				$session['config_data']['filters']['filter_featured']	= $session['config_data']['filters']['filter_featured'] ? $session['config_data']['filters']['filter_featured']	: 0;
				
				$visibility	= array( array( 'open', $this->lang->words['gal_status__open'] ), array( 'closed', $this->lang->words['gal_status__closed'] ), array( 'either', $this->lang->words['gal_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__visibility'],
									'description'	=> $this->lang->words['feed_gal__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__media'],
									'description'	=> $this->lang->words['feed_gal__media_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_media', $session['config_data']['filters']['filter_media'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__featured'], 
									'description'	=> $this->lang->words['feed_gal__featured_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_featured', $session['config_data']['filters']['filter_featured'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__posted'],
									'description'	=> $this->lang->words['feed_gal__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__submitter'],
									'description'	=> $this->lang->words['feed_gal__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);
			break;
			
			case 'comments':
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__albums'],
									'description'	=> $this->lang->words['feed_gal__albums_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_albums[]', $album_cache, explode( ',', $session['config_data']['filters']['filter_albums'] ), 10 ),
									);

				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';

				$visibility	= array( array( 'open', $this->lang->words['galc_status__open'] ), array( 'closed', $this->lang->words['galc_status__closed'] ), array( 'either', $this->lang->words['galc_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_galc__visibility'],
									'description'	=> $this->lang->words['feed_galc__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_galc__posted'],
									'description'	=> $this->lang->words['feed_galc__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);
			break;

			case 'albums':
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__albums'],
									'description'	=> $this->lang->words['feed_gal__albums_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_albums[]', $album_cache, explode( ',', $session['config_data']['filters']['filter_albums'] ), 10 ),
									);

				$session['config_data']['filters']['filter_public']	= $session['config_data']['filters']['filter_public'] ? $session['config_data']['filters']['filter_public'] : 1;
				$session['config_data']['filters']['filter_owner']	= $session['config_data']['filters']['filter_owner'] ? $session['config_data']['filters']['filter_owner'] : '';

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__publica'],
									'description'	=> $this->lang->words['feed_gal__publica_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_public', $session['config_data']['filters']['filter_public'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_gal__owner'],
									'description'	=> $this->lang->words['feed_gal__owner_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_owner', $session['config_data']['filters']['filter_owner'] ),
									);
			break;

			case 'categories':
				// No extra filters
			break;
		}
		
		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @param	array 			Session data
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $session, $data )
	{
		$filters						= array();
		$filters['filter_albums']		= is_array($data['filter_albums']) ? implode( ',', $data['filter_albums'] ) : '';
		$filters['filter_categories']	= is_array($data['filter_categories']) ? implode( ',', $data['filter_categories'] ) : '';

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_media']		= $data['filter_media'] ? $data['filter_media'] : 0;
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
				$filters['filter_submitter']	= $data['filter_submitter'] ? $data['filter_submitter'] : '';
				$filters['filter_featured']		= $data['filter_featured'] ? $data['filter_featured']	: 0;
			break;
			
			case 'comments':
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
			break;

			case 'albums':
				$filters['filter_public']		= $data['filter_public'] ? $data['filter_public'] : 1;
				$filters['filter_owner']		= $data['filter_owner'] ? $data['filter_owner'] : '';
			break;
		}
		
		return array( true, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'submitted';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_gal__title'] ), 
								array( 'filename', $this->lang->words['sort_gal__filename'] ), 
								array( 'views', $this->lang->words['sort_gal__views'] ), 
								array( 'comments', $this->lang->words['sort_gal__comments'] ), 
								array( 'submitted', $this->lang->words['sort_gal__submitted'] ),
								array( 'lastcomment', $this->lang->words['sort_gal__lastcomment'] ),
								array( 'size', $this->lang->words['sort_gal__size'] ),
								array( 'rate', $this->lang->words['sort_gal__rate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'post_date';

				$sortby	= array( 
								array( 'post_date', $this->lang->words['sort_galc__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;

			case 'albums':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'last_file';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_gala__name'] ), 
								array( 'files', $this->lang->words['sort_gala__files'] ), 
								array( 'comments', $this->lang->words['sort_gala__comments'] ), 
								array( 'last_file', $this->lang->words['sort_gala__lastdate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;

			case 'categories':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_galcat__name'] ), 
								array( 'files', $this->lang->words['sort_galcat__files'] ), 
								array( 'last_file', $this->lang->words['sort_galcat__lastdate'] ), 
								array( 'position', $this->lang->words['sort_galcat__position'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
		}
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_order_direction'],
							'description'	=> $this->lang->words['feed_order_direction_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortorder', array( array( 'desc', 'DESC' ), array( 'asc', 'ASC' ) ), $session['config_data']['sortorder'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_start'],
							'description'	=> $this->lang->words['feed_limit_offset_start_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_start', $session['config_data']['offset_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_end'],
							'description'	=> $this->lang->words['feed_limit_offset_end_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_end', $session['config_data']['offset_end'] ),
							);
		
		return $filters;
	}
	
	/**
	 * Check the feed ordering options
	 *
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data, $session )
	{
		$limits		= array();
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		switch( $session['config_data']['content_type'] )
		{
			case 'images':
			default:
				$sortby	= array( 'title', 'filename', 'views', 'comments', 'submitted', 'lastcomment', 'size', 'rate', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'submitted';
			break;
			
			case 'comments':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;

			case 'albums':
				$sortby					= array( 'name', 'last_file', 'files', 'comments', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'last_file';
			break;

			case 'categories':
				$sortby					= array( 'name', 'last_file', 'files', 'position', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'position';
			break;
		}
		
		return array( true, $limits );
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @param	array 		Block data
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function executeFeed( $block, $previewMode=false )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );

		//-----------------------------------------
		// Init
		//-----------------------------------------
						
		$config	= unserialize( $block['block_config'] );
		$where	= array();

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		switch( $config['content'] )
		{
			case 'images':
				if( $config['filters']['filter_categories'] )
				{
					$where[]	= "i.image_category_id IN(" . $config['filters']['filter_categories'] . ")";
				}

				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "i.image_album_id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "i.image_approved=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if ( $config['filters']['filter_featured'] == '1' )
				{
					$where[]	= "i.image_feature_flag=1";
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "i.image_date > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "i.image_member_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_submitter'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder = new $classToLoad( $this->registry );
					$friends	 = $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "i.image_member_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_submitter'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_submitter'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "i.image_member_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
			
			case 'comments':
				if( $config['filters']['filter_categories'] )
				{
					$where[]	= "i.image_category_id IN(" . $config['filters']['filter_categories'] . ")";
				}

				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "i.image_album_id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "c.comment_approved=" .( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}
				
				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "c.comment_post_date > " . $timestamp;
					}
				}
			break;

			case 'albums':
				if( $config['filters']['filter_categories'] )
				{
					$where[]	= "a.album_category_id IN(" . $config['filters']['filter_categories'] . ")";
				}

				if( $config['filters']['filter_albums'] )
				{
					$where[]	= "a.album_id IN(" . $config['filters']['filter_albums'] . ")";
				}

				if( $config['filters']['filter_public'] )
				{
					$where[]	= "a.album_type=1";
				}

				if( $config['filters']['filter_owner'] == 'myself' )
				{
					$where[]	= "a.album_owner_id = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_owner'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder = new $classToLoad( $this->registry );
					$friends	 = $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "a.album_owner_id IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_owner'] != '' )
				{
					$member	= IPSMember::load( $config['filters']['filter_owner'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "a.album_owner_id = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;

			case 'categories':
				if( $config['filters']['filter_categories'] )
				{
					$where[]	= "c.category_id IN(" . $config['filters']['filter_categories'] . ")";
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'images':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"i.image_caption ";
					break;
		
					case 'filename':
						$order	.=	"i.image_file_name ";
					break;
					
					case 'views':
						$order	.=	"i.image_views ";
					break;
		
					case 'comments':
						$order	.=	"i.image_comments ";
					break;

					default:
					case 'submitted':
						$order	.=	"i.image_date ";
					break;

					case 'lastcomment':
						$order	.=	"i.image_last_comment ";
					break;

					case 'size':
						$order	.=	"i.image_file_size ";
					break;

					case 'rate':
						$order	.=	"i.image_rating ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
			break;
			
			case 'comments':
				switch( $config['sortby'] )
				{
					default:
					case 'post_date':
						$order	.=	"c.comment_post_date ";
					break;
				}
			break;

			case 'albums':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"a.album_name ";
					break;
		
					default:
					case 'last_file':
						$order	.=	"a.album_last_img_date ";
					break;
					
					case 'files':
						$order	.=	"a.album_count_imgs ";
					break;
		
					case 'comments':
						$order	.=	"a.album_count_comments ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
			break;

			case 'categories':
				switch( $config['sortby'] )
				{
					case 'name':
						$order	.=	"c.category_name ";
					break;
		
					default:
					case 'last_file':
						$order	.=	"c.category_last_img_date ";
					break;
					
					case 'files':
						$order	.=	"c.category_count_imgs ";
					break;
		
					case 'position':
						$order	.=	"c.category_position ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
			break;
		}
		
		$order	.= $config['sortorder'];
		
		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$content	= array();

		switch( $config['content'] )
		{
			case 'images':
				$this->DB->build( array(
										'select'	=> 'i.image_id as imgid, i.image_thumbnail as hasthumb, i.*',
										'from'		=> array( 'gallery_images' => 'i' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'c.*',
																'from'		=> array( 'gallery_categories' => 'c' ),
																'where'		=> 'c.category_id=i.image_category_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'a.*',
																'from'		=> array( 'gallery_albums' => 'a' ),
																'where'		=> 'a.album_id=i.image_album_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=i.image_member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;image=' . $r['imgid'], 'none', $r['image_caption_seo'], 'viewimage' );
					$r['date']		= $r['image_date'];
					$r['content']	= $r['image_description'];
					$r['title']		= $r['image_caption'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'gallery_image';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );

					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					$r['thumbnail']	= $this->registry->getClass('gallery')->helper('image')->makeImageLink( $r, array( 'type' => 'thumb', 'link-type' => 'page' ) );

					//-----------------------------------------
					// Temporary backwards compatibility
					// @todo Remove this in a future release
					//-----------------------------------------

					$r['directory']			= $r['image_directory'];
					$r['masked_file_name']	= $r['image_masked_file_name'];
					$r['caption']			= $r['image_caption'];
					$r['description']		= $r['content'];
					
					$content[]		= $r;
				}
			break;
			
			case 'comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'gallery_comments' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.image_id as imgid, i.image_thumbnail as hasthumb, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.image_id=c.comment_img_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'cat.*',
																'from'		=> array( 'gallery_categories' => 'cat' ),
																'where'		=> 'cat.category_id=i.image_category_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'a.*',
																'from'		=> array( 'gallery_albums' => 'a' ),
																'where'		=> 'a.album_id=i.image_album_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.comment_author_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;image=' . $r['imgid'], 'none', $r['image_caption_seo'] ? $r['image_caption_seo'] : IPSText::makeSeoTitle( $r['image_caption'] ), 'viewimage' );
					$r['date']		= $r['comment_post_date'];
					$r['content']	= $r['comment_text'];
					$r['title']		= $r['image_caption'];
					
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'gallery_comment';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
		
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );

					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					$r['thumbnail']	= $this->registry->getClass('gallery')->helper('image')->makeImageLink( $r, array( 'type' => 'thumb', 'link-type' => 'page' ) );
					
					$content[]		= $r;
				}
			break;

			case 'albums':
				$this->DB->build( array(
										'select'	=> 'a.*',
										'from'		=> array( 'gallery_albums' => 'a' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.image_id as imgid, i.image_thumbnail as hasthumb, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.image_id=a.album_last_img_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=a.album_owner_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;album=' . $r['album_id'], 'none', $r['album_name_seo'] ? $r['album_name_seo'] : IPSText::makeSeoTitle( $r['album_name'] ), 'viewalbum' );
					$r['date']		= $r['album_last_img_date'];
					$r['content']	= $r['album_description'];
					$r['title']		= $r['album_name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					$r['thumbnail']	= $this->registry->getClass('gallery')->helper('image')->makeImageLink( $r, array( 'type' => 'thumb', 'link-type' => 'container' ) );
					
					$content[]		= $r;
				}
			break;

			case 'categories':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'gallery_categories' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'i.image_id as imgid, i.image_thumbnail as hasthumb, i.*',
																'from'		=> array( 'gallery_images' => 'i' ),
																'where'		=> 'i.image_id=c.category_last_img_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=i.image_member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					$r['member_id']	= $r['mid'];
					
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=gallery&amp;category=' . $r['category_id'], 'none', $r['category_name_seo'], 'viewcategory' );
					$r['date']		= $r['category_last_img_date'];
					$r['content']	= $r['category_description'];
					$r['title']		= $r['category_name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$r['id']		= $r['imgid'];
					$r['thumbnail']	= $this->registry->getClass('gallery')->helper('image')->makeImageLink( $r, array( 'type' => 'thumb', 'link-type' => 'container' ) );
					
					$content[]		= $r;
				}
			break;
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();

		//-----------------------------------------
		// Gallery template or custom template?
		//-----------------------------------------

		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($content) )
		{
			return '';
		}
		
		ob_start();
		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $content );
		ob_end_clean();
		return $_return;
	}
}