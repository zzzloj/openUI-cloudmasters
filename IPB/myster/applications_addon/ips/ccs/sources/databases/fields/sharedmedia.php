<?php

/**
 * <pre>
 * Invision Power Services
 * Shared media field
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		18 Nov 2009
 * @version		$Revision: 8644 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class fields_sharedmedia
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
					array( 'sharedmedia', $this->lang->words['field_type__sharedmedia'] ),
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
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= $field['field_extra'];
		
		if( $type == 'sharedmedia' )
		{
			$display	= $this->lang->words['no_sm_field_selected'];
			
			if( $default )
			{
				$display	= $this->getFieldValue( $field, array( 'field_' . $field['field_id'] => $default ) );
			}
			
			$_html	= $this->registry->output->loadTemplate( 'cp_skin_databases' );
			
			return $_html->sharedMediaField( $field, $default, $display );
		}
		
		return '';
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
		
		if( $type == 'sharedmedia' )
		{
			$display	= $this->lang->words['no_sm_field_selected'];
			
			if( $default )
			{
				$display	= $this->getFieldValue( $field, array( 'field_' . $field['field_id'] => $default ) );
			}
			
			return $this->registry->output->getTemplate('ccs_global')->sharedMediaField( $field, $default, $display );
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
		
		if( $field['field_type'] == 'sharedmedia' )
		{
			//-----------------------------------------
			// Required and no value?
			//-----------------------------------------
			
			$value	= trim( $this->request['field_' . $field['field_id'] ] );

			if( $field['field_required'] AND !trim($value) )
			{
				$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );

				return $value;
			}
			
			//-----------------------------------------
			// Parse and test
			//-----------------------------------------

			$value	= IPSText::getTextClass('bbcode')->preDbParse( $value );

			if ( IPSText::getTextClass('bbcode')->error )
			{
				$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
				
				$this->error	= sprintf( $this->lang->words['dbfield_sm_parseerror'], $field['field_name'], $this->lang->words[ IPSText::getTextClass('bbcode')->error ] );
			}

			if( $field['field_required'] )
			{
				if( !trim($value) )
				{
					$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
				}
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
		//-------------------------------------------
		// If no text, just return
		//-------------------------------------------
		
		if( empty($record['field_' . $field['field_id'] ]) )
		{
			return '';
		}
		
		//-------------------------------------------
		// If truncating, just return a count
		//-------------------------------------------
		
		if( $truncate )
		{
			return sprintf( $this->lang->words['sm_field__count'], substr_count( $record['field_' . $field['field_id'] ], '[sharedmedia=' ) );
		}

		//-------------------------------------------
		// Parse out the field value
		//-------------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 0;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'global';

		return IPSText::getTextClass('bbcode')->parseSingleBbcodes( $record['field_' . $field['field_id'] ], 'display', 'sharedmedia' );
	}
	
	/**
	 * Produce where clause for search queries
	 *
	 * @param	array 		Field data
	 * @param	string		Supplid value
	 * @return	@e string
	 */
	public function getSearchWhere( $field, $search='' )
	{
		/* Cannot effectively search this field */
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
		return $this->getDifferenceReport( $current, $previous );
	}
}