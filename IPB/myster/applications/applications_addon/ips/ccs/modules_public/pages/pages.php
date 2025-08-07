<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS pages: displays the pages created in the ACP
 * Last Updated: $Date: 2012-01-25 17:21:54 -0500 (Wed, 25 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10192 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class public_ccs_pages_pages extends ipsCommand
{
	/**
	 * Temp output
	 *
	 * @var		string
	 */
	protected $output		= '';
	
	/**
	 * Page builder
	 *
	 * @var		object
	 */
	protected $pageBuilder;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Check online/offline first
		//-----------------------------------------

		if( !$this->settings['ccs_online'] )
		{
			$show		= false;
			
			if( $this->settings['ccs_offline_groups'] )
			{
				$groups		= explode( ',', $this->settings['ccs_offline_groups'] );
				$myGroups	= array( $this->memberData['member_group_id'] );
				$secondary	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
				$secondary	= explode( ',', $secondary );
				
				if( count($secondary) )
				{
					$myGroups	= array_merge( $myGroups, $secondary );
				}
				
				foreach( $myGroups as $groupId )
				{
					if( in_array( $groupId, $groups ) )
					{
						$show	= true;
						break;
					}
				}
			}
			
			if( !$show )
			{
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= 0;
				IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
				IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
				$this->registry->output->showError( IPSText::getTextClass('bbcode')->preDisplayParse( $this->settings['ccs_offline_message'] ), '10CCS13' );
			}
		}
		
		//-----------------------------------------
		// Load skin file
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$this->pageBuilder	= new $classToLoad( $this->registry );
		$this->pageBuilder->loadSkinFile();

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'redirect':
				$this->_redirector();
			break;

			case 'blockPreview':
				$this->showBlockPreview();
			break;
			
			default:
				$this->_view();
			break;
		}
	}

	/**
	 * Show a preview of a block
	 *
	 * @param	bool		Whether to return content instead of printing it or not
	 * @return	@e void
	 */
	public function showBlockPreview( $return=false )
	{
		//-----------------------------------------
		// Check data
		//-----------------------------------------
		
		/*if( !$this->memberData['g_access_cp'] )
		{
			exit;
		}*/

		$id			= intval($this->request['id']);
		
		if( !$id )
		{
			exit;
		}
		
		//-----------------------------------------
		// Get block
		//-----------------------------------------
		
		$block		= $this->DB->buildAndFetch( array(
														'select'	=> 'b.*',
														'from'		=> array( 'ccs_blocks' => 'b' ),
														'where'		=> 'b.block_id=' . $id,
														'add_join'	=> array(
																			array(
																				'select'	=> 't.*',
																				'from'		=> array( 'ccs_template_blocks' => 't' ),
																				'where'		=> 'b.block_template=t.tpb_id',
																				'type'		=> 'left',
																				)
																			)
												)		);
		
		if( !$block['block_id'] )
		{
			exit;
		}
		
		$_key		= md5( $block['block_id'] . $block['block_key'] );
		
		if( $_key != $this->request['k'] )
		{
			exit;
		}

		//-----------------------------------------
		// Get block content
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $block['block_type'] . '/admin.php', "adminBlockHelper_" . $block['block_type'], 'ccs' );

		$_block		= new $classToLoad( $this->registry );
		$content	= $_block->getBlockContent( $block );

		//-----------------------------------------
		// Return or output
		//-----------------------------------------
		
		if( $return )
		{
			return $content;
		}
		else if( $this->request['widget'] )
		{
			$content	.= $this->registry->output->getTemplate('ccs_global')->widgetJavascript();
		}

		print $this->registry->ccsFunctions->injectBlockFramework( $this->registry->output->popUpWindow( $content, true ) );
		exit;
	}
		
	/**
	 * Redirector: sends person to the page based on URL config
	 *
	 * @return	@e void
	 */
	protected function _redirector()
	{
		//-----------------------------------------
		// If we have a page but it's a name and not an id, correct that
		//-----------------------------------------
		
		if( $this->request['page'] AND !is_int($this->request['page']) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$this->request['page']}' AND page_folder='{$this->request['folder']}'" ) );
			
			if( $page['page_id'] )
			{
				$id	= $page['page_id'];
			}
		}
		
		$id	= $id ? $id : intval($this->request['page']);
		
		//-----------------------------------------
		// Not a page?
		//-----------------------------------------
		
		if( !$id )
		{
			$database	= intval($this->request['database']);
			$category	= intval($this->request['category']);
			$record		= intval($this->request['record']);
			$comment	= $this->request['comment'];
			
			//-----------------------------------------
			// Or a database?
			//-----------------------------------------
			
			if( $this->request['to'] == 'articles' )
			{
				foreach( $this->caches['ccs_databases'] as $_id => $_db )
				{
					if( $_db['database_is_articles'] )
					{
						$database	= $_id;
						break;
					}
				}
			}
			
			if( !$database AND !$record )
			{
				$this->registry->output->showError( $this->lang->words['nopage_id_red'], '10CCS14' );
			}

			if( $record )
			{
				$_database	= $this->caches['ccs_databases'][ $database ];
				$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $_database['database_database'], 'where' => 'primary_id_field=' . $record ) );
			}

			$url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database, $category, $record, $comment, $this->request['st'] );
			
			if( is_array($url) OR $url == '#' )
			{
				$this->registry->output->showError( $url == '#' ? $this->lang->words['badurl_redirect'] : $url[0], '10CCS15', FALSE, '', 404 );
			}
			
			$this->_redirectToUrl( $url );
		}
		
		//-----------------------------------------
		// Normal pages
		//-----------------------------------------
		
		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
		
		if( !$page['page_id'] )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nopage_id_red'], '10CCS16' );
			}
		}
		
		if( !$this->registry->ccsFunctions->canView( $page ) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['nopage_id_red'], '10CCS17' );
			}
		}

		$this->_redirectToUrl( $this->registry->ccsFunctions->returnPageUrl( $page ) );
	}
	
	/**
	 * View a page
	 *
	 * @return	@e void
	 */
	protected function _view()
	{
		$folderName	= IPSText::parseCleanValue( $this->registry->ccsFunctions->getFolder() );
		$pageName	= IPSText::parseCleanValue( $this->registry->ccsFunctions->getPageName() );
		
		//-----------------------------------------
		// Sort out query where clause
		//-----------------------------------------
		
		$where	= array();
		$_where	= '';

		if( $pageName )
		{
			$where[]	= "page_seo_name='{$pageName}'";
			
			//-----------------------------------------
			// Even if not in a folder, need to make sure
			// page_folder='' in query
			//-----------------------------------------
			
			//if( $this->request['folder'] )
			//{
				$where[]	= "page_folder='{$folderName}'";
			//}
		}
		else if( $this->request['id'] )
		{
			$id			= intval($this->request['id']);
			$where[]	= "page_id=" . $id;
		}
		else if( $folderName )
		{
			$where[]	= "page_seo_name='{$this->settings['ccs_default_page']}'";
			$where[]	= "page_folder='{$folderName}'";
		}
		else
		{
			$where[]	= "page_folder=''";
			$where[]	= "page_seo_name='{$this->settings['ccs_default_page']}'";
		}

		if( !count($where) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( 'no_page_specified', '10CCS1' );
			}
			else
			{
				$folderName	= '';
				$pageName	= $this->settings['ccs_default_errorpage'];
			}
		}
		else
		{
			$_where	= implode( ' AND ', $where );
		}

		//-----------------------------------------
		// Get page
		//-----------------------------------------

		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_where ) );
		
		if( !$page['page_id'] )
		{
			//-----------------------------------------
			// If we request site.com/pages/delete with no
			// trailing slash "delete" will be file instead
			// of folder - this could be valid, but let's
			// see if there is a delete folder + index file
			//-----------------------------------------
			
			if( !$this->request['id'] )
			{
				$where		= array();
				$where[]	= "page_seo_name='{$this->settings['ccs_default_page']}'";
				$where[]	= "page_folder='{$folderName}/{$pageName}'";

				if( count($where) )
				{
					$_where	= implode( ' AND ', $where );

					$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_where ) );

					//-----------------------------------------
					// We ALSO could have /pagename/ WITH a
					// trailing slash - try to find/fix this too
					// @see http://community.invisionpower.com/tracker/issue-20417-url-with/
					//-----------------------------------------

					if( !$page['page_id'] )
					{
						$where		= array();
						$where[]	= "page_seo_name='" . substr( $folderName, 1 ) . "'";
						$where[]	= "page_folder=''";
						$_where		= implode( ' AND ', $where );
						
						$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_where ) );

						if( $page['page_id'] )
						{
							$folderName	= "/";
							$pageName	= substr( $folderName, 1 );
						}
					}
					else
					{
						$folderName	= "{$folderName}/{$pageName}";
						$pageName	= $this->settings['ccs_default_page'];
					}
				}
			}
			
			//-----------------------------------------
			// Still no page...try memory
			//-----------------------------------------

			if( !$page['page_id'] )
			{
				$url	= $this->registry->ccsFunctions->returnPageUrl( array( 'page_folder' => $folderName, 'page_seo_name' => $pageName ) );
				$memory	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_slug_memory', 'where' => "memory_type='page' AND memory_url='" . $this->DB->addSlashes( $url ) . "'" ) );

				if( $memory['memory_id'] )
				{
					$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_id=" . intval($memory['memory_type_id']) ) );
					
					$this->_redirectToUrl( $this->registry->ccsFunctions->returnPageUrl( $page ) );
				}
			}

			//-----------------------------------------
			// Still nothing.. gateway file, maybe?
			// Ticket 850245, aka Blame Ryan
			//-----------------------------------------
			
			if ( ! $page['page_id'] )
			{
				if ( $pageName == $this->settings['ccs_root_filename'] )
				{
					$page		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_page']}'" ) );
					$pageName	= $this->settings['ccs_default_page'];
				}
			}

			//-----------------------------------------
			// Still no page...try error page
			//-----------------------------------------
			
			if( !$page['page_id'] )
			{
				$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
				
				if( !$page['page_id'] )
				{
					$this->registry->output->showError( 'no_page_specified', '10CCS2', FALSE, '', 404 );
				}
				else
				{
					$folderName	= '';
					$pageName	= $this->settings['ccs_default_errorpage'];
				}
			}
		}
		
		if( !$this->request['id'] AND $pageName != $this->settings['ccs_default_errorpage'] )
		{
			//-----------------------------------------
			// Verify we visited the correct URL
			//-----------------------------------------
			
			$url	= $this->registry->ccsFunctions->returnPageUrl( $page );
			$visit	= urldecode( $this->registry->ccsFunctions->returnVisitedUrlWithoutDatabase() );

			if( $this->registry->ccsFunctions->getDatabaseFurlString() AND !$this->registry->ccsFunctions->getPageName() )
			{
				$visit	= rtrim( $visit, '/' ) . '/';
			}

			if( $url != $visit )
			{
				if( $this->registry->ccsFunctions->getDatabaseFurlString() )
				{
					$url	= rtrim( $url, '/' ) . '/' . DATABASE_FURL_MARKER . '/' . $this->registry->ccsFunctions->getDatabaseFurlString();
				}
				//print $url. '<br>'.$visit.'<br>'.$this->registry->ccsFunctions->getDatabaseFurlString();exit;
				$this->_redirectToUrl( $url );
			}
		}
		
		//-----------------------------------------
		// Check page viewing permissions
		//-----------------------------------------
		
		if( !$this->registry->ccsFunctions->canView( $page ) )
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_folder='' AND page_seo_name='{$this->settings['ccs_default_errorpage']}'" ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( 'no_page_permission', '10CCS3', FALSE, '', 403 );
			}
			else
			{
				$folderName	= '';
				$pageName	= $this->settings['ccs_default_errorpage'];
			}
		}
		
		//-----------------------------------------
		// Base navigation entry
		//-----------------------------------------
		
		if( $this->settings['ccs_pages_navbar'] )
		{
			$this->registry->getClass('output')->addNavigation( IPSLib::getAppTitle('ccs'), "app=ccs" );
		}

		//-----------------------------------------
		// Get page content and parse blocks
		//-----------------------------------------
		
		$this->output	= $this->_getPageContent( $page );

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$title	= $page['page_title'] ? $page['page_title'] : $page['page_name'];
		
		if( !$this->settings['ccs_online'] )
		{
			$title .= ' [' . $this->lang->words['ccs_offline_title'] . ']';
		}

		if( !$this->registry->output->getTitle() )
		{
			$this->registry->output->setTitle( $title );
		}
		
		if( $this->settings['ccs_default_errorpage'] AND $pageName == $this->settings['ccs_default_errorpage'] )
		{
			$this->registry->output->setHeaderCode( 404, "Not Found" );
		}
		
		//-----------------------------------------
		// Pass to CP output hander or output
		//-----------------------------------------
				
		if( !$page['page_ipb_wrapper'] )
		{
			//-----------------------------------------
			// Take care of hooks
			//-----------------------------------------

			$this->output	= $this->registry->output->templateHooks( $this->output );
			
			$this->output = preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $this->output );
			
			//-----------------------------------------
			// Query debugging
			//-----------------------------------------
			
			if( $this->DB->obj['debug'] )
			{
				flush();
				print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
				print "<h1 align='center'>SQL Total Time: {$this->DB->sql_time} for {$this->DB->query_count} queries</h1><br />".$this->DB->debug_html;
				print "<br /><div align='center'><strong>Total SQL Time: {$this->DB->sql_time}</div></body></html>";
				
				print "<br />SQL Fetch Total Memory: " . IPSLib::sizeFormat( $this->DB->_tmpT, TRUE );
				exit();
			}
        
			$this->registry->output->outputFormatClass->printHeader();
			
			$_output	= $this->registry->output->outputFormatClass->parseIPSTags( preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $this->output ) );
			
			//-----------------------------------------
			// Cross domain AJAX?
			//-----------------------------------------
			
			$_output	= $this->_checkDomain( $_output );

			//-----------------------------------------
			// Inject block framework
			//-----------------------------------------

			$_output = $this->registry->ccsFunctions->injectBlockFramework( $_output );
			
			print $_output;
			
			$this->registry->output->outputFormatClass->finishUp();
			
			exit;
		}
		else
		{
			$this->settings['query_string_formatted']	= "app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page={$page['page_id']}";
			
			if( !defined('CCS_NAV_DONE') OR !CCS_NAV_DONE )
			{
				$this->registry->getClass('output')->addNavigation( $page['page_name'], '' );
			}
						
			$this->registry->output->addContent( $this->output );

			$_output	= $this->registry->output->sendOutput( true );

			//-----------------------------------------
			// Cross domain AJAX?
			//-----------------------------------------
			
			$_output	= $this->_checkDomain( $_output );

			//-----------------------------------------
			// Inject block framework
			//-----------------------------------------

			$_output = $this->registry->ccsFunctions->injectBlockFramework( $_output );
			
			print $_output;
			
			exit;
		}
	}

	/**
	 * Get the page content (verifies cache, etc.)
	 *
	 * @param	array 		Page data
	 * @return	string		Page output
	 */
	public function _checkDomain( $output )
	{
		$_domain	= parse_url( $this->settings['base_url'], PHP_URL_HOST );
		$_reqDomain	= $_SERVER['HTTP_HOST'];
		
		if( $_domain != $_reqDomain )
		{
			$replacement	= '';
			$_possibilities	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );
			
			foreach( $_possibilities as $_try )
			{
				$_thisDomain = parse_url( $_try, PHP_URL_HOST );
				
				if( $_thisDomain == $_reqDomain )
				{
					$replacement = rtrim( $_try, '/' ) . '/' . $this->settings['ccs_root_filename'] . '?s=' . $this->member->session_id . '&';
					break;
				}
			}
			
			if( $replacement )
			{
				$output	= preg_replace( "/ipb\.vars\['base_url'\]\s*?=\s*?['\"].+?[\"'];/ims", "ipb.vars['base_url']	= '{$replacement}';", $output );
			}
		}
		
		return $output;
	}

	/**
	 * Get the page content (verifies cache, etc.)
	 *
	 * @param	array 		Page data
	 * @return	string		Page output
	 */
	protected function _getPageContent( $page )
	{
		//-----------------------------------------
		// Is this a different content type?
		//-----------------------------------------
		
		if( $page['page_content_type'] != 'page' )
		{
			switch( $page['page_content_type'] )
			{
				case 'css':
					@header( "Content-type: text/css" );
				break;
				
				case 'js':
					@header( "Content-type: application/x-javascript" );
				break;
			}

			$content	= $this->registry->output->outputFormatClass->parseIPSTags( $page['page_cache'] ? $page['page_cache'] : $page['page_content'] );
			
			preg_match_all( "#\{parse block=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				foreach( $matches[1] as $index => $key )
				{
					$content = str_replace( $matches[0][ $index ], $this->pageBuilder->getBlock( $key ), $content );
				}
			}

			preg_match_all( "#\{parse ipcmedia=\"(.+?)\"\}#", $content, $matches );
			
			if( count($matches) )
			{
				if( is_file( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
				{
					require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/

					if( defined('CCS_MEDIA') AND CCS_MEDIA_URL AND CCS_MEDIA AND is_dir(CCS_MEDIA) )
					{
						foreach( $matches[1] as $index => $key )
						{
							$content = str_replace( $matches[0][ $index ], CCS_MEDIA_URL . '/' . ltrim( $key, '/' ), $content );
						}
					}
				}
			}
			
			print $content;
						
			$this->registry->output->outputFormatClass->finishUp();
			exit;
		}
		
		//-----------------------------------------
		// Meta tags
		//-----------------------------------------
		
		// This is handled in recachePage 
		/*$_metaTags = '';

		if( $page['page_meta_keywords'] )
		{
			if ( !$page['page_ipb_wrapper'] )
			{
				$_metaTags .= "<meta name='keywords' content='{$page['page_meta_keywords']}' />\n";
			}
			else
			{
				$this->registry->output->addMetaTag( 'keywords', $page['page_meta_keywords'], false );
			}
		}

		if( $page['page_meta_description'] )
		{
			if ( !$page['page_ipb_wrapper'] )
			{
				$_metaTags .= "<meta name='description' content='{$page['page_meta_description']}' />\n";
			}
			else
			{
				$this->registry->output->addMetaTag( 'description', $page['page_meta_description'], false );
			}
		}*/
		
		// Uncommenting this because, if for example, the page is paginated, it's wrong
		/*$canonical = $this->registry->ccsFunctions->returnPageUrl( $page );
		if ( !$page['page_ipb_wrapper'] )
		{
			$_metaTags .= "<link rel='canonical' href='{$canonical}' />\n";
			
			$page['page_cache'] = str_replace( '{ccs special_tag="meta_tags"}', $_metaTags, $page['page_cache'] );
		}
		else
		{
			$this->registry->output->addToDocumentHead( 'raw' , '<link rel="canonical" href="' . $canonical . '" />' );
		}*/	
		
		if ( !$page['page_ipb_wrapper'] )
		{
			$page['page_cache'] = str_replace( '{ccs special_tag="meta_tags"}', $_metaTags, $page['page_cache'] );
		}

		//-----------------------------------------
		// Ignore the cache if it's a database
		//-----------------------------------------

		if( strpos( $page['page_content'], '{parse database=' ) !== false OR strpos( $page['page_content'], '{parse articles' ) !== false )
		{
			$page['page_cache']			= null;
			$page['page_cache_ttl']		= 0;
			$page['page_cache_last']	= 0;
		}
						
		//-----------------------------------------
		// Indefinite caching
		//-----------------------------------------

		if( $page['page_cache_ttl'] == '*' AND $page['page_cache'] )
		{
			return $page['page_cache'];
		}
				
		//-----------------------------------------
		// Caching enabled (verify not expired)
		//-----------------------------------------
		
		if( $page['page_cache_ttl'] > 0 AND $page['page_cache'] )
		{
			if( ($page['page_cache_last'] + ( 60 * $page['page_cache_ttl'] )) > time() )
			{
				//-----------------------------------------
				// Set meta tags
				// We don't need to worry about non-IPB wrapper pages
				// since the meta tags would already be cached with the page HTML
				//-----------------------------------------
				
				if( $page['page_meta_keywords'] )
				{
					$this->registry->output->addMetaTag( 'keywords', $page['page_meta_keywords'], false );
				}

				if( $page['page_meta_description'] )
				{
					$this->registry->output->addMetaTag( 'description', $page['page_meta_description'], false );
				}

				return $page['page_cache'];
			}
		}

		//-----------------------------------------
		// Page expired - get page builder
		//-----------------------------------------
		
		$content		= $this->pageBuilder->recachePage( $page );
				
		//-----------------------------------------
		// If caching enabled, update cache
		//-----------------------------------------
		
		if( $page['page_cache_ttl'] )
		{
			$this->DB->update( 'ccs_pages', array( 'page_cache' => $content, 'page_cache_last' => time() ), 'page_id=' . $page['page_id'] );
		}
		
		//-----------------------------------------
		// Return content
		//-----------------------------------------
		
		return $content;
	}
	
	/**
	 * Redirect to a given URL without relying on IP.Board output class
	 * 
	 * @param	string	URL
	 * @return	@e void
	 */
	protected function _redirectToUrl( $url )
	{
		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
		{
			header( "HTTP/1.0 301 Moved Permanently" );
		}
		else
		{
			header( "HTTP/1.1 301 Moved Permanently" );
		}

		header( "Location: " . str_replace( '&amp;', '&', $url ) );
		exit;
	}
}
