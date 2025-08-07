<?php

/**
 * <pre>
 * Invision Power Services
 * WYSIWYG editor field type abstraction
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class fields_editor
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
	protected $caches;
	protected $cache;
	/**#@-*/
	
	/**
	 * Error string stored from last process
	 *
	 * @var		string
	 */
	protected $error		= '';
	
	/**
	 * Differences library
	 *
	 * @var		object
	 */
	protected $differences;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry
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
	 * Return default field types
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getTypes()
	{
		return array(
					array( 'editor', $this->lang->words['field_type__editor'] ),
					);
	}
	
	/**
	 * Return HTML to display field on ACP form
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getAcpField( $field, $default='' )
	{
		return $this->getPublicField( $field, $default );
	}
	
	/**
	 * Return HTML to display the field on the front-end
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getPublicField( $field, $default='' )
	{
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= $field['field_extra'];
		
		if( $type == 'editor' )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor = new $classToLoad();

			$editor->setIsHtml( $field['field_html'] );
			$editor->setAllowHtml( $field['field_html'] );	

			if( $default )
			{			
				$editor->setContent( $default );
			}

			return $editor->show( 'field_' . $id );
		}
		
		return '';
	}
	
	/**
	 * Get error, if set
	 *
	 * @return	@e mixed
	 */
	public function getError()
	{
		return $this->error ? $this->error : false;
	}
	
	/**
	 * Process the input and return normalized value to store
	 *
	 * @param	array 		Field data
	 * @return	@e string
	 */
	public function processInput( $field )
	{
		$value	= '';
		
		if( $field['field_type'] == 'editor' )
		{
			//-----------------------------------------
			// Set some global bbcode properties
			//-----------------------------------------
			
			IPSText::getTextClass('bbcode')->parse_html			= $field['field_html'] ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_nl2br		= $field['field_html'] ? 0 : 1;
			IPSText::getTextClass('bbcode')->parse_smilies		= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'ccs_database';

			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor = new $classToLoad();
			$editor->setIsHtml( $field['field_html'] );
			$editor->setAllowHtml( $field['field_html'] );		
			$value	= $editor->process( $_POST[ 'field_' . $field['field_id'] ] );

			$value	= IPSText::getTextClass('bbcode')->preDbParse( $value );
			$test	= IPSText::getTextClass('bbcode')->preDisplayParse( $value );
						
			if ( IPSText::getTextClass( 'bbcode' )->error != "" )
			{
				ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_post' ), 'forums' );
				$this->error	= $this->lang->words[ IPSText::getTextClass( 'bbcode' )->error ];
			}
			
			if( $field['field_max_length'] AND strlen(trim($value)) > $field['field_max_length'] )
			{
				$this->error	= sprintf( $this->lang->words['dbfield_too_long'], $field['field_name'] );
			}
			
			if( $field['field_required'] AND !trim($value) )
			{
				$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
			}
			
			return $value;
		}

		return '';
	}
	
	/**
	 * Process input after data has been saved to database.  Returns false on error.
	 *
	 * @param	array 		Field data
	 * @return	@e bool
	 */
	public function postProcessInput( $field, $record_id=0 )
	{
		return true;
	}
	
	/**
	 * Record deletion callback.  Returns false on error.
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @return	@e bool
	 */
	public function postProcessDelete( $field, $record )
	{
		return true;
	}
	
	/**
	 * Process the field and return a display value
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @param	int			Number of characters to truncate at (0 means no truncating)
	 * @return	@e string
	 */
	public function getFieldValue( $field, $record=array(), $truncate=0 )
	{
		$fieldValue	= $record['field_' . $field['field_id'] ];
		
		if( !$fieldValue )
		{
			return '';
		}
				
		if( $field['field_type'] == 'editor' )
		{
			//-----------------------------------------
			// Set a base record URL for [page] bbcode
			//-----------------------------------------

			if( strpos( $fieldValue, '[page]' ) !== false )
			{
				$this->cache->updateCacheWithoutSaving( 'pagination_url', $this->registry->ccsFunctions->returnDatabaseUrl( $field['field_database_id'], 0, $record ) );
			}
			else
			{
				$this->cache->updateCacheWithoutSaving( 'pagination_url', '' );
			}

			//-----------------------------------------
			// Set some global bbcode properties
			//-----------------------------------------
			
			IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass('bbcode')->parsing_section		= 'ccs_database';

			//-----------------------------------------
			// Do we have the member data?
			//-----------------------------------------

			if( $record['member_group_id'] === null AND $record['member_id'] )
			{
				$_record	= IPSMember::load( $record['member_id'] );

				$record['member_group_id']	= $_record['member_group_id'];
				$record['mgroup_others']	= $_record['mgroup_others'];
			}

			IPSText::getTextClass('bbcode')->parsing_mgroup			= $record['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $record['mgroup_others'];

			if( $truncate )
			{
				if( IPSText::mbstrlen( strip_tags( $fieldValue ) ) > $truncate )
				{
					return IPSText::truncate( IPSText::getTextClass('bbcode')->stripAllTags( strip_tags( $fieldValue ) ), $truncate );
				}
				else
				{
					IPSText::getTextClass('bbcode')->parse_html			= $field['field_html'] ? 1 : 0;
					IPSText::getTextClass('bbcode')->parse_nl2br		= $field['field_html'] ? 0 : 1;

					$result	= IPSText::getTextClass('bbcode')->preDisplayParse( $fieldValue );

					$this->cache->updateCacheWithoutSaving( 'pagination_url', '' );

					return $result;
				}
			}
			else
			{
				IPSText::getTextClass('bbcode')->parse_html			= $field['field_html'] ? 1 : 0;
				IPSText::getTextClass('bbcode')->parse_nl2br		= $field['field_html'] ? 0 : 1;

				$result	= IPSText::getTextClass('bbcode')->preDisplayParse( $fieldValue );

				$this->cache->updateCacheWithoutSaving( 'pagination_url', '' );

				return $result;
			}
		}

		return '';
	}
	
	/**
	 * Produce where clause for search queries
	 *
	 * @param	array 		Field data
	 * @param	string		Supplied value
	 * @param	array 		Array of database information
	 * @return	@e string
	 */
	public function getSearchWhere( $field, $search='', $database=array() )
	{
		if( !$search )
		{
			return '';
		}

		if( $field['field_type'] == 'editor' )
		{
			if( count($database) AND is_array($database) AND ( $database['database_field_title'] == 'field_' . $field['field_id'] OR $database['database_field_content'] == 'field_' . $field['field_id'] ) )
			{
				return $this->DB->buildSearchStatement( 'field_' . $field['field_id'], $search, true, false, ipsRegistry::$settings['use_fulltext'] );
			}
			else
			{
				$search	= strtolower($search);
				return $this->DB->buildLower('field_' . $field['field_id'] ) . " LIKE '%{$search}%'";
			}
		}

		return '';
	}
	
	/**
	 * Get a true difference report, showing exact changes
	 *
	 * @param	string		Current text
	 * @param	string		Previous text
	 * @param	@e string
	 */
	public function getDifferenceReport( $current, $previous )
	{
		if( !is_object($this->differences) )
		{
			//-----------------------------------------
			// Get Diff library
			//-----------------------------------------
			
			$classToLoad		= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classDifference.php', 'classDifference' );
			$this->differences	= new $classToLoad();
			$this->differences->method = 'PHP';
		}
		
		$result	= $this->differences->formatDifferenceReport( $this->differences->getDifferences( IPSText::br2nl( $previous ), IPSText::br2nl( $current ), 'unified' ), 'unified', false );

		if( !$result )
		{
			$result	= nl2br( str_replace( "\t", "&nbsp; &nbsp; ", IPSText::htmlspecialchars( IPSText::br2nl( $previous ) ) ) );
		}

		return $result;
	}
	
	/**
	 * Compare two versions of a particular field and return an HTML diff report
	 *
	 * @param	array 		Field data
	 * @param	string		Current data in the field
	 * @param	string		Previous data in the field
	 * @return	@e string
	 */
	public function compareRevision( $field, $current, $previous )
	{
		if( $field['field_type'] == 'editor' )
		{
			return $this->getDifferenceReport( $current, $previous );
		}

		return '';
	}
}