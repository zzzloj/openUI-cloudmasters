<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS page caching and building
 * Last Updated: $Date: 2012-01-31 15:33:40 -0500 (Tue, 31 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10223 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class pageBuilder
{
	/**
	 * Page template content
	 *
	 * @access	public
	 * @var		string
	 */
	public $pageTemplate		= '';
	
	/**
	 * Temp navigation html
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_navigation		= '';
	
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
	protected $caches;
	protected $cache;
	/**#@-*/

	/**
	 * Cached templates...
	 *
	 * @access	public
	 * @var		array
	 */
	public $templates		= array();
	
	/**
	 * Temporary stored meta tags
	 *
	 * @access	protected
	 * @var		array
	 */
	public $meta			= array( 'keywords' => '', 'description' => '', 'canonical' => '', 'generic' => array() );
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Builds a page "cache"
	 *
	 * @access	public
	 * @param	array		[$page]		Page data (we use random variable name to prevent collisions from eval'd pages)
	 * @return	@e void
	 */
	public function recachePage( $aksdhkjashdkhsdjkhakjsdhkh=array() )
	{
		
		if( !count($aksdhkjashdkhsdjkhakjsdhkh) )
		{
			return '';
		}
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );

		//-----------------------------------------
		// Set meta tags
		//-----------------------------------------
		
		if( $aksdhkjashdkhsdjkhakjsdhkh['page_meta_keywords'] )
		{
			$this->setMetaKeywords( $aksdhkjashdkhsdjkhakjsdhkh['page_meta_keywords'] );
		}

		if( $aksdhkjashdkhsdjkhakjsdhkh['page_meta_description'] )
		{
			$this->setMetaDescription( $aksdhkjashdkhsdjkhakjsdhkh['page_meta_description'] );
		}
		
		// Uncommenting this because, if for example, the page is paginated, it's wrong
		//$this->setCanonical( $this->registry->ccsFunctions->returnPageUrl( $aksdhkjashdkhsdjkhakjsdhkh ) );
			
		//-----------------------------------------
		// Only need to parse if we actually have content
		//-----------------------------------------
				
		if( $aksdhkjashdkhsdjkhakjsdhkh['page_content'] )
		{
			switch( $aksdhkjashdkhsdjkhakjsdhkh['page_type'] )
			{
				case 'bbcode':
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					if( IN_ACP )
					{
						$_baseUrlBackup				= $this->settings['base_url'];
						$this->settings['base_url']	= $this->settings['board_url'] . '/index.php?';
					}
					
					$content	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $aksdhkjashdkhsdjkhakjsdhkh['page_content'] );
										
					if( IN_ACP )
					{
						$this->settings['base_url']	= $_baseUrlBackup;
					}
				break;
				
				case 'html':
					$content	= $aksdhkjashdkhsdjkhakjsdhkh['page_content'];
				break;
				
				case 'php':
					ob_start();
					eval( $aksdhkjashdkhsdjkhakjsdhkh['page_content'] );
					$content	= ob_get_contents();
					ob_end_clean();
				break;
			}
		}

		//-----------------------------------------
		// Is this inheriting from a template?
		//-----------------------------------------
		
		if( $aksdhkjashdkhsdjkhakjsdhkh['page_content_only'] AND $aksdhkjashdkhsdjkhakjsdhkh['page_template_used'] )
		{
			$this->loadSkinFile();
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $aksdhkjashdkhsdjkhakjsdhkh['page_template_used'] ) );
			
			if( !count($template) OR !$template['template_key'] )
			{
				return '';
			}
			
			$func_name			= 'template_' . $template['template_key'];
			$template_content	= $this->registry->output->getTemplate('ccs')->$func_name();
			$content			= str_replace( '{ccs special_tag="page_content"}', $content, $template_content );
		}
				
		//-----------------------------------------
		// Parse out articles section
		//-----------------------------------------
		
		if( strpos( $content, "{parse articles}" ) !== false )
		{
			if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
			{
				foreach( $this->caches['ccs_databases'] as $dbid => $_database )
				{
					if( $_database['database_is_articles'] )
					{
						$_articlesKey	= $_database['database_key'];
						break;
					}
				}
			}

			$this->registry->ccsFunctions->pageData[ $_articlesKey ]	= $aksdhkjashdkhsdjkhakjsdhkh;
			
			$content	= str_replace( "{parse articles}", $this->getArticles( $aksdhkjashdkhsdjkhakjsdhkh ), $content );
		}
		else if( strpos( $content, "{parse articles category=" ) !== false )
		{
			preg_match_all( "#\{parse articles category=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
				{
					foreach( $this->caches['ccs_databases'] as $dbid => $_database )
					{
						if( $_database['database_is_articles'] )
						{
							$_articlesKey	= $_database['database_key'];
							break;
						}
					}
				}

				$this->registry->ccsFunctions->pageData[ $_articlesKey ]	= $aksdhkjashdkhsdjkhakjsdhkh;
				
				foreach( $matches[1] as $index => $categoryId )
				{
					$content = str_replace( $matches[0][ $index ], $this->getArticles( $aksdhkjashdkhsdjkhakjsdhkh, $categoryId ), $content );
				}
			}
		}

		//-----------------------------------------
		// Parse out databases..
		//-----------------------------------------
		
		if( strpos( $content, "{parse database" ) !== false )
		{
			preg_match_all( "#\{parse database=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				foreach( $matches[1] as $index => $key )
				{
					$this->registry->ccsFunctions->pageData[ $key ]	= $aksdhkjashdkhsdjkhakjsdhkh;
					
					$content = str_replace( $matches[0][ $index ], $this->getDatabase( $key, $aksdhkjashdkhsdjkhakjsdhkh ), $content );
				}
			}
		}
		
		//-----------------------------------------
		// Meta tags and navigation
		//-----------------------------------------
		
		if( !$aksdhkjashdkhsdjkhakjsdhkh['page_ipb_wrapper'] )
		{
			$_metaTags	= '';

			if( $this->meta['keywords'] )
			{
				$_metaTags .= "<meta name='keywords' content='{$this->meta['keywords']}' />\n";
			}

			if( $this->meta['description'] )
			{
				$_metaTags .= "<meta name='description' content='{$this->meta['description']}' />\n";
			}
			
			if( $this->meta['canonical'] )
			{
				$_metaTags .= "<link rel='canonical' href='{$this->meta['canonical']}' />\n";
			}

			if( count($this->meta['generic']) )
			{
				foreach( $this->meta['generic'] as $_generic )
				{
					$_metaTags .= $_generic . "\n";
				}
			}
						
			$content			= str_replace( '{ccs special_tag="meta_tags"}', $_metaTags, $content );
		}
		else
		{
			if( $this->meta['description'] )
			{
				$this->registry->output->addMetaTag( 'description', $this->meta['description'], false );
			}
			
			if( $this->meta['keywords'] )
			{
				$this->registry->output->addMetaTag( 'keywords', $this->meta['keywords'], false );
			}
			
			if( $this->meta['canonical'] )
			{
				$this->registry->output->addToDocumentHead( 'raw' , '<link rel="canonical" href="' . $this->meta['canonical'] . '" />' );
			}

			if( count($this->meta['generic']) )
			{
				foreach( $this->meta['generic'] as $_generic )
				{
					$this->registry->output->addToDocumentHead( 'raw' , $_generic );
				}
			}
		}
		
		//$content			= str_replace( '{ccs special_tag="page_title"}', $aksdhkjashdkhsdjkhakjsdhkh['page_name'], $content );
		$content			= str_replace( '{ccs special_tag="page_title"}', $this->registry->output->getTitle() ? $this->registry->output->getTitle() : ( $aksdhkjashdkhsdjkhakjsdhkh['page_title'] ? $aksdhkjashdkhsdjkhakjsdhkh['page_title'] : $aksdhkjashdkhsdjkhakjsdhkh['page_name'] ), $content );
		$content			= str_replace( '{ccs special_tag="navigation"}', $this->_navigation, $content );
		
		//-----------------------------------------
		// Parse out page blocks..
		//-----------------------------------------
		
		if( strpos( $content, "{parse block=" ) !== false )
		{
			preg_match_all( "#\{parse block=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				foreach( $matches[1] as $index => $key )
				{
					$content = str_replace( $matches[0][ $index ], $this->getBlock( $key ), $content );
				}
			}
		}
		
		//-----------------------------------------
		// Parse ipcmedia tags
		//-----------------------------------------

		if( strpos( $content, '{parse ipcmedia="') !== false && is_file( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
		{			
			require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/
			
			if( defined('CCS_MEDIA') && CCS_MEDIA_URL && CCS_MEDIA && is_dir(CCS_MEDIA) )
			{
				$content = preg_replace( "#\{parse ipcmedia=\"(.+?)\"\}#ei", "CCS_MEDIA_URL . '/' . ltrim('\\1', '/')", $content );
			}
		}

		//-----------------------------------------
		// Return data
		//-----------------------------------------
		
		return $content;
	}
	
	/**
	 * Recache "skin" file
	 *
	 * @access	public
	 * @param	object		Template engine (if already loaded)
	 * @return	string		Skin file class
	 */
	public function recacheTemplateCache( $engine=null )
	{
		//-----------------------------------------
		// Make sure we got the engine
		//-----------------------------------------

		if( !$engine )
		{
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
			$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );
		}
		
		//-----------------------------------------
		// Recache the blocks
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$cache	= array(
							'cache_type'	=> 'block',
							'cache_type_id'	=> $r['tpb_id'],
							);
	
			$cache['cache_content']	= $engine->convertHtmlToPhp( $r['tpb_name'], $r['tpb_params'], $r['tpb_content'], '', false, true );

			$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type', 'cache_type_id' ) );
		}
		
		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$templateBits	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_cache', 'where' => "cache_type NOT IN('full','page')" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templateBits[]	= $r['cache_content'];
		}
		
		//-----------------------------------------
		// Now create the pseudo-code
		//-----------------------------------------
		
		$_fakeClass	= "<" . "?php\n\n";
		$_fakeClass	.= "class skin_ccs_1 {\n\n";

		$_fakeClass	.= "\n\n}";

		$fullFile	= $engine->convertCacheToEval( $_fakeClass, "skin_ccs", 1 );
		$fullFile	= str_replace( "\n\n}", implode( "\n\n", $templateBits ) . "\n\n}", $fullFile );
		
		$cache	= array(
						'cache_type'	=> 'full',
						'cache_type_id'	=> 0,
						'cache_content'	=> $fullFile,
						);
		
		$this->DB->replace( 'ccs_template_cache', $cache, array( 'cache_type' ), true );

		return $fullFile;
	}
	
	/**
	 * Load the skin file
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function loadSkinFile()
	{
		if( !$this->registry->output->compiled_templates['skin_ccs'] )
		{
			$skinFile	= $this->DB->buildAndFetch( array( 'select' => 'cache_content', 'from' => 'ccs_template_cache', 'where' => "cache_type='full'" ) );
			
			if( !$skinFile['cache_content'] )
			{
				$skinFile['cache_content']	= $this->recacheTemplateCache();
			}
			
			//-----------------------------------------
			// And now we have a skin file..
			//-----------------------------------------
						
			eval( $skinFile['cache_content'] );
			
			$this->registry->output->compiled_templates['skin_ccs']	= new skin_ccs( $this->registry );
		}
	}

	/**
	 * Get the user's friends
	 *
	 * @access	public
	 * @return	array 		Friend ids
	 */
	public function getFriends()
	{
		$friends	= array();
		
		if( $this->memberData['member_id'] )
		{
			$this->DB->build( array( 'select' => 'friends_friend_id', 'from' => 'profile_friends', 'where' => 'friends_approved=1 AND friends_member_id=' . $this->memberData['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$friends[]	= $r['friends_friend_id'];
			}
		}
		
		return $friends;
	}
	
	/**
	 * Get and return block HTML
	 *
	 * @access	public
	 * @param	string		Block key
	 * @return	string		Block HTML
	 */
	public function getBlock( $blockKey )
	{
		static $parsedBlocks	= array();
		
		if( !$blockKey )
		{
			return '';
		}
		
		if( isset( $parsedBlocks[ $blockKey ] ) )
		{
			return $parsedBlocks[ $blockKey ];
		}
		
		//-----------------------------------------
		// If we haven't already fetched blocks, do so now
		//-----------------------------------------
		
		if( !$this->caches['ccs_blocks'] )
		{
			$blocks	= array();
			
			$this->DB->build( array( 
									'select'	=> 'b.*',
									'from'		=> array( 'ccs_blocks' => 'b' ),
									'where'		=> 'b.block_active=1',
									'add_join'	=> array(
														array(
															'select'	=> 't.tpb_name',
															'from'		=> array( 'ccs_template_blocks' => 't' ),
															'where'		=> 't.tpb_id=b.block_template',
															'type'		=> 'left',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$blocks[ $r['block_key'] ] = $r;
			}
			
			$this->cache->updateCacheWithoutSaving( 'ccs_blocks', $blocks );
		}

		//-----------------------------------------
		// Get HTML
		//-----------------------------------------
		
		$_content	= '';

		if( $this->caches['ccs_blocks'][ $blockKey ] )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php', "adminBlockHelper", "ccs" );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $this->caches['ccs_blocks'][ $blockKey ]['block_type'] . '/admin.php', "adminBlockHelper_" . $this->caches['ccs_blocks'][ $blockKey ]['block_type'], "ccs" );
			$_block			= new $classToLoad( $this->registry );
			$_content		= $_block->getBlockContent( $this->caches['ccs_blocks'][ $blockKey ] );
			
			$parsedBlocks[ $blockKey ]	= $_content;
		}

		
		return $_content;
	}
	
	/**
	 * Get and return database HTML
	 *
	 * @access	public
	 * @param	string		Database key
	 * @param	array 		Page data
	 * @return	string		Database HTML
	 */
	public function getDatabase( $key, $page )
	{
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', "databaseBuilder", "ccs" );
		$databases	= new $classToLoad( $this->registry, $this );
		
		return $databases->getDatabase( $key, $page );
	}
	
	/**
	 * Get the articles section, which sits on top of databases functionality.
	 *
	 * @access	public
	 * @param	array 		Page data
	 * @param	int			Category ID
	 * @return	string		Database HTML
	 */
	public function getArticles( $page, $categoryId=0 )
	{
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', "databaseBuilder", "ccs" );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/articles.php', "articlesBuilder", "ccs" );
		$databases	= new $classToLoad( $this->registry, $this );
		
		if( $categoryId )
		{
			$databases->limitCategory( $categoryId );
		}
		
		return $databases->getArticles( $page );
	}
	
	/**
	 * Set navigation entries
	 *
	 * @access	public
	 * @param	array 		Navigation data
	 * @param	string		Database/articles key
	 * @return	@e void
	 */
	public function addNavigation( $crumbies, $databaseKey='' )
	{
		if( $this->registry->ccsFunctions->pageData[ $databaseKey ]['page_ipb_wrapper'] )
		{
			foreach( $crumbies as $crumb )
			{
				$this->registry->output->addNavigation( $crumb[0], $crumb[1], '', '', 'none' );
			}
		}
		else
		{
			$this->_navigation	= $this->registry->output->getTemplate('ccs_global')->databaseNavigation( $crumbies );
		}

		define( 'CCS_NAV_DONE', true );
	}
	
	/**
	 * Set meta keywords tag
	 *
	 * @access	public
	 * @param	string		Keywords
	 * @return	@e void
	 */
	public function setMetaKeywords( $keywords='' )
	{
		if( $keywords )
		{
			$this->meta['keywords']	= $keywords;
		}
	}
	
	/**
	 * Set meta description tag
	 *
	 * @access	public
	 * @param	string		Keywords
	 * @return	@e void
	 */
	public function setMetaDescription( $description='' )
	{
		if( $description )
		{
			$this->meta['description']	= $description;
		}
	}
	
	/**
	 * Set the page canonical tag
	 *
	 * @access	public
	 * @param	string		Keywords
	 * @return	@e void
	 */
	public function setCanonical( $url='' )
	{
		if( $url )
		{
			$this->meta['canonical']	= $url;
		}
	}
	
	/**
	 * Set other generic 'head' item
	 *
	 * @access	public
	 * @param	string		tag
	 * @return	@e void
	 */
	public function setHeadLink( $tag='' )
	{
		if( $tag )
		{
			$this->meta['generic'][]	= $tag;
		}
	}
}