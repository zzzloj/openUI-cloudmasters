<?php

/**
* Blog This Module for IP.Content
*
* @package		IP.Content
* @author		bfarber
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_ccs implements iBlogThis
{
	/**
	 * Incoming data (e.g. request data)
	 *
	 * @var	array
	 */
	protected $_incoming	= array();

	/**
	 * Database data
	 *
	 * @var	array
	 */
	protected $database		= array();

	/**
	 * CONSTRUCTOR
	 *
	 * @param	object	$registry
	 * @param	string	Application
	 * @param	array 	Array of existing incoming data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() )
	{
		if ( ! $this->registry )
		{
			/* Make registry objects */
			$this->registry		=  $registry;
			$this->DB			=  $this->registry->DB();
			$this->settings		=& $this->registry->fetchSettings();
			$this->request		=& $this->registry->fetchRequest();
			$this->lang			=  $this->registry->getClass('class_localization');
			$this->member		=  $this->registry->member();
			$this->memberData	=& $this->registry->member()->fetchMemberData();
			$this->cache		=  $this->registry->cache();
			$this->caches		=& $this->registry->cache()->fetchCaches();
		}
		
		$this->_incoming	= $incoming;
		$this->database		= NULL;

		//-----------------------------------------
		// Find the database
		//-----------------------------------------

		if ( isset( $this->_incoming['id1'] ) )
		{
			$databaseCache	= $this->cache->getCache('ccs_databases');

			if ( isset( $databaseCache[ $this->_incoming['id1'] ] ) )
			{
				$this->database	= $databaseCache[ $this->_incoming['id1'] ];
			}
		}

		//-----------------------------------------
		// Get our functions library
		//-----------------------------------------

		if( !$this->registry->isClassLoaded( 'ccsFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$this->registry->setClass( 'ccsFunctions', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Check permission
	 *
	 * @return boolean
	 */
	public function checkPermission()
	{
		if ( empty( $this->database ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Check view permissions
		//-----------------------------------------

		if ( $this->database['perm_2'] != '*' )
		{
			$permCheck = array_intersect_assoc( explode( ',', IPSText::cleanPermString( $this->database['perm_2'] ) ), $this->member->perm_id_array );

			if ( empty( $permCheck ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Returns the data for the items
	 * Data should be post textarea ready
	 *
	 * @return	array( title / content )
	 */
	public function fetchData()
	{
		$return	= array( 'title' => '', 'content' => '', 'topicData' => array() );
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if ( ! $this->checkPermission() )
		{
			return $return;
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------

		$article	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->database['database_database'], 'where' => 'primary_id_field=' . intval( $this->_incoming['id2'] ) ) );

		if ( $article['primary_id_field'] )
		{
			$return['title']	= $article[ $this->database['database_field_title'] ];
			$author				= IPSMember::load( $article['member_id'] );
			$author				= $author['members_display_name'];
			$date				= $article['record_saved'];
			$content			= $article[ $this->database['database_field_content'] ];
			
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$ccsFunctions		= new $classToLoad( $this->registry );
			$url				= $ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $article );
			
			IPSText::getTextClass('bbcode')->parsing_section		= 'ccs_database';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$return['title']		= $this->lang->words['bt_from'] . ' ' . $return['title'];
			$return['content']		= IPSText::raw2form( trim( "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $author ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $date, 'LONG', 1 ) ) . "' timestamp='{$date}']<br />{$content}<br />" . "[/quote]" ) . "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$return['title']}[/url]<br />" );
		}
		
		return $return;
	}
	
	/**
	 * Get IDs
	 *
	 * @param	string	URL
	 * @param	mixed	Friendly URL regex string, or null
	 * @return	array	IDs
	 */
	public function getIds( $url, $furlRegex=NULL )
	{
		//-----------------------------------------
		// If we already have the ID, easy query
		//-----------------------------------------
		
		if ( is_array( $url ) )
		{
			$recordId	= $url['record'];
			$page		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_id=" . intval( $url['id'] ) ) );
		}
		else
		{
			//-----------------------------------------
			// Get the page data
			//-----------------------------------------
		
			$url			= str_replace( '/page/', '', $url );

			$folderName		= IPSText::parseCleanValue( $this->registry->ccsFunctions->getFolder( $url ) );
			$pageName		= IPSText::parseCleanValue( $this->registry->ccsFunctions->getPageName( $url ) );
			$databaseString	= $this->registry->ccsFunctions->getDatabaseFurlString( $url );
			
			//-----------------------------------------
			// Sort out query where clause
			//-----------------------------------------
			
			$where	= array();

			if( $pageName )
			{
				$where[]	= "page_seo_name='{$pageName}'";
				$where[]	= "page_folder='{$folderName}'";
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

			$page = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => implode( ' AND ', $where ) ) );
		}

		//-----------------------------------------
		// No data?  Just return
		//-----------------------------------------

		if ( empty( $page ) )
		{
			return array( 1 => 0, 2 => 0 );
		}

		//-----------------------------------------
		// Articles?
		//-----------------------------------------

		if ( strstr( $page['page_content'], '{parse articles}' ) )
		{
			foreach ( $this->cache->getCache('ccs_databases') as $dbid => $_database )
			{
				if ( $_database['database_is_articles'] )
				{
					return array( 1 => $dbid, 2 => $this->_getRecordId( $databaseString, $_database ) );
				}
			}
		}

		//-----------------------------------------
		// Embedded database?
		//-----------------------------------------

		preg_match( '/{parse database="(.+?)"}/', $page['page_content'], $matches );

		if ( isset( $matches[1] ) )
		{
			foreach ( $this->cache->getCache('ccs_databases') as $dbid => $_database )
			{
				if ( $_database['database_key'] == $matches[1] )
				{

					return array( 1 => $dbid, 2 => $this->_getRecordId( $databaseString, $_database ) );
				}
			}
		}
		
		return array( 1 => 0, 2 => 0 );
	}

	/**
	 * Get the record ID
	 *
	 * @param	string	Database FURL string
	 * @param	array 	Database info
	 * @return	@e int
	 */
	protected function _getRecordId( $databaseString, $_database )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$_bits		= explode( '/', $databaseString );
		$recordId	= 0;

		if( count($_bits) )
		{
			$_last	= array_pop($_bits);
			
			//-----------------------------------------
			// Does last element appear to be a record?
			//-----------------------------------------
			
			if( preg_match( "/.*?\-r(\d+)($|#|&|\?)/", $_last, $matches ) )
			{
				$recordId	= $matches[1];
			}
			else
			{
				$record		= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> $_database['database_database'], 
															'where'		=> "record_static_furl='" . $this->DB->addSlashes( $_last ) . "'",
													)		);

				$recordId	= $record['primary_id_field'];
			}
		}

		return $recordId;
	}
}