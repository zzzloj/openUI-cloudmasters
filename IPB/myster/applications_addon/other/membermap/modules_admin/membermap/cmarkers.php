<?php

/**
 * Custom Markers ACP
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

if ( ! defined( 'IN_ACP' ) )
{
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class admin_membermap_membermap_cmarkers extends ipsCommand
{
    /**
     * Class entry point
     *
     * @access	public
     * @param	object		Registry reference
     * @return	void		[Outputs to screen/redirects]
     */
    public function doExecute( ipsRegistry $registry )
    {
		/* Keep my camel case naming standards happy */
		$this->dB = $this->DB;

		$this->html = $this->registry->output->loadTemplate( 'cp_skin_membermap' );

		switch($this->request['action'])
		{
			case 'newmarkergroup':
				$this->_newCMarkerGroup();
			break;

			case 'newmarker':
				$this->_newCMarker();
			break;

			case 'editmarkergroup':
				$this->_editCMarkerGroup();
			break;

			case 'editmarker':
				$this->_editCMarker();
			break;

			case 'removemarkergroup':
				$this->_removeCMarkerGroup();
			break;

			case 'removemarker':
				$this->_removeCMarker();
			break;

			default:
				$this->_cMarkerOverview();
			break;
		}
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
	}

	/**
	 * Add a new custom marker to the map
	 */
	private function _newCMarkerGroup()
	{
		if(isset($this->request['pin_icon']))
		{
			if(empty($this->request['pin_icon']) || empty($this->request['pin_colour']) || empty($this->request['g_title']))
			{
				$this->registry->output->global_message = 'You have not filled in all of the form fields, please submit the form again.';
				$this->registry->output->html .= $this->html->membermapMarkerGroupForm($this->_getMarkerIconArray());
			}
			else
			{
				$this->dB->insert('member_map_cmarkers_groups', array('g_title' => $this->request['g_title'],
																		'pin_colour' => $this->request['pin_colour'],
																		'pin_icon' => $this->request['pin_icon']));

				$this->registry->output->global_message =  'The marker group has been added.';
				$this->_cMarkerOverview();
			}
		}
		else
		{
			$this->registry->output->html .= $this->html->membermapMarkerGroupForm($this->_getMarkerIconArray());
		}
	}

	/**
	 * edit existing custom markers
	 */
	private function _editCMarkerGroup()
	{
		if(isset($this->request['pin_icon']))
		{
			if(empty($this->request['pin_icon']) || empty($this->request['pin_colour']) || empty($this->request['g_title']))
			{
				$this->registry->output->global_message = 'You have not filled in all of the form fields, please submit the form again.';
				$this->registry->output->html .= $this->html->membermapMarkerGroupForm($this->_getMarkerIconArray(), 'edit');
			}
			else
			{
				$this->dB->update('member_map_cmarkers_groups', array('g_title' => $this->request['g_title'],
																		'pin_colour' => $this->request['pin_colour'],
																		'pin_icon' => $this->request['pin_icon']), 'id='.intval($this->request['do']));

				$this->registry->output->global_message = 'The marker group has been updated.';
				$this->_cMarkerOverview();
			}
		}
		else
		{
			$_POST = $this->dB->buildAndFetch(array('select' => 'mcg.*',
													'from' => array('member_map_cmarkers_groups' => 'mcg'),
													'where' => 'id='.intval($this->request['do'])));
			$this->registry->output->html .= $this->html->membermapMarkerGroupForm($this->_getMarkerIconArray(), 'edit');
		}
	}

	/**
	 * remove existing custom marker
	 */
	private function _removeCMarkerGroup()
	{
		if(isset($this->request['move_to']) && isset($this->request['do']))
		{
			if($this->request['move_to'] == $this->request['do'])
			{
				$this->registry->output->global_message = 'That is just silly, you cannot move the markers to a group you are about to delete.';
				$this->registry->output->html .= $this->html->membermapMarkerGroupDeleteForm($this->_getGroupArray($this->request['do']));
				return;
			}
			$this->dB->update('member_map_cmarkers', array('g_id' => intval($this->request['move_to'])), 'g_id='.intval($this->request['do']));
			$this->dB->delete('member_map_cmarkers_groups', 'id='. intval($this->request['do']));
			$this->registry->output->global_message = 'The marker group has been removed.';
			$this->_cMarkerOverview();
		}
		else
		{
			if(count($this->_getGroupArray($this->request['do'])) == 0 && $this->request['request_method'] == 'post')
			{
				$this->dB->delete('member_map_cmarkers_groups', 'id='. intval($this->request['do']));
				$this->registry->output->global_message = 'The marker group has been removed.';
				$this->_cMarkerOverview();
			}
			else
			{
				$this->registry->output->html .= $this->html->membermapMarkerGroupDeleteForm($this->_getGroupArray($this->request['do']));
			}
		}
	}

	/**
	 * Add a new custom marker to the map
	 */
	private function _newCMarker()
	{
		$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();

		if(isset($this->request['title']))
		{
			if(empty($this->request['title']) || empty($this->request['g_id'])
					|| empty($this->request['lat']) || empty($this->request['lon']))
			{
				$this->registry->output->global_message = 'You have not filled in all of the form fields, please submit the form again.';
				$this->editor->setContent( $_POST['description'] );
				$editor = $this->editor->show( 'description' );
				$this->registry->output->html .= $this->html->membermapMarkerForm($this->_getGroupArray(), 'add', FALSE, $editor);
			}
			else
			{
				$this->request['description'] = $this->editor->process( $_POST['description'] );
				$this->dB->insert('member_map_cmarkers', array('title' => $this->request['title'],
																'lat' => $this->_floatVal($this->request['lat']),
																'lon' => $this->_floatVal($this->request['lon']),
																'description' => $this->request['description'],
																'g_id' => $this->request['g_id']));

				$this->registry->output->global_message =  'The marker has been added.';
				$this->_cMarkerOverview();
			}
		}
		else
		{
			if(count($this->_getGroupArray()) == 0)
			{
				$this->registry->output->global_message = 'You need to add a custom marker group before you can add markers.';
				$disabled = 1;
			}
			$editor = $this->editor->show( 'description' );
			$this->registry->output->html .= $this->html->membermapMarkerForm($this->_getGroupArray(), 'add', $disabled, $editor);
		}
	}

	/**
	 * edit existing custom markers
	 */
	private function _editCMarker()
	{
		$classToLoad  = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();

		if(isset($this->request['title']))
		{
			if(empty($this->request['title']) || empty($this->request['g_id'])
					|| empty($this->request['lat']) || empty($this->request['lon']))
			{
				$this->registry->output->global_message = 'You have not filled in all of the form fields, please submit the form again.';
				$this->editor->setContent( $_POST['description'] );
				$editor = $this->editor->show( 'description' );
				$this->registry->output->html .= $this->html->membermapMarkerForm($this->_getGroupArray(), 'edit', FALSE, $editor);
			}
			else
			{
				$this->request['description'] = $this->editor->process( $_POST['description'] );

				$this->dB->update('member_map_cmarkers', array('title' => $this->request['title'],
															'lat' => $this->_floatVal($this->request['lat']),
															'lon' => $this->_floatVal($this->request['lon']),
															'description' => $this->request['description'],
															'g_id' => intval($this->request['g_id'])), 'm_id='.intval($this->request['do']));

				$this->registry->output->global_message = 'The marker has been updated.';
				$this->_cMarkerOverview();
			}
		}
		else
		{
			$_POST = $this->dB->buildAndFetch(array('select' => 'mcm.*',
														'from' => array('member_map_cmarkers' => 'mcm'),
														'where' => 'm_id='.intval($this->request['do'])));

			$this->editor->setContent( $_POST['description'] );
			$editor = $this->editor->show( 'description' );

			$this->registry->output->html .= $this->html->membermapMarkerForm($this->_getGroupArray(), 'edit', FALSE, $editor);
		}
	}

	/**
	 * remove existing custom marker
	 */
	private function _removeCMarker()
	{
		if(isset($this->request['delete']) && isset($this->request['do']))
		{
			$this->dB->delete('member_map_cmarkers', 'm_id='. intval($this->request['do']));
			$this->registry->output->global_message = 'The marker has been removed.';
			$this->_cMarkerOverview();
		}
		else
		{
			$this->registry->output->html .= $this->html->membermapMarkerDeleteForm();
		}
	}

	/**
	 * Custom Marker overview
	 */
	private function _cMarkerOverview()
	{
		$_perPage = 30;
		$_st = intval($this->request['st']);

		$count = array(	'select' => 'COUNT(*) as count',
						'from'   => array( 'member_map_cmarkers_groups' => 'mcmg' ),
						'add_join' => array(array(	'from'   => array( 'member_map_cmarkers' => 'mcm' ),
													'where'  => "mcm.g_id=mcmg.id",
													'type'   => 'left')));

		$count = $this->dB->buildAndFetch( $count );

		// custom markers
		$pages = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['count'],
																	 'itemsPerPage'			=> $_perPage,
																	 'currentStartValue'	=> $_st,
																	 'baseUrl'				=> $this->settings['this_url'],
														)		);

		$query = array(	'select' => 'mcmg.*',
						'from'   => array( 'member_map_cmarkers_groups' => 'mcmg' ),
						'add_join' => array(array('select' => 'mcm.*',
													'from'   => array( 'member_map_cmarkers' => 'mcm' ),
													'where'  => "mcm.g_id=mcmg.id",
													'type'   => 'left')),
						'limit'		=> array( $_st, $_perPage ));

		$this->dB->build( $query );

		$this->dB->execute();
		$customMarkers = array();
		while($cm = $this->dB->fetch())
		{
			$customMarkers[$cm['id']]['pinColour'] = $cm['pin_colour'];
			$customMarkers[$cm['id']]['pinIcon'] = $cm['pin_icon'];
			$customMarkers[$cm['id']]['gTitle'] = $cm['g_title'];
			if(!empty($cm['title']))
			{
				$customMarkers[$cm['id']]['markers'][] = array(	'id' => $cm['m_id'],
																'title' => $cm['title']);
			}
		}
		$this->registry->output->html .= $this->html->membermapCustomMarkerTop();
		$this->registry->output->html .= $this->html->membermapCustomMarkerTable( $customMarkers, $pages );
	}

	/**
	 * Fetch array of icons for dropdown form field
	 *
	 * @return	array
	 */
	private function _getMarkerIconArray()
	{
		return array(
					array('accomm', 'Accommodation'),
					array('airport', 'Airport'),
					array('baby', 'Baby'),
					array('bar', 'Bar'),
					array('bicycle', 'Bicycle'),
					array('bus', 'Bus'),
					array('cafe', 'Cafe'),
					array('camping', 'Camping'),
					array('car', 'Car'),
					array('caution', 'Caution'),
					array('cinema', 'Cinema'),
					array('computer', 'Computer'),
					array('corporate', 'Corporate'),
					array('dollar', 'Dollar'),
					array('euro', 'Euro'),
					array('fire', 'Fire'),
					array('flag', 'Flag'),
					array('floral', 'Floral'),
					array('helicopter', 'Helicopter'),
					array('home', 'Home'),
					array('info', 'Info'),
					array('landslide', 'Landslide'),
					array('legal', 'Legal'),
					array('location', 'Location'),
					array('locomotive', 'Locomotive'),
					array('medical', 'Medical'),
					array('mobile', 'Mobile/Cell Phone'),
					array('motorcycle', 'Motorcycle'),
					array('music', 'Music'),
					array('parking', 'Parking'),
					array('pet', 'Pet'),
					array('petrol', 'Petrol'),
					array('phone', 'Phone'),
					array('picnic', 'Picnic'),
					array('postal', 'Postal'),
					array('pound', 'Pound'),
					array('repair', 'Repair'),
					array('restaurant', 'Restaurant'),
					array('sail', 'sail'),
					array('school', 'School'),
					array('scissors', 'Scissors'),
					array('ship', 'Ship'),
					array('shoppingbag', 'Shopping Bag'),
					array('shoppingcart', 'Shopping Cart'),
					array('ski', 'Ski'),
					array('snack', 'Snack'),
					array('snow', 'Snow'),
					array('sport', 'Sport'),
					array('swim', 'Swim'),
					array('taxi', 'Taxi'),
					array('train', 'Train'),
					array('truck', 'Truck'),
					array('wheelchair', 'Wheelchair'),
					array('yen', 'Yen'));
	}

	private function _getGroupArray($omit=FALSE)
	{
		$query = array(	'select' => 'g.*',
						'from' => array('member_map_cmarkers_groups' => 'g'));

		if($omit)
		{
			$query['where'] = 'id!='.intval($omit);
		}

		$this->dB->build($query);

		$this->dB->execute();

		$groups = array();
		while($grp = $this->dB->fetch())
		{
			$groups[] = array($grp['id'], $grp['g_title']);
		}

		return $groups;
	}



    /**
     * Locale friendly floatval() ready for MySQL
     *
     * @param   string  float value
     * @return  integer floated integer
     */
    private function _floatVal($floatString)
    {
        $floatString = floatval($floatString);

        if($floatString)
        {
            $localeInfo = localeconv();
            $floatString = str_replace($localeInfo["mon_thousands_sep"], "", $floatString);
            $floatString = str_replace($localeInfo["mon_decimal_point"], ".", $floatString);
        }
        return $floatString;
    }
}
?>