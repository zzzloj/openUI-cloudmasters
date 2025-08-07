<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Downloads - Items Extension
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		26th January 2010
 * @version		$Revision: 10721 $
 */

class items_downloads
{
	protected $fileCache = array();

	/**
	 * Get Package Image
	 *
	 * @param	string	App
	 * @param	string	Item type
	 * @param	mixed	Item ID
	 * @return	string	URL to image
	 */
	public function getPackageImage( $app, $type, $id )
	{
		if ( $type != 'file' )
		{
			return NULL;
		}
		
		try
		{
			ipsRegistry::getClass('idmFunctions');
		}
		catch ( Exception $e )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'downloads' ) . "/sources/classes/functions.php", 'downloadsFunctions', 'downloads' );
			ipsRegistry::setClass( 'idmFunctions', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		if ( !array_key_exists( $id, $this->fileCache ) )
		{
			$this->fileCache[$id] = ipsRegistry::getClass('idmFunctions')->returnScreenshotUrl( $id );
		}
								
		return $this->fileCache[$id];
	}

	/**
	 * Get item types
	 *
	 * @return	array	Items this application provides
	 */
	public function getItems()
	{
		$return['file'] = "File";
		return $return;
	}
	
	/**
	 * Init HTML
	 * Called before any form_* methods so that the skin_cp can be loaded
	 */
	public function init_html()
	{
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_idm_nexus', 'downloads' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );
	}
	
	/**
	 * Add Item
	 *
	 * @param	invoice	Invoice object
	 * @return	string	HTML to display
	 */
	public function form_file( $invoice )
	{
		//-----------------------------------------
		// How many paid files do we have?
		//-----------------------------------------
	
		$files = array();
		$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'downloads_files', 'where' => 'file_cost > 0' ) );
		if ( !$count['count'] )
		{
			ipsRegistry::getClass('output')->showError( ipsRegistry::getClass('class_localization')->words['no_paid_files_acp'], 12345.1 );
		}
		elseif ( $count['count'] < 20 )
		{
			ipsRegistry::DB()->build( array( 'select' => 'file_id, file_name', 'from' => 'downloads_files', 'where' => 'file_cost > 0' ) );
			ipsRegistry::DB()->execute();
			while ( $r = ipsRegistry::DB()->fetch() )
			{
				$files[] = array( $r['file_id'], $r['file_name'] );
			}
		}
	
		return $this->html->add( $invoice->id, $files );
	}
	
	/**
	 * Save Item
	 *
	 * @param	invoice	Invoice object
	 * @return	array	Data to pass to invoiceModel::addItem without 'app' or 'type'
	 */
	public function save_file( $invoice )
	{
		if ( ipsRegistry::$request['file_id'] )
		{
			$id = intval( ipsRegistry::$request['file_id'] );
			$file = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => 'file_id='.$id ) );
		}
		else
		{
			$name = ipsRegistry::DB()->addSlashes( ipsRegistry::$request['file_name'] );
			$file = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => "file_name='{$name}'" ) );
		}
		
		if ( !$file['file_id'] )
		{
			ipsRegistry::getClass('output')->showError( ipsRegistry::getClass('class_localization')->words['couldnot_locatepaid'], 12345.2 );
		}
	
		return array(
			'act'		=> 'new',
			'cost'		=> $file['file_cost'],
			'itemName'	=> $file['file_name'],
			'physical'	=> FALSE,
			'itemID'	=> $file['file_id'],
			'itemURI'	=> "app=downloads&module=display&section=file&id={$file['file_id']}",
			'payTo'		=> $file['file_submitter'],
			'commission'=> ipsRegistry::$settings['idm_nexus_percent'],
			);
	}

}