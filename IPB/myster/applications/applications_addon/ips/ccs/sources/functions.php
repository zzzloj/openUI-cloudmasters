<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS miscellaneous functions
 * Last Updated: $Date: 2012-02-06 15:39:43 -0500 (Mon, 06 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10256 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * Ensure app class has been loaded
 */
if( !class_exists('app_class_ccs') )
{
	ipsRegistry::getAppClass( 'ccs' );
}

class ccsFunctions
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
	 * Folder/page checked.  We set a property
	 * to indicate this in case there actually
	 * is no folder/page so we don't do the work twice.
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $folderCheckComplete	= false;
	
	/**
	 * Requested folder
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $folder				= '';
	
	/**
	 * Requested page SEO name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $page					= '';
	
	/**
	 * Database data from the FURL
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $databaseString		= '';
	
	/**
	 * Categories handlers
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $categories			= array();
	
	/**
	 * Fields class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $fieldsClass			= null;
	
	/**
	 * Cached page data for articles/database pages
	 *
	 * @access	public
	 * @var		array
	 */
	public $pageData				= array();
	
	/**
	 * Do not return a cached URL - build fresh
	 *
	 * @var		bool
	 */
	public $fetchFreshUrl			= false;

	/**#@+
	 * Error constants
	 *
	 * @var		const
	 * @var		const
	 * @var		const
	 */
	const BTEMPLATE_DIR_NOT_WRITABLE = -1;
	const BTEMPLATE_DUPE	= 0;
	const BTEMPLATE_ERROR	= 1;
	const BTEMPLATE_SUCCESS	= 2;
	/**#@-*/

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
		//-----------------------------------------
		// Do not set memberData or lang
		// It causes issues with session plugin
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Return templates available to assign to articles as option values (for use inside an HTML select list)
	 * 
	 * @param	int		Default to mark as selected
	 * @return	@e string
	 */
 	public function returnArticleTemplates( $default=0 )
 	{
		$templates	= '';
		
		$this->DB->build( array( 'select' => 'template_id, template_name', 'from' => 'ccs_page_templates', 'where' => 'template_database=5' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templates .= "<option value='{$r['template_id']}'" . ( $default == $r['template_id'] ? " selected='selected'" : '' ) . ">{$r['template_name']}</option>";
		}
		
		return $templates;
 	}
	
	/**
	 * Injects javascript into the page for block templates
	 *
	 * @param	string 		Page data
	 * @param	bool		True = return the code to include with an external block, False = inject the code automatically into the output
	 * @param	bool		True = return the code to inject into HTML output, False = inject the code automatically into the supplied output
	 * @return	string		Page output
	 * @link	http://community.invisionpower.com/tracker/issue-36303-addedit-article-page-in-ie9/
	 */
	public function injectBlockFramework( $output, $return=false, $returnFullCode=false )
	{	
		if( $this->settings['disable_js_injection'] )
		{
			return $output;
		}

		// We use document.write here because it is blocking, and the page therefore
		// waits for jQuery to be loaded before continuing. If we created a dom node
		// instead, widgets might execute before jQuery is ready
		if( $return )
		{
		return <<<JAVASCRIPT
	(function(){
		if( !window.jQuery ){
			if( typeof(_ccsLoadedAssets) == 'undefined' || !_ccsLoadedAssets )
			{
				document.write("<"+"script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></"+"script>");
				document.write("<"+"script type='text/javascript'>_ccsjQ = jQuery.noConflict();</"+"script>");
			}
		} else {
			_ccsjQ = jQuery;
		}

		if( typeof(_ccsLoadedAssets) == 'undefined' || !_ccsLoadedAssets )
		{
			document.write("<"+"script type='text/javascript' src='{$this->settings['js_base_url']}ipc_blocks/compiled.js'></"+"script>");
			document.write("<"+"link rel='stylesheet' media='screen' type='text/css' href='{$this->settings['css_base_url']}ipc_blocks/compiled.css' /"+">");
			document.write("<"+"link rel='stylesheet' media='screen' type='text/css' href='{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css' /"+">");
		}
	})();
	var _ccsLoadedAssets = true;
JAVASCRIPT;
		}
		else
		{
		// We use document.write here because it is blocking, and the page therefore
		// waits for jQuery to be loaded before continuing. We then have to break the <script> node in the DOM
		// in order for the script to be inserted there and then start script injection again afterwards.
		$inject = <<<JAVASCRIPT
		var weLoaded = false;
	(function(){
		if( typeof(_ccsLoadedAssets) == 'undefined' || !_ccsLoadedAssets )
		{
			if( !window.jQuery )
			{
				document.write("<"+"script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></"+"script>");
				weLoaded	= true;
			}
		}
	})();
	</script>
	<script type='text/javascript'>

	(function(){
		if( typeof(_ccsLoadedAssets) == 'undefined' || !_ccsLoadedAssets )
		{
			if( weLoaded ){
				_ccsjQ = jQuery.noConflict();
			} else {
				_ccsjQ = jQuery;
			}

			document.write("<"+"script type='text/javascript' src='{$this->settings['js_base_url']}ipc_blocks/compiled.js'></"+"script>");
			document.write("<"+"link rel='stylesheet' media='screen' type='text/css' href='{$this->settings['css_base_url']}ipc_blocks/compiled.css' /"+">");
			document.write("<"+"link rel='stylesheet' media='screen' type='text/css' href='{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css' /"+">");
		}
	})();
	var _ccsLoadedAssets = true;
JAVASCRIPT;

			if( $returnFullCode )
			{
				return $inject;
			}

			$output = preg_replace( "#</head>#", "<script type='text/javascript'>" . $inject . "</script>" . "</head>\r\n", $output, 1 );

			return $output;
		}
	}
	
	/**
	 * Import a block template from provided array
	 *
	 * @param 	array 		Block template entry array
	 * @param 	array 		File array
	 * @param 	bool 		Import a template even if it's a duplicate?
	 * @param 	array 		A whitelist filter of template names, i.e. only import templates in this array
	 * @return	array		Keys 'duplicates', 'errored', 'success' with status codes for each block
	 */
	public function importBlockTemplates( $templateData, $templateResources, $importDupes = false, $filter = array() )
	{
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classTemplateEngine.php', 'classTemplate' );
		$engine			= new $classToLoad( IPS_ROOT_PATH . 'sources/template_plugins' );

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$fileManagement	= new $classToLoad();

		$blockDir 		= DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks';

		$return 		= array();

		//-----------------------------------------
		// An initial permission check
		//-----------------------------------------	

		if( count( $templateResources ) )
		{
			if( !IPSLib::isWritable( $blockDir ) )
			{
				return self::BTEMPLATE_DIR_NOT_WRITABLE;
			}
		}

		//-----------------------------------------
		// Loop through each template to import
		//-----------------------------------------

		$xml->loadXML( $templateData );

		foreach( $xml->fetchElements('btemplate') as $template )
		{
			$entry  = $xml->fetchElementsFromRecord( $template );
			
			if( count( $filter ) && !in_array( $entry['tpb_name'], $filter ) )
			{
				continue;
			}

			//-----------------------------------------
			// Let's see if there's an identical template already
			//-----------------------------------------
			
			$dupe	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$entry['tpb_name']}'" ) );
			
			if( $dupe && !$importDupes )
			{
				$return['duplicates'][ $entry['tpb_name'] ] = self::BTEMPLATE_DUPE;
				continue;
			}

			//-----------------------------------------
			// Test the syntax just in case anything has happened to it
			//-----------------------------------------

			$_TEST	= $engine->convertHtmlToPhp( 'test__whatever' . md5($entry['tpb_name']), '$test', $entry['tpb_content'], '', false, true );
			
			ob_start();
			eval( $_TEST );
			$_RETURN = ob_get_contents();
			ob_end_clean();

			if( $_RETURN )
			{
				$return['errored'][ $entry['tpb_name'] ] = self::BTEMPLATE_ERROR;
				continue;
			}

			//-----------------------------------------
			// Sort the image out
			//-----------------------------------------

			$this->settings['upload_dir']	= $this->settings['upload_dir'] ? $this->settings['upload_dir'] : DOC_IPS_ROOT_PATH . 'uploads';

			if( $entry['tpb_image'] && $entry['tpb_image_data'] )
			{
				$imageData = base64_decode( $entry['tpb_image_data'] );

				if( $dupe )
				{
					@unlink( $this->settings['upload_dir'] . '/content_templates/' . $entry['tpb_image'] );
				}

				if( $imageData !== false )
				{
					file_put_contents( $this->settings['upload_dir'] . '/content_templates/' . $entry['tpb_image'], $imageData );
					@chmod( $this->settings['upload_dir'] . '/content_templates/' . $entry['tpb_image'], IPS_FILE_PERMISSION );
				}
				else
				{
					$entry['tpb_image'] = '';
				}
			}

			//-----------------------------------------
			// Build the insert
			//-----------------------------------------

			foreach( array( 'name', 'params', 'content', 'human_name', 'app_type', 'content_type', 'image', 'protected', 'desc' ) as $item )
			{
				$save[ 'tpb_' . $item ] = $entry['tpb_' . $item ];
			}

			//-----------------------------------------
			// Write any assets this template has
			//-----------------------------------------

			$folder = str_replace('feed__', '', $entry['tpb_name'] );

			if( isset( $templateResources[ $folder ] ) && count( $templateResources[ $folder ] ) )
			{
				//-----------------------------------------
				// Delete the directory if it already exists
				//-----------------------------------------

				if( $dupe && file_exists( $blockDir . '/' . $folder ) )
				{
					$fileManagement->removeDirectory( $blockDir .'/'. $folder );
				}

				if( !file_exists( $blockDir .'/'. $folder ) )
				{
					if ( @mkdir( $blockDir .'/'. $folder, IPS_FOLDER_PERMISSION ) )
					{
						@chmod( $blockDir .'/'. $folder, IPS_FOLDER_PERMISSION );
						
						@file_put_contents( $blockDir .'/'. $folder .'/index.html', '' );
						@chmod( $blockDir .'/'. $folder .'/index.html', IPS_FILE_PERMISSION );
					}
				}

				foreach( $templateResources[ $folder ] as $fileName => $fileContent )
				{
					if( $fileName == 'index.html' )
					{
						continue;
					}

					if( !file_exists( $blockDir .'/'. $folder .'/'. $fileName ) )
					{
						$fp = @fopen( $blockDir .'/'. $folder .'/'. $fileName, 'wb' );

						if( !$fp )
						{
							continue;
						}

						@fwrite( $fp, $fileContent );
						fclose( $fp );

						@chmod( $blockDir .'/'. $folder .'/'. $fileName, IPS_FILE_PERMISSION );
					}
				}
			}

			//-----------------------------------------
			// Write to the DB
			//-----------------------------------------

			if( !$dupe )
			{
				$this->DB->insert( 'ccs_template_blocks', $save );
				$newID = $this->DB->getInsertId();

				//-----------------------------------------
				// Cache it
				//-----------------------------------------

				$cache	= array(
								'cache_type'	=> 'block',
								'cache_type_id'	=> $newID,
								'cache_content' => $_TEST
								);
				
				$this->DB->insert( 'ccs_template_cache', $cache );
			}
			else
			{
				$this->DB->update( 'ccs_template_blocks', $save, "tpb_id = {$dupe['tpb_id']}" );
				$this->DB->update( 'ccs_template_cache', array( 'cache_content' => $_TEST ), "cache_type_id = {$dupe['tpb_id']} AND cache_type = 'block'");
			}

			$return['success'][ $entry['tpb_name'] ] = self::BTEMPLATE_SUCCESS;
		}

		return $return;
	}

	/**
	 * Programatically set folder
	 *
	 * @param	string	Folder to set
	 * @return	@e void
	 * @link	http://community.invisionpower.com/tracker/issue-37244-omit-page-filename-omits-folder/
	 * @note	IP.Content does not use this method, it is here only for developers facing issues due to the bug report linked
	 */
	public function setFolder( $folder='' )
	{
		$this->folder	= $folder;
	}

	/**
	 * Programatically set page
	 *
	 * @param	string	Page to set
	 * @return	@e void
	 * @link	http://community.invisionpower.com/tracker/issue-37244-omit-page-filename-omits-folder/
	 * @note	IP.Content does not use this method, it is here only for developers facing issues due to the bug report linked
	 */
	public function setPage( $page='' )
	{
		$this->page		= $page;
	}

	/**
	 * Retrieve folder
	 *
	 * @access	public
	 * @param	string		Optional URL to check
	 * @return	string		Requested folder name
	 */
	public function getFolder( $_url='' )
	{
		if( $this->folderCheckComplete )
		{
			return $this->folder;
		}
		
		$this->_getPageAndFolder( $_url );

		return $this->folder;
	}
	
	/**
	 * Retrieve page name
	 *
	 * @access	public
	 * @param	string		Optional URL to check
	 * @return	string		Requested page name
	 */
	public function getPageName( $_url='' )
	{
		if( $this->folderCheckComplete )
		{
			return $this->page;
		}
		
		$this->_getPageAndFolder( $_url );
		
		return $this->page;
	}
	
	/**
	 * Retrieve database FURL string
	 *
	 * @access	public
	 * @param	string		Optional URL to check
	 * @return	string		Database FURL string (if any)
	 */
	public function getDatabaseFurlString( $_url='' )
	{
		if( $this->folderCheckComplete )
		{
			return $this->databaseString;
		}
		
		$this->_getPageAndFolder( $_url );
		
		return $this->databaseString;
	}
	
	/**
	 * Sort out page and folder
	 *
	 * @access	protected
	 * @param	string		Optional URL to check
	 * @return	@e void
	 */
	protected function _getPageAndFolder( $_url='' )
	{
		$page	= '';
		$folder	= '';
		
		//-----------------------------------------
		// What page?
		//-----------------------------------------

		if( !$this->request['page'] AND !$this->request['id'] )
		{
			$page	= $this->settings['ccs_default_page'];
		}
		else
		{
			$page	= urldecode($this->request['page']);
		}
		
		//-----------------------------------------
		// Fix folder, page and db
		//-----------------------------------------

		$_reconstructed	= $_url ? $_url : $this->returnVisitedUrl( true );
		$_reconstructed	= rtrim( $_reconstructed, '/' );

		//-----------------------------------------
		// Remove "base url"
		//-----------------------------------------

		if( $this->settings['ccs_root_url'] )
		{
			$_urls	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );
			$_path	= $_reconstructed;
			
			foreach( $_urls as $_url )
			{
				$_default		= rtrim( str_replace( parse_url( $_url, PHP_URL_SCHEME ) . '://www.', $this->returnUrlProtocol(), $_url ), '/' );
				$_path			= str_ireplace( $_default, '', $_path );
			}
		}
		else
		{
			$_path			= str_ireplace( rtrim( str_replace( parse_url( $this->settings['board_url'], PHP_URL_SCHEME ) . '://www.', $this->returnUrlProtocol(), $this->settings['board_url'] ), '/' ), '', $_reconstructed );
		}

		//-----------------------------------------
		// If running through IPB, fix url
		//-----------------------------------------

		if( IPS_DEFAULT_PUBLIC_APP == 'ccs' )
		{
			$_path	= preg_replace( "/^\/index\.php\?(s=.+)?($|\/)/i", "\\1\\2", $_path );
			$_path	= preg_replace( "/^\/index\.php($|\/)/i", "\\1", $_path );
		}

		//-----------------------------------------
		// Does URI have db marker in it
		//-----------------------------------------
		
		if( strpos( $_path, '/' . DATABASE_FURL_MARKER . '/' ) !== false )
		{
			$_rest	= substr( $_path, ( strpos( $_path, '/' . DATABASE_FURL_MARKER . '/' ) + ( strlen(DATABASE_FURL_MARKER) + 2 ) ) );
			$_path	= substr( $_path, 0, ( strpos( $_path, '/' . DATABASE_FURL_MARKER . '/' ) ) );

			if( strpos( $_rest, '?' ) )
			{
				$this->databaseString	= rtrim( substr( $_rest, 0, strpos( $_rest, '?' ) ), '/' );
			}
			else
			{
				$this->databaseString	= $_rest;
			}
		}

		//-----------------------------------------
		// Accessing URL dynamically (thru IPB)?
		//-----------------------------------------
		
		if( strpos( $_path, 'app=ccs' ) !== false )
		{
			$_folder	= ( strpos( urldecode($this->request['folder']), '/' ) === 0 ) ? substr( urldecode($this->request['folder']), 1 ) : urldecode($this->request['folder']);
			
			$_path	= $_folder . '/' . $this->request['page'];
		}
		
		//-----------------------------------------
		// Accessing through IPB friendly urls?
		//-----------------------------------------
		
		if( stripos( $_reconstructed, str_ireplace( array( 'http://www.', 'https://www.' ), array( 'http://', 'https://' ), $this->settings['board_url'] ) ) !== false )
		{
			$_PATHFIX = '';
			require( IPSLib::getAppDir('ccs') . '/extensions/furlTemplates.php' );/*noLibHook*/
			$_path = preg_replace( $_PATHFIX, "\\1", $_path );
		}

		//-----------------------------------------
		// Accessing through main gateway
		//-----------------------------------------
		
		if( $this->settings['ccs_root_filename'] AND stripos( $_path, $this->settings['ccs_root_filename'] . '/' ) !== false )
		{
			$_path	= substr( $_path, ( stripos( $_path, $this->settings['ccs_root_filename'] . '/' ) + ( strlen($this->settings['ccs_root_filename']) + 1 ) ) );
		}

		$uriBits	= explode( '/', $_path );
		$myFolder	= '';

		if( count($uriBits) > 1 )
		{
			$page		= array_pop($uriBits); // Retrieve the file name
			$myFolder	= count($uriBits) ? '/' . implode( '/', $uriBits ) : '';
			$myFolder	= ( $myFolder == '/' ) ? '' : $myFolder;
		}
		else
		{
			$page		= $_path;
		}

		$folder	= $myFolder;

		if( strpos( $page, '?' ) !== false )
		{
			$page	= substr( $page, 0, strpos( $page, '?' ) );
		}
		
		if( strpos( $page, '/' ) !== false )
		{
			$page	= substr( $page, 0, strpos( $page, '/' ) );
		}
		
		if( strpos( $page, '&' ) !== false )
		{
			$page	= substr( $page, 0, strpos( $page, '&' ) );
		}

		$page	= IPSText::parseCleanValue( $page );
		$folder	= IPSText::parseCleanValue( preg_replace( "#^//(.*?)$#", "/$1", $folder ) );

		$this->folder	= urldecode($folder);
		$this->page		= urldecode($page);
		
		//-----------------------------------------
		// Now fix input params
		// http://localhost/invisionboard/trunk/index.php?/page/index.html?do=add&category=
		// Sets: [/page/index_html?do] => add
		//-----------------------------------------
		
		foreach( $this->request as $k => $v )
		{
			if( strpos( $k, '?' ) !== false )
			{
				$this->request[ substr( $k, strpos( $k, '?' ) + 1 ) ]	= $v;
			}
		}
		//print_r($this->request);exit;
		//print 'Page:'.$this->page.'<br>'.'Folder:'.$this->folder.'<br>'.'Database string:'.$this->databaseString;exit;	
		$this->folderCheckComplete	= true;
	}
	
	/**
	 * Return URL protocol (http:// or https://)
	 * 
	 * @return	string
	 */
 	public function returnUrlProtocol()
 	{
 		return ( $_SERVER['HTTPS'] and $_SERVER['HTTPS'] != 'off' ) ? "https://" : "http://";
 	}
	
	/**
	 * Return the page URL we actually visited
	 * 
	 * @param	bool	Strip www. from hostname
	 * @return	string
	 */
 	public function returnVisitedUrl( $stripWww=false )
 	{
		$_requestUri	= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
		$_reconstructed	= $this->returnUrlProtocol() . ( $stripWww ? preg_replace( '/^www\./', '', $_SERVER['HTTP_HOST'] ) : $_SERVER['HTTP_HOST'] ) . ( substr( $_requestUri, 0, 1 ) == '/' ? $_requestUri : '/' . $_requestUri );

		if( preg_match( "/(?<!index\.php)\?(.+?)$/i", $_reconstructed, $matches ) )
		{
			$_reconstructed	= str_replace( $matches[0], '', $_reconstructed );

			$_pairs			= explode( '&', $matches[1] );

			foreach( $_pairs as $_pair )
			{
				$_bits	= explode( '=', $_pair );

				if( strpos( $_bits[0], '[' ) === false OR strpos( $_bits[0], ']' ) === false )
				{
					$this->request[ IPSText::parseCleanKey( $_bits[0] ) ]	= IPSText::parseCleanValue( $_bits[1] );
				}
				else
				{
					$_key1		= IPSText::parseCleanKey( substr( $_bits[0], 0, strpos( $_bits[0], '[' ) ) );
					$_bits[0]	= substr( $_bits[0], strpos( $_bits[0], '[' ) );
					$_key2		= IPSText::parseCleanKey( str_replace( array( '[', ']' ), '', substr( $_bits[0], 0, strpos( $_bits[0], ']' ) + 1 ) ) );
					$_bits[0]	= substr( $_bits[0], strpos( $_bits[0], ']' ) + 1 );
					$_key3		= null;

					if( strpos( $_bits[0], ']' ) !== false )
					{
						$_key3		= IPSText::parseCleanKey( str_replace( array( '[', ']' ), '', substr( $_bits[0], 0, strpos( $_bits[0], ']' ) + 1 ) ) );

						if( !$_key3 )
						{
							$_key3	= 0;
						}
					}
					
					if( isset($_key3) )
					{
						$this->request[ $_key1 ][ $_key2 ][ $_key3 ]	= IPSText::parseCleanValue( $_bits[1] );
					}
					else if( isset($_key2) )
					{
						$this->request[ $_key1 ][ $_key2 ]	= IPSText::parseCleanValue( $_bits[1] );
					}
					else if( isset($_key1) )
					{
						$this->request[ $_key1 ]	= IPSText::parseCleanValue( $_bits[1] );
					}
				}
			}
		}

		return $_reconstructed;
 	}

	/**
	 * Return the page URL we actually visited, minus the database string
	 * 
	 * @note	Stripping www. from the URL means URLs generated that SHOULD have it based on config will never match
	 * @link	http://community.invisionpower.com/tracker/issue-35736-www-vs-no-www
	 * @return	string
	 */
 	public function returnVisitedUrlWithoutDatabase()
 	{
		$_requestUri	= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
		//$_reconstructed	= $this->returnUrlProtocol() . preg_replace( '/^www\./', '', $_SERVER['HTTP_HOST'] ) . ( substr( $_requestUri, 0, 1 ) == '/' ? $_requestUri : '/' . $_requestUri );
		$_reconstructed	= $this->returnUrlProtocol() . $_SERVER['HTTP_HOST'] . ( substr( $_requestUri, 0, 1 ) == '/' ? $_requestUri : '/' . $_requestUri );
		$db				= $this->getDatabaseFurlString();

		if( preg_match( "/(?<!index\.php)\?(.+?)$/i", $_reconstructed, $matches ) )
		{
			$_reconstructed	= str_replace( $matches[0], '', $_reconstructed );

			$_pairs			= explode( '&', $matches[1] );

			foreach( $_pairs as $_pair )
			{
				$_bits	= explode( '=', $_pair );

				$this->request[ IPSText::parseCleanKey( $_bits[0] ) ]	= IPSText::parseCleanValue( $_bits[1] );
			}
		}
		$_reconstructed	= preg_replace( "/\?([^\/]+?)$/", '', $_reconstructed );

		if( $db )
		{
			$_reconstructed	= rtrim( str_replace( '/' . DATABASE_FURL_MARKER . '/' . $db, '', $_reconstructed ), '/' );
		}

		return $_reconstructed;
 	}
	
	/**
	 * Get frontpage cache.  Handles resetting default params if frontpage cache is empty.
	 *
	 * @access	public
	 * @return	array
	 */
	public function returnFrontpageCache()
	{
		$_result	= array_merge(
									array(
										'categories'		=> '*',
										'limit'				=> 10,
										'sort'				=> 'record_updated',
										'order'				=> 'desc',
										'pinned'			=> 0,
										'paginate'			=> 0,
										'template'			=> 0,
										'exclude_subcats'	=> 0,
										),
									$this->cache->getCache('ccs_frontpage')
									);

		if( !$_result['sort'] )
		{
			$_result['sort']	= 'record_updated';
		}
		
		if( !$_result['order'] )
		{
			$_result['order']	= 'desc';
		}
		
		return $_result;
	}

	/**
	 * Return the proper page url
	 *
	 * @access	public
	 * @param	array 	Page data
	 * @return	string	Page URL based on enabled settings
	 */
	public function returnPageUrl( $page=array() )
	{
		if ( isset( $page['page_edit_id'] ) )
		{
			$page['page_id'] = $page['page_edit_id'];
		}

		//-----------------------------------------
		// Have an external url?
		//-----------------------------------------
		
		$url	= $this->returnFurlRoot();
		
		if( $url )
		{
			if( !$this->settings['ccs_mod_rewrite'] )
			{
				if( substr( $url, -1 ) != '/' )
				{
					$url .= '/';
				}
			
				$url .= $this->settings['ccs_root_filename'];
			}
			else
			{
				$url	= rtrim( $url, "/" );
			}
			
			if( $page['page_folder'] )
			{
				$url .= $page['page_folder'] . '/';
			}
			else
			{
				$url	.= '/';
			}
			
			if( !$page['page_omit_filename'] )
			{
				$url	.= $page['page_seo_name'];
			}
		}
		else if( $this->settings['use_friendly_urls'] )
		{
			$url	= str_replace( array('http:/', 'https:/'), array('http://', 'https://'), str_replace( '//', '/', $this->registry->output->formatUrl( $this->settings['board_url'] . '/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;folder=' . $page['page_folder'] . '&amp;id=' . $page['page_id'], $page['page_seo_name'], 'page' ) ) );
		}
		else
		{
			$url	= $this->settings['board_url'] . '/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;id=' . $page['page_id'];
		}
		
		return $url;
	}
	
	/**
	 * Return URL to database/category/record/comment.
	 *
	 * @access	public
	 * @param	int		Database ID
	 * @param	int		Category ID
	 * @param	mixed	Record ID or array of record data
	 * @param	mixed	Comment ID or '_last_' to find last comment
	 * @param	int		'st' value
	 * @return	string	URL
	 */
	public function returnDatabaseUrl( $database, $category=0, $record=null, $comment=0, $st=0 )
	{
		$_recordId	= is_array($record) ? $record['primary_id_field'] : $record;
		
		//-----------------------------------------
		// Caching
		//-----------------------------------------
		
		static $builtUrls	= array();
		$_thisKey			= md5( $database . $category . $_recordId . $comment );

		if( isset( $builtUrls[ $_thisKey ] ) AND !$this->fetchFreshUrl )
		{
			return $builtUrls[ $_thisKey ];
		}
		
		$_database	= $this->caches['ccs_databases'][ $database ];
		
		if( !$_database['database_id'] )
		{
			$builtUrls[ $_thisKey ]	= '#';
			
			return '#';
		}
		
		if( $_database['database_is_articles'] )
		{
			if( !empty($record['category_id']) )
			{
				$_findWhere		= "(page_content LIKE '%\{parse articles category=\"{$record['category_id']}\"\}%' OR page_content LIKE '%\{parse articles\}%')";
			}
			else
			{
				$_findWhere		= "page_content LIKE '%\{parse articles\}%'";
			}
			$_findWhereKey	= md5($_findWhere);
		}
		else
		{
			$_findWhere		= "(page_content LIKE '%parse database=\"{$_database['database_key']}\"%' OR page_content LIKE '%parse database=&quot;{$_database['database_key']}&quot;%')";
			$_findWhereKey	= $_database['database_key'];
		}
		
		//-----------------------------------------
		// Pull from cache if available, else get from db
		//-----------------------------------------

		if( isset( $this->pageData[ $_findWhereKey ] ) )
		{
			$page	= $this->pageData[ $_findWhereKey ];
		}
		else
		{
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => $_findWhere ) );
			
			$this->pageData[ $_findWhereKey ]	= $page;
		}
		
		if( !$page['page_id'] )
		{
			$builtUrls[ $_thisKey ]	= '#';
			
			return '#';
		}
		
		$url	= $this->returnPageUrl( $page );
		
		if( $category )
		{
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				$url	.= '&amp;category=' . $category;
			}
			else
			{
				$url	= $this->getCategoriesClass( $_database )->getCategoryUrl( $url . '?', $category );
			}
		}
		else if( $_recordId )
		{
			if( !$this->settings['use_friendly_urls'] AND !$this->settings['ccs_root_url'] )
			{
				$url	.= '&amp;record=' . $_recordId;
				
				if( !is_array($record) )
				{
					$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $_database['database_database'], 'where' => 'primary_id_field=' . $_recordId ) );
				}

				if( !$record['primary_id_field'] )
				{
					$builtUrls[ $_thisKey ]	= '#';
					
					return '#';
				}
			}
			else
			{
				$_database['base_link']	= $url . '?';

				$_fields	= array();
		
				if( is_array($this->caches['ccs_fields'][ $_database['database_id'] ]) AND count($this->caches['ccs_fields'][ $_database['database_id'] ]) )
				{
					foreach( $this->caches['ccs_fields'][ $_database['database_id'] ] as $_field )
					{
						$_fields[ $_field['field_id'] ]	= $_field;
					}
				}

				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
				$_dbLibrary					= new $classToLoad( $this->registry );
				$_dbLibrary->categories		= $this->getCategoriesClass( $_database );
				$_dbLibrary->database		= $_database;
				$_dbLibrary->fields			= $_fields;
				$_dbLibrary->fieldsClass	= $this->getFieldsClass();
				$_dbLibrary->_initDatabase( $page );
				
				if( !is_array($record) )
				{
					$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $_database['database_database'], 'where' => 'primary_id_field=' . $_recordId ) );
				}

				if( !$record['primary_id_field'] )
				{
					$builtUrls[ $_thisKey ]	= '#';
					
					return '#';
				}
				
				$url	= $_dbLibrary->getRecordUrl( $record, ( $comment OR $st ) ? true : false );
			}
		}
				
		//-----------------------------------------
		// Comment?
		//-----------------------------------------
		
		if( $comment )
		{
			$_cat				= $this->getCategoriesClass( $_database )->categories[ $record['category_id'] ];
		    $forumIntegration	= $_cat['category_forum_override'] ? $_cat['category_forum_record'] : $_database['database_forum_record'];
		    $commentsToo		= $_cat['category_forum_override'] ? $_cat['category_forum_comments'] : $_database['database_forum_comments'];

			//-----------------------------------------
			// Forum integration or built in?
			//-----------------------------------------
		
		    if( $forumIntegration AND $commentsToo )
		    {
				//-----------------------------------------
				// class_forums is often not instantiated, so using the API
				// methods leads to unnecessary resource overhead
				//-----------------------------------------
			
				$q	= " AND queued=0";// " AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ) );
				
				if( $_database['moderate_approvec'] AND $_database['moderate_deletec'] )
				{
					$q	= " AND queued IN(0,1,2)";// " AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'notVisible', 'visible' ) );
				}
				else if( $_database['moderate_approvec'] )
				{
					$q	= " AND queued IN(1,0)";// " AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'queued', 'visible' ) );
				}
				else if( $_database['moderate_deletec'] )
				{
					$q	= " AND queued IN(2,0)";// " AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'sdelete', 'visible' ) );
				}

		    	$topic	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid={$record['record_topicid']}" ) );
		    	
		    	$topic['topic_firstpost']	= intval($topic['topic_firstpost']);

				//-----------------------------------------
				// Last comment or specific id?
				//-----------------------------------------
		
				if ( $comment == '_last_' )
				{
					$comment = $this->DB->buildAndFetch( array(  'select' => 'pid as comment_id',
																 'from'   => 'posts',
																 'where'  => "pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" . $q,
																 'order'  => 'pid DESC',
																 'limit'  => array( 0, 1 ) ) );
				}
				else
				{
					$comment = array( 'comment_id' => intval($comment) );
				}

				//-----------------------------------------
				// Get total number of comments
				//-----------------------------------------

				$total	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total',
														   'from'   => 'posts',
														   'where'  => "pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" . $q . ' AND pid <=' . intval($comment['comment_id']) ) );
		    }
		    else
		    {
		    	$q	= $_database['moderate_approvec'] ? '' : " AND comment_approved=1";

				//-----------------------------------------
				// Last comment or specific id?
				//-----------------------------------------
		
				if ( $comment == '_last_' )
				{
					$comment = $this->DB->buildAndFetch( array(  'select' => 'comment_id',
																 'from'   => 'ccs_database_comments',
																 'where'  => 'comment_database_id=' . $_database['database_id'] . ' AND comment_record_id=' . $record['primary_id_field'] . $q,
																 'order'  => 'comment_id DESC',
																 'limit'  => array( 0, 1 ) ) );
				}
				else
				{
					$comment = array( 'comment_id' => intval($comment) );
				}

				//-----------------------------------------
				// Get total number of comments
				//-----------------------------------------

				$total	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total',
														   'from'   => 'ccs_database_comments',
														   'where'  => 'comment_database_id=' . $_database['database_id'] . ' AND comment_record_id=' . $record['primary_id_field'] . $q . ' AND comment_id <=' . intval($comment['comment_id']) ) );
		    }

			//-----------------------------------------
			// Figure out page number now
			//-----------------------------------------
		
			$st			= 0;
			$perpage	= 25;

			if ( $total['total'] > $perpage )
			{
				if ( $total['total'] % $perpage == 0 )
				{
					$pages = $total['total'] / $perpage;
				}
				else
				{
					$number = ( $total['total'] / $perpage );
					$pages = ceil( $number);
				}
				
				$st = ($pages - 1) * $perpage;
			}
		
			$url	.= 'st=' . $st . '#comment_' . intval($comment['comment_id']);
		}
		else if( $st )
		{
			$url	.= 'st=' . $st . '#commentsStart';
		}

		$builtUrls[ $_thisKey ]	= $url;
		
		return $url;
	}

	/**
	 * Get fields class (prevents instantiating more than once)
	 *
	 * @access	public
	 * @return	object
	 */	
	public function getFieldsClass()
	{
		if( !is_object($this->fieldsClass) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/fields.php', 'ccs_database_fields', 'ccs' );
			$this->fieldsClass	= new $classToLoad( $this->registry );
			$this->fieldsClass->getHandlers();
		}
		
		return $this->fieldsClass;
	}
	
	/**
	 * Get fields class (prevents instantiating more than once)
	 *
	 * @access	public
	 * @param	mixed 		Database data, or database id
	 * @param	bool		Whether or not to hide cats you do not have permission to access
	 * @return	object
	 */	
	public function getCategoriesClass( $database, $hide=true )
	{
		$caches		= $this->cache->getCache('ccs_databases');
		$database	= is_array($database) ? $database : $caches[ $database ];

		if( !is_object( $this->categories[ $database['database_id'] ] ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/categories.php', 'ccs_categories', 'ccs' );
			$this->categories[ $database['database_id'] ]	= new $classToLoad( $this->registry );
			$this->categories[ $database['database_id'] ]->hide	= $hide;
			$this->categories[ $database['database_id'] ]->init( $database );
		}
		
		return $this->categories[ $database['database_id'] ];
	}

	/**
	 * Apply ACP-defined "record" language bits to all language strings.
	 *
	 * @access	public
	 * @param	integer		Database id
	 * @param	bool		Force rebuild?
	 * @return	@e void
	 */
	public function fixStrings( $database, $forceRebuild=false )
	{
		//-----------------------------------------
		// Have we already built?
		//-----------------------------------------
		
		static $stringsFixed	= array();
		static $backup			= array();
		
		if( isset($stringsFixed[$database]) AND !$forceRebuild )
		{
			foreach( $stringsFixed[ $database ] as $_k => $_v )
			{
				$this->registry->getClass('class_localization')->words[ $_k ]	= $_v;
			}

			return;
		}
		
		$stringsFixed[ $database ]	= array();
		
		//-----------------------------------------
		// Get database
		//-----------------------------------------
		
		$_database	= $this->caches['ccs_databases'][ $database ];
		
		//-----------------------------------------
		// If backup is populated, we've built once
		// and need to use backup now
		//-----------------------------------------
		
		if( count($backup) )
		{
			foreach( $backup as $_k => $_v )
			{
				$_v	= str_replace( "_RUS_", $_database['database_lang_su'] ? $_database['database_lang_su'] : $this->registry->getClass('class_localization')->words['database_lang_su'], $_v );
				$_v	= str_replace( "_RUP_", $_database['database_lang_pu'] ? $_database['database_lang_pu'] : $this->registry->getClass('class_localization')->words['database_lang_pu'], $_v );
				$_v	= str_replace( "_RLS_", $_database['database_lang_sl'] ? $_database['database_lang_sl'] : $this->registry->getClass('class_localization')->words['database_lang_sl'], $_v );
				$_v	= str_replace( "_RLP_", $_database['database_lang_pl'] ? $_database['database_lang_pl'] : $this->registry->getClass('class_localization')->words['database_lang_pl'], $_v );
				
				$stringsFixed[ $database ][ $_k ]	= $_v;
				$this->registry->getClass('class_localization')->words[ $_k ]	= $_v;
			}
		}
		else
		{
			foreach( $this->registry->getClass('class_localization')->words as $_k => $_v )
			{
				if( strpos( $_v, '_RUS_' ) !== false OR strpos( $_v, '_RUP_' ) !== false OR strpos( $_v, '_RLS_' ) !== false OR strpos( $_v, '_RLP_' ) !== false )
				{
					$backup[ $_k ]	= $_v;
					
					$_v	= str_replace( "_RUS_", $_database['database_lang_su'] ? $_database['database_lang_su'] : $this->registry->getClass('class_localization')->words['database_lang_su'], $_v );
					$_v	= str_replace( "_RUP_", $_database['database_lang_pu'] ? $_database['database_lang_pu'] : $this->registry->getClass('class_localization')->words['database_lang_pu'], $_v );
					$_v	= str_replace( "_RLS_", $_database['database_lang_sl'] ? $_database['database_lang_sl'] : $this->registry->getClass('class_localization')->words['database_lang_sl'], $_v );
					$_v	= str_replace( "_RLP_", $_database['database_lang_pl'] ? $_database['database_lang_pl'] : $this->registry->getClass('class_localization')->words['database_lang_pl'], $_v );
					
					$stringsFixed[ $database ][ $_k ]	= $_v;
					$this->registry->getClass('class_localization')->words[ $_k ]	= $_v;
				}
			}
		}
	}
	
	/**
	 * Check page viewing permissions
	 *
	 * @access	protected
	 * @param	array 		Page information
	 * @return	bool		Can view or not
	 */
	public function canView( $page )
	{
		//-----------------------------------------
		// Reset in case object was instantiated from
		// session plugin callback (where member is empty)
		//-----------------------------------------
		
		$this->member		= $this->registry->member();
		
		//-----------------------------------------
		// Open to all
		//-----------------------------------------
		
		if( $page['page_view_perms'] == '*' )
		{
			return true;
		}
		
		//-----------------------------------------
		// Figure out which perm masks to check
		//-----------------------------------------
		
		$_allowedMasks	= explode( ',', $page['page_view_perms'] );
		$_myMasks		= $this->member->perm_id_array;
		
		foreach( $_allowedMasks as $maskId )
		{
			if( in_array( $maskId, $_myMasks ) )
			{
				return true;
			}
		}
		
		return false;
	}

	
	/**
	 * Return image dimensions for template plugin
	 *
	 * @access	public
	 * @param	string		Image path/url
	 * @param	int			Max width
	 * @param	int			Max height
	 * @deprecated			Method moved to IPSLib (same method signature), just leaving in place to prevent issues with older templates
	 */
	public function getTemplateDimensions( $image, $width, $height )
	{
		return IPSLib::getTemplateDimensions( $image, $width, $height );
	}

	/**
	 * Get root FURL, if configured
	 *
	 * @access	public
	 * @return	string	Page URL based on enabled settings
	 */
	public function returnFurlRoot()
	{
		//-----------------------------------------
		// Try to sniff for proper base URL, or
		// use first in list if proper one cannot be
		// found.
		//-----------------------------------------
		
		$url	= '';
		
		if( trim($this->settings['ccs_root_url']) )
		{
			$_urls	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );
			$_http	= $this->returnUrlProtocol();
			$_host	= preg_replace( '/^www\./', '', $_SERVER['HTTP_HOST'] );
			
			foreach( $_urls as $_url )
			{
				if ( $_url && ( strpos( preg_replace( '/^' . str_replace( '/', '\/', $_http ) . 'www\./', $_http, $_url ), $_http . $_host . $_SERVER['REQUEST_URI'] ) === 0
					OR strpos( $_http . $_host . $_SERVER['REQUEST_URI'], preg_replace( '/^' . str_replace( '/', '\/', $_http ) . 'www\./', $_http, $_url ) ) === 0 ) )
				{
					$url = $_url;
					break;
				}
			}

			if( !$url )
			{
				$url = $_urls[0];
			}
		}
		
		return $url;
	}

	/**
	 * Return the expected page URL for ACP preview
	 *
	 * @access	public
	 * @return	array	'url' => page URL beginning, 'furl' => furl or not
	 */
	public function returnUrlPreview()
	{
		//-----------------------------------------
		// Have an external url?
		//-----------------------------------------
		
		$url	= $this->returnFurlRoot();
		$_furl	= false;
		
		if( $url )
		{
			if( !$this->settings['ccs_mod_rewrite'] )
			{
				if( substr( $url, -1 ) != '/' )
				{
					$url .= '/';
				}
			
				$url .= $this->settings['ccs_root_filename'];
			}
			else
			{
				$url	= rtrim( $url, "/" );
			}
			
			$_furl	= true;
		}
		else if( $this->settings['use_friendly_urls'] )
		{
			$url	= $this->settings['board_url'] . '/page';
			$_furl	= true;
		}
		else
		{
			$url	= $this->settings['board_url'] . '/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;folder=';
		}
		
		return array( 'url' => $url, 'furl' => $_furl );
	}
}