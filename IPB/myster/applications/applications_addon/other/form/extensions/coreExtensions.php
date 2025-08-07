<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

$_PERM_CONFIG = array( 'Form' );

class formPermMappingForm
{
	public function getMapping()
	{
		return array(
						'view'	  => 'perm_view',
						'submit'  => 'perm_2',
						'logs'	  => 'perm_3',
					);
	}

	public function getPermNames()
	{
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'admin_external' ), 'form' );
		
		return array(
						'view'		=> ipsRegistry::getClass('class_localization')->words['view_form'],
						'submit'	=> ipsRegistry::getClass('class_localization')->words['submit_form'],
						'logs'		=> ipsRegistry::getClass('class_localization')->words['view_logs'],
					);
	}

	public function getPermColors()
	{
		return array(
						'view'		=> '#fff0f2',
						'submit'	=> '#effff6',
						'logs'		=> '#edfaff',
					);
	}

	public function getPermItems()
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'form' ) . "/sources/forms.php", 'class_forms', 'form' );
		$forms		 = new $classToLoad( ipsRegistry::instance() );	   
		$forms->init();		
			
		$forms = $forms->formDropdown();
		
		$_return_arr = array();
		foreach( $forms as $r )
		{
			$return_arr[$r[0]] = array(
										'title'     => $r[1],
										'perm_view' => $forms->form_data_id[$r[0]]['perm_view'],
										'perm_2'    => $forms->form_data_id[$r[0]]['perm_2'],
										'perm_3'    => $forms->form_data_id[$r[0]]['perm_3'],						
									);
		}
		
		return $return_arr;
	}	
}

class itemMarking__form
{
	private $_convertData = array( 'forumID' => 'item_app_key_1' );
	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	
	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/* Convert Data */
	public function convertData( $data )
	{
		$_data = array();
		
		foreach( $data as $k => $v )
		{
			if ( isset($this->_convertData[$k]) )
			{
				$_data[ $this->_convertData[ $k ] ] = intval( $v );
			}
			else
			{
				$_data[ $k ] = $v;
			}
		}
		
		return $_data;
	}
	
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{		
		$last_count = 0;
		$count      = 0;
		$readItems  = is_array( $readItems ) ? $readItems : array( 0 );

		if ( $data['forumID'] )
		{
			$sql = $this->DB->buildAndFetch( array( 
													'select' => 'COUNT(*) as cnt, MIN(log_date) AS last_item',
													'from'   => 'form_logs',
													'where'  => "log_form_id=" . intval( $data['forumID'] ) . " AND log_id NOT IN(".implode(",",array_keys($readItems)).") AND log_date > ".intval($lastReset)
												)	);
													
			$count      = intval( $sql['cnt'] );
			$last_count = intval( $sql['last_item'] );
		}

		return array( 'count' => $count, 'lastItem' => $last_count );
	}
}

class publicSessions__form
{
	public function getSessionVariables()
	{
		$array = array( 'location_1_type'   => '',
						'location_1_id'     => 0,
						'location_2_type'   => '',
						'location_2_id'     => 0
						);
		
		# Main Display
		if ( ipsRegistry::$request['module'] == 'display' )
		{
			$array = array(  'location_1_type'   => ipsRegistry::$request['section'],
							 'location_1_id'	 => intval(ipsRegistry::$request['id']),
							 'location_2_type'   => substr( ipsRegistry::$request['do'], 0, 10 ),
						);
		}
		
		return $array;
	}

	public function parseOnlineEntries( $rows )
	{		
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_external' ), 'form' );

		$forms_raw	= array();
		$forms		= array();
		$final      = array();
		
		foreach( $rows as $row )
		{
			if ( $row['current_appcomponent'] != 'form' OR !$row['current_module'] )
			{
				continue;
			}				
			
			if( $row['current_module'] == 'display' )
			{
				# View Form
				if( $row['location_2_type'] == 'view_form' )
				{
					$forms_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
				}				
			}
		}

		//-----------------------------------------
		// Get the forms
		//-----------------------------------------	
			
		require_once( IPSLib::getAppDir( 'form' ) . "/sources/forms.php" );		
		ipsRegistry::setClass('formForms', new class_forms( ipsRegistry::instance() ) );
		ipsRegistry::getClass('formForms')->init();	
		
		if( count($forms_raw) )
		{
			foreach( ipsRegistry::getClass('formForms')->viewForms as $f_id => $form )
			{
				if( isset($forms_raw[ $f_id ]) )
				{
					$forms[ $f_id ] = $form;
				}
			}
		}	

		# Put humpty dumpty together again
		foreach ( $rows as $row )
		{
			if ( $row['current_appcomponent'] != 'form' )
			{
				$final[ $row['id'] ] = $row;
				
				continue;
			}
			
			if ( !$row['current_module'] )
			{
				$row['where_line']      = ipsRegistry::getClass('class_localization')->words['where_main'];
				$final[ $row['id'] ] = $row;
				
				continue;
			}
			
			if ( $row['current_module'] == 'display' )
			{
				if( $row['location_2_type'] == 'view_form' )
				{
					if ( $row['location_1_id'] && isset( $forms[ $row['location_1_id'] ] ) )
					{
						$row['where_line']      = ipsRegistry::getClass('class_localization')->words['where_form'];
						$row['where_line_more'] = $forms[ $row['location_1_id'] ]['form_name'];
						$row['where_link']      = 'app=form&amp;do=view_form&amp;id=' . $row['location_1_id'];
						$row['_whereLinkSeo']   = ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=form&amp;do=view_form&amp;id=".$row['location_1_id'], 'public' ), $forms[ $row['location_1_id'] ]['name_seo'], 'form_view_form' );
					}
				}
				else
				{
					$row['where_line']      = ipsRegistry::getClass('class_localization')->words['where_main'];
				}
			}
			else
			{
				$row['where_line']      = ipsRegistry::getClass('class_localization')->words['where_main'];
			}
			
			$final[ $row['id'] ] = $row;
		}		

		return $final;
	}
}