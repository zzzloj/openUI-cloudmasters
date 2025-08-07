<?php
/**
 * Database feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10065 $ 
 * @since		1st March 2009
 */

class feed_databases implements feedBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
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
	 * @access	public
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
		
		if ( !array_key_exists( 'ccs_databases', $this->caches ) )
		{
			$this->caches['ccs_databases']	= $this->cache->getCache('ccs_databases');
		}
		
		if ( !array_key_exists( 'ccs_fields', $this->caches ) )
		{
			$this->caches['ccs_fields']		= $this->cache->getCache('ccs_fields');
		}
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$registry->setClass( 'ccsFunctions', new $classToLoad( $registry ) );
		}
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Additional info (database id;type)
	 * @return	array
	 */
	public function getTags( $info='' )
	{
		$_bits		= explode( ';', $info );
		$_return	= array();
		
		//-----------------------------------------
		// Get fields
		//-----------------------------------------
		
		$fields	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=' . $_bits[0], 'order' => 'field_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$fields[]	= $r;
		}
		
		//-----------------------------------------
		// Member information columns
		//-----------------------------------------
		
		$_finalColumns		= array();
		$_noinfoColumns		= array();
		
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
		{
			if( $this->lang->words['col__sessions_' . $_column ] )
			{
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
		
		switch( $_bits[1] )
		{
			default:
			case 'db':
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_articles_db']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__records'], 
																					array(
																						array( "&#36;r['url']", $this->lang->words['block_feed__dburl'] ),
																						array( "&#36;r['date']", $this->lang->words['block_feed__dbdate'] ),
																						array( "&#36;r['content']", $this->lang->words['block_feed__dbcontent'] ),
																						array( "&#36;r['title']", $this->lang->words['block_feed__dbtitle'] ),
																						array( "&#36;r['_isRead']", $this->lang->words['block_feed__dbisread'] ),
																						
																						array( "&#36;r['primary_id_field']", $this->lang->words['col__dba_primary_id_field'] ),
																						array( "&#36;r['member_id']", $this->lang->words['col__special_my_member_id'] ),
																						array( "&#36;r['record_saved']", $this->lang->words['col__dba_record_saved'] ),
																						array( "&#36;r['rating_real']", $this->lang->words['col__dba_rating_real'] ),
																						array( "&#36;r['rating_hits']", $this->lang->words['col__dba_rating_hits'] ),
																						array( "&#36;r['category_id']", $this->lang->words['col__dba_category_id'] ),
																						array( "&#36;r['record_locked']", $this->lang->words['col__dba_record_locked'] ),
																						array( "&#36;r['record_comments']", $this->lang->words['col__dba_record_comments'] ),
																						array( "&#36;r['record_views']", $this->lang->words['col__dba_record_views'] ),
																						array( "&#36;r['record_approved']", $this->lang->words['col__dba_record_approved'] ),
																						array( "&#36;r['record_pinned']", $this->lang->words['col__dba_record_pinned'] ),
																						array( "&#36;r['record_meta_keywords']", $this->lang->words['col__dba_record_meta_keywords'] ),
																						array( "&#36;r['record_meta_description']", $this->lang->words['col__dba_record_meta_description'] ),
																						array( "&#36;r['record_template']", $this->lang->words['col__dba_record_template'] ),
																						array( "&#36;r['record_topicid']", $this->lang->words['col__dba_record_topicid'] ),
																						)
																					),
																				),
							);
							
				foreach( $fields as $field )
				{
					$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
					$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
					
					$_return[ $this->lang->words['block_feed_articles_db'] ][0][2][]	= array( "&#36;r['field_{$field['field_id']}']", $_field_field );
					$_return[ $this->lang->words['block_feed_articles_db'] ][0][2][]	= array( "&#36;r['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;r['{$field['field_key']}']", $_field_value );
				}
				
				$_return[ $this->lang->words['block_feed_articles_db'] ][0][2]	= IPSLib::mergeArrays( $_return[ $this->lang->words['block_feed_articles_db'] ][0][2], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) );
			break;
			
			case 'categories':
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_articles_cats']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__cats'], 
																					array(
																						array( "&#36;r['level']", $this->lang->words['block_feed__dbcatlevel'] ),
																						array( "&#36;r['category']['subcats']", $this->lang->words['block_feed__dbcatsubcats'] ),
																						array( "&#36;r['category']['_has_unread']", $this->lang->words['block_feed__dbcathasunread'] ),
																						array( "&#36;r['category']['image']", $this->lang->words['block_feed__dbcatimage'] ),
																						array( "&#36;r['category']['category_last_record_cat']", $this->lang->words['block_feed__dbcatlastcat'] ),
																						),
																					),
																				),
									);

				foreach( $this->DB->getFieldNames( 'ccs_database_categories' ) as $_column )
				{
					if( $this->lang->words['col__cdcat_' . $_column ] )
					{
						$_return[ $this->lang->words['block_feed_articles_cats'] ][0][2][]	= array( "&#36;r['category']['" . $_column . "']", $this->lang->words['col__cdcat_' . $_column ] );
					}
					else
					{
						$_return[ $this->lang->words['block_feed_articles_cats'] ][0][2][]	= array( "&#36;r['category']['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_latestRecord = array(
										1000000 =>array( "&#36;r['category']['primary_id_field']", $this->lang->words['col__dbac_primary_id_field'] ),
										1000001 =>array( "&#36;r['category']['member_id']", $this->lang->words['col__special_my_member_id'] ),
										1000002 =>array( "&#36;r['category']['record_saved']", $this->lang->words['col__dbac_record_saved'] ),
										1000003 =>array( "&#36;r['category']['rating_real']", $this->lang->words['col__dbac_rating_real'] ),
										1000004 =>array( "&#36;r['category']['rating_hits']", $this->lang->words['col__dbac_rating_hits'] ),
										1000005 =>array( "&#36;r['category']['category_id']", $this->lang->words['col__dbac_category_id'] ),
										1000006 =>array( "&#36;r['category']['record_locked']", $this->lang->words['col__dbac_record_locked'] ),
										1000007 =>array( "&#36;r['category']['record_comments']", $this->lang->words['col__dbac_record_comments'] ),
										1000008 =>array( "&#36;r['category']['record_views']", $this->lang->words['col__dbac_record_views'] ),
										1000009 =>array( "&#36;r['category']['record_approved']", $this->lang->words['col__dbac_record_approved'] ),
										1000010 =>array( "&#36;r['category']['record_pinned']", $this->lang->words['col__dbac_record_pinned'] ),
										1000011 =>array( "&#36;r['category']['record_meta_keywords']", $this->lang->words['col__dbac_record_meta_keywords'] ),
										1000012 =>array( "&#36;r['category']['record_meta_description']", $this->lang->words['col__dbac_record_meta_description'] ),
										1000013 =>array( "&#36;r['category']['record_template']", $this->lang->words['col__dbac_record_template'] ),
										1000014 =>array( "&#36;r['category']['record_topicid']", $this->lang->words['col__dbac_record_topicid'] ),
										);

				foreach( $fields as $field )
				{
					$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
					$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
					
					$_latestRecord[]	= array( "&#36;r['category']['field_{$field['field_id']}']", $_field_field );
					$_latestRecord[]	= array( "&#36;r['category']['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;r['category']['{$field['field_key']}']", $_field_value );
				}

				$_return[ $this->lang->words['block_feed_articles_cats'] ][0][2]	= IPSLib::mergeArrays( $_return[ $this->lang->words['block_feed_articles_cats'] ][0][2], $_latestRecord );
			break;
			
			case 'comments':
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_articles_comments']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__comments'], 
																					array(
																						array( "&#36;r['url']", $this->lang->words['block_feed__dbcurl'] ),
																						array( "&#36;r['date']", $this->lang->words['block_feed__dbcdate'] ),
																						array( "&#36;r['content']", $this->lang->words['block_feed__dbccontent'] ),
																						array( "&#36;r['title']", $this->lang->words['block_feed__dbtitle'] ),
																						array( "&#36;r['_isRead']", $this->lang->words['block_feed__dbisread'] ),
																						
																						array( "&#36;r['comment_id']", $this->lang->words['col__cdc_comment_id'] ),
																						array( "&#36;r['comment_user']", $this->lang->words['col__cdc_comment_user'] ),
																						array( "&#36;r['comment_approved']", $this->lang->words['col__cdc_comment_approved'] ),
																						
																						array( "&#36;r['primary_id_field']", $this->lang->words['col__dba_primary_id_field'] ),
																						array( "&#36;r['member_id']", $this->lang->words['col__special_my_member_id'] ),
																						array( "&#36;r['record_saved']", $this->lang->words['col__dba_record_saved'] ),
																						array( "&#36;r['rating_real']", $this->lang->words['col__dba_rating_real'] ),
																						array( "&#36;r['rating_hits']", $this->lang->words['col__dba_rating_hits'] ),
																						array( "&#36;r['category_id']", $this->lang->words['col__dba_category_id'] ),
																						array( "&#36;r['record_locked']", $this->lang->words['col__dba_record_locked'] ),
																						array( "&#36;r['record_comments']", $this->lang->words['col__dba_record_comments'] ),
																						array( "&#36;r['record_views']", $this->lang->words['col__dba_record_views'] ),
																						array( "&#36;r['record_approved']", $this->lang->words['col__dba_record_approved'] ),
																						array( "&#36;r['record_pinned']", $this->lang->words['col__dba_record_pinned'] ),
																						array( "&#36;r['record_meta_keywords']", $this->lang->words['col__dba_record_meta_keywords'] ),
																						array( "&#36;r['record_meta_description']", $this->lang->words['col__dba_record_meta_description'] ),
																						array( "&#36;r['record_template']", $this->lang->words['col__dba_record_template'] ),
																						array( "&#36;r['record_topicid']", $this->lang->words['col__dba_record_topicid'] ),
																						)
																					),
																				),
							);
							
				foreach( $fields as $field )
				{
					$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
					$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
					
					$_return[ $this->lang->words['block_feed_articles_comments'] ][0][2][]	= array( "&#36;r['field_{$field['field_id']}']", $_field_field );
					$_return[ $this->lang->words['block_feed_articles_comments'] ][0][2][]	= array( "&#36;r['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;r['{$field['field_key']}']", $_field_value );
				}
				
				$_return[ $this->lang->words['block_feed_articles_comments'] ][0][2]	= IPSLib::mergeArrays( $_return[ $this->lang->words['block_feed_articles_comments'] ][0][2], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) );
			break;
		}

		return $_return;
	}
	
	/**
	 * Provides the ability to modify the feed type or content type values
	 * before they are passed into the gallery template search query
	 *
	 * @access 	public
	 * @param 	string 		Current feed type 
	 * @param 	string 		Current content type
	 * @return 	array 		Array with two keys: feed_type and content_type
	 */
	public function returnTemplateGalleryKeys( $feed_type, $content_type )
	{
		if( strpos( $content_type, ';' ) !== false )
		{
			$content_type = preg_replace( '#(.+?);(.+?)$#', '$2', $content_type );
		}
		
		return array( 'feed_type' => $feed_type, 'content_type' => $content_type );
	}

	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		$count	= count($this->caches['ccs_databases']);
		
		if( !$count )
		{
			return array();
		}
		
		return array(
					'key'			=> 'databases',
					'app'			=> 'ccs',
					'name'			=> $this->lang->words['feed_name__databases'],
					'description'	=> $this->lang->words['feed_description__databases'],
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
		$options	= array();

		$_database	= explode( ';', $session['config_data']['content_type']	);
		$_html		= array();
		$_types		= array( array( 'db', $this->lang->words['feed_db__db'] ), array( 'categories', $this->lang->words['feed_db__categories'] ), array( 'comments', $this->lang->words['feed_db__comments'] ) );

		if( !$asHTML )
		{
			return $_types;
		}
		
		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $database_id => $database )
			{
				if( $database['database_is_articles'] )
				{
					continue;
				}

				$options[]		= array( $database_id, $database['database_name'] );
			}
		}
				
		foreach( $_types as $_type )
		{
			$_html[]	= "<input type='radio' name='content_type_2' id='content_type_2_{$_type[0]}' value='{$_type[0]}'" . ( $_database[1] == $_type[0] ? " checked='checked'" : '' ) . " /> <label for='content_type_2_{$_type[0]}'>{$_type[1]}</label>"; 
		}
		
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_db'],
						'description'	=> $this->lang->words['generic__desc_db'],
						'field'			=> $this->registry->output->formDropdown( 'content_type', $options, $_database[0] ),
						),
					array(
						'label'			=> $this->lang->words['feed_db__type'],
						'description'	=> '',
						'field'			=> '<ul style="line-height: 1.6"><li>' . implode( '</li><li>', $_html ) . '</ul>',
						),
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		$types	= array();

		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $database_id => $database )
			{
				$types[]		= $database_id;
			}
		}
		
		$return	= true;
		
		if( !in_array( $data['content_type'], $types ) )
		{
			$return	= false;
		}
		
		if( $data['content_type_2'] )
		{
			$data['content_type']	= $data['content_type'] . ';' . $data['content_type_2'];
		}

		return array( $return, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();

		$session['config_data']['filters']['filter_status']		= $session['config_data']['filters']['filter_status'] ? $session['config_data']['filters']['filter_status'] : 'either';
		$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'approved';
		$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 'either';
		$session['config_data']['filters']['filter_starter']	= $session['config_data']['filters']['filter_starter'] ? $session['config_data']['filters']['filter_starter'] : '';
		$session['config_data']['filters']['filter_rating']		= $session['config_data']['filters']['filter_rating'] ? $session['config_data']['filters']['filter_rating'] : 0;
		$session['config_data']['filters']['filter_category']	= $session['config_data']['filters']['filter_category'] ? explode( ',', $session['config_data']['filters']['filter_category'] ) : array();
		
		$_database	= explode( ';', $session['config_data']['content_type'] );
		
		//-----------------------------------------
		// Category filter
		//-----------------------------------------
		
		$_back	= ipsRegistry::$request['category'];
		ipsRegistry::$request['category']	= $session['config_data']['filters']['filter_category'];
		$_cats	= $this->registry->ccsFunctions->getCategoriesClass( $this->caches['ccs_databases'][ $_database[0] ] );

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_db__cat'],
							'description'	=> $_database[1] != 'comments' ? $this->lang->words['feed_db__cat_desc'] : $this->lang->words['feed_dbc__cat_desc'],
							'field'			=> "<select name='filter_category[]' class='dropdown' multiple='multiple' size='5'>" . $_cats->getSelectMenu() . "</select>",
							);

		ipsRegistry::$request['category']	= $_back;

		if( $_database[1] == 'db' OR !$_database[1] )
		{
			$status		= array( array( 'open', $this->lang->words['status__openr'] ), array( 'closed', $this->lang->words['status__closedr'] ), array( 'either', $this->lang->words['status__either'] ) );
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_db__status'],
								'description'	=> $this->lang->words['feed_db__status_desc'],
								'field'			=> $this->registry->output->formDropdown( 'filter_status', $status, $session['config_data']['filters']['filter_status'] ),
								);

			$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'hidden', $this->lang->words['approved__hide'] ), array( 'either', $this->lang->words['approved__either'] ) );
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_db__visibility'],
								'description'	=> $this->lang->words['feed_db__visibility_desc'],
								'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
								);
	
			$pinned		= array( array( 'pinned', $this->lang->words['pinned__yes'] ), array( 'unpinned', $this->lang->words['pinned__no'] ), array( 'either', $this->lang->words['pinned__either'] ) );
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_db__pinned'],
								'description'	=> '',
								'field'			=> $this->registry->output->formDropdown( 'filter_pinned', $pinned, $session['config_data']['filters']['filter_pinned'] ),
								);
	
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_db__starter'],
								'description'	=> $this->lang->words['feed_db__starter_desc'],
								'field'			=> $this->registry->output->formInput( 'filter_starter', $session['config_data']['filters']['filter_starter'] ),
								);
	
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_db__rating'],
								'description'	=> $this->lang->words['feed_db__rating_desc'],
								'field'			=> $this->registry->output->formInput( 'filter_rating', $session['config_data']['filters']['filter_rating'] ),
								);

			//-----------------------------------------
			// Allow to filter based on custom fields
			// (Yes, let the fun begin...sigh)
			//-----------------------------------------

			$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
	
			$fields			= array();
			$_fieldOptions	= array();

			if( is_array($this->caches['ccs_fields'][ $_database[0] ]) AND count($this->caches['ccs_fields'][ $_database[0] ]) )
			{
				foreach( $this->caches['ccs_fields'][ $_database[0] ] as $_field )
				{
					$fields[ $_field['field_id'] ]	= $_field;
					
					if( !in_array( $_field['field_type'], array( 'input', 'textarea', 'radio', 'select', 'editor', 'date' ) ) )
					{
						continue;
					}
					
					$_fieldOptions[]	= array( $_field['field_id'], $_field['field_name'] );
				}
			}

			for( $j=1; $j < 6; $j++ )
			{
				$filters[]	= array(
									'label'			=> sprintf( $this->lang->words['feed_db__custom'], $j ),
									'description'	=> $this->lang->words['feed_db__custom_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_ct_' . $j, $_fieldOptions, $session['config_data']['filters']['filter_ct_' . $j ] ) . ' ' . $this->registry->output->formInput( 'filter_cv_' . $j, $session['config_data']['filters']['filter_cv_' . $j ] ),
									);
			}
		}
		else if( $_database[1] == 'categories' )
		{
			return array();
		}
		else if( $_database[1] == 'comments' )
		{
			$visibility	= array( array( 'approved', $this->lang->words['approved__yes'] ), array( 'unapproved', $this->lang->words['approved__no'] ), array( 'hidden', $this->lang->words['approved__hide'] ), array( 'either', $this->lang->words['approved__either'] ) );
			$filters[]	= array(
								'label'			=> $this->lang->words['feed_dbc__visibility'],
								'description'	=> $this->lang->words['feed_dbc__visibility_desc'],
								'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
								);

			$filters[]	= array(
								'label'			=> $this->lang->words['feed_dbc__starter'],
								'description'	=> $this->lang->words['feed_dbc__starter_desc'],
								'field'			=> $this->registry->output->formInput( 'filter_starter', $session['config_data']['filters']['filter_starter'] ),
								);
		}

		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $session, $data )
	{
		//-----------------------------------------
		// Filters are same for records and comments,
		// they're just labeled different on the form
		//-----------------------------------------
		
		$filters	= array();

		$filters['filter_status']		= $data['filter_status'] ? $data['filter_status'] : 'either';
		$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'approved';
		$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 'either';
		$filters['filter_starter']		= $data['filter_starter'] ? $data['filter_starter'] : '';
		$filters['filter_rating']		= $data['filter_rating'] ? $data['filter_rating'] : 0;
		$filters['filter_category']		= ( is_array($data['filter_category']) AND count($data['filter_category']) ) ? implode( ',', $data['filter_category'] ) : 0;

		for( $j=1; $j < 6; $j++ )
		{
			$filters['filter_ct_' . $j ]	= ( $data['filter_ct_' . $j ] AND $data['filter_cv_' . $j ] ) ? $data['filter_ct_' . $j ] : '';
			$filters['filter_cv_' . $j ]	= $data['filter_cv_' . $j ] ? $data['filter_cv_' . $j ] : '';
		}

		return array( true, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$_database								= explode( ';', $session['config_data']['content_type'] );
		
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;
		$session['config_data']['sortby']		= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'record_saved';

		$filters	= array();
		
		if( $_database[1] == 'comments' )
		{
			$fields		= array(  
								array( 
									'special_key'	=> 'comment_user',
									'field_name'	=> $this->lang->words['field__member'],
									), 
								array( 
									'special_key'	=> 'comment_date',
									'field_name'	=> $this->lang->words['field__saved'],
									),
								);
		}
		else if( $_database[1] == 'categories' )
		{
			return array();
		}
		else
		{
			$fields		= array( 
								array( 
									'special_key'	=> 'primary_id_field',
									'field_name'	=> $this->lang->words['field__id'],
									), 
								array( 
									'special_key'	=> 'member_id',
									'field_name'	=> $this->lang->words['field__member'],
									), 
								array( 
									'special_key'	=> 'record_saved',
									'field_name'	=> $this->lang->words['field__saved'],
									), 
								array( 
									'special_key'	=> 'record_updated',
									'field_name'	=> $this->lang->words['field__updated'],
									), 
								array( 
									'special_key'	=> 'record_approved',
									'field_name'	=> $this->lang->words['field__approved'],
									), 
								array( 
									'special_key'	=> 'record_locked',
									'field_name'	=> $this->lang->words['field__locked'],
									), 
								array( 
									'special_key'	=> 'record_comments',
									'field_name'	=> $this->lang->words['field__comments'],
									),
								array( 
									'special_key'	=> 'rating_real',
									'field_name'	=> $this->lang->words['field__rating'],
									), 
								array( 
									'special_key'	=> 'record_views',
									'field_name'	=> $this->lang->words['field__views'],
									), 
								);
		}
	
		$sortby	= array();
		
		foreach( $fields as $_field )
		{
			$sortby[]	= array( $_field['special_key'], $_field['field_name'] );
		}
		
		$_database	= explode( ';', $session['config_data']['content_type'] );
		
		if( is_array($this->caches['ccs_fields'][ $_database[0] ]) AND count($this->caches['ccs_fields'][ $_database[0] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $_database[0] ] as $_field )
			{
				$sortby[]	= array( 'field_' . $_field['field_id'], $_field['field_name'] );
			}
		}
		
		$sortby[]	= array( 'RAND()', $this->lang->words['sort_generic__rand'] );

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_sort_by'],
							'description'	=> $this->lang->words['feed_sort_by_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
							);
		
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
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data, $session )
	{
		$limits		= array();

		$limits['sortby']			= $data['sortby'];
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		return array( true, $limits );
	}
	
	/**
	 * Modify the template
	 *
	 * @access	public
	 * @param	array 			Feed configuration
	 * @param	string			Template HTML
	 * @return	string			Template HTML
	 */
	public function modifyTemplate( $config, $template )
	{
		if( $config['config_data']['block_id'] )
		{
			return $template;
		}
		
		$_database	= explode( ';', $config['config_data']['content_type'] );

		if( $_database[1] == 'categories' )
		{
			$_template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='feed__database_categories'" ) );
			
			if( $_template['tpb_content'] )
			{
				return $_template['tpb_content'];
			}
		}
		//else
		//{
		//	return str_replace( '<span class=\'desctext\'>{IPSText::truncate( strip_tags($r[\'content\']), 32 )}</span>', '', $template );
		//}
		
		return $template;
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function executeFeed( $block, $previewMode=false )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$config		= unserialize( $block['block_config'] );
		$where		= array();
		$_database	= explode( ';', $config['content'] );
		
		//-----------------------------------------
		// Fix language strings
		//-----------------------------------------
		
		$this->registry->ccsFunctions->fixStrings( $_database[0] );

		if( $_database[1] == 'comments' )
		{
			return $this->_getComments( $block, $config, $_database, $previewMode );
		}
		else if( $_database[1] == 'categories' )
		{
			return $this->_getCategories( $block, $config, $_database, $previewMode );
		}
		else
		{
			return $this->_getRecords( $block, $config, $_database, $previewMode );
		}
	}
	
	/**
	 * Get the categories
	 *
	 * @access	protected
	 * @param	array 		Block config
	 * @param	array 		Config data for this feed
	 * @param	array 		Database info
	 * @param	bool		Preview mode
	 * @return	string		Output HTML
	 */
	protected function _getCategories( $block, $config, $_database, $previewMode=false )
	{
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		$database	= $this->caches['ccs_databases'][ $_database[0] ];

		//-----------------------------------------
		// Now get categories....
		//-----------------------------------------
		
		$records	= $this->registry->ccsFunctions->getCategoriesClass( $database )->getCategoriesWithDepth();

		foreach( $records as $k => $v )
		{
			$records[ $k ]['url']		= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], $v['category']['category_id'] );
			$records[ $k ]['date']		= $v['category']['category_last_record_date'];
			$records[ $k ]['title']		= $v['category']['category_name'];
			$records[ $k ]['content']	= $v['category']['category_description'];
		}

		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		
		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($records) )
		{
			return '';
		}

		ob_start();
		$return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $records );
		ob_end_clean();
		return $return;
	}
	
	/**
	 * Return the records for database feed
	 *
	 * @access	protected
	 * @param	array 		Block config
	 * @param	array 		Config data for this feed
	 * @param	array 		Database info
	 * @param	bool		Preview mode
	 * @return	string		Output HTML
	 */
	protected function _getRecords( $block, $config, $_database, $previewMode=false )
	{
		//-----------------------------------------
		// Does table still exist?
		//-----------------------------------------
		
		if( !$this->DB->checkForTable( 'ccs_custom_database_' . $_database[0] ) )
		{
			return '';
		}

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		$where		= array();
		
		if( $config['filters']['filter_status'] != 'either' )
		{
			$where[]	= "p.record_locked=" . ( $config['filters']['filter_status'] == 'open' ? 0 : 1 );
		}
		
		if( $config['filters']['filter_visibility'] != 'either' )
		{
			$where[]	= "p.record_approved=" . ( $config['filters']['filter_visibility'] == 'approved' ? 1 : ( $config['filters']['filter_visibility'] == 'hidden' ? -1 : 0 ) );
		}

		if( $config['filters']['filter_pinned'] != 'either' )
		{
			$where[]	= "p.record_pinned=" . ( $config['filters']['filter_pinned'] == 'pinned' ? 1 : 0 );
		}
		
		if( $config['filters']['filter_category'] )
		{
			$where[]	= "p.category_id IN(" . $config['filters']['filter_category'] . ")";
		}
		else
		{
			//-----------------------------------------
			// Get category lib so we can restrict to cat we have permissions for
			//-----------------------------------------
			
			$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->caches['ccs_databases'][ $_database[0] ] );
			
			if( count($_categories->categories) )
			{
				$where[]	= "p.category_id IN(" . implode( ',', array_keys( $_categories->categories ) ) . ")";
			}
		}

		if( $config['filters']['filter_starter'] == 'myself' )
		{
			$where[]	= "p.member_id = " . $this->memberData['member_id'];
		}
		else if( $config['filters']['filter_starter'] == 'friends' )
		{
			//-----------------------------------------
			// Get page builder for friends
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$friends		= $pageBuilder->getFriends();
			
			if( count($friends) )
			{
				$where[]	= "p.member_id IN( " . implode( ',', $friends ) . ")";
			}
			else
			{
				return '';
			}
		}
		else if( $config['filters']['filter_starter'] != '' )
		{
			$member	= IPSMember::load( $config['filters']['filter_starter'], 'basic', 'displayname' );
			
			if( $member['member_id'] )
			{
				$where[]	= "p.member_id = " . $member['member_id'];
			}
			else
			{
				return '';
			}
		}
		
		if( $config['filters']['filter_rating'] )
		{
			$where[]	= "p.rating_real >= " . $config['filters']['filter_rating'];
		}
		
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		$database	= $this->caches['ccs_databases'][ $_database[0] ];

		if( ipsRegistry::getClass('permissions')->check( 'show', $database ) != TRUE )
		{
			return '';
		}
		
		//-----------------------------------------
		// Custom filtering
		//-----------------------------------------

		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields		= array();
		$_numeric	= 0;

		if( count($this->caches['ccs_fields'][ $database['database_id'] ]) AND is_array($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $r )
			{
				$fields[ $r['field_id'] ]	= $r;
				
				if( 'field_' . $r['field_id'] == $config['sortby'] )
				{
					$_numeric	= $r['field_is_numeric'];
				}
			}
		}

		for( $j=1; $j < 6; $j++ )
		{
			$_fieldId	= $config['filters']['filter_ct_' . $j ] ? $config['filters']['filter_ct_' . $j ] : 0;
			
			if( !$_fieldId )
			{
				continue;
			}
			
			//-----------------------------------------
			// If first character is @, get value from input
			//-----------------------------------------
			
			if( strpos( $config['filters']['filter_cv_' . $j ], '@' ) === 0 )
			{
				$config['filters']['filter_cv_' . $j ]	= $this->request[ substr( $config['filters']['filter_cv_' . $j ], 1 ) ];
			}

			if( $config['filters']['filter_cv_' . $j ] )
			{
				if( $fieldsClass->getSearchWhere( array( $_fieldId => $fields[ $_fieldId ] ) , $config['filters']['filter_cv_' . $j ] ) )
				{
					$where[]	= $fieldsClass->getSearchWhere( array( $_fieldId => $fields[ $_fieldId ] ) , $config['filters']['filter_cv_' . $j ] );
				}
			}
		}

		if( $config['sortby'] == 'RAND()' )
		{
			$order	= $this->DB->buildRandomOrder();
		}
		else
		{
			$order	= $this->DB->checkForField( $config['sortby'], 'ccs_custom_database_' . $_database[0] ) ? $config['sortby'] : 'primary_id_field';
	
			$order	= 'p.' . $order . ( $_numeric ? '+0' : '' ) . ' ' . $config['sortorder'];
		}

		//-----------------------------------------
		// Get database class
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
		$dbClass		= new $classToLoad( $this->registry );

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------

		$records	= array();
		$memberIds	= array();
		
		$this->DB->build( array(
								'select'	=> 'p.*',
								'from'		=> array( 'ccs_custom_database_' . $_database[0] => 'p' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'c.*',
														'from'		=> array( 'ccs_database_categories' => 'c' ),
														'where'		=> 'c.category_id=p.category_id',
														'type'		=> 'left',
														)
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-----------------------------------------
			// Normalization
			//-----------------------------------------
			
			$r['url']		= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $r );
			$r['date']		= $r['record_updated'];
			$r['_isRead']	= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
			$r['_database']	= $database;
			
			$records[]						= $r;
			$memberIds[ $r['member_id'] ]	= $r['member_id'];
		}
		
		//-----------------------------------------
		// Get member info
		//-----------------------------------------
		
		if( count($memberIds) )
		{
			$_members	= IPSMember::load( $memberIds );
			$_mems		= array();
			
			foreach( $_members as $k => $v )
			{
				foreach( $v as $_k => $_v )
				{
					if( strpos( $_k, 'field_' ) === 0 )
					{
						$_k	= str_replace( 'field_', 'user_field_', $_k );
					}
					
					$_mems[ $k ][ $_k ]	= $_v;
				}
			}
			
			$_members	= $_mems;
			
			foreach( $records as $key => $record )
			{
				$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['member_id'] ] ) );
				$records[ $key ]['title']	= $records[ $key ]['_title'];
			}
		}

		foreach( $records as $key => $record )
		{
			foreach( $record as $k => $v )
			{
				if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					$records[ $key ][ $k . '_value' ]	= $fieldsClass->getFieldValue( $fields[ $matches[1] ], $record, $fields[ $matches[1] ]['field_truncate'] );
					$records[ $key ][ $fields[ $matches[1] ]['field_key'] ]	= $records[ $key ][ $k . '_value' ];
				}
			}

			$records[ $key ]['title']	= strip_tags( $records[ $key ][ $database['database_field_title'] . '_value' ] );
			$records[ $key ]['image']	= $records[ $key ][ 'field_' . $fieldsByKey['article_image']['field_id'] . '_value' ];

			$records[ $key ]			= $dbClass->parseAttachments( $database, $fields, $records[ $key ] );

			//-----------------------------------------
			// Block content we can't view
			//-----------------------------------------
			
			if ( is_object($_categories) AND $_categories->categories[ $records[ $key ]['category_id'] ]['category_has_perms'] AND ipsRegistry::getClass('permissions')->check( 'show', $_categories->categories[ $records[ $key ]['category_id'] ] ) != TRUE )
			{
				$records[ $key ]['content']	= ipsRegistry::getClass('class_localization')->words['nosearchpermview'];
			}
			else if( ipsRegistry::getClass('permissions')->check( 'show', $database ) != TRUE )
			{
				$records[ $key ]['content']	= ipsRegistry::getClass('class_localization')->words['nosearchpermview'];
			}
			else
			{
				$records[ $key ]['content']	= $records[ $key ][ $database['database_field_content'] . '_value' ];
			}
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		
		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($records) )
		{
			return '';
		}

		ob_start();
		$return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $records );
		ob_end_clean();
		return $return;
	}
	
	/**
	 * Return the comments for database feed
	 *
	 * @access	protected
	 * @param	array 		Block config
	 * @param	array 		Config data for this feed
	 * @param	array 		Database info
	 * @param	bool		Preview mode
	 * @return	string		Output HTML
	 */
	protected function _getComments( $block, $config, $_database, $previewMode=false )
	{
		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		$where		= array();

		$where[]	= "c.comment_database_id=" . $_database[0];
		
		if( $config['filters']['filter_visibility'] != 'either' )
		{
			$where[]	= "c.comment_approved=" . ( $config['filters']['filter_visibility'] == 'approved' ? 1 : ( $config['filters']['filter_visibility'] == 'hidden' ? -1 : 0 ) );
		}

		if( $config['filters']['filter_category'] )
		{
			$where[]	= "r.category_id IN(" . $config['filters']['filter_category'] . ")";
		}
		else
		{
			//-----------------------------------------
			// Get category lib so we can restrict to cat we have permissions for
			//-----------------------------------------
			
			$_categories	= $this->registry->ccsFunctions->getCategoriesClass( $this->caches['ccs_databases'][ $_database[0] ] );
			
			if( count($_categories->categories) )
			{
				$where[]	= "r.category_id IN(" . implode( ',', array_keys( $_categories->categories ) ) . ")";
			}
		}
		
		if( $config['filters']['filter_starter'] == 'myself' )
		{
			$where[]	= "c.comment_user = " . $this->memberData['member_id'];
		}
		else if( $config['filters']['filter_starter'] == 'friends' )
		{
			//-----------------------------------------
			// Get page builder for friends
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$friends		= $pageBuilder->getFriends();
			
			if( count($friends) )
			{
				$where[]	= "c.comment_user IN( " . implode( ',', $friends ) . ")";
			}
			else
			{
				return '';
			}
		}
		else if( $config['filters']['filter_starter'] != '' )
		{
			$member	= IPSMember::load( $config['filters']['filter_starter'], 'basic', 'displayname' );
			
			if( $member['member_id'] )
			{
				$where[]	= "c.comment_user = " . $member['member_id'];
			}
			else
			{
				return '';
			}
		}

		if( $config['sortby'] == 'comment_user' OR $config['sortby'] == 'comment_date' )
		{
			$_p	= 'c.';
		}
		else
		{
			$_p	= 'r.';
		}

		if( $config['sortby'] == 'RAND()' )
		{
			$order	= $this->DB->buildRandomOrder();
		}
		else
		{
			$order	.= ' ' . $_p . $config['sortby'] . ' ' . $config['sortorder'];
		}
		
		//-----------------------------------------
		// Get DB
		//-----------------------------------------
		
		$database	= $this->caches['ccs_databases'][ $_database[0] ];
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields	= array();

		if( count($this->caches['ccs_fields'][ $database['database_id'] ]) AND is_array($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $r )
			{
				$fields[ $r['field_id'] ]	= $r;
			}
		}

		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'ccs_comment';

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------

		$records	= array();
		$memberIds	= array();
		
		$this->DB->build( array(
								'select'	=> 'c.*',
								'from'		=> array( 'ccs_database_comments' => 'c' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'r.*',
														'from'		=> array( 'ccs_custom_database_' . $_database[0] => 'r' ),
														'where'		=> "c.comment_record_id=r.primary_id_field",
														'type'		=> 'left',
														)
													)
						)		);
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			foreach( $r as $k => $v )
			{
				if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					$r[ $k . '_value' ]	= $fieldsClass->getFieldValue( $fields[ $matches[1] ], $r, $fields[ $matches[1] ]['field_truncate'] );
					$r[ $fields[ $matches[1] ]['field_key'] ]	= $r[ $k . '_value' ];
				}
			}

			//-----------------------------------------
			// Normalization
			//-----------------------------------------
			
			$r['url']		= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $r, $r['comment_id'] );
			$r['date']		= $r['comment_date'];
			$r['content']	= $r['comment_post'];
			$r['_title']	= strip_tags( $r[ $database['database_field_title'] . '_value' ] );
			$r['_isRead']	= $this->registry->classItemMarking->isRead( array( 'catID' => $r['category_id'], 'itemID' => $r['primary_id_field'], 'itemLastUpdate' => $r['record_updated'] ), 'ccs' );
			$r['_database']	= $database;
	
			$records[]							= $r;
			$memberIds[ $r['comment_user'] ]	= $r['comment_user'];
		}

		//-----------------------------------------
		// Get member info
		//-----------------------------------------
		
		if( count($memberIds) )
		{
			$_members	= IPSMember::load( $memberIds );
			$_mems		= array();
			
			foreach( $_members as $k => $v )
			{
				foreach( $v as $_k => $_v )
				{
					if( strpos( $_k, 'field_' ) === 0 )
					{
						$_k	= str_replace( 'field_', 'user_field_', $_k );
					}
					
					$_mems[ $k ][ $_k ]	= $_v;
				}
			}
			
			$_members	= $_mems;
			
			$_members[0]['member_id']				= 0;
			$_members[0]['members_display_name']	= $this->lang->words['global_guestname'];
			
			foreach( $records as $key => $record )
			{
				$records[ $key ]	= array_merge( $record, IPSMember::buildDisplayData( $_members[ $record['comment_user'] ] ) );
				$records[ $key ]['title']	= $records[ $key ]['_title'];

				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $records[ $key ]['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $records[ $key ]['mgroup_others'];
				
				$records[ $key ]['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $records[ $key ]['content'] );
			}
		}
		else
		{
			foreach( $records as $key => $record )
			{
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->settings['guest_group'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= '';
				
				$records[ $key ]['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $records[ $key ]['content'] );
			}
		}

		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();

		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}

		if( $config['hide_empty'] AND !count($records) )
		{
			return '';
		}

		ob_start();
		$return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $records );
		ob_end_clean();
		return $return;
	}
}