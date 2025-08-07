<?php
class publicSessions__quiz
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @return	array
	 */
	public function getSessionVariables()
	{
		$result = array(
				'location_1_type' => 'quiz',
				'location_1_id' => 0,
				'location_2_type' => '',
				'location_2_id' => 0,
		);

		if (ipsRegistry::$request['module'] == 'quiz')
		{
			$result['location_1_type'] = 'quiz';
			$result['location_2_type'] = ipsRegistry::$request['section'];
			$result['location_2_id'] = (isset(ipsRegistry::$request['quiz']) ? intval(ipsRegistry::$request['quiz']) : 0);
		}

		if (ipsRegistry::$request['module'] == 'categories')
		{
			$result['location_1_type'] = 'categories';
			$result['location_2_type'] = ipsRegistry::$request['section'];
			$result['location_2_id'] = (isset(ipsRegistry::$request['categories']) ? ipsRegistry::$request['categories'] : 0);
		}

		return $result;
	}

	/**
	 * Parse/format the online list data for the records
	 *
	 * @param	array			 Online list rows to check against
	 * @return	array			 Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
	
		//-----------------------------------------
		// Init
		//-----------------------------------------
	
		$records	= array();
		$data		= array();
		$final		 = array();
	
		//-----------------------------------------
		// Extract the data
		//-----------------------------------------
	
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'quiz' OR !$row['current_module'] )
			{
				continue;
			}
				
			if( $row['current_module'] == 'quiz' )
			{
				if( $row['current_section'] == 'quiz' )
				{
					$records[ $row['location_1_id'] ]	= $row['location_1_id'];
				}
			}
		}
	
		//-----------------------------------------
		// Get records
		//-----------------------------------------
	
		if( count($records) )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'quiz_quizzes', 'where' => 'quiz_approved=1 AND quiz_id IN(' . implode( ',', $records ) . ')' ) );
			$tr = ipsRegistry::DB()->execute();
				
			while( $r = ipsRegistry::DB()->fetch($tr) )
			{
				$data[ $r['quiz_id'] ]	= $r;
			}
		}
	
		//-----------------------------------------
		// Put humpty dumpty together again
		//-----------------------------------------
	
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'quiz' )
			{
				$final[ $row['id'] ]	= $row;
	
				continue;
			}
				
			if( $row['current_module'] == 'quiz' )
			{
				if( $row['current_section'] == 'quiz' )
				{
					if( isset($data[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['quiz_wol_viewing_quiz'];
						$row['where_line_more']	= $data[ $row['location_1_id'] ]['title'];
						$row['where_link']		= 'app=quiz&amp;module=quiz&amp;section=quiz&amp;view=' . $row['location_1_id'];
						$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $data[ $row['location_1_id'] ]['title_furl'], 'quizquiz' );
					}
				}
			}
			else
			{
				$row['where_link']		= 'app=quiz';
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['quiz_wol_quiz'];
				$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', 'false', 'app=quiz' );
			}
	
			$final[ $row['id'] ]	= $row;
		}
	
		return $final;
	}

}