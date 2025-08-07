<?php
/**
 * Nexus feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10065 $ 
 * @since		1st March 2009
 */

class feed_nexus implements feedBlockInterface
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
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Additional info (database id;type)
	 * @return	array
	 * @todo	Whole function
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
			case 'packages':
			default:
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__npackageurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__npackagedate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__npackagetitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__npackagecontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'nexus_packages' ) as $_column )
				{
					if( $this->lang->words['col__nexus_packages_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_packages_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'nexus_packages_products' ) as $_column )
				{
					if( $this->lang->words['col__nexus_packages_products_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_packages_products_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_npackages']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__npackages'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'purchases':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__npurchaseurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__npurchasedate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__npurchasetitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__npurchasecontent'] ),
											);

				foreach( $this->DB->getFieldNames( 'nexus_purchases' ) as $_column )
				{
					if( $this->lang->words['col__nexus_purchases_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_purchases_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'nexus_packages' ) as $_column )
				{
					if( $this->lang->words['col__nexus_packages_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_packages_' . $_column ] );
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
								
							$this->lang->words['block_feed_npurchases']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__npurchases'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'tickets':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__nticketurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__nticketdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__ntickettitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__nticketcontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'nexus_support_requests' ) as $_column )
				{
					if( $this->lang->words['col__nexus_support_requests_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_support_requests_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'nexus_support_statuses' ) as $_column )
				{
					if( $this->lang->words['col__nexus_support_statuses_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_support_statuses_' . $_column ] );
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
								
							$this->lang->words['block_feed_ntickets']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__ntickets'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'invoices':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__ninvoiceurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__ninvoicedate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__ninvoicetitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__ninvoicecontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'nexus_invoices' ) as $_column )
				{
					if( $this->lang->words['col__nexus_invoices_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_invoices_' . $_column ] );
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
								
							$this->lang->words['block_feed_ninvoices']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__ninvoices'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'transactions':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__ntransurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__ntransdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__ntranstitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__ntranscontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'nexus_transactions' ) as $_column )
				{
					if( $this->lang->words['col__nexus_transactions_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_transactions_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'nexus_invoices' ) as $_column )
				{
					if( $this->lang->words['col__nexus_invoices_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_invoices_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'nexus_paymethods' ) as $_column )
				{
					if( $this->lang->words['col__nexus_paymethods_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_paymethods_' . $_column ] );
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
								
							$this->lang->words['block_feed_ntransactions']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__ntransactions'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;

			case 'shipping':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__nshippingurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__nshippingdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__nshippingtitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__nshippingcontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'nexus_ship_orders' ) as $_column )
				{
					if( $this->lang->words['col__nexus_ship_orders_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_ship_orders_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'nexus_invoices' ) as $_column )
				{
					if( $this->lang->words['col__nexus_invoices_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__nexus_invoices_' . $_column ] );
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
								
							$this->lang->words['block_feed_nshiporders']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__nshiporders'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
		}

		return $_return;
	}
	
	/**
	 * Appends member columns to existing arrays
	 *
	 * @access	protected
	 * @param	array 		Columns that have descriptions
	 * @param	array 		Columns that do not have descriptions
	 * @return	@e void		[Params are passed by reference and modified]
	 * @todo	Whole function
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
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
			}
			else
			{
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
	 * @access 	public
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
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		return array(
					'key'			=> 'nexus',
					'app'			=> 'nexus',
					'name'			=> $this->lang->words['feed_name__nexus'],
					'description'	=> $this->lang->words['feed_description__nexus'],
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
							array( 'packages', $this->lang->words['ct_nexus_packages'] ),
							array( 'purchases', $this->lang->words['ct_nexus_purchases'] ),
							array( 'tickets', $this->lang->words['ct_nexus_tickets'] ),
							array( 'invoices', $this->lang->words['ct_nexus_invoices'] ),
							array( 'transactions', $this->lang->words['ct_nexus_transactions'] ),
							array( 'shipping', $this->lang->words['ct_nexus_shipping'] ),
							);
		$_html		= array();
		
		if( !$asHTML )
		{
			return $_types;
		}
		
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
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'packages', 'purchases', 'tickets', 'invoices', 'transactions', 'shipping' ) ) )
		{
			$data['content_type']	= 'packages';
		}

		return array( true, $data['content_type'] );
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

		ipsRegistry::getAppClass( 'nexus' );

		$yesnoeither	= array(
								array( 'yes', $this->lang->words['dd_yesnoeither_yes'] ),
								array( 'no', $this->lang->words['dd_yesnoeither_no'] ),
								array( 'either', $this->lang->words['dd_yesnoeither_both'] ),
								);

		switch( $session['config_data']['content_type'] )
		{
			case 'packages':
			default:
				$session['config_data']['filters']['filter_stock']			= $session['config_data']['filters']['filter_stock'] ? $session['config_data']['filters']['filter_stock'] : '';
				$session['config_data']['filters']['filter_regfilter']		= $session['config_data']['filters']['filter_regfilter'] ? $session['config_data']['filters']['filter_regfilter'] : 'either';
				$session['config_data']['filters']['filter_store']			= $session['config_data']['filters']['filter_store'] ? $session['config_data']['filters']['filter_store'] : 'either';
				$session['config_data']['filters']['filter_storefeature']	= $session['config_data']['filters']['filter_storefeature'] ? $session['config_data']['filters']['filter_storefeature'] : 'either';
				$session['config_data']['filters']['filter_physical']		= $session['config_data']['filters']['filter_physical'] ? $session['config_data']['filters']['filter_physical'] : 'either';
				$session['config_data']['filters']['filter_subscription']	= $session['config_data']['filters']['filter_subscription'] ? $session['config_data']['filters']['filter_subscription'] : 'either';
				$session['config_data']['filters']['filter_upgrading']		= $session['config_data']['filters']['filter_upgrading'] ? $session['config_data']['filters']['filter_upgrading'] : 'either';
				$session['config_data']['filters']['filter_downgrading']	= $session['config_data']['filters']['filter_downgrading'] ? $session['config_data']['filters']['filter_downgrading'] : 'either';
				$session['config_data']['filters']['filter_support']		= $session['config_data']['filters']['filter_support'] ? $session['config_data']['filters']['filter_support'] : 'either';
				$session['config_data']['filters']['filter_minprice']		= $session['config_data']['filters']['filter_minprice'] ? $session['config_data']['filters']['filter_minprice'] : 0;
				$session['config_data']['filters']['filter_maxprice']		= $session['config_data']['filters']['filter_maxprice'] ? $session['config_data']['filters']['filter_maxprice'] : 0;
				$session['config_data']['filters']['filter_renewable']		= $session['config_data']['filters']['filter_renewable'] ? $session['config_data']['filters']['filter_renewable'] : 'either';
				
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__stock'],
									'description'	=> $this->lang->words['feed_nexuspa__stock_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_stock', $session['config_data']['filters']['filter_stock'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__registration'],
									'description'	=> $this->lang->words['feed_nexuspa__registration_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_regfilter', $yesnoeither, $session['config_data']['filters']['filter_regfilter'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__store'],
									'description'	=> $this->lang->words['feed_nexuspa__store_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_store', $yesnoeither, $session['config_data']['filters']['filter_store'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__storefeature'],
									'description'	=> $this->lang->words['feed_nexuspa__storefeature_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_storefeature', $yesnoeither, $session['config_data']['filters']['filter_storefeature'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__physical'],
									'description'	=> $this->lang->words['feed_nexuspa__physical_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_physical', $yesnoeither, $session['config_data']['filters']['filter_physical'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__subscription'],
									'description'	=> $this->lang->words['feed_nexuspa__subscription_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_subscription', $yesnoeither, $session['config_data']['filters']['filter_subscription'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__upgrading'],
									'description'	=> $this->lang->words['feed_nexuspa__upgrading_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_upgrading', $yesnoeither, $session['config_data']['filters']['filter_upgrading'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__downgrading'],
									'description'	=> $this->lang->words['feed_nexuspa__downgrading_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_downgrading', $yesnoeither, $session['config_data']['filters']['filter_downgrading'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__support'],
									'description'	=> $this->lang->words['feed_nexuspa__support_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_support', $yesnoeither, $session['config_data']['filters']['filter_support'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__minprice'],
									'description'	=> $this->lang->words['feed_nexuspa__minprice_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_minprice', $session['config_data']['filters']['filter_minprice'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__maxprice'],
									'description'	=> $this->lang->words['feed_nexuspa__maxprice_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_maxprice', $session['config_data']['filters']['filter_maxprice'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspa__renewable'],
									'description'	=> $this->lang->words['feed_nexuspa__renewable_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_renewable', $yesnoeither, $session['config_data']['filters']['filter_renewable'] ),
									);
			break;
			
			case 'purchases':
				$session['config_data']['filters']['filter_pproduct']		= $session['config_data']['filters']['filter_pproduct'] ? $session['config_data']['filters']['filter_pproduct'] : '';
				$session['config_data']['filters']['filter_pmember']		= $session['config_data']['filters']['filter_pmember'] ? $session['config_data']['filters']['filter_pmember'] : '';
				$session['config_data']['filters']['filter_pactive']		= $session['config_data']['filters']['filter_pactive'] ? $session['config_data']['filters']['filter_pactive'] : 'either';
				$session['config_data']['filters']['filter_pcancelled']		= $session['config_data']['filters']['filter_pcancelled'] ? $session['config_data']['filters']['filter_pcancelled'] : 'no';
				$session['config_data']['filters']['filter_ppurchasedate']	= $session['config_data']['filters']['filter_ppurchasedate'] ? $session['config_data']['filters']['filter_ppurchasedate'] : '';
				$session['config_data']['filters']['filter_pexpiredate']	= $session['config_data']['filters']['filter_pexpiredate'] ? $session['config_data']['filters']['filter_pexpiredate'] : '';
				$session['config_data']['filters']['filter_ppending']		= $session['config_data']['filters']['filter_ppending'] ? $session['config_data']['filters']['filter_ppending'] : 'either';

				$products	= array();

				$this->DB->build( array( 'select' => 'p_id, p_name', 'from' => 'nexus_packages', 'order' => 'p_name ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$prodcuts[]	= array( $r['p_id'], $r['p_name'] );
				}
				
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__products'],
									'description'	=> $this->lang->words['feed_nexuspu__products_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_pproduct[]', $products, explode( ',', $session['config_data']['filters']['filter_pproduct'] ), 10 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__member'],
									'description'	=> $this->lang->words['feed_nexuspu__member_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_pmember', $session['config_data']['filters']['filter_pmember'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__active'],
									'description'	=> $this->lang->words['feed_nexuspu__active_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pactive', $yesnoeither, $session['config_data']['filters']['filter_pactive'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__pending'],
									'description'	=> $this->lang->words['feed_nexuspu__pending_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_ppending', $yesnoeither, $session['config_data']['filters']['filter_ppending'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__cancelled'],
									'description'	=> $this->lang->words['feed_nexuspu__cancelled_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_pcancelled', $yesnoeither, $session['config_data']['filters']['filter_pcancelled'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__purchasedate'],
									'description'	=> $this->lang->words['feed_nexuspu__purchasedate_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_ppurchasedate', $session['config_data']['filters']['filter_ppurchasedate'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexuspu__expiredate'],
									'description'	=> $this->lang->words['feed_nexuspu__expiredate_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_pexpiredate', $session['config_data']['filters']['filter_pexpiredate'] ),
									);
			break;
			
			case 'tickets':
				$session['config_data']['filters']['filter_tdepartment']	= $session['config_data']['filters']['filter_tdepartment'] ? $session['config_data']['filters']['filter_tdepartment'] : '';
				$session['config_data']['filters']['filter_tstatus']		= $session['config_data']['filters']['filter_tstatus'] ? $session['config_data']['filters']['filter_tstatus'] : '';
				$session['config_data']['filters']['filter_tseverity']		= $session['config_data']['filters']['filter_tseverity'] ? $session['config_data']['filters']['filter_tseverity'] : '';
				$session['config_data']['filters']['filter_tmember']		= $session['config_data']['filters']['filter_tmember'] ? $session['config_data']['filters']['filter_tmember'] : '';
				$session['config_data']['filters']['filter_tminreplies']	= $session['config_data']['filters']['filter_tminreplies'] ? $session['config_data']['filters']['filter_tminreplies'] : 0;
				$session['config_data']['filters']['filter_tmaxreplies']	= $session['config_data']['filters']['filter_tmaxreplies'] ? $session['config_data']['filters']['filter_tmaxreplies'] : 0;
				$session['config_data']['filters']['filter_topened']		= $session['config_data']['filters']['filter_topened'] ? $session['config_data']['filters']['filter_topened'] : '';
				$session['config_data']['filters']['filter_tlastreply']		= $session['config_data']['filters']['filter_tlastreply'] ? $session['config_data']['filters']['filter_tlastreply'] : '';

				$departments	= array();

				$this->DB->build( array( 'select' => 'dpt_id, dpt_name', 'from' => 'nexus_support_departments', 'order' => 'dpt_position ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$departments[]	= array( $r['dpt_id'], $r['dpt_name'] );
				}
				
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__departments'],
									'description'	=> $this->lang->words['feed_nexust__departments_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_tdepartment[]', $departments, explode( ',', $session['config_data']['filters']['filter_tdepartment'] ), 10 ),
									);

				$status	= array();

				$this->DB->build( array( 'select' => 'status_id, status_name', 'from' => 'nexus_support_statuses', 'order' => 'status_position ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$status[]	= array( $r['status_id'], $r['status_name'] );
				}

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__status'],
									'description'	=> $this->lang->words['feed_nexust__status_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_tstatus[]', $status, explode( ',', $session['config_data']['filters']['filter_pmember'] ), 10 ),
									);

				$severity	= array();

				$this->DB->build( array( 'select' => 'sev_id, sev_name', 'from' => 'nexus_support_severities', 'order' => 'sev_position ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$severity[]	= array( $r['sev_id'], $r['sev_name'] );
				}

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__severity'],
									'description'	=> $this->lang->words['feed_nexust__severity_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_tseverity[]', $severity, explode( ',', $session['config_data']['filters']['filter_tseverity'] ), 5 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__member'],
									'description'	=> $this->lang->words['feed_nexust__member_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tmember', $session['config_data']['filters']['filter_tmember'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__minreplies'],
									'description'	=> $this->lang->words['feed_nexust__minreplies_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tminreplies', $session['config_data']['filters']['filter_tminreplies'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__maxreplies'],
									'description'	=> $this->lang->words['feed_nexust__maxreplies_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tmaxreplies', $session['config_data']['filters']['filter_tmaxreplies'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__opened'],
									'description'	=> $this->lang->words['feed_nexust__opened_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_topened', $session['config_data']['filters']['filter_topened'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexust__lastreply'],
									'description'	=> $this->lang->words['feed_nexust__lastreply_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tlastreply', $session['config_data']['filters']['filter_tlastreply'] ),
									);
			break;

			case 'invoices':
				$session['config_data']['filters']['filter_istatus']		= $session['config_data']['filters']['filter_istatus'] ? $session['config_data']['filters']['filter_istatus'] : '';
				$session['config_data']['filters']['filter_icreated']		= $session['config_data']['filters']['filter_icreated'] ? $session['config_data']['filters']['filter_icreated'] : '';
				$session['config_data']['filters']['filter_ipaid']			= $session['config_data']['filters']['filter_ipaid'] ? $session['config_data']['filters']['filter_ipaid'] : '';
				$session['config_data']['filters']['filter_imintotal']		= $session['config_data']['filters']['filter_imintotal'] ? $session['config_data']['filters']['filter_imintotal'] : 0;
				$session['config_data']['filters']['filter_imaxtotal']		= $session['config_data']['filters']['filter_imaxtotal'] ? $session['config_data']['filters']['filter_imaxtotal'] : 0;

				$status	= array( array( 'paid', $this->lang->words['feed_nexusi__spaid'] ), array( 'pend', $this->lang->words['feed_nexusi__spend'] ), array( 'expd', $this->lang->words['feed_nexusi__sexpd'] ), array( 'canc', $this->lang->words['feed_nexusi__scanc'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusi__status'],
									'description'	=> $this->lang->words['feed_nexusi__status_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_istatus[]', $status, explode( ',', $session['config_data']['filters']['filter_renewable'] ), 4 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusi__created'],
									'description'	=> $this->lang->words['feed_nexusi__created_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_icreated', $session['config_data']['filters']['filter_icreated'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusi__paid'],
									'description'	=> $this->lang->words['feed_nexusi__paid_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_ipaid', $session['config_data']['filters']['filter_ipaid'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusi__mintotal'],
									'description'	=> $this->lang->words['feed_nexusi__mintotal_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_imintotal', $session['config_data']['filters']['filter_imintotal'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusi__maxtotal'],
									'description'	=> $this->lang->words['feed_nexusi__maxtotal_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_imaxtotal', $session['config_data']['filters']['filter_imaxtotal'] ),
									);
			break;

			case 'transactions':
				$session['config_data']['filters']['filter_tranmember']		= $session['config_data']['filters']['filter_tranmember'] ? $session['config_data']['filters']['filter_tranmember'] : '';
				$session['config_data']['filters']['filter_tranmethod']		= $session['config_data']['filters']['filter_tranmethod'] ? $session['config_data']['filters']['filter_tranmethod'] : '';
				$session['config_data']['filters']['filter_transtatus']		= $session['config_data']['filters']['filter_transtatus'] ? $session['config_data']['filters']['filter_transtatus'] : '';
				$session['config_data']['filters']['filter_tranmintotal']	= $session['config_data']['filters']['filter_tranmintotal'] ? $session['config_data']['filters']['filter_tranmintotal'] : 0;
				$session['config_data']['filters']['filter_tranmaxtotal']	= $session['config_data']['filters']['filter_tranmaxtotal'] ? $session['config_data']['filters']['filter_tranmaxtotal'] : 0;
				$session['config_data']['filters']['filter_tranmindate']	= $session['config_data']['filters']['filter_tranmindate'] ? $session['config_data']['filters']['filter_tranmindate'] : '';
				$session['config_data']['filters']['filter_tranmaxdate']	= $session['config_data']['filters']['filter_tranmaxdate'] ? $session['config_data']['filters']['filter_tranmaxdate'] : '';

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__member'],
									'description'	=> $this->lang->words['feed_nexustr__member_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tranmember', $session['config_data']['filters']['filter_tranmember'] ),
									);

				$methods	= array();

				$this->DB->build( array( 'select' => 'm_id, m_name', 'from' => 'nexus_paymethods', 'order' => 'm_position ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$methods[]	= array( $r['m_id'], $r['m_name'] );
				}

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__paymethod'],
									'description'	=> $this->lang->words['feed_nexustr__paymethod_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_tranmethod[]', $methods, explode( ',', $session['config_data']['filters']['filter_tranmethod'] ), 10 ),
									);

				$status	= array( 
								array( 'okay', $this->lang->words['feed_nexustr__sokay'] ), 
								array( 'hold', $this->lang->words['feed_nexustr__shold'] ), 
								array( 'wait', $this->lang->words['feed_nexustr__swait'] ), 
								array( 'revw', $this->lang->words['feed_nexustr__srevw'] ),
								array( 'fail', $this->lang->words['feed_nexustr__sfail'] ),
								array( 'rfnd', $this->lang->words['feed_nexustr__srfnd'] ),
								array( 'pend', $this->lang->words['feed_nexustr__spend'] ),
								);
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__status'],
									'description'	=> $this->lang->words['feed_nexustr__status_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_transtatus[]', $status, explode( ',', $session['config_data']['filters']['filter_transtatus'] ), 5 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__mintotal'],
									'description'	=> $this->lang->words['feed_nexustr__mintotal_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tranmintotal', $session['config_data']['filters']['filter_tranmintotal'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__maxtotal'],
									'description'	=> $this->lang->words['feed_nexustr__maxtotal_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tranmaxtotal', $session['config_data']['filters']['filter_tranmaxtotal'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__mindate'],
									'description'	=> $this->lang->words['feed_nexustr__mindate_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tranmindate', $session['config_data']['filters']['filter_tranmindate'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexustr__maxdate'],
									'description'	=> $this->lang->words['feed_nexustr__maxdate_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_tranmaxdate', $session['config_data']['filters']['filter_tranmaxdate'] ),
									);
			break;

			case 'shipping':
				$session['config_data']['filters']['filter_sostatus']		= $session['config_data']['filters']['filter_sostatus'] ? $session['config_data']['filters']['filter_sostatus'] : '';
				$session['config_data']['filters']['filter_sodate']			= $session['config_data']['filters']['filter_sodate'] ? $session['config_data']['filters']['filter_sodate'] : '';
				$session['config_data']['filters']['filter_sosaved']		= $session['config_data']['filters']['filter_sosaved'] ? $session['config_data']['filters']['filter_sosaved'] : '';
				$session['config_data']['filters']['filter_somethod']		= $session['config_data']['filters']['filter_somethod'] ? $session['config_data']['filters']['filter_somethod'] : '';

				$status	= array( array( 'done', $this->lang->words['feed_nexusso__sdone'] ), array( 'pend', $this->lang->words['feed_nexusso__spend'] ), array( 'canc', $this->lang->words['feed_nexusso__scanc'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusso__status'],
									'description'	=> $this->lang->words['feed_nexusso__status_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_sostatus[]', $status, explode( ',', $session['config_data']['filters']['filter_sostatus'] ), 3 ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusso__date'],
									'description'	=> $this->lang->words['feed_nexusso__date_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_sodate', $session['config_data']['filters']['filter_sodate'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusso__saved'],
									'description'	=> $this->lang->words['feed_nexusso__saved_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_sosaved', $session['config_data']['filters']['filter_sosaved'] ),
									);

				$methods	= array();

				$this->DB->build( array( 'select' => 's_id, s_name', 'from' => 'nexus_shipping', 'order' => 's_name ASC' ) );
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$methods[]	= array( $r['s_id'], $r['s_name'] );
				}

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_nexusso__method'],
									'description'	=> $this->lang->words['feed_nexusso__method_desc'],
									'field'			=> $this->registry->output->formMultiDropdown( 'filter_somethod[]', $methods, explode( ',', $session['config_data']['filters']['filter_somethod'] ), 10 ),
									);
			break;
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
		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'packages':
			default:
				$filters['filter_stock']			= $data['filter_stock'] ? $data['filter_stock'] : '';
				$filters['filter_regfilter']		= $data['filter_regfilter'] ? $data['filter_regfilter'] : 'either';
				$filters['filter_store']			= $data['filter_store'] ? $data['filter_store'] : 'either';
				$filters['filter_storefeature']		= $data['filter_storefeature'] ? $data['filter_storefeature'] : 'either';
				$filters['filter_physical']			= $data['filter_physical'] ? $data['filter_physical'] : 'either';
				$filters['filter_subscription']		= $data['filter_subscription'] ? $data['filter_subscription'] : 'either';
				$filters['filter_upgrading']		= $data['filter_upgrading'] ? $data['filter_upgrading'] : 'either';
				$filters['filter_downgrading']		= $data['filter_downgrading'] ? $data['filter_downgrading'] : 'either';
				$filters['filter_support']			= $data['filter_support'] ? $data['filter_support'] : 'either';
				$filters['filter_minprice']			= $data['filter_minprice'] ? $data['filter_minprice'] : 0;
				$filters['filter_maxprice']			= $data['filter_maxprice'] ? $data['filter_maxprice'] : 0;
				$filters['filter_renewable']		= $data['filter_renewable'] ? $data['filter_renewable'] : 'either';
			break;
			
			case 'purchases':
				$filters['filter_pproduct']			= is_array($data['filter_pproduct']) ? implode( ',', $data['filter_pproduct'] ) : '';
				$filters['filter_pmember']			= $data['filter_pmember'] ? $data['filter_pmember'] : '';
				$filters['filter_pactive']			= $data['filter_pactive'] ? $data['filter_pactive'] : 'either';
				$filters['filter_pcancelled']		= $data['filter_pcancelled'] ? $data['filter_pcancelled'] : 'no';
				$filters['filter_ppurchasedate']	= $data['filter_ppurchasedate'] ? $data['filter_ppurchasedate'] : '';
				$filters['filter_pexpiredate']		= $data['filter_pexpiredate'] ? $data['filter_pexpiredate'] : '';
				$filters['filter_ppending']			= $data['filter_ppending'] ? $data['filter_ppending'] : 'either';
			break;
			
			case 'tickets':
				$filters['filter_tdepartment']		= is_array($data['filter_tdepartment']) ? implode( ',', $data['filter_tdepartment'] ) : '';
				$filters['filter_tstatus']			= is_array($data['filter_tstatus']) ? implode( ',', $data['filter_tstatus'] ) : '';
				$filters['filter_tseverity']		= is_array($data['filter_tseverity']) ? implode( ',', $data['filter_tseverity'] ) : '';
				$filters['filter_tmember']			= $data['filter_tmember'] ? $data['filter_tmember'] : '';
				$filters['filter_tminreplies']		= $data['filter_tminreplies'] ? $data['filter_tminreplies'] : 0;
				$filters['filter_tmaxreplies']		= $data['filter_tmaxreplies'] ? $data['filter_tmaxreplies'] : 0;
				$filters['filter_topened']			= $data['filter_topened'] ? $data['filter_topened'] : '';
				$filters['filter_tlastreply']		= $data['filter_tlastreply'] ? $data['filter_tlastreply'] : '';
			break;

			case 'invoices':
				$filters['filter_istatus']			= is_array($data['filter_istatus']) ? implode( ',', $data['filter_istatus'] ) : '';
				$filters['filter_icreated']			= $data['filter_icreated'] ? $data['filter_icreated'] : '';
				$filters['filter_ipaid']			= $data['filter_ipaid'] ? $data['filter_ipaid'] : '';
				$filters['filter_imintotal']		= $data['filter_imintotal'] ? $data['filter_imintotal'] : 0;
				$filters['filter_imaxtotal']		= $data['filter_imaxtotal'] ? $data['filter_imaxtotal'] : 0;
			break;

			case 'transactions':
				$filters['filter_tranmember']		= $data['filter_tranmember'] ? $data['filter_tranmember'] : '';
				$filters['filter_tranmethod']		= is_array($data['filter_tranmethod']) ? implode( ',', $data['filter_tranmethod'] ) : '';
				$filters['filter_transtatus']		= is_array($data['filter_transtatus']) ? implode( ',', $data['filter_transtatus'] ) : '';
				$filters['filter_tranmintotal']		= $data['filter_tranmintotal'] ? $data['filter_tranmintotal'] : 0;
				$filters['filter_tranmaxtotal']		= $data['filter_tranmaxtotal'] ? $data['filter_tranmaxtotal'] : 0;
				$filters['filter_tranmindate']		= $data['filter_tranmindate'] ? $data['filter_tranmindate'] : '';
				$filters['filter_tranmaxdate']		= $data['filter_tranmaxdate'] ? $data['filter_tranmaxdate'] : '';
			break;

			case 'shipping':
				$filters['filter_sostatus']			= is_array($data['filter_sostatus']) ? implode( ',', $data['filter_sostatus'] ) : '';
				$filters['filter_sodate']			= $data['filter_sodate'] ? $data['filter_sodate'] : '';
				$filters['filter_sosaved']			= $data['filter_sosaved'] ? $data['filter_sosaved'] : '';
				$filters['filter_somethod']			= is_array($data['filter_somethod']) ? implode( ',', $data['filter_somethod'] ) : '';
			break;
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
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'packages':
			default:
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'price';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_nexuspackage__name'] ), 
								array( 'stock', $this->lang->words['sort_nexuspackage__stock'] ), 
								array( 'upgrade_charge', $this->lang->words['sort_nexuspackage__upgrade'] ),
								array( 'downgrade_refund', $this->lang->words['sort_nexuspackage__downgrade'] ),
								array( 'price', $this->lang->words['sort_nexuspackage__price'] ),
								array( 'renewal_price', $this->lang->words['sort_nexuspackage__renewal'] ),
								array( 'position', $this->lang->words['sort_nexuspackage__position'] ),
								array( 'featured', $this->lang->words['sort_nexuspackage__featured'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;
			
			case 'purchases':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'startdate';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_nexuspurchase__name'] ), 
								array( 'active', $this->lang->words['sort_nexuspurchase__active'] ), 
								array( 'cancelled', $this->lang->words['sort_nexuspurchase__cancelled'] ),
								array( 'startdate', $this->lang->words['sort_nexuspurchase__startdate'] ),
								array( 'expiredate', $this->lang->words['sort_nexuspurchase__expiredate'] ),
								array( 'renewal_price', $this->lang->words['sort_nexuspurchase__renewal'] ),
								array( 'package_name', $this->lang->words['sort_nexuspurchase__packagename'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;
			
			case 'tickets':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'started';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_nexusticket__title'] ), 
								array( 'started', $this->lang->words['sort_nexusticket__started'] ), 
								array( 'last_reply', $this->lang->words['sort_nexusticket__lastreply'] ),
								array( 'last_new_reply', $this->lang->words['sort_nexusticket__lastnewreply'] ),
								array( 'last_staff_reply', $this->lang->words['sort_nexusticket__laststaffreply'] ),
								array( 'replies', $this->lang->words['sort_nexusticket__replies'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;

			case 'invoices':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'date';

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_nexusinvoice__title'] ), 
								array( 'total', $this->lang->words['sort_nexusinvoice__total'] ), 
								array( 'date', $this->lang->words['sort_nexusinvoice__date'] ),
								array( 'paiddate', $this->lang->words['sort_nexusinvoice__paiddate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;

			case 'transactions':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'date';

				$sortby	= array( 
								array( 'amount', $this->lang->words['sort_nexustrans__amount'] ), 
								array( 'date', $this->lang->words['sort_nexustrans__date'] ), 
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;

			case 'shipping':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'date';

				$sortby	= array( 
								array( 'date', $this->lang->words['sort_nexusso__date'] ), 
								array( 'shipdate', $this->lang->words['sort_nexusso__shipdate'] ), 
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);
			break;
		}

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
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		switch( $session['config_data']['content_type'] )
		{
			case 'packages':
			default:
				$sortby					= array( 'name', 'stock', 'upgrade_charge', 'downgrade_refund', 'price', 'renewal_price', 'position', 'featured', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'price';
			break;
			
			case 'purchases':
				$sortby					= array( 'name', 'active', 'cancelled', 'startdate', 'expiredate', 'renewal_price', 'package_name', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'startdate';
			break;
			
			case 'tickets':
				$sortby					= array( 'title', 'started', 'last_reply', 'last_new_reply', 'last_staff_reply', 'replies', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'started';
			break;

			case 'invoices':
				$sortby					= array( 'title', 'total', 'date', 'paiddate', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'date';
			break;

			case 'transactions':
				$sortby					= array( 'amount', 'date', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'date';
			break;

			case 'shipping':
				$sortby					= array( 'date', 'shipdate', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'date';
			break;
		}

		return array( true, $limits );
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
		$this->lang->loadLanguageFile( array( 'admin_nexus' ), 'nexus' );
		
		$config	= unserialize( $block['block_config'] );

		switch( $config['content'] )
		{
			case 'packages':
			default:
				$records	= $this->getPackages( $block, $config, $previewMode );
			break;
			
			case 'purchases':
				$records	= $this->getPurchases( $block, $config, $previewMode );
			break;
			
			case 'tickets':
				$records	= $this->getTickets( $block, $config, $previewMode );
			break;

			case 'invoices':
				$records	= $this->getInvoices( $block, $config, $previewMode );
			break;

			case 'transactions':
				$records	= $this->getTransactions( $block, $config, $previewMode );
			break;

			case 'shipping':
				$records	= $this->getShipping( $block, $config, $previewMode );
			break;
		}

		$feedConfig		= $this->returnFeedInfo();

		//-----------------------------------------
		// Gallery or custom template?
		//-----------------------------------------

		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit	= $block['tpb_name'];
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
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getPackages( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_stock'] )
		{
			$where[]	= "(p.p_stock = -1 OR p.p_stock >= " . intval($config['filters']['filter_stock']) . ")";
		}

		if( $config['filters']['filter_regfilter'] != 'either' )
		{
			$where[]	= "p.p_reg=" . ( $config['filters']['filter_regfilter'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_store'] != 'either' )
		{
			$where[]	= "p.p_store=" . ( $config['filters']['filter_store'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_storefeature'] != 'either' )
		{
			$where[]	= "p.p_featured=" . ( $config['filters']['filter_storefeature'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_physical'] != 'either' )
		{
			$where[]	= "prod.p_physical=" . ( $config['filters']['filter_physical'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_subscription'] != 'either' )
		{
			$where[]	= "prod.p_subscription=" . ( $config['filters']['filter_subscription'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_upgrading'] != 'either' )
		{
			$where[]	= "p.p_allow_upgrading=" . ( $config['filters']['filter_upgrading'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_downgrading'] != 'either' )
		{
			$where[]	= "p.p_allow_downgrading=" . ( $config['filters']['filter_downgrading'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_support'] != 'either' )
		{
			$where[]	= "p.p_support=" . ( $config['filters']['filter_support'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_renewable'] != 'either' )
		{
			$where[]	= "p.p_renewals=" . ( $config['filters']['filter_renewable'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_minprice'] )
		{
			$where[]	= "p.p_base_price >= " . intval($config['filters']['filter_minprice']);
		}

		if( $config['filters']['filter_maxprice'] )
		{
			$where[]	= "p.p_base_price <= " . intval($config['filters']['filter_maxprice']);
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'price':
			default:
				$order	.= "p.p_base_price ";
			break;

			case 'name':
				$order	.= "p.p_name ";
			break;

			case 'stock':
				$order	.= "p.p_stock ";
			break;

			case 'upgrade_charge':
				$order	.= "p.p_upgrade_charge ";
			break;

			case 'downgrade_refund':
				$order	.= "p.p_downgrade_refund ";
			break;

			case 'renewal_price':
				$order	.= "p.p_renewal_price ";
			break;

			case 'position':
				$order	.= "p.p_position ";
			break;

			case 'featured':
				$order	.= "p.p_featured ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 'p.*',
								'from'		=> array( 'nexus_packages' => 'p' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'prod.*',
														'from'		=> array( 'nexus_packages_products' => 'prod' ),
														'where'		=> "prod.p_id=p.p_id",
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
			
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=payments&amp;section=store&amp;do=item&amp;id=' . $r['p_id'], 'none', $r['p_seo_name'], 'storeitem' );
			$r['date']		= 0;
			$r['content']	= $r['p_desc'];
			$r['title']		= $r['p_name'];

			$records[]		= $r;
		}

		return $records;
	}

	/**
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getPurchases( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_pproduct'] )
		{
			$where[]	= "p.ps_app='nexus' AND p.ps_item_id IN(" . $config['filters']['filter_pproduct'] . ")";
		}

		if( $config['filters']['filter_pactive'] != 'either' )
		{
			$where[]	= "p.ps_active=" . ( $config['filters']['filter_pactive'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_pcancelled'] != 'either' )
		{
			$where[]	= "p.ps_cancelled=" . ( $config['filters']['filter_pcancelled'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_ppending'] != 'either' )
		{
			$where[]	= "p.ps_invoice_pending=" . ( $config['filters']['filter_ppending'] == 'yes' ? 1 : 0 );
		}

		if( $config['filters']['filter_pmember'] == 'myself' )
		{
			$where[]	= "p.ps_member = " . $this->memberData['member_id'];
		}
		else if( $config['filters']['filter_pmember'] == 'friends' )
		{
			//-----------------------------------------
			// Get page builder for friends
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$friends		= $pageBuilder->getFriends();
			
			if( count($friends) )
			{
				$where[]	= "p.ps_member IN( " . implode( ',', $friends ) . ")";
			}
			else
			{
				return array();
			}
		}
		else if( $config['filters']['filter_pmember'] == '@request' )
		{
			$where[]	= "p.ps_member = " . intval($this->request['author']);
		}
		else if( $config['filters']['filter_pmember'] != '' )
		{
			$member	= IPSMember::load( $config['filters']['filter_pmember'], 'basic', 'displayname' );
			
			if( $member['member_id'] )
			{
				$where[]	= "p.ps_member = " . $member['member_id'];
			}
			else
			{
				return array();
			}
		}

		if( $config['filters']['filter_ppurchasedate'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_ppurchasedate'] );
			
			if( $timestamp )
			{
				$where[]	= "p.ps_start > " . $timestamp;
			}
		}

		if( $config['filters']['filter_pexpiredate'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_pexpiredate'] );
			
			if( $timestamp )
			{
				$where[]	= "p.ps_expire > " . $timestamp;
			}
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'startdate':
			default:
				$order	.= "p.ps_start ";
			break;

			case 'name':
				$order	.= "p.ps_name ";
			break;

			case 'active':
				$order	.= "p.ps_active ";
			break;

			case 'cancelled':
				$order	.= "p.ps_cancelled ";
			break;

			case 'expiredate':
				$order	.= "p.ps_expire ";
			break;

			case 'renewal_price':
				$order	.= "p.ps_renewal_price ";
			break;

			case 'package_name':
				$order	.= "pack.p_name ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 'p.*',
								'from'		=> array( 'nexus_purchases' => 'p' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'pack.*',
														'from'		=> array( 'nexus_packages' => 'pack' ),
														'where'		=> "pack.p_id=p.ps_item_id AND p.ps_app='nexus'",
														'type'		=> 'left',
														),
													array(
														'select'	=> 'm.*, m.member_id as mid',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=r.r_member',
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=clients&amp;section=purchases', 'none' );
			$r['date']		= $r['ps_start'];
			$r['content']	= sprintf( $this->lang->words['nexus_purchase_content'], $r['p_name'], $r['members_display_name'] );
			$r['title']		= $r['ps_name'];

			$r				= IPSMember::buildDisplayData( $r );
			
			$records[]		= $r;
		}

		return $records;
	}

	/**
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getTickets( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_tdepartment'] )
		{
			$where[]	= "r.r_department IN(" . $config['filters']['filter_tdepartment'] . ")";
		}

		if( $config['filters']['filter_tstatus'] )
		{
			$where[]	= "r.r_status IN(" . $config['filters']['filter_tstatus'] . ")";
		}

		if( $config['filters']['filter_tseverity'] )
		{
			$where[]	= "r.r_severity IN(" . $config['filters']['filter_tseverity'] . ")";
		}

		if( $config['filters']['filter_tmember'] == 'myself' )
		{
			$where[]	= "r.r_member = " . $this->memberData['member_id'];
		}
		else if( $config['filters']['filter_tmember'] == 'friends' )
		{
			//-----------------------------------------
			// Get page builder for friends
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$friends		= $pageBuilder->getFriends();
			
			if( count($friends) )
			{
				$where[]	= "r.r_member IN( " . implode( ',', $friends ) . ")";
			}
			else
			{
				return array();
			}
		}
		else if( $config['filters']['filter_tmember'] == '@request' )
		{
			$where[]	= "r.r_member = " . intval($this->request['author']);
		}
		else if( $config['filters']['filter_tmember'] != '' )
		{
			$member	= IPSMember::load( $config['filters']['filter_tmember'], 'basic', 'displayname' );
			
			if( $member['member_id'] )
			{
				$where[]	= "r.r_member = " . $member['member_id'];
			}
			else
			{
				return array();
			}
		}

		if( $config['filters']['filter_tminreplies'] )
		{
			$where[]	= "r.r_replies >= " . intval($config['filters']['filter_tminreplies']);
		}

		if( $config['filters']['filter_tmaxreplies'] )
		{
			$where[]	= "r.r_replies < " . intval($config['filters']['filter_tmaxreplies']);
		}

		if( $config['filters']['filter_topened'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_topened'] );
			
			if( $timestamp )
			{
				$where[]	= "r.r_started > " . $timestamp;
			}
		}

		if( $config['filters']['filter_tlastreply'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_tlastreply'] );
			
			if( $timestamp )
			{
				$where[]	= "r.r_last_reply > " . $timestamp;
			}
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'started':
			default:
				$order	.= "r.r_started ";
			break;

			case 'title':
				$order	.= "r.r_title ";
			break;

			case 'last_reply':
				$order	.= "r.r_last_reply ";
			break;

			case 'last_new_reply':
				$order	.= "r.r_last_new_reply ";
			break;

			case 'last_staff_reply':
				$order	.= "r.r_last_staff_reply ";
			break;

			case 'replies':
				$order	.= "r.r_replies ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( 'nexus_support_requests' => 'r' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'stat.*',
														'from'		=> array( 'nexus_support_statuses' => 'stat' ),
														'where'		=> 'r.r_status=stat.status_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'm.*, m.member_id as mid',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=r.r_member',
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=support&amp;section=view&amp;id=' . $r['r_id'], 'none' );
			$r['date']		= $r['r_last_reply'];
			$r['content']	= sprintf( $this->lang->words['nexus_ticket_content'], $r['members_display_name'], $this->registry->class_localization->getDate( $r['r_started'], 'SHORT' ), $r['status_name'] );
			$r['title']		= $r['r_title'];

			$r				= IPSMember::buildDisplayData( $r );
			
			$records[]		= $r;
		}

		return $records;
	}

	/**
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getInvoices( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_istatus'] )
		{
			$where[]	= "i.i_status IN('" . implode( "','", explode( ',', $config['filters']['filter_istatus'] ) ) . "')";
		}

		if( $config['filters']['filter_imintotal'] )
		{
			$where[]	= "i.i_total  >= '" . floatval($config['filters']['filter_imintotal']) . "'";
		}

		if( $config['filters']['filter_imaxtotal'] )
		{
			$where[]	= "i.i_total  <= '" . floatval($config['filters']['filter_imaxtotal']) . "'";
		}

		if( $config['filters']['filter_icreated'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_icreated'] );
			
			if( $timestamp )
			{
				$where[]	= "i.i_date > " . $timestamp;
			}
		}

		if( $config['filters']['filter_ipaid'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_ipaid'] );
			
			if( $timestamp )
			{
				$where[]	= "i.i_paid > " . $timestamp;
			}
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'date':
			default:
				$order	.= "i.i_date ";
			break;

			case 'title':
				$order	.= "i.i_title ";
			break;

			case 'paiddate':
				$order	.= "i.i_paid ";
			break;

			case 'total':
				$order	.= "i.i_total  ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 'i.*',
								'from'		=> array( 'nexus_invoices' => 'i' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'm.*, m.member_id as mid',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=t.t_member',
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=clients&amp;section=invoices&amp;do=view&amp;id=' . $r['i_id'], 'none' );
			$r['date']		= $r['i_date'];
			$r['content']	= sprintf( $this->lang->words['nexus_invoice_content'], floatval($r['i_total']), $this->lang->words[ 'istatus_'. $r['i_status'] ] );
			$r['title']		= $r['i_title'];

			$r				= IPSMember::buildDisplayData( $r );
			
			$records[]		= $r;
		}

		return $records;
	}

	/**
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getTransactions( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_tranmethod'] )
		{
			$where[]	= "t.t_method IN(" . $config['filters']['filter_tranmethod'] . ")";
		}

		if( $config['filters']['filter_transtatus'] )
		{
			$where[]	= "t.t_status IN('" . implode( "','", explode( ',', $config['filters']['filter_transtatus'] ) ) . "')";
		}

		if( $config['filters']['filter_tranmintotal'] )
		{
			$where[]	= "t.t_amount >= '" . floatval($config['filters']['filter_tranmintotal']) . "'";
		}

		if( $config['filters']['filter_tranmaxtotal'] )
		{
			$where[]	= "t.t_amount <= '" . floatval($config['filters']['filter_tranmaxtotal']) . "'";
		}

		if( $config['filters']['filter_tranmethod'] == 'myself' )
		{
			$where[]	= "t.t_member = " . $this->memberData['member_id'];
		}
		else if( $config['filters']['filter_tranmethod'] == 'friends' )
		{
			//-----------------------------------------
			// Get page builder for friends
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
			$pageBuilder	= new $classToLoad( $this->registry );
			$friends		= $pageBuilder->getFriends();
			
			if( count($friends) )
			{
				$where[]	= "t.t_member IN( " . implode( ',', $friends ) . ")";
			}
			else
			{
				return array();
			}
		}
		else if( $config['filters']['filter_tranmethod'] == '@request' )
		{
			$where[]	= "t.t_member = " . intval($this->request['author']);
		}
		else if( $config['filters']['filter_tranmethod'] != '' )
		{
			$member	= IPSMember::load( $config['filters']['filter_tranmethod'], 'basic', 'displayname' );
			
			if( $member['member_id'] )
			{
				$where[]	= "t.t_member = " . $member['member_id'];
			}
			else
			{
				return array();
			}
		}

		if( $config['filters']['filter_tranmindate'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_tranmindate'] );
			
			if( $timestamp )
			{
				$where[]	= "t.t_date > " . $timestamp;
			}
		}

		if( $config['filters']['filter_tranmaxdate'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_tranmaxdate'] );
			
			if( $timestamp )
			{
				$where[]	= "t.t_date > " . $timestamp;
			}
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'date':
			default:
				$order	.= "t.t_date ";
			break;

			case 'amount':
				$order	.= "t.t_amount ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 't.*',
								'from'		=> array( 'nexus_transactions' => 't' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'i.*',
														'from'		=> array( 'nexus_invoices' => 'i' ),
														'where'		=> 't.t_invoice=i.i_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'pm.*',
														'from'		=> array( 'nexus_paymethods' => 'pm' ),
														'where'		=> 't.t_method=pm.m_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'm.*, m.member_id as mid',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=t.t_member',
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=clients&amp;section=invoices&amp;do=view&amp;id=' . $r['i_id'], 'none' );
			$r['date']		= $r['t_date'];
			$r['content']	= sprintf( $this->lang->words['nexus_trans_content'], floatval($r['t_amount']) , $r['m_name'], $this->lang->words[ 'tstatus_'. $r['t_status'] ] );
			$r['title']		= $r['members_display_name'];

			$r				= IPSMember::buildDisplayData( $r );
			
			$records[]		= $r;
		}

		return $records;
	}

	/**
	 * Return the results for a package feed
	 *
	 * @param	array 		Block data
	 * @param	array 		Block custom config
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function getShipping( $block, $config, $previewMode )
	{
		$where	= array();
		$order	= '';

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_sostatus'] )
		{
			$where[]	= "s.o_status IN('" . implode( "','", explode( ',', $config['filters']['filter_sostatus'] ) ) . "')";
		}

		if( $config['filters']['filter_sodate'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_sodate'] );
			
			if( $timestamp )
			{
				$where[]	= "s.o_shipped_date > " . $timestamp;
			}
		}

		if( $config['filters']['filter_sosaved'] )
		{
			$timestamp	= @strtotime( $config['filters']['filter_sosaved'] );
			
			if( $timestamp )
			{
				$where[]	= "s.o_date > " . $timestamp;
			}
		}

		if( $config['filters']['filter_somethod'] )
		{
			$where[]	= "s.o_method IN(" . $config['filters']['filter_somethod'] . ")";
		}

		//-----------------------------------------
		// Set up ordering
		//-----------------------------------------

		switch( $config['sortby'] )
		{
			case 'date':
			default:
				$order	.= "s.o_date ";
			break;

			case 'shipdate':
				$order	.= "s.o_date ";
			break;

			case 'rand':
				$order	.= $this->DB->buildRandomOrder() . ' ';
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$records	= array();

		$this->DB->build( array(
								'select'	=> 's.*',
								'from'		=> array( 'nexus_ship_orders' => 's' ),
								'where'		=> implode( ' AND ', $where ),
								'order'		=> $order,
								'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
								'add_join'	=> array(
													array(
														'select'	=> 'i.*',
														'from'		=> array( 'nexus_invoices' => 'i' ),
														'where'		=> 's.o_invoice=i.i_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 'm.*, m.member_id as mid',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=i.i_member',
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
														'select'	=> 'sess.*',
														'from'		=> array( 'sessions' => 'sess' ),
														'where'		=> 'sess.member_id=m.member_id AND sess.running_time > ' . ( time() - ( 60 * 60 ) ),
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
			$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=nexus&amp;module=clients&amp;section=invoices&amp;do=view&amp;id=' . $r['i_id'], 'none' );
			$r['date']		= $r['o_date'];
			$r['content']	= $r['o_tracknumber'] ? sprintf( $this->lang->words['nexus_so_content'], $r['o_tracknumber'] , $r['o_service'] ) : $this->lang->words['nexus_so_notshipped'];
			$r['title']		= $r['i_title'];

			$r				= IPSMember::buildDisplayData( $r );
			
			$records[]		= $r;
		}

		return $records;
	}
}