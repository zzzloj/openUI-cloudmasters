<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS miscellaneous functions for ACP
 * Last Updated: $Date: 2011-11-14 21:28:24 -0500 (Mon, 14 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 9817 $
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

class ccsAcpFunctions
{
	/**#@+
	 * Registry objects
	 *
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
	 * Constructor
	 *
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
	 * Exports blocks and block templates
	 * 
	 * @param 	array 	Array of templates to export
	 * @param 	array 	Array of block data to include
	 * @return 	@e 	string 	The XML output ready to be sent to the browser
	 */
	public function exportBlockTemplates( $records = array(), $block = array() )
	{
		$foldersToScan		= array();
		$exportTemplates	= array();
		$blockDir			= DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks';

		//---------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive	= new classXMLArchive();

		//-----------------------------------------
		// Process each template
		//-----------------------------------------

		if( is_array( $records ) && count( $records ) )
		{
			foreach( $records as $r )
			{
				//-----------------------------------------
				// Add directory to check
				//-----------------------------------------

				$foldersToScan[] = str_replace('feed__', '', $r['tpb_name']);

				//-----------------------------------------
				// le image
				//-----------------------------------------

				if( $r['tpb_image'] )
				{
					$img = $this->settings['upload_dir'] . '/content_templates/' . $r['tpb_image'];

					if( is_file( $img ) )
					{
						$imageData = fread( fopen( $img, "r"), filesize( $img ) );
						$r['tpb_image_data'] = base64_encode( $imageData );
					}
					else
					{
						$r['tpb_image'] = '';
					}
				}

				$exportTemplates[] = $r;
			}

			//-----------------------------------------
			// Do the directories first
			//-----------------------------------------

			$xmlarchive->setStripPath( $blockDir .'/' );

			if( count( $foldersToScan ) )
			{
				foreach( $foldersToScan as $folder )
				{
					if( is_dir( $blockDir .'/'. $folder ) )
					{
						$xmlarchive->add( $blockDir .'/'. $folder );
					}
				}
			}

			//-----------------------------------------
			// Now do the template data bits
			//-----------------------------------------

			$xml->newXMLDocument();
			$xml->addElement( 'btemplateexport' );
			$xml->addElement( 'btemplates', 'btemplateexport' );

			foreach( $exportTemplates as $template )
			{
				$xml->addElementAsRecord( 'btemplates', 'btemplate', $template );
			}

			$xmlData = $xml->fetchDocument();
			$xmlarchive->add( $xmlData, 'btemplates_data.xml' );
		}

		//-----------------------------------------
		// And add in the block data
		//-----------------------------------------

		if( is_array( $block ) && count( $block ) )
		{
			$xml->newXMLDocument();
			$xml->addElement( 'blockexport' );
			$xml->addElement( 'blockdata', 'blockexport' );
			$xml->addElementAsRecord( 'blockdata', 'block', $block );
			
			$xmlData = $xml->fetchDocument();
			$xmlarchive->add( $xmlData, 'blockdata.xml' );
		}

		$archive = $xmlarchive->getArchiveContents();
		
		return $archive;
	}

	/**
	 * Recache the block javascript file
	 *
	 * @return	@e void
	 */
	public function recacheResources()
	{
		$validTemplates	= array();
		$fileOutput		= array();
		$blockDir		= DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/ipc_blocks';
		$publicDir		= $this->settings['public_dir'] . 'ipc_blocks/';

		// We keep an array of MD5 file hashes so that identical files
		// aren't included more than once. This allows authors to ship
		// 'core' files with each of their templates, but enable IP.Content
		// to only include them once in the compiled file.
		$doneFiles = array('js' => array(), 'css' => array());

		//-----------------------------------------
		// Get all valid block templates
		//-----------------------------------------

		$this->DB->build( array( 	'select' => 'tpb_name',
									'from' => 'ccs_template_blocks',
									'where' => "tpb_app_type != '' AND tpb_content_type != ''"
								) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$validTemplates[] = str_replace('feed__', '', $r['tpb_name'] );
		}

		//-----------------------------------------
		// Get core files
		//-----------------------------------------

		if( is_file( $blockDir .'/core.js' ) )
		{
			$fileOutput['js'][] = array(	'name' => 'core.js',
											'dir' => '/',
											'content' => file_get_contents( $blockDir . '/core.js' )
										);

			$doneFiles['js'][] = md5_file( $blockDir .'/core.js' );
		}

		if( is_file( $blockDir .'/core.css' ) )
		{
			$fileOutput['css'][] = array(	'name'	=> 'core.css',
											'dir' => '/',
											'content' => file_get_contents( $blockDir . '/core.css' )
										);
			
			$doneFiles['css'][] = md5_file( $blockDir .'/core.css' );
		}
					
		//-----------------------------------------
		// Loop through each folder in ipc_blocks to get JS file contents
		//-----------------------------------------

		foreach( new DirectoryIterator( $blockDir ) as $object )
		{
			if( $object->isDir() && in_array( $object->getFilename(), $validTemplates ) )
			{
				foreach( new DirectoryIterator( $blockDir .'/'. $object->getFilename() ) as $file )
				{
					if( !$file->isDot() && !$file->isDir() )
					{
						if( pathinfo( $file->getPathname(), PATHINFO_EXTENSION ) == 'js' )
						{
							$hash = md5_file( $file->getPathname() );

							if( in_array( $hash, $doneFiles['js'] ) )
							{
								$skippedFiles['js'][] = array( 	'name' => $file->getFilename(),
																'dir' => str_replace( $blockDir, '', $object->getFilename() )
															);
							} 
							else
							{
								$fileOutput['js'][] = array( 	'name' => $file->getFilename(),
																'dir'	=> str_replace( $blockDir, '', $object->getFilename() ),
																'content' => file_get_contents( $file->getPathname() )
														);

								$doneFiles['js'][] = $hash;
							}
						}
						else if( pathinfo( $file->getPathname(), PATHINFO_EXTENSION ) == 'css' )
						{
							$hash = md5_file( $file->getPathname() );

							if( in_array( $hash, $doneFiles['css'] ) )
							{
								$skippedFiles['css'][] = array( 	'name' => $file->getFilename(),
																	'dir' => str_replace( $blockDir, '', $object->getFilename() )
															);
							} 
							else
							{
								$fileOutput['css'][] = array( 	'name' => $file->getFilename(),
																'dir'	=> str_replace( $blockDir, '', $object->getFilename() ),
																'content' => str_replace("{block_dir}", $publicDir . $object->getFilename(), file_get_contents( $file->getPathname() ) )
														);

								$doneFiles['css'][] = $hash;
							}
						}
					}
				}
			}
		}

		if( !count($fileOutput['js']) && !count($fileOutput['css']) )
		{
			return false;
		}

		//-----------------------------------------
		// Now build le JS file
		//-----------------------------------------
		if( count( $fileOutput['js'] ) )
		{
			$FILE_CONTENTS = $this->buildResourceFile( 
				array('valid' => $fileOutput['js'], 'skipped' => $skippedFiles['js'] ),
				array('open' => '//', 'close' => '')
			);

			if( !$fp = fopen( $blockDir .'/compiled.js', 'w' ) )
			{
				return false;
			}

			//-----------------------------------------
			// Let's attempt to minify it
			//-----------------------------------------
			if( !$this->request['_skip_min'] )
			{
				try {
					include_once( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/min/lib/JSMin.php' );/*noLibHook*/

					$min_content = JSMin::minify( $FILE_CONTENTS );

					if( $min_content )
					{
						$FILE_CONTENTS = $min_content;
					}
				} catch( Exception $e ){
					// Well, doesn't matter, we'll write the unminified contents
				}
			}

			// Write the file
			if( @fwrite( $fp, $FILE_CONTENTS ) === false )
			{
				return false;
			}

			fclose( $fp );
		}

		//-----------------------------------------
		// Now build le CSS file
		//-----------------------------------------
		if( count( $fileOutput['css'] ) )
		{
			$FILE_CONTENTS = $this->buildResourceFile( 
				array('valid' => $fileOutput['css'], 'skipped' => $skippedFiles['css'] ),
				array('open' => '/*', 'close' => '*/')
			);

			if( !$fp = fopen( $blockDir .'/compiled.css', 'w' ) )
			{
				return false;
			}

			//-----------------------------------------
			// Let's attempt to minify it
			//-----------------------------------------
			if( !$this->request['_skip_min'] )
			{
				try {
					include_once( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/min/lib/Minify/CSS/Compressor.php' );/*noLibHook*/

					$min_content = Minify_CSS_Compressor::process( $FILE_CONTENTS );

					if( $min_content )
					{
						$FILE_CONTENTS = $min_content;
					}
				} catch( Exception $e ){
					// Well, doesn't matter, we'll write the unminified contents
				}
			}

			if( @fwrite( $fp, $FILE_CONTENTS ) === false )
			{
				return false;
			}

			fclose( $fp );
		}

		return true;
	}

	/**
	 * Builds the contents of a resource file (JS/CSS)
	 *
	 * @param 	array  Array of file data
	 * @param 	array  Defines the start/end comment style
	 * @return	@e string
	 */
	function buildResourceFile( $files, $comments )
	{
		$FILE_CONTENTS = '';
		$FILE_CONTENTS .= "{$comments['open']} Last cached " . date('r') . "{$comments['close']}\n";
		$FILE_CONTENTS .= "{$comments['open']} Don't edit this file directly!{$comments['close']}\n\n";

		foreach( $files['valid'] as $leFile )
		{
			$FILE_CONTENTS .= <<<FILE
{$comments['open']}------------------------------------{$comments['close']}
{$comments['open']} {$leFile['name']} in {$leFile['dir']} {$comments['close']}
{$comments['open']}------------------------------------{$comments['close']}
{$leFile['content']}
\n\n
FILE;
		}

		//-----------------------------------------
		// Did we skip any?
		//-----------------------------------------
		if( count( $files['skipped'] ) )
		{
			$FILE_CONTENTS .= "\n\n{$comments['open']}------------------------------------{$comments['close']}\n";
			$FILE_CONTENTS .= "{$comments['open']} " . count( $skippedFiles ) . " skipped files:{$comments['close']}\n";

			foreach( $files['skipped'] as $skipped )
			{
				$FILE_CONTENTS .= "{$comments['open']} " . $skipped['name'] . ' in ' . $skipped['dir'] . "{$comments['close']}\n";
			}
		}

		return $FILE_CONTENTS;
	}


	/**
	 * Get all feed block configs
	 *
	 * @return	@e array
	 */
	public function getFeedBlockConfigs()
	{
		$_feedTypes = array();

		//-----------------------------------------
		// Get interface
		//-----------------------------------------

		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );/*noLibHook*/

		//-----------------------------------------
		// First, grab all feed blocks in app directories
		//-----------------------------------------

		foreach( IPSLib::getEnabledApplications() as $appDir => $application )
		{
			if( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/content/feed_blocks' ) )
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/content/feed_blocks' ) as $object )
				{
					if( !$object->isDir() AND !$object->isDot() )
					{
						$_key = str_replace( IPSLib::getAppDir( $appDir ) . '/extensions/content/feed_blocks/', '', str_replace( '\\', '/', $object->getPathname() ) );
						
						if( strpos( $_key, '.php' ) !== false )
						{
							$_key			= str_replace( '.php', '', $_key );

							$classToLoad	= IPSLib::loadLibrary( $object->getPathname(), 'feed_' . $_key, $appDir );

							if( class_exists( $classToLoad ) )
							{
								$_class 		= new $classToLoad( $this->registry );
								$_feedInfo		= $_class->returnFeedInfo();
								
								if( $_feedInfo['key'] )
								{
									if( $_feedInfo['app'] AND !IPSLib::appIsInstalled( $_feedInfo['app'] ) )
									{
										continue;
									}

									$_feedTypes[ $_key ]	= $_feedInfo;
								}
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Now get all feed blocks in IP.Content dir
		//-----------------------------------------

		foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources' ) as $object )
		{
			if( !$object->isDir() AND !$object->isDot() )
			{
				$_key = str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/', '', str_replace( '\\', '/', $object->getPathname() ) );
				
				if( strpos( $_key, '.php' ) !== false )
				{
					$_key			= str_replace( '.php', '', $_key );

					//-----------------------------------------
					// Don't load if we got this feed block from the app already
					//-----------------------------------------

					if( !isset($_feedTypes[ $_key ]) )
					{
						$classToLoad	= IPSLib::loadLibrary( $object->getPathname(), 'feed_' . $_key, 'ccs' );

						if( class_exists( $classToLoad ) )
						{
							$_class 		= new $classToLoad( $this->registry );
							$_feedInfo		= $_class->returnFeedInfo();
							
							if( $_feedInfo['key'] )
							{
								if( $_feedInfo['app'] AND !IPSLib::appIsInstalled( $_feedInfo['app'] ) )
								{
									continue;
								}

								$_feedTypes[ $_key ]	= $_feedInfo;
							}
						}
					}
				}
			}
		}

		return $_feedTypes;
	}

	/**
	 * Get all plugin block configs
	 *
	 * @return	@e array
	 */
	public function getPluginBlockConfigs()
	{
		$_blockTypes = array();

		//-----------------------------------------
		// Get interface
		//-----------------------------------------

		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );/*noLibHook*/

		//-----------------------------------------
		// First, grab all feed blocks in app directories
		//-----------------------------------------

		foreach( IPSLib::getEnabledApplications() as $appDir => $application )
		{
			if( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/content/plugin_blocks' ) )
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/content/plugin_blocks' ) as $object )
				{
					if( $object->isDir() AND !$object->isDot() )
					{
						if( is_file( $object->getPathname() . '/plugin.php' ) )
						{
							$_folder		= str_replace( IPSLib::getAppDir( $appDir ) . '/extensions/content/plugin_blocks/', '', str_replace( '\\', '/', $object->getPathname() ) );
							$classToLoad	= IPSLib::loadLibrary( $object->getPathname() . '/plugin.php', "plugin_" . $_folder, $appDir );
							$_class 		= new $classToLoad( $this->registry );

							if( count($_class->returnPluginInfo()) )
							{
								$_blockTypes[ $_folder ]	= $_class->returnPluginInfo();
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Now get all feed blocks in IP.Content dir
		//-----------------------------------------

		foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin' ) as $object )
		{
			if( $object->isDir() AND !$object->isDot() )
			{
				if( is_file( $object->getPathname() . '/plugin.php' ) )
				{
					$_folder	= str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/', '', str_replace( '\\', '/', $object->getPathname() ) );

					/* http://community.invisionpower.com/tracker/issue-34596-watched-content-block */
					if( $_folder == 'watched_content' )
					{
						continue;
					}

					if( !isset( $_blockTypes[ $_folder ] ) )
					{
						$classToLoad	= IPSLib::loadLibrary( $object->getPathname() . '/plugin.php', "plugin_" . $_folder, 'ccs' );
						$_class 		= new $classToLoad( $this->registry );

						if( count($_class->returnPluginInfo()) )
						{
							$_blockTypes[ $_folder ]	= $_class->returnPluginInfo();
						}
					}
				}
			}
		}

		return $_blockTypes;
	}

	/**
	 * Return a block object
	 *
	 * @param	string		Block key
	 * @param	string		feed|plugin
	 * @return	@e mixed	Object if successful, or false
	 */
	public function getBlockObject( $blockKey, $blockType='feed' )
	{
		//-----------------------------------------
		// Caching
		//-----------------------------------------

		static $blockCache	= array();

		if( isset( $blockCache[ md5( $blockKey . $blockType ) ] ) )
		{
			return $blockCache[ md5( $blockKey . $blockType ) ];
		}
		
		//-----------------------------------------
		// Get interface
		//-----------------------------------------

		if( $blockType == 'feed' )
		{
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );/*noLibHook*/
		}
		else
		{
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );/*noLibHook*/
		}

		$classToLoad	= null;

		foreach( IPSLib::getEnabledApplications() as $appDir => $application )
		{
			if( $blockType == 'feed' )
			{
				$_fileName	= IPSLib::getAppDir( $appDir ) . '/extensions/content/feed_blocks/' . $blockKey . '.php';
			}
			else
			{
				$_fileName	= IPSLib::getAppDir( $appDir ) . '/extensions/content/plugin_blocks/' . $blockKey . '/plugin.php';
			}

			if( is_file( $_fileName ) )
			{
				$classToLoad	= IPSLib::loadLibrary( $_fileName, $blockType == 'feed' ? "feed_" . $blockKey : "plugin_" . $blockKey, $appDir );
				break;
			}
		}

		if( !$classToLoad )
		{
			if( $blockType == 'feed' )
			{
				$_fileName	= IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $blockKey . '.php';
			}
			else
			{
				$_fileName	= IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $blockKey . '/plugin.php';
			}
			
			if( is_file( $_fileName ) )
			{
				$classToLoad	= IPSLib::loadLibrary( $_fileName, $blockType == 'feed' ? "feed_" . $blockKey : "plugin_" . $blockKey, 'ccs' );
			}
		}

		if( $classToLoad )
		{
			$blockCache[ md5( $blockKey . $blockType ) ]	= new $classToLoad( $this->registry );

			return $blockCache[ md5( $blockKey . $blockType ) ];
		}

		$blockCache[ md5( $blockKey . $blockType ) ]	= false;

		return $blockCache[ md5( $blockKey . $blockType ) ];
	}

	/**
	 * Returns array of generic feed content types
	 *
	 * @return	@e array
	 */
	public function getGenericContentTypes()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		return array( 
			array('*', ipsRegistry::getClass('class_localization')->words['btemplate_generic_contentlist'] ),
			array('comments', ipsRegistry::getClass('class_localization')->words['btemplate_generic_comments'] ),
			array('userlist', ipsRegistry::getClass('class_localization')->words['btemplate_generic_userlist'] ),
		);
	}

	/**
	 * Retrieve template tags HTML
	 *
	 * @param	bool	Show special tags (for templates)
	 * @param	bool	Show database tab
	 * @param	bool	Do not load database information
	 * @return	@e string
	 */
	public function getTemplateTags( $showSpecial=false, $tabDatabase=false, $noDatabases=false )
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		//-----------------------------------------
		// Get current blocks
		//-----------------------------------------
		
		$blocks	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'order' => 'block_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$blocks[ intval($r['block_category']) ][]	= $r;
		}
		
		//-----------------------------------------
		// Get databases
		//-----------------------------------------
		
		$databases	= array();

		if( !$noDatabases )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases', 'order' => 'database_name ASC' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$databases[]	= $r;
			}
		}
		
		//-----------------------------------------
		// Get block categories
		//-----------------------------------------
		
		$categories	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}
		
		//-----------------------------------------
		// Return the HTML output
		//-----------------------------------------
		
		return $htmlTemplate->inlineTemplateTags( $categories, $blocks, $databases, $showSpecial, $tabDatabase );
	}

	/**
	 * Retrieve template tags HTML
	 *
	 * @param	string		Block type
	 * @param	mixed		Extra block details
	 * @param	bool		Only show the tab content
	 * @return	@e string
	 */
	public function getBlockTags( $_type, $_extra, $contentOnly=false )
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang', 'admin_blockhelp' ) );
		
		//-----------------------------------------
		// Get tags
		//-----------------------------------------
		
		$tags	= array();
		
		if( is_file( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $_type . '/admin.php' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php', 'adminBlockHelper', 'ccs' );
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $_type . '/admin.php', "adminBlockHelper_" . $_type, 'ccs' );

			$_admin	= new $classToLoad( $this->registry );
			$tags	= $_admin->getTags( $_extra );
		}
		
		//-----------------------------------------
		// Return the HTML output
		//-----------------------------------------
		
		return $contentOnly ? $htmlTemplate->inlineBlockTagsContent( $tags ) : $htmlTemplate->inlineBlockTags( $tags );
	}

	/**
	 * Add extra navigation given a path
	 * 
	 * @param	string	Path
	 * @return	void
	 */
 	public function addNavigation( $path )
 	{
		$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=pages&amp;section=list&amp;do=viewdir&amp;dir=' . urlencode( '/' ), '/' );
		
		if( $path != '/' )
		{
			$navPath	= substr( $path, 1 );
			$pathBits	= explode( '/', $navPath );

			$bitsSoFar	= '';
			
			if( count($pathBits) )
			{
				foreach( $pathBits as $bit )
				{
					$thisPath	= $bitsSoFar . '/' . $bit;
					$bitsSoFar	.= '/' . $bit;
					
					$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=pages&amp;section=list&amp;do=viewdir&amp;dir=' . urlencode( $thisPath ), '/' . $bit );
				}
			}
		}
 	}
}