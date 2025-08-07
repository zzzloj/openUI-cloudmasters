<?php

/**
 * <pre>
 * Invision Power Services
 * Date entry field type abstraction
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

class fields_date
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
	 * Date CSS and JS printed?
	 *
	 * @var		bool
	 */
	protected $cssAndJs		= false;
	
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
					array( 'date', $this->lang->words['field_type__date'] ),
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
		
		if( $type == 'date' )
		{
			$return	= '';
			
			if( !$this->cssAndJs )
			{
				$return .= "<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/calendar_date_select/calendar_date_select.js'></script>
				<style type='text/css'>
				 	@import url('{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/calendar_select.css');
				</style>";

				if( $this->settings['calendar_date_select_locale'] AND $this->settings['calendar_date_select_locale'] != 'en' )
				{
					$return .= "<script type='text/javascript' src='{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/format_iso_date.js'></script>";
					$return .= "<script type='text/javascript' src='{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/locale/{$this->settings['calendar_date_select_locale']}.js'></script>";
				}

				$this->cssAndJs	= true;
			}

			if( !is_numeric($default) AND $default )
			{
				$_ts		= strtolower($default);
				$default	= @strtotime($default);
				
				/* When it's a date/time like June 10 2011 4:00 PM you have to remove your offset before it is re-added below.
					When it's a string, offset is accounted for already */
				if( $_ts != 'now' AND $_ts != 'today' )
				{
					$default	-= $this->registry->class_localization->getTimeOffset();
				}
			}

			if( $default )
			{
				$default	= date( "Y-m-d H:i", $default + $this->registry->class_localization->getTimeOffset() );
			}
			
			$return	.= $this->registry->output->formInput( 'field_' . $id, $default, 'field_' . $id, 20, 'text', '', 'date' ) . 
				" <img src='{$this->settings['img_url']}/date.png' alt='{$this->lang->words['icon']}' id='field_{$id}_icon' style='cursor: pointer; vertical-align: middle;' />
				<script type='text/javascript'>
					$('field_{$id}_icon').observe('click', function(e){
						var dateSelect_{$id} = new CalendarDateSelect( $('field_{$id}'), { year_range: 100, time: true, format: 'iso_date' } );
					});
				</script>";

			return $return;
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

		if( $type == 'date' )
		{
			$return	= '';
			
			if( !$this->cssAndJs )
			{
				$return .= "<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/calendar_date_select/calendar_date_select.js'></script>
				<style type='text/css'>
				 	@import url('{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/calendar_select.css');
				</style>";

				if( $this->settings['calendar_date_select_locale'] AND $this->settings['calendar_date_select_locale'] != 'en' )
				{
					$return .= "<script type='text/javascript' src='{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/format_iso_date.js'></script>";
					$return .= "<script type='text/javascript' src='{$this->settings['js_base_url']}js/3rd_party/calendar_date_select/locale/{$this->settings['calendar_date_select_locale']}.js'></script>";
				}

				$this->cssAndJs	= true;
			}
			
			if( !is_numeric($default) AND $default )
			{
				$_ts		= strtolower($default);
				$default	= @strtotime($default);

				/* When it's a date/time like June 10 2011 4:00 PM you have to remove your offset before it is re-added below.
					When it's a string, offset is accounted for already */
				if( $_ts != 'now' AND $_ts != 'today' )
				{
					$default	-= $this->registry->class_localization->getTimeOffset();
				}
			}

			if( $default )
			{
				$default	= date( "Y-m-d H:i", $default + $this->registry->class_localization->getTimeOffset() );
			}
			
			$return	.= "<input type='text' class='input_text date' name='field_{$id}' id='field_{$id}' value='{$default}' />
				<input type='hidden' class='input_text date' name='field_{$id}_formatted' id='field_{$id}_formatted' value='{$default}' />
				<img src='{$this->settings['img_url']}/date.png' alt='{$this->lang->words['icon']}' id='field_{$id}_icon' style='cursor: pointer' />
				<script type='text/javascript'>
					var dateSelect_{$id} = '';
					
					function callback_for_calendar_{$id}(e)
					{
						var d    = new Date( \$H(dateSelect_{$id}).get('selected_date') );
						var unix = d.getTime() / 1000;
					
						$('field_{$id}_formatted').setValue( unix );
					}
					$('field_{$id}_icon').observe('click', function(e){
						dateSelect_{$id} = new CalendarDateSelect( $('field_{$id}'), { year_range: 100, time: true, onchange: callback_for_calendar_{$id}, format: 'iso_date' } );
					});
					
					
				</script>";

			return $return;
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
		$dateValue = ( ! empty( $this->request['field_' . $field['field_id'] . '_formatted'] )  )? $this->request['field_' . $field['field_id'] . '_formatted'] : $this->request['field_' . $field['field_id'] ];
		
		if( $field['field_type'] == 'date' )
		{
			if( $dateValue )
			{
				$value	= ( is_numeric( $dateValue ) && strlen( $dateValue ) == 10 ) ? $dateValue : @strtotime( $dateValue );
				
				if( !$value AND $field['field_required'] )
				{
					$this->error	= sprintf( $this->lang->words['dbfield_invalidvalue'], $field['field_name'] );
					return '';
				}
				else
				{
					$value	-= $this->registry->class_localization->getTimeOffset();
				}
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

		//-----------------------------------------
		// Preview sends plaintext field
		// @link	http://community.invisionpower.com/tracker/issue-31372-various-date-issues
		//	Changed to !is_numeric from is_numeric, because timestamp was being passed in and ended up being just the offset.
		//-----------------------------------------
		
		if ( !is_numeric( $fieldValue ) )
		{
			$fieldValue	= @strtotime( $this->request['field_' . $field['field_id'] ] ) - $this->registry->class_localization->getTimeOffset();
		}

		if( $field['field_type'] == 'date' )
		{
			$dateFormat	= $field['field_extra'] ? $field['field_extra'] : ( $truncate ? 'short' : 'long' );

			$fieldValue	+= $this->registry->class_localization->getTimeOffset();
			
			$_format	= '';
			
			if( isset( $this->registry->class_localization->time_options[ strtoupper($dateFormat) ] ) )
			{
				$_format	= $this->registry->class_localization->time_options[ strtoupper($dateFormat) ];
			}
			
			if( preg_match( "#^manual\{([^\{]+?)\}#i", $dateFormat, $match ) )
			{
				$_format	= $match[1];
			}
			
			if( !$_format )
			{
				$_format	= $this->registry->class_localization->time_options['LONG'];
			}

			return @strftime( $_format, $fieldValue );
			//return $this->registry->class_localization->getDate( $fieldValue, $dateFormat );
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
		if( $field['field_type'] == 'date' && $search )
		{
			$search	= @strtotime($search);
			
			if( $search )
			{
				return 'field_' . $field['field_id'] . "+0 > {$search}";
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
		if( $field['field_type'] == 'date' )
		{
			$key	= 'field_' . $field['field_id'];
			return $current == $previous ? $this->getFieldValue( $field, array( $key => $current ) ) : "<ins>" . $this->getFieldValue( $field, array( $key => $current ) ) . "</ins> <del>" . $this->getFieldValue( $field, array( $key => $previous ) ) . "</del>";
		}

		return '';
	}
}