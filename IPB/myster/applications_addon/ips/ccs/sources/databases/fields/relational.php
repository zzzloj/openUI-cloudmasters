<?php

/**
 * <pre>
 * Invision Power Services
 * Cross-database relational field
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		18 Nov 2009
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class fields_relational
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
					array( 'relational', $this->lang->words['field_type__relational'] ),
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
		$options	= explode( ',', $field['field_extra'] );		// Database,Field,Type
		$_defaults	= $options[2] == 'dropdown' ? array() : explode( ',', IPSText::cleanPermString( $default ) );
		
		if( $type == 'relational' )
		{
			$return	= '';
			
			$database	= intval(trim($options[0]));
			$field		= intval(trim($options[1]));
			
			if( $database AND $field )
			{
				$_options	= array();
				$_strings	= array();
	
				$this->DB->build( array( 'select' => 'primary_id_field, field_' . $field, 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'order' => $this->caches['ccs_databases'][ $database ]['database_field_sort'] . ' ' . $this->caches['ccs_databases'][ $database ]['database_field_direction'] ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$_options[]	= array( $r['primary_id_field'], $r[ 'field_' . $field ] );

					if( in_array( $r['primary_id_field'], $_defaults ) )
					{
						$_strings[]	= $r[ 'field_' . $field ];
					}
				}
				
				if( $options[2] == 'dropdown' )
				{
					array_unshift( $_options, array( 0, $this->lang->words['select_one_rel'] ) );
					
					return $this->registry->output->formDropdown( 'field_' . $id, $_options, $default );
				}
				else if( $options[2] == 'multiselect' )
				{
					return $this->registry->output->formMultiDropdown( 'field_' . $id . '[]', $_options, $_defaults );
				}
				else
				{
					$url	= $this->settings['public_url'] . 'app=ccs&module=ajax&section=relational&secure_key=' . $this->member->form_hash . '&field=' . $options[1] . '&value=';

					return $this->registry->output->formInput( 'field_' . $id, implode( ', ', $_strings ), 'field_' . $id, 30, 'text' ) . 
						"<script type='text/javascript'>
							document.observe('dom:loaded', function(){
								if( $('field_{$id}') )
								{
									var autoComplete = new ipb.Autocomplete( $('field_{$id}'), { multibox: true, url: '{$url}', templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
								}
							});
						</script>";
				}
			}
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
		$options	= explode( ',', $field['field_extra'] );		// Database,Field,Type
		$_defaults	= $options[2] == 'dropdown' ? array() : explode( ',', IPSText::cleanPermString( $default ) );
		
		if( $type == 'relational' )
		{
			$database	= intval(trim($options[0]));
			$field		= intval(trim($options[1]));
			
			if( $database AND $field )
			{
				$_options	= array();
				$_strings	= array();
	
				$this->DB->build( array( 'select' => 'primary_id_field, field_' . $field, 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'record_approved=1', 'order' => $this->caches['ccs_databases'][ $database ]['database_field_sort'] . ' ' . $this->caches['ccs_databases'][ $database ]['database_field_direction'] ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$_options[]	= array( $r['primary_id_field'], $r[ 'field_' . $field ] );

					if( in_array( $r['primary_id_field'], $_defaults ) )
					{
						$_strings[]	= $r[ 'field_' . $field ];
					}
				}
				
				if( $options[2] == 'typeahead' )
				{
					$url		= $this->settings['base_url'] . 'app=ccs&module=ajax&section=relational&secure_key=' . $this->member->form_hash . '&field=' . $options[1] . '&value=';
					$_strings	= implode( ', ', $_strings );

					return <<<EOF
					<input type='text' class='input_text' name='field_{$id}' id='field_{$id}' value='{$_strings}' />
						<script type='text/javascript'>
							document.observe('dom:loaded', function(){
								ipb.templates['autocomplete_generic'] = new Template("<li id='#{id}' data-url='#{url}' style='padding: 4px;'>#{itemvalue}</li>");

								if( $('field_{$id}') )
								{
									var autoComplete = new ipb.Autocomplete( $('field_{$id}'), { multibox: true, url: '{$url}', templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_generic'] } } );
								}
							});
						</script>
EOF;
				}

				if( $options[2] == 'dropdown' )
				{
					$_html	= "<select name='field_{$id}' id='field_{$id}'><option value='0'>{$this->lang->words['select_one_rel']}</option>";
				}
				else
				{
					$_html	= "<select name='field_{$id}[]' id='field_{$id}' multiple='multiple' size='5'>";
				}
				
				foreach( $_options as $_items )
				{
					$_selected	= '';
					
					if( $options[2] == 'dropdown' )
					{
						if( $_items[0] == $default )
						{
							$_selected	= "selected='selected'";
						}
					}
					else
					{
						if( in_array( $_items[0], $_defaults ) )
						{
							$_selected	= "selected='selected'";
						}
					}
					
					$_items[1]	= strip_tags($_items[1]);
					
					$_html	.= "<option value='{$_items[0]}'{$_selected}>{$_items[1]}</option>";
				}
				
				$_html	.= "</select>";
				
				return $_html;
			}
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
		
		if( $field['field_type'] == 'relational' )
		{
			//-----------------------------------------
			// Validate value
			//-----------------------------------------
			
			$options	= explode( ',', $field['field_extra'] );		// Database,Field,Type
			$database	= intval(trim($options[0]));
			$_field		= intval(trim($options[1]));
			
			if( $options[2] == 'dropdown' )
			{
				$value	= intval( $this->request['field_' . $field['field_id'] ] );
			}
			else if( $options[2] == 'multiselect' )
			{
				$value	= ',' . implode( ',', IPSLib::cleanIntArray( $this->request['field_' . $field['field_id'] ] ) ) . ',';
			}
			else
			{
				$submitted	= explode( ',', IPSText::cleanPermString( trim( str_replace( ', ', ',', $this->request['field_' . $field['field_id'] ] ) ) ) );
				$values		= array();

				$this->DB->build( array( 'select' => 'primary_id_field, field_' . $_field, 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'record_approved=1', 'order' => $this->caches['ccs_databases'][ $database ]['database_field_sort'] . ' ' . $this->caches['ccs_databases'][ $database ]['database_field_direction'] ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					if( in_array( $r['field_' . $_field ], $submitted ) )
					{
						$values[]	= $r['primary_id_field'];
					}
				}

				$value	= ',' . implode( ',', $values ) . ',';
			}

			if( $field['field_required'] AND !$value )
			{
				$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
			}

			if( $value )
			{
				if( $options[2] == 'dropdown' )
				{
					$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'primary_id_field=' . $value ) );
					
					if( !$record['primary_id_field'] )
					{
						$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
					}
				}
				else
				{
					if( IPSText::cleanPermString( $value ) )
					{
						$this->DB->build( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'primary_id_field IN(' . IPSText::cleanPermString( $value ) . ')' ) );
						$this->DB->execute();
						
						while( $record = $this->DB->fetch() )
						{
							if( !$record['primary_id_field'] )
							{
								$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
							}
						}
					}
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
		$options	= explode( ',', $field['field_extra'] );		// Database,Field,Type
		$database	= intval(trim($options[0]));
		$_field		= intval(trim($options[1]));
		$link		= intval(trim($options[3]));

		if( $database AND $_field )
		{
			if( $record['field_' . $field['field_id'] ] )
			{
				if( $options[2] == 'dropdown' )
				{
					if( $this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ] )
					{
						if( $link )
						{
							return $this->_link( $database, $this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ], $_field, $truncate );
						}
						else
						{
							return ( $truncate ? IPSText::truncate( $this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ][ 'field_' . $_field ], $truncate ) : $this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ][ 'field_' . $_field ] );
						}
					}
					
					if( !intval($record['field_' . $field['field_id'] ]) )
					{
						return '';
					}
	
					$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'primary_id_field=' . intval($record['field_' . $field['field_id'] ]) ) );

					$this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ]	= $_record;
					
					if( $link )
					{
						return $this->_link( $database, $_record, $_field, $truncate );
					}
					else
					{
						return ( $truncate ? IPSText::truncate( $_record[ 'field_' . $_field ], $truncate ) : $_record[ 'field_' . $_field ] );
					}
				}
				else
				{
					$_value		= IPSText::cleanPermString( $record['field_' . $field['field_id'] ] ) ? explode( ',', IPSText::cleanPermString( $record['field_' . $field['field_id'] ] ) ) : array();
					$_return	= array();
					$_still		= array();

					foreach( $_value as $_val )
					{
						if( $this->caches['records'][ $database ][ $_val ] )
						{
							if( $link )
							{
								$_return[ $this->_link( $database, $this->caches['records'][ $database ][ $_val ], $_field ) ]	= $this->caches['records'][ $database ][ $_val ];
							}
							else
							{
								$_return[ $this->caches['records'][ $database ][ $_val ][ 'field_' . $_field ] ]	= $this->caches['records'][ $database ][ $_val ][ 'field_' . $_field ];
							}
						}
						else
						{
							$_still[]	= $_val;
						}
					}
					
					if( count($_still) )
					{
						$this->DB->build( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'primary_id_field IN(' . implode( ',', $_still ) . ')' ) );
						$outer	= $this->DB->execute();
						
						while( $_r = $this->DB->fetch($outer) )
						{
							$this->caches['records'][ $database ][ $record['field_' . $field['field_id'] ] ]	= $_r;

							if( $link )
							{
								$_return[ $this->_link( $database, $_r, $_field ) ]	= $_r[ 'field_' . $_field ];
							}
							else
							{
								$_return[ $_r[ 'field_' . $_field ] ]	= $_r[ 'field_' . $_field ];
							}
						}
					}
					
					//-----------------------------------------
					// Sort
					//-----------------------------------------
					
					natcasesort($_return);
					
					return implode( ', ', array_keys( $_return ) );
				}
			}
		}
			
		return '';
	}

	/**
	 * Link the value to the related database record
	 *
	 * @param	int		Database ID
	 * @param	array 	Record information
	 * @param	int		Field ID
	 * @param	int		Number of chars to truncate on
	 * @return	@e string
	 */
	protected function _link( $database, $record, $field, $truncate=0 )
	{
		return "<a href='" . $this->registry->ccsFunctions->returnDatabaseUrl( $database, 0, $record ) . "' title='" . $record['field_' . $field ] . "'>" . ( $truncate ? IPSText::truncate( $record['field_' . $field ], $truncate ) : $record['field_' . $field ] ) . "</a>";
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
		if( $field['field_type'] == 'relational' && $search )
		{
			$options	= explode( ',', $field['field_extra'] );		// Database,Field,Type
			$database	= intval(trim($options[0]));
			$_field		= intval(trim($options[1]));

			if( $database AND $_field )
			{
				$this->DB->build( array( 'select' => '*', 'from' => $this->caches['ccs_databases'][ $database ]['database_database'], 'where' => 'record_approved=1 AND field_' . $_field . " LIKE '%{$search}%'" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$potentialIds[]	= $r['primary_id_field'];
				}
			
				if( count($potentialIds) )
				{
					if( $options[2] == 'dropdown' )
					{
						return 'field_' . $field['field_id'] . " IN (" . implode( ',', $potentialIds ) . ")";
					}
					else
					{
						$_return	= array();
						
						foreach( $potentialIds as $_id )
						{
							$_return[]	= 'field_' . $field['field_id'] . " LIKE '%," . $_id . ",%'";
						}
						
						return '(' . implode( ' OR ', $_return ) . ')';
					}
				}
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
		if( $field['field_type'] == 'relational' )
		{
			$key	= 'field_' . $field['field_id'];
			return $current == $previous ? $this->getFieldValue( $field, array( $key => $current ) ) : "<ins>" . $this->getFieldValue( $field, array( $key => $current ) ) . "</ins> <del>" . $this->getFieldValue( $field, array( $key => $previous ) ) . "</del>";
		}

		return '';
	}
	
	/**
	 * Modify field data to be saved via ACP
	 *
	 * @param	array 		Field data
	 * @return	@e array
	 */
	public function preSaveField( $field )
	{
		if( $field['field_type'] == 'relational' )
		{
			$field['field_extra']	= intval($this->request['field_database']) . ',' . intval($this->request['field_fields']) . ',' . $this->request['field_rel_type'] . ',' . intval($this->request['field_rel_link']) . ',' . intval($this->request['field_rel_crosslink']);
		}

		return $field;
	}
}