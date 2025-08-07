<?php
/**
 * RSS feed blocks
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10309 $ 
 * @since		1st March 2009
 */

class feed_rss implements feedBlockInterface
{
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
	 * RSS kernel lib
	 *
	 * @access	public
	 * @var		object
	 */
	public $class_rss;
	
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
	 */
	public function getTags( $info='' )
	{
		return array(
					$this->lang->words['block_feed__generic']	=> array( 
																		array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																		),	
						
					$this->lang->words['block_feed_rss']	=> array(
																		array( '&#36;records', $this->lang->words['block_feed__rssentries'], 
																			array(
																				array( "&#36;r['url']", $this->lang->words['block_feed__rssurl'] ),
																				array( "&#36;r['date']", $this->lang->words['block_feed__rssdate'] ),
																				array( "&#36;r['content']", $this->lang->words['block_feed__rsscontent'] ),
																				array( "&#36;r['title']", $this->lang->words['block_feed__rsstitle'] ),
																				array( "{$this->lang->words['rssfeed_note']}", $this->lang->words['block_feed__rssothernote'] ),
																				)
																			),
																		),
					);
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
					'key'			=> 'rss',
					'app'			=> '',
					'name'			=> $this->lang->words['feed_name__rss'],
					'description'	=> $this->lang->words['feed_description__rss'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic',
					'inactiveSteps'	=> array(),
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
							array( 'rss', $this->lang->words['ct_rss_feed'] ),
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
		if( !in_array( $data['content_type'], array( 'rss' ) ) )
		{
			$data['content_type']	= 'rss';
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
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__feed'],
							'description'	=> $this->lang->words['feed_rss__feed_desc'],
							'field'			=> $this->registry->output->formInput( 'rss_feed_url', $session['config_data']['filters']['rss_feed'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__limit'],
							'description'	=> $this->lang->words['feed_rss__limit_desc'],
							'field'			=> $this->registry->output->formInput( 'rss_limit', $session['config_data']['filters']['rss_limit'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_rss__html'],
							'description'	=> $this->lang->words['feed_rss__html_desc'],
							'field'			=> $this->registry->output->formYesNo( 'rss_html', $session['config_data']['filters']['rss_html'] ),
							);
							
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
		// Init RSS kernel library
		//-----------------------------------------
		
		if ( ! is_object( $this->class_rss ) )
		{
			$classToLoad		= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
			$this->class_rss	=  new $classToLoad();
			$this->class_rss->doc_type 		= IPS_DOC_CHAR_SET;
		}
		
		//-----------------------------------------
		// Get feed
		//-----------------------------------------

		$this->class_rss->parseFeedFromUrl( $this->request['rss_feed_url'] );

		//-----------------------------------------
		// Error checking
		//-----------------------------------------

		if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
		{
			$this->registry->output->showError( $this->lang->words['rssfeed_not_validated'], '10CCS91.rss' );
		}
		
		if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
		{
			$this->registry->output->showError( $this->lang->words['rssfeed_not_validated'], '10CCS91.rss' );
		}
		
		return array( true, array(
								'rss_feed'		=> $this->request['rss_feed_url'],
								'rss_limit'		=> intval($this->request['rss_limit']),
								'rss_html'		=> intval($this->request['rss_html']),
					) 			);
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
		return array();
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
		return array( true, array() );
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
		
		$config	= unserialize( $block['block_config'] );

		//-----------------------------------------
		// Init RSS kernel library
		//-----------------------------------------
		
		if ( ! is_object( $this->class_rss ) )
		{
			$classToLoad		= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
			$this->class_rss	=  new $classToLoad();
			$this->class_rss->doc_type 		= IPS_DOC_CHAR_SET;
		}

		$this->class_rss->errors		= array();
		$this->class_rss->rss_items		= array();
		$this->class_rss->auth_req		= '';
		$this->class_rss->auth_user		= '';
		$this->class_rss->auth_pass		= '';
		$this->class_rss->rss_count		= 0;
		$this->class_rss->rss_max_show	= $config['filters']['rss_limit'];
		
		//-----------------------------------------
		// Get feed
		//-----------------------------------------

		$this->class_rss->parseFeedFromUrl( $config['filters']['rss_feed'] );

		//-----------------------------------------
		// Error checking
		//-----------------------------------------
				
		if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
		{
			return '';
		}
		
		if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
		{
			return '';
		}
		
		if ( $config['hide_empty'] and ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) ) )
		{
			return '';
		}
		
		//-----------------------------------------
		// Loop over items and put into array
		//-----------------------------------------
		
		$content	= array();
		
		foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
		{
			if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
			{
				foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
				{
					//-----------------------------------------
					// Check basic data
					//-----------------------------------------
					
					$item_data['content']	= $item_data['content']   ? $item_data['content']  : $item_data['description'];
					$item_data['url']		= $item_data['link'];
					$item_data['date']		= intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : time();

					//-----------------------------------------
					// Convert charset
					//-----------------------------------------
					
					if ( $this->class_rss->doc_type != $this->class_rss->orig_doc_type )
					{
						$item_data['title']   = IPSText::convertCharsets( $item_data['title']  , "UTF-8", IPS_DOC_CHAR_SET );
						$item_data['content'] = IPSText::convertCharsets( $item_data['content'], "UTF-8", IPS_DOC_CHAR_SET );
					}

					//-----------------------------------------
					// Dates
					//-----------------------------------------
					
					if ( $item_data['date'] < 1 )
					{
						$item_data['date'] = time();
					}
					else if ( $item_data['date'] > time() )
					{
						$item_data['date'] = time();
					}

					//-----------------------------------------
					// Got stuff?
					//-----------------------------------------
					
					if ( ! $item_data['title'] OR ! $item_data['content'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Strip html if needed
					//-----------------------------------------
					
					if( !$config['filters']['rss_html'] )
					{
						$item_data['title']		= strip_tags($item_data['title']);
						$item_data['content']	= strip_tags($item_data['content']);
					}

					$content[]	= $item_data;
				}
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