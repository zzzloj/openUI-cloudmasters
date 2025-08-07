<?php

##########################
## OK, READY TO RELEASE ##
##########################

class publicSessions__topicslist
{

public function getSessionVariables()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$array = array( 'location_1_type'   => '',
						'location_1_id'     => 0,
						'location_2_type'   => '',
						'location_2_id'     => 0 );
		
		if( ( isset(ipsRegistry::$request['f']) AND ipsRegistry::$request['f'] ) AND ipsRegistry::$request['section'] == 'view' AND ipsRegistry::$request['module'] == 'list' )
		{
			$array['location_1_type']	= 'view';
			$array['location_1_id']		=  ipsRegistry::$request['f'];
		}

		return $array;
	}
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_home' ), 'topicslist' );
		
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		$final		= array();
		$inside 	= array();
		$names		= array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'topicslist' OR !$row['current_module'] )
			{
				continue;
			}

			if( $row['current_section'] == 'view' )
			{
				$inside[]				= $row['location_1_id'];
			}
		}

		if( count($inside) )
		{
			ipsRegistry::DB()->build( array( 'select' => 'id, name, name_seo', 'from' => 'forums', 'where' => 'id IN (' . implode( ',', $inside ) . ')' ) );
			$pr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($pr) )
			{
				$names[ $r['id'] ] = array( 'name' => $r['name'], 'seo'	=>	$r['name_seo'], 'id'	=>	$r['id'], );
			}
		}

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'topicslist' )
			{
				if( $row['current_section'] == 'view' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['tl_wherewall'];
					$row['where_line_more']	= $names[ $row['location_1_id'] ]['name'];
					$row['where_link']		= 'app=topicslist&amp;module=list&amp;section=view&amp;f=' . $row['location_1_id'];

				}
				
				if( $row['current_section'] == 'forums' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['tl_whereinf'];
					$row['where_link']		= 'app=topicslist&amp;module=list&amp;section=forums';

				}
				
				else
				{
				
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['tl_whereinhome'];
					$row['where_link']		= 'app=topicslist';
				
				}

			}
			
			$final[ $row['id'] ]	= $row;
		}
		
		return $final;
	}
}

?>