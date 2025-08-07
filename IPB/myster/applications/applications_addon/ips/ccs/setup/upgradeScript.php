<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class ccs_upgradeScript
{
	/**
	 * Constructor
	 * 
	 * @param	object		Registry object
	 * @return	@e void
	 */
 	public function __construct( ipsRegistry $registry )
 	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
 	}
 	
	/**
	 * Execute selected method
	 *
	 * @return	@e void
	 */
	public function run() 
	{
		//-----------------------------------------
		// Get plugin block templates
		//-----------------------------------------
		
		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/plugin_block_templates.xml' );
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );
		
		//-----------------------------------------
		// Loop over and insert
		//-----------------------------------------
		
		foreach( $xml->fetchElements('template') as $template )
		{
			$_template	= $xml->fetchElementsFromRecord( $template );

			if( $_template['tpb_name'] )
			{
				unset($_template['tpb_id']);
				
				$_exist	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$_template['tpb_name']}'" ) );
				
				if( $_exist['tpb_id'] )
				{
					$this->DB->update( "ccs_template_blocks", $_template, "tpb_id=" . $_exist['tpb_id'] );
				}
				else
				{
					$this->DB->insert( "ccs_template_blocks", $_template );
				}
			}
		}

		//-----------------------------------------
		// Now the feed block templates
		//-----------------------------------------

		$content	= file_get_contents( IPS_ROOT_PATH . 'applications_addon/ips/ccs/xml/feed_block_templates.xml' );

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/functions.php', 'ccsFunctions', 'ccs' );
		$functions		= new $classToLoad( $this->registry );

		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlarchive	= new classXMLArchive();

		//-----------------------------------------
		// Read the XML archive
		//-----------------------------------------
		
		$xmlarchive->readXML( $content );

		foreach( $xmlarchive->asArray() as $fileName => $fileMeta )
		{
			if( $fileName == 'btemplates_data.xml' )
			{
				$templateData = $fileMeta['content'];
			}
			else
			{
				$templateResources[ $fileMeta['path'] ][ $fileMeta['filename'] ] = $fileMeta['content'];
			}
		}

		//-----------------------------------------
		// Import the actual XML
		//-----------------------------------------

		$return = $functions->importBlockTemplates( $templateData, $templateResources, true );

		if( count( $return['success'] ) )
		{
			//-----------------------------------------
			// Recache our static resources
			//-----------------------------------------

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/acpFunctions.php', 'ccsAcpFunctions', 'ccs' );
			$functions		= new $classToLoad( $this->registry );

			$functions->recacheResources();
		}

		//-----------------------------------------
		// Ignore errors here - shouldn't happen with defaults anyways
		//-----------------------------------------

		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$this->registry->output->addMessage( "Default block templates updated..." );			
		return true;
	}
}