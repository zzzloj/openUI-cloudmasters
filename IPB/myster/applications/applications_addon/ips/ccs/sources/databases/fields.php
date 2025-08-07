<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS fields abstraction layer for database management
 * Last Updated: $Date: 2012-02-10 20:05:52 -0500 (Fri, 10 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10288 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class ccs_database_fields
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
	 * Default field types
	 *
	 * @var		array 		Field types
	 */
	protected $fieldTypes	= array();

	/**
	 * Default field validators
	 *
	 * @var		array 		Field validators
	 */
	protected $fieldValidators	= array();
	
	/**
	 * Field handlers
	 *
	 * @var		array 		Field type to object mapping
	 */
	protected $handlers		= array();
	
	/**
	 * Value caches - we cache the parsed value and return cache when re-requested
	 *
	 * @var		array 		Cached parsed values
	 */
	protected $_cache		= array();
	
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
	 * Check a field key to prevent issues.  Centralized as this can be called from multiple areas.
	 *
	 * @param	int		Database ID
	 * @param	string	Field key
	 * @return	@e bool
	 */
	public function checkFieldKey( $database, $key )
	{
		$field	= $this->DB->buildAndFetch( array( 'select' => 'field_id', 'from' => 'ccs_database_fields', 'where' => "field_database_id={$database} AND field_key='{$key}'" ) );

		if( $field['field_id'] )
		{
			return false;
		}

		if( $this->DB->checkForField( $key, 'ccs_database_categories' ) )
		{
			return false;
		}

		$protected	= array( 'record_link', 'url', 'title', 'content', '_isRead', '_database', 'category_link', 'primary_id_field', 'member_id', 'record_saved', 'record_updated',
							'post_key', 'rating_real', 'rating_hits', 'rating_value', 'category_id', 'record_locked', 'record_comments', 'record_views', 'record_approved', 
							'record_pinned', 'record_dynamic_furl', 'record_static_furl', 'record_meta_keywords', 'record_meta_description', 'record_template', 'record_topicid',
							'record_comments_queued' );

		if( in_array( $key, $protected ) )
		{
			return false;
		}

		if( preg_match( '/^field_\d+$/', $key ) )
		{
			return false;
		}

		return true;
	}
	
	/**
	 * Retrieve available field types.  Abstracted for plugin functionality.
	 *
	 * @return	@e array
	 */
	public function getTypes()
	{
		if( count($this->fieldTypes) )
		{
			return $this->fieldTypes;
		}
		else
		{
			$_fields	= array();
			
			try
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/databases/fields/' ) as $dir )
				{
					if( ! $dir->isDot() && $dir->isFile() && strpos($dir->getFilename(), '.php') !== FALSE )
					{
						//-----------------------------------------
						// PHP 5.1 fix
						// @see http://community.invisionpower.com/tracker/issue-19009-fatal-error-call-to-undefined-method-directoryiteratorgetbasename/
						//-----------------------------------------
						
						if( method_exists( $dir, 'getBasename' ) )
						{
							$className	= 'fields_' . $dir->getBasename('.php');
						}
						else
						{
							$filename	= $dir->getFilename();
							$filename	= substr( $filename, 0, strrpos( $filename, '.' ) );
							
							$className	= 'fields_' . $filename;
						}

						if( strpos( $className, '.' ) === false )
						{
							$className	= IPSLib::loadLibrary( $dir->getPathname(), $className, 'ccs' );
							
							if( class_exists($className) )
							{
								$fieldClass	= new $className( $this->registry );
								$_fields	= array_merge( $_fields, $fieldClass->getTypes() );
							}
						}
					}
				}
			} catch ( Exception $e ) {}
			
			$this->fieldTypes	= $_fields;
			
			return $this->fieldTypes;
		}
	}
	
	/**
	 * Retrieve all available default validators.
	 *
	 * @return	@e array
	 */
	public function getValidators()
	{
		if( count($this->fieldValidators) )
		{
			return $this->fieldValidators;
		}
		else
		{
			try
			{
				foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/databases/validators/' ) as $dir )
				{
					if( ! $dir->isDot() && $dir->isFile() && strpos($dir->getFilename(), '.php') !== FALSE )
					{
						$CONFIG	= array();
						
						require_once( $dir->getPathname() );/*noLibHook*/
						
						if( is_array($CONFIG) AND count($CONFIG) )
						{
							$this->fieldValidators[ $CONFIG['key'] ]	= $CONFIG;
						}
					}
				}
			} catch ( Exception $e ) {}

			return $this->fieldValidators;
		}
	}
	
	/**
	 * Get the object references for the field types
	 *
	 * @return	@e bool
	 */
	public function getHandlers()
	{
		if( count($this->handlers) )
		{
			return true;
		}
		else
		{
			try
			{
				$_types		= array();
				
				foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/databases/fields/' ) as $dir )
				{
					$_fields	= array();
					
					if( ! $dir->isDot() && $dir->isFile() && strpos($dir->getFilename(), '.php') !== FALSE )
					{
						if( method_exists( $dir, 'getBasename' ) )
						{
							$className	= 'fields_' . $dir->getBasename('.php');
						}
						else
						{
							$filename	= $dir->getFilename();
							$filename	= substr( $filename, 0, strrpos( $filename, '.' ) );
							
							$className	= 'fields_' . $filename;
						}

						if( strpos( $className, '.' ) === false )
						{
							$className	= IPSLib::loadLibrary( $dir->getPathname(), $className, 'ccs' );

							if( class_exists($className) )
							{
								$fieldClass	= new $className( $this->registry );
								$_fields	= $fieldClass->getTypes();
								$_types		= array_merge( $_types, $_fields );

								if( is_array($_fields) AND count($_fields) )
								{
									foreach( $_fields as $_field )
									{
										$this->handlers[ $_field[0] ]	= $fieldClass;
									}
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {}

			$this->fieldTypes	= $_types;
			
			return true;
		}
	}
	
	/**
	 * Return the HTML to show for the ACP form field
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getAcpField( $field, $default='' )
	{
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			return $this->handlers[ $field['field_type'] ]->getAcpField( $field, $default );
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Return the HTML to show for the public form field
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getPublicField( $field, $default='' )
	{
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			return $this->handlers[ $field['field_type'] ]->getPublicField( $field, $default );
		}
		else
		{
			return '';
		}
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
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			$_return	= $this->handlers[ $field['field_type'] ]->processInput( $field );
			
			if( $error = $this->handlers[ $field['field_type'] ]->getError() )
			{
				$this->error	= $error;
			}
			
			//-----------------------------------------
			// Validate?
			//-----------------------------------------
			
			if( !$this->error AND $_return AND $field['field_validator'] )
			{
				$validators	= $this->getValidators();
				
				$_validator		= explode( ';_;', $field['field_validator'] );
				$thisValidator	= $validators[ $_validator[0] ];
				
				if( $_validator[0] == 'custom' AND $_validator[1] )
				{
					if( !preg_match( $_validator[1], $_return ) )
					{
						$this->error	= $_validator[2] ? $_validator[2] : sprintf( $thisValidator['error'], $field['field_name'] );
					}
				}
				else
				{
					if( $thisValidator['regex'] )
					{
						if( !preg_match( $thisValidator['regex'], $_return ) )
						{
							$this->error	= sprintf( $thisValidator['error'], $field['field_name'] );
						}
					}
					else if( $thisValidator['callback'] )
					{
						if( !call_user_func( $thisValidator['callback'], $_return ) )
						{
							$this->error	= sprintf( $thisValidator['error'], $field['field_name'] );
						}
					}
				}
			}

			return $_return;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Process a field after record has been saved.  Returns false on error
	 *
	 * @param	array 		Field data
	 * @param	int			Record ID
	 * @return	@e bool
	 */
	public function postProcessInput( $field, $record_id )
	{
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			return $this->handlers[ $field['field_type'] ]->postProcessInput( $field, $record_id );
		}
		else
		{
			return false;
		}
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
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			return $this->handlers[ $field['field_type'] ]->postProcessDelete( $field, $record );
		}
		else
		{
			return false;
		}
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
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			$_hash	= md5( implode( ',', array_values($field) ) . implode( ',', array_values($record) ) . $truncate );
			
			if( !isset($this->_cache[ $_hash ]) )
			{
				$this->_cache[ $_hash ]	= $this->handlers[ $field['field_type'] ]->getFieldValue( $field, $record, $truncate );
			}
			
			return $this->_cache[ $_hash ];
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Process the field and return a display value when previewing.  If field does not define this method, getFieldValue() is called automatically.
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @param	int			Number of characters to truncate at (0 means no truncating)
	 * @see		getFieldValue()
	 * @return	@e string
	 */
	public function getFieldValuePreview( $field, $record=array(), $truncate=0 )
	{
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			if( method_exists( $this->handlers[ $field['field_type'] ], 'getFieldValuePreview' ) )
			{
				return $this->handlers[ $field['field_type'] ]->getFieldValuePreview( $field, $record, $truncate );
			}
			else
			{
				return $this->handlers[ $field['field_type'] ]->getFieldValue( $field, $record, $truncate );
			}
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Get search 'where' clause
	 *
	 * @param	array 		Field data
	 * @param	string		Search string
	 * @param	array 		Array of database information
	 * @return	@e string
	 */
	public function getSearchWhere( $fields, $search='', $database=array() )
	{
		$search	= trim($search);
		
		if( !$search )
		{
			return '';
		}
		
		$_where	= array();
		
		foreach( $fields as $_field )
		{
			$_thisSearch	= $this->handlers[ $_field['field_type'] ]->getSearchWhere( $_field, $search, $database );
			
			if( $_thisSearch )
			{
				$_where[]	= $_thisSearch;
			}
		}

		if( count($_where) )
		{
			return implode( ' OR ', $_where );
		}
		else
		{
			return '1=1';
		}
	}
	
	/**
	 * Compare two versions of a particular field
	 *
	 * @param	array 		Field data
	 * @param	string		Current data in the field
	 * @param	string		Previous data in the field
	 * @return	@e string
	 */
	public function compareRevision( $field, $current, $previous )
	{
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			$_return	= $this->handlers[ $field['field_type'] ]->compareRevision( $field, $current, $previous );
			
			if( $error = $this->handlers[ $field['field_type'] ]->getError() )
			{
				$this->error	= $error;
			}
			
			return $_return;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Allow field handlers to modify data to be saved
	 *
	 * @param	array 		Field data
	 * @return	@e array
	 */
	public function preSaveField( $field )
	{
		$this->getHandlers();
		
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			if( method_exists( $this->handlers[ $field['field_type'] ], 'preSaveField' ) )
			{
				return $this->handlers[ $field['field_type'] ]->preSaveField( $field );
			}
		}
		
		return $field;
	}
	
	/**
	 * On error callback - called if a field has an error, allowing other fields to cleanup.  Returns the field data.
	 *
	 * @param	array 		Field data
	 * @return	@e array
	 */
	public function onErrorCallback( $field )
	{
		$this->getHandlers();
		
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			if( method_exists( $this->handlers[ $field['field_type'] ], 'onErrorCallback' ) )
			{
				return $this->handlers[ $field['field_type'] ]->onErrorCallback( $field );
			}
		}
		
		return $field;
	}

	/**
	 * On preview callback - called if the request is a preview.  Returns the field data.
	 *
	 * @param	array 		Field data
	 * @return	@e array
	 */
	public function onPreviewCallback( $field )
	{
		$this->getHandlers();
		
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			if( method_exists( $this->handlers[ $field['field_type'] ], 'onPreviewCallback' ) )
			{
				return $this->handlers[ $field['field_type'] ]->onPreviewCallback( $field );
			}
		}
		
		return $field;
	}

	/**
	 * Callback method used by fields to "clean up" when the field is being deleted
	 *
	 * @param	array 		Database data
	 * @param	array 		Field data
	 * @return	@e array
	 */
	public function preDeleteField( $database, $field )
	{
		$this->getHandlers();
		
		if( isset( $this->handlers[ $field['field_type'] ] ) )
		{
			if( method_exists( $this->handlers[ $field['field_type'] ], 'preDeleteField' ) )
			{
				return $this->handlers[ $field['field_type'] ]->preDeleteField( $database, $field );
			}
		}
		
		return $field;
	}
}