<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */
 
 
/**
* Main loader class
*/
class publicSessions__testimonials
{
	public function getSessionVariables()
	{
		/* Init */
		$array = array( 'location_1_type'   => '',
						'location_1_id'     => 0,
						'location_2_type'   => '',
						'location_2_id'     => 0
		);

		/* Módulo da APP */
		if ( ipsRegistry::$request['module'] == 'testemunhos' )
		{
			# Visualizando um Testemunho
	    	if ( ipsRegistry::$request['section'] == 'view' AND intval(ipsRegistry::$request['showtestimonial']) )
	    	{
				$array = array( 'location_1_type' => 'testemunho',
								'location_1_id'   => intval(ipsRegistry::$request['showtestimonial'])
				);
	    	}
	    	elseif ( ipsRegistry::$request['section'] == 'post' )
	    	{
	    		if ( ipsRegistry::$request['do'] == 'new' )
    			{
	    			$array = array( 'location_1_type' => 'new' );
	    		}
	    		elseif ( ipsRegistry::$request['do'] == 'edit' AND intval(ipsRegistry::$request['id']) )
    			{
	    			$array = array( 'location_1_type' => 'edit',
									'location_1_id'   => intval(ipsRegistry::$request['id'])
					);
	    		}
	    	}
	    	elseif ( ipsRegistry::$request['section'] == 'comment' )
	    	{
	    	 	if ( ipsRegistry::$request['do'] == 'new' )
	    	 	{
					$array = array( 'location_1_type' => 'newcomment',
									'location_1_id'	  => intval(ipsRegistry::$request['testemunho'])
					);
				}
				elseif ( ipsRegistry::$request['do'] == 'editComment' )
				{
					$array = array( 'location_1_type' => 'editComm',
									'location_1_id'	  => intval(ipsRegistry::$request['testemunho'])
					);
				}
				
			}
	    }

		return $array;
	}

	public function parseOnlineEntries( $rows )
	{
		if ( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		/* Init vars */
		$skipParse  = true;
		$testRaw	= array();
		$test		= array();
		$final		= array();
		
		//-----------------------------------------
		// Extract the ticket data
		//-----------------------------------------
		foreach ( $rows as $row )
		{
			# Not this app or we are erroring?			
			if ( $row['current_appcomponent'] != 'testemunhos' || $row['in_error'] == 1 )
			{
				continue;
			}
			
			# Check passed at least once?
			$skipParse = false;
			
			if ( !$row['current_module'] )
			{
				continue;
			}
			
			if ( $row['current_section'] == 'view' AND $row['location_1_id'] )
			{
				$testRaw[ $row['location_1_id'] ] = $row['location_1_id'];
			}
			elseif ( $row['current_section'] == 'post' && $row['location_1_id'] )
			{
				$testRaw[ $row['location_1_id'] ] = $row['location_1_id'];
			}
			elseif ( $row['current_section'] == 'comment' && $row['location_1_id'] )
			{
				$testRaw[ $row['location_1_id'] ] = $row['location_1_id'];
			}
		}
		
		if ( $skipParse )
		{
			return $rows;
		} 

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
		
		if( count($testRaw) )
		{
			ipsRegistry::DB()->build( array( 'select'	=> 't.t_id, t.t_title, t.t_member_id', 
											 'from'		=> array( 'testemunhos' => 't' ),
											 'where'	=> 't.t_id IN ('.implode(',', $testRaw).')'			
			) );
			
			$tr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($tr) )
			{
				$testemunhos[ $r['t_id'] ] = $r['t_title'];
			}
		}

		//-----------------------------------------
		// Let's setup everything finally
		//-----------------------------------------
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'testemunhos' )
			{
				$final[ $row['id'] ] = $row;
				
				continue;
			}
			
			$row['where_line'] = ipsRegistry::getClass('class_localization')->words['WHERE_testemunhos'];
			$row['where_link'] = 'app=testemunhos';
		
			# We have the right module?
			if ( $row['current_module'] == 'testemunhos' )
			{
				if( $row['current_section'] == 'view' )
				{
					if( isset($testemunhos[ $row['location_1_id'] ]) )
					{
						$row['where_line']      = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho'];
						$row['where_line_more'] = $testemunhos[ $row['location_1_id'] ];
						$row['where_link']     .= '&amp;showtestimonial=' . $row['location_1_id'];
					}
					else
					{
						$row['where_line'] = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho_noid'];
					}
				}
				elseif ( $row['current_section'] == 'post' )
				{
					if ( $row['location_1_type'] == 'new' )
	    			{
						$row['where_line']  = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho_new'];							
						$row['where_link'] .= '&amp;module=testemunhos&amp;section=post&amp;do=new';
		    		}
		    		if ( $row['location_1_type'] == 'edit' )
		    		{
		    			if ( isset($testemunhos[ $row['location_1_id'] ]) )
		    			{
							$row['where_line']      = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho_edit'];
							$row['where_line_more']	= $testemunhos[ $row['location_1_id'] ];
							$row['where_link']     .= '&amp;module=testemunhos&amp;section=post&amp;do=edit&amp;id='. $row['location_1_id'];
						}
		    		}
				}
				elseif ( $row['current_section'] == 'comment' )
				{
					if( $row['location_1_type'] == 'newcomment' )
					{
						if( isset($testemunhos[ $row['location_1_id'] ]) )
						{
							$row['where_line']      = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho_newcomment'];
							$row['where_line_more'] = $testemunhos[ $row['location_1_id'] ];
							$row['where_link']     .= '&amp;module=testemunhos&amp;section=comment&amp;testemunho='.$row['location_1_id'];
						}
					}
					
					if( $row['location_1_type'] == 'editComm' )
					{
						if( isset($testemunhos[ $row['location_1_id'] ]) )
						{
							$row['where_line']      = ipsRegistry::getClass('class_localization')->words['WHERE_testemunho_editcomment'];
							$row['where_line_more'] = $testemunhos[ $row['location_1_id'] ];
							$row['where_link']     .= '&amp;module=testemunhos&amp;section=comment&amp;testemunho='.$row['location_1_id'];
						}
					}
				}
			}

			$final[ $row['id'] ] = $row;
		}
		
		return $final;
	}
}

class itemMarking__testimonials
{
	private $_convertData = array( 'testemunhoCat' => 'item_app_key_1' );
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	public function convertData( $data )
	{
		$_data = array();
		
		foreach ( $data as $k => $v )
		{
			if ( isset( $this->_convertData[$k] ) )
			{
				# Make sure we use intval here as all 'forum' app fields are integers.
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
		$count     = 0;
		$lastItem  = 0;
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );

		if ( $data['testemunhoCat'] )
		{
			$_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt, MIN(t_date) as lastItem',
													   'from'   => 'testemunhos',
													   'where'  => 't_id NOT IN('.implode(',', array_keys($readItems)).') AND ( t_date > '.intval($lastReset).' OR t_last_comment > '.intval($lastReset) . ')' 
											   )	  );
													
			$count 	  = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );
		}
		
		return array( 'count'    => $count,
					  'lastItem' => $lastItem );
	}
}