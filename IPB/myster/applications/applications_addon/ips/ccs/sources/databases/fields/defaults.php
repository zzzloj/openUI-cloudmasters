<?php

/**
 * <pre>
 * Invision Power Services
 * Default field types abstraction
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

class fields_defaults
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
					array( 'input', $this->lang->words['field_type__input'] ),
					array( 'textarea', $this->lang->words['field_type__textarea'] ),
					array( 'checkbox', $this->lang->words['field_type__checkbox'] ),
					array( 'radio', $this->lang->words['field_type__radio'] ),
					array( 'select', $this->lang->words['field_type__select'] ),
					array( 'multiselect', $this->lang->words['field_type__multiselect'] ),
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
		$types	= $this->getTypes();
		
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= trim($field['field_extra']);
		
		foreach( $types as $_type )
		{
			if( $type == $_type[0] )
			{
				switch( $type )
				{
					case 'input':
						if( $field['field_html'] )
						{
							$default	= IPSText::htmlspecialchars( $default );
						}
						
						return $this->registry->output->formInput( 'field_' . $id, $default );
					break;
					
					case 'textarea':
						if( $field['field_html'] )
						{
							$default	= IPSText::htmlspecialchars( $default );
						}
						else
						{
							$default	= IPSText::br2nl( $default );
						}

						return $this->registry->output->formTextarea( 'field_' . $id, $default, 40, 5, '', '', 'normal' );
					break;
					
					case 'checkbox':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_default	= explode( ',', IPSText::cleanPermString( $default ) );
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							
							$_return[]	= $this->registry->output->formCheckbox( 'field_' . $id . '[]', in_array( $key, $_default ) ? true : false, $key, 'field_' . $id . '_' . $key ) . " <label for='field_{$id}_{$key}' class='normal-label'>{$value}</label>";
						}
						
						return implode( '<br />', $_return );
					break;
					
					case 'radio':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							
							$checked	= $key == $default ? "checked='checked'" : '';
							$_return[]	= "<input type='radio' name='field_{$id}' value='{$key}' id='field_{$id}_{$key}' {$checked} /> <label for='field_{$id}_{$key}' class='normal-label'>{$value}</label>";
						}
						
						return implode( '<br />', $_return );
					break;
					
					case 'select':
						if( !$options )
						{
							return '';
						}
						
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_list		= array();
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							$list[]	= array( $key, $value );
						}
						
						return $this->registry->output->formDropdown( 'field_' . $id, $list, $default );
					break;
					
					case 'multiselect':
						if( !$options )
						{
							return '';
						}
						
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_default	= explode( ',', IPSText::cleanPermString( $default ) );
						$_list		= array();
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							$list[]	= array( $key, $value );
						}
						
						return $this->registry->output->formMultiDropdown( 'field_' . $id . '[]', $list, $_default, 5, 'field_' . $id );
					break;
				}
			}
		}
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
		$types	= $this->getTypes();
		
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= trim($field['field_extra']);
		
		foreach( $types as $_type )
		{
			if( $type == $_type[0] )
			{
				switch( $type )
				{
					case 'input':
						if( $field['field_html'] )
						{
							$default	= IPSText::htmlspecialchars( $default );
						}
						
						return "<input type='text' name='field_{$id}' id='field_{$id}' value='{$default}' class='input_text' size='50' />";
					break;
					
					case 'textarea':
						if( $field['field_html'] )
						{
							$default	= IPSText::htmlspecialchars( $default );
						}
						else
						{
							$default	= IPSText::br2nl( $default );
						}
						
						return "<textarea name='field_{$id}' id='field_{$id}' wrap='soft' class='input_text' cols='50' rows='5'>" . $default . "</textarea>";
					break;
					
					case 'checkbox':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_default	= is_array($default) ? $default : explode( ',', IPSText::cleanPermString( $default ) );
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							
							$_checked	= in_array( $key, $_default ) ? "checked='checked'" : '';
							$_return[]	= "<input type='checkbox' class='input_check' name='field_{$id}[]' id='field_{$id}_{$key}' value='{$key}' {$_checked} /> <label for='field_{$id}_{$key}' class='normal-label'>{$value}</label>";
						}
						
						return implode( '<br />', $_return );
					break;
					
					case 'radio':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							
							$checked	= $key == $default ? "checked='checked'" : '';
							$_return[]	= "<input class='input_radio' type='radio' name='field_{$id}' value='{$key}' id='field_{$id}_{$key}' {$checked} /> <label for='field_{$id}_{$key}' class='normal-label'>{$value}</label>";
						}
						
						return implode( '<br />', $_return );
					break;
					
					case 'select':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_list		= array();
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							$list[]	= array( $key, $value );
						}
						
						$_html	= "<select name='field_{$id}' id='field_{$id}'>";
						
						foreach( $list as $_items )
						{
							$_selected	= '';
							
							if( $_items[0] == $default )
							{
								$_selected	= "selected='selected'";
							}
							
							$_html	.= "<option value='{$_items[0]}'{$_selected}>{$_items[1]}</option>";
						}
						
						$_html	.= "</select>";
						
						return $_html;
					break;
					
					case 'multiselect':
						if( !$options )
						{
							return '';
						}
						
						$_return	= array();
						$_options	= explode( "\n", str_replace( "\r", '', $options ) );
						$_default	= is_array($default) ? $default : explode( ',', IPSText::cleanPermString( $default ) );
						$_list		= array();
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							$list[]	= array( $key, $value );
						}
						
						$_html	= "<select name='field_{$id}[]' id='field_{$id}' multiple='multiple' size='5'>";
						
						foreach( $list as $_items )
						{
							$_selected	= '';
							
							if( in_array( $_items[0], $_default ) )
							{
								$_selected	= "selected='selected'";
							}
							
							$_html	.= "<option value='{$_items[0]}'{$_selected}>{$_items[1]}</option>";
						}
						
						$_html	.= "</select>";
						
						return $_html;
					break;
				}
			}
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
	 * Apply specialized formatting to input fields
	 *
	 * @param	string		Field value
	 * @param	string		Formatting options (comma sep)
	 * @return	@e string
	 */
	protected function _applyInputFormatting( $value, $format )
	{
		//-----------------------------------------
		// Just return value if not formatting
		//-----------------------------------------

		if( !$format )
		{
			return $value;
		}
		
		//-----------------------------------------
		// Get options
		//-----------------------------------------
		
		$_formatOpts	= explode( ',', $format );
		
		//-----------------------------------------
		// Use MB functions?
		//-----------------------------------------
		
		$_useMb	= false;
		
		if( function_exists('mb_convert_case') )
		{
			if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
			{
				$_useMb	= true;
			}
		}

		foreach( $_formatOpts as $option )
		{
			switch( $option )
			{
				case 'strtolower':
					$value	= $_useMb ? mb_convert_case( $value, MB_CASE_LOWER, $this->settings['gb_char_set'] ) : strtolower($value);
				break;
				
				case 'strtoupper':
					$value	= $_useMb ? mb_convert_case( $value, MB_CASE_UPPER, $this->settings['gb_char_set'] ) : strtoupper($value);
				break;
				
				case 'ucfirst':
					$value	= $_useMb ? ( mb_strtoupper( mb_substr( $value, 0, 1, $this->settings['gb_char_set'] ), $this->settings['gb_char_set'] ) . mb_substr( $value, 1, mb_strlen( $value ), $this->settings['gb_char_set'] ) ) : ucfirst($value);
				break;
				
				case 'ucwords':
					$value	= $_useMb ? mb_convert_case( $value, MB_CASE_TITLE, $this->settings['gb_char_set'] ) : ucwords($value);
				break;
				
				case 'punct':
					$value	= preg_replace( "/\?{1,}/"		, "?"		, $value );
					$value	= preg_replace( "/(&#33;){1,}/"	, "&#33;"	, $value );
				break;
				
				case 'numerical':
					$value	= $this->registry->class_localization->formatNumber( $value );
				break;
			}
		}
		
		return $value;
	}
	
	/**
	 * Process the input and return normalized value to store
	 *
	 * @param	array 		Field data
	 * @return	@e string
	 */
	public function processInput( $field )
	{
		switch( $field['field_type'] )
		{
			case 'input':
			case 'radio':
			case 'textarea':
			case 'select':
				if( $field['field_type'] == 'input' OR $field['field_type'] == 'textarea' )
				{
					if( $field['field_max_length'] AND IPSText::mbstrlen(trim($this->request['field_' . $field['field_id'] ])) > $field['field_max_length'] )
					{
						$this->error	= sprintf( $this->lang->words['dbfield_too_long'], $field['field_name'] );
					}
				}
				
				if( $field['field_required'] AND !(trim($this->request['field_' . $field['field_id'] ])) )
				{
					if( $this->request['field_' . $field['field_id'] ] !== 0 AND $this->request['field_' . $field['field_id'] ] !== "0" )
					{
						$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
					}
				}

				if( $field['field_type'] == 'input' OR $field['field_type'] == 'textarea' )
				{
					return $field['field_html'] ? trim($_POST['field_' . $field['field_id'] ]) : trim($this->request['field_' . $field['field_id'] ]);
				}
				else
				{
					$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
					$_found		= false;
					
					foreach( $_options as $_option )
					{
						list( $key, $value ) = explode( "=", $_option );
						
						if( $key == trim($this->request['field_' . $field['field_id'] ]) )
						{
							$_found	= true;
							break;
						}
					}

					if( !$_found AND $this->request['field_' . $field['field_id'] ] )
					{
						$this->error	= sprintf( $this->lang->words['dbfield_invalidvalue'], $field['field_name'] );
					}
					
					return trim($this->request['field_' . $field['field_id'] ]);
				}
			break;
			
			case 'multiselect':
			case 'checkbox':
				if( is_array($this->request['field_' . $field['field_id'] ]) AND count($this->request['field_' . $field['field_id'] ]) )
				{
					$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
					$_invalid	= false;
					
					foreach( $this->request['field_' . $field['field_id'] ] as $_submittedValue )
					{
						$_found	= false;
						
						foreach( $_options as $_option )
						{
							list( $key, $value ) = explode( "=", $_option );
							
							if( $key == $_submittedValue )
							{
								$_found	= true;
								break;
							}
						}
						
						if( !$_found )
						{
							$_invalid	= true;
							break;
						}
					}
					
					if( $_invalid )
					{
						$this->error	= sprintf( $this->lang->words['dbfield_invalidvalue'], $field['field_name'] );
					}
				}					

				if( is_array($this->request['field_' . $field['field_id'] ]) AND count($this->request['field_' . $field['field_id'] ]) )
				{
					return ',' . implode( ',', $this->request['field_' . $field['field_id'] ] ) . ',';
				}
				else if( $field['field_required'] )
				{
					$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
				}
				
				return '';
			break;
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
	 * Return truncated text or return full text
	 *
	 * @param	string		Text to return
	 * @param	int			Chars to truncate at
	 * @return	@e string
	 */
	protected function _truncate( $text, $truncate=0 )
	{
		if( $truncate )
		{
			if( IPSText::mbstrlen( strip_tags( $text ) ) > $truncate )
			{
				return IPSText::truncate( strip_tags( $text ), $truncate );
			}
		}

		return $text;
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
		
		if( !is_numeric($fieldValue) AND !$fieldValue  )
		{
			return '';
		}
				
		switch( $field['field_type'] )
		{
			case 'input':
			case 'textarea':
				$_return	= $fieldValue;
				
				if( $field['field_type'] == 'input' AND $field['field_format_opts'] )
				{
					$_return	= $this->_applyInputFormatting( $_return, $field['field_format_opts'] );
				}
					
				return $this->_truncate( $_return, $truncate );
			break;
			
			case 'select':
			case 'radio':
				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
				
				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( $key == $fieldValue )
					{
						return $value;
					}
				}
				
				return $this->_truncate( $fieldValue, $truncate );
			break;
			
			case 'multiselect':
			case 'checkbox':
				$list		= array();
				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
				$_default	= is_array($fieldValue) ? $fieldValue : explode( ',', IPSText::cleanPermString( $fieldValue ) );
				
				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( in_array( $key, $_default ) )
					{
						$list[]	= $value;
					}
				}
				
				return $this->_truncate( implode( ', ', $list ), $truncate );
			break;
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
	
		switch( $field['field_type'] )
		{
			case 'input':
			case 'textarea':
				if( $field['field_is_numeric'] )
				{
					if( is_numeric($search) )
					{
						$searchAsNumber = intval( $search );
						return 'field_' . $field['field_id'] . "+0 = {$searchAsNumber}";
					}
					else
					{
						return '';
					}
				}
				else
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
			break;
			
			case 'select':
			case 'radio':
				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
				$_possible	= array();
				
				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( stripos( $value, $search ) !== false )
					{
						$_possible[]	= 'field_' . $field['field_id'] . "='{$key}'";
					}
				}
				
				return count($_possible) ? '(' . implode( ' OR ', $_possible ) . ')' : '';
			break;
			
			case 'multiselect':
			case 'checkbox':
				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
				$_possible	= array();
				
				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( stripos( $value, $search ) !== false )
					{
						$_possible[]	= 'field_' . $field['field_id'] . " LIKE '%,{$key},%'";
					}
				}
				
				return count($_possible) ? '(' . implode( ' OR ', $_possible ) . ')' : '';
			break;
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
		switch( $field['field_type'] )
		{
			case 'input':
			case 'textarea':
				return $this->getDifferenceReport( $current, $previous );
			break;

			case 'select':
			case 'radio':
				$curDisplay	= '';
				$preDisplay	= '';

				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );

				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( $key == $current )
					{
						$curDisplay	= $value;
					}
					
					if( $key == $previous )
					{
						$preDisplay	= $value;
					}
				}
				
				if( $current == $previous )
				{
					return $curDisplay;
				}
				else
				{
					return "<ins>" . $curDisplay . "</ins> <del>" . $preDisplay . "</del>";
				}
			break;
			
			case 'multiselect':
			case 'checkbox':
				$_previous	= array();
				$_current	= array();
				$_options	= explode( "\n", str_replace( "\r", '', trim($field['field_extra']) ) );
				$_cDefault	= explode( ',', IPSText::cleanPermString( $current ) );
				$_pDefault	= explode( ',', IPSText::cleanPermString( $previous ) );
				
				foreach( $_options as $_option )
				{
					list( $key, $value ) = explode( "=", $_option );
					
					if( in_array( $key, $_cDefault ) )
					{
						$_current[]		= $value;
					}
					
					if( in_array( $key, $_pDefault ) )
					{
						$_previous[]	= $value;
					}
				}
				
				$_intersect	= array_intersect( $_current, $_previous );
				
				if( $current == $previous )
				{
					return implode( ', ', $_current );
				}
				else
				{
					$return	= array();
					
					foreach( $_current as $_currentItem )
					{
						if( in_array( $_currentItem, $_intersect ) )
						{
							$return[]	= $_currentItem;
						}
						else
						{
							$return[]	= "<ins>" . $_currentItem . "</ins>";
						}
					}
					
					foreach( $_previous as $_previousItem )
					{
						if( !in_array( $_previousItem, $_intersect ) )
						{
							$return[]	= "<del>" . $_previousItem . "</del>";
						}
					}
					
					return implode( ', ', $return );
				}
			break;
		}

		return '';
	}
}