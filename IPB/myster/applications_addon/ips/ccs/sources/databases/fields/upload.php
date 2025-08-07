<?php

/**
 * <pre>
 * Invision Power Services
 * Single file upload field
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		15th Feb 2010
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class fields_upload
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
	 * Filename (stored in case another field errors)
	 *
	 * @var		string
	 */
	protected $_tmpName		= '';
	
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
					array( 'upload', $this->lang->words['field_type__upload'] ),
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
		
		if( $type == 'upload' )
		{
			$_return	= $this->registry->output->formUpload( 'field_' . $id );
			
			if( $this->_tmpName )
			{
				$_return	.= "<input type='hidden' name='field_{$id}_temp' value='{$this->_tmpName}' />";
			}
			else if( $default AND file_exists( $this->settings['upload_dir'] . "/" . $default ) )
			{
				$_return	.= '<br /> ' . $this->registry->output->formCheckbox( 'field_' . $id . '_remove' ) . '&nbsp;&nbsp;' . $this->lang->words['rm_uf__file'];
				$_return	.= "<div class='desctext'>{$this->lang->words['uploadfield__already']}</div>";
			}
			
			return $_return;
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
		
		if( $type == 'upload' )
		{
			$_return	= "<input type='file' name='field_{$id}' id='field_{$id}' size='30' />";
			
			if( $this->_tmpName )
			{
				$_return	.= "<input type='hidden' name='field_{$id}_temp' value='{$this->_tmpName}' />";
			}
			else if( $default AND file_exists( $this->settings['upload_dir'] . "/" . $default ) )
			{
				$_return	.= " <input type='checkbox' class='input_check' value='1' name='field_{$id}_remove' />&nbsp;<span class='desc'>" . $this->lang->words['rm_uf__file'] . "</span>";
				$_return	.= "<div class='desc'>{$this->lang->words['uploadfield__already']}</div>";
			}
			
			return $_return;
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
		
		if( $this->request['record'] )
		{
			$_record	= $this->DB->buildAndFetch( array( 'select' => 'field_' . $field['field_id'], 'from' => 'ccs_custom_database_' . $field['field_database_id'], 'where' => 'primary_id_field=' . intval($this->request['record']) ) );
			
			$value		= $_record['field_' . $field['field_id'] ];
		}

		if( $field['field_type'] == 'upload' )
		{
			//-----------------------------------------
			// If we are editing, remove any existing file
			//-----------------------------------------

			if( $this->request['field_' . $field['field_id'] . '_remove'] )
			{
				if( $_record['field_' . $field['field_id'] ] )
				{
					$this->postProcessDelete( $field, $_record );
					
					$value	= '';
				}
			}

			//-----------------------------------------
			// If we previewed and have an existing file, set that
			//-----------------------------------------

			if( $this->request['field_' . $field['field_id'] . '_temp'] )
			{
				if( $_record['field_' . $field['field_id'] ] )
				{
					$this->postProcessDelete( $field, $_record );
				}
				
				$value	= $this->request['field_' . $field['field_id'] . '_temp'];
			}
				
			//-----------------------------------------
			// Load the library
			//-----------------------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
			$upload			= new $classToLoad();

			//-----------------------------------------
			// Set up the variables
			//-----------------------------------------

			$upload->out_file_name		= md5( uniqid( microtime(), true ) );
			$upload->out_file_dir		= $this->settings['upload_dir'];
			$upload->upload_form_field	= 'field_' . $field['field_id'];
			
			if( $field['field_extra'] )
			{
				$upload->allowed_file_ext	= explode( ',', $field['field_extra'] );
			}
			else
			{
				$upload->check_file_ext		= false;
			}

			//-----------------------------------------
			// Upload...
			//-----------------------------------------
			
			$upload->process();
			
			//-----------------------------------------
			// Error?
			//-----------------------------------------
			
			if ( $upload->error_no )
			{
				if( $upload->error_no > 1 OR ( $field['field_required'] AND !$value ) )
				{
					$this->error	= sprintf( $this->lang->words['field_upload_error__' . $upload->error_no ], $field['field_name'] );
				}

				return $value;
			}
			else
			{
				//-----------------------------------------
				// Successful upload, delete old file
				//-----------------------------------------
				
				if( $this->request['record'] )
				{
					$this->postProcessDelete( $field, $_record );
				}
				
				if( $value )
				{
					$this->postProcessDelete( $field, array( 'field_' . $field['field_id'] => $value ) );
				}
			}
	
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$this->_tmpName	= $upload->parsed_file_name;
			
			return $upload->parsed_file_name;
		}

		return $value;
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
		//-----------------------------------------
		// Delete the file
		//-----------------------------------------
		
		if( $record['field_' . $field['field_id'] ] )
		{
			if( is_file( $this->settings['upload_dir'] . "/" . $record['field_' . $field['field_id'] ] ) )
			{
				return @unlink( $this->settings['upload_dir'] . "/" . $record['field_' . $field['field_id'] ] );
			}
		}
		
		return true;
	}
	
	/**
	 * Callback when a field issues an error
	 *
	 * @param	array 		Field data
	 * @return	@e bool
	 * @note	Here we will simply return, because the form will be redisplayed and we don't want to force the user to reupload their file
	 */
	public function onErrorCallback( $field )
	{
		//-----------------------------------------
		// Delete the file
		//-----------------------------------------
		
		if( $this->_tmpName )
		{
			if( is_file( $this->settings['upload_dir'] . "/" . $this->_tmpName ) )
			{
				//$this->_tmpName	= '';

				//return @unlink( $this->settings['upload_dir'] . "/" . $this->_tmpName );
			}
		}
		
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
				
		if( $field['field_type'] == 'upload' )
		{
			return $this->settings['upload_url'] . "/" . $fieldValue;
		}

		return '';
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
		if( !$search )
		{
			return '';
		}

		if( $field['field_type'] == 'upload' )
		{
			if( $search )
			{
				return 'field_' . $field['field_id'] . " LIKE '%{$search}%'";
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
		if( $field['field_type'] == 'upload' )
		{
			$key	= 'field_' . $field['field_id'];
			return $current == $previous ? $this->getFieldValue( $field, array( $key => $current ) ) : "<ins>" . $this->getFieldValue( $field, array( $key => $current ) ) . "</ins> <del>" . $this->getFieldValue( $field, array( $key => $previous ) ) . "</del>";
		}

		return '';
	}

	/**
	 * Field deletion callback.
	 *
	 * @param	array 		Database data
	 * @param	array		Field data
	 * @return	@e bool
	 */
	public function preDeleteField( $database, $field )
	{
		//-----------------------------------------
		// Loop over records
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'field_' . $field['field_id'], 'from' => $database['database_database'] ) );
		$this->DB->execute();

		while( $record = $this->DB->fetch() )
		{
			if( is_file( $this->settings['upload_dir'] . "/" . $record['field_' . $field['field_id'] ] ) )
			{
				@unlink( $this->settings['upload_dir'] . "/" . $record['field_' . $field['field_id'] ] );
			}
		}
		
		return true;
	}
}