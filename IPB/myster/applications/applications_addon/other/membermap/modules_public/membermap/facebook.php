<?php

/**
 * IP.Board Member Map - Facebook
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_membermap_membermap_facebook extends ipsCommand
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

        /**
         * Load Language
         */
        $this->registry->class_localization->loadLanguageFile( array( 'public_map' ) );

        switch($this->request['action'])
        {
            case 'update':
                $this->doUpdate();
            break;
            case 'add':
                $this->doAdd();
            break;
        }

		$this->doMap($friends);
    }

    /**
     * Check permissions and process data and add location to database.
     */
    public function doAdd()
    {
        $perm = $this->_loadMapPermissions();

		// guests can't alter markers
		if(!$this->_guestCheck())
		{
			return;
		}

        if ( $this->registry->permissions->check( 'add', $perm ) )
        {
            $result = $this->dB->buildAndFetch(array('select' => 'mm.member_id',
													'from'   => array( 'member_map' => 'mm' ),
													'where' => 'mm.member_id='.$this->memberData['member_id']));

            if($this->dB->getTotalRows() > 0)
            {
				$this->message = $this->lang->words['locationAlreadyAdded'];
            }

            if(isset($this->request['lat']) && isset($this->request['lon']))
            {
                $this->dB->insert('member_map', array('member_id' => $this->memberData['member_id'],
                                                        'lat' => $this->_floatVal($this->request['lat']),
                                                        'lon' => $this->_floatVal($this->request['lon'])));

                $this->message = $this->lang->words['locationAdded'];

				/* Support for the content spy application */
				if(IPSLib::appIsInstalled( 'spy' ))
				{
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'spy' ) . "/sources/spyLibrary.php", 'spyLibrary' );
					$this->spyLibrary = new $classToLoad( ipsRegistry::instance() );
					$data = array(	'app'		=>	'membermap',
									'data_type' =>	'add_location',
									'member_id'	=>	$this->memberData['member_id'],
									'approved'	=>	1);
					$this->spyLibrary->insertStreamData($data);
				}

                $this->_sendNotificationToFriends($this->memberData['member_id'], 'add');
            }
            else
            {
                $this->message = $this->lang->words['problemAddingLocation'];
            }
        }
        else
        {
			$this->message = $this->lang->words['noPermissionAdd'];
        }
    }

    /**
     * Same as above really... (Check permissions and process data and update location in database.)
     */
    public function doUpdate()
    {
        $perm = $this->_loadMapPermissions();

		// guests can't alter markers
		if(!$this->_guestCheck())
		{
			return;
		}

        if ( $this->registry->permissions->check( 'edit', $perm ) )
        {
            $result = $this->dB->buildAndFetch(array('select' => 'mm.member_id',
                                    'from'   => array( 'member_map' => 'mm' ),
                                    'where' => 'mm.member_id='.$this->memberData['member_id']));

            if($this->dB->getTotalRows() == 0)
            {
				$this->message = $this->lang->words['noUpdateExists'];
			}

            if(isset($this->request['lat']) && isset($this->request['lon']))
            {
                $this->dB->update('member_map',
                                    array('lat' => $this->_floatVal($this->request['lat']),
                                          'lon' => $this->_floatVal($this->request['lon'])),
                                  'member_id='.$this->memberData['member_id']);

				$this->message = $this->lang->words['locationUpdated'];

				/* Support for the content spy application */
				if(IPSLib::appIsInstalled( 'spy' ))
				{
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'spy' ) . "/sources/spyLibrary.php", 'spyLibrary' );
					$this->spyLibrary = new $classToLoad( ipsRegistry::instance() );
					$data = array(	'app'		=>	'membermap',
									'data_type' =>	'update_location',
									'member_id'	=>	$this->memberData['member_id'],
									'approved'	=>	1);
					$this->spyLibrary->insertStreamData($data);
				}

                $this->_sendNotificationToFriends($this->memberData['member_id'], 'update');
            }
            else
            {
                $this->message = $this->lang->words['problemUpdating'];
            }
        }
        else
        {
            $this->message = $this->lang->words['noPermissionUpdate'];
        }
    }

    /**
     * The 'meat', this will get the map up and running and on the users screen
     *
     * @param   boolean     show only the viewers friends
     */
    public function doMap()
    {
        $perm = $this->_loadMapPermissions();

        if ( $this->registry->permissions->check( 'view', $perm ) )
        {
            /**
             * Google is only supported at the moment, and let's face it,
             * It's not like I've announced that it's abstracted so we could use things like Bing! etc ;-)
             */
            $this->settings['membermapMapService'] = 'google';

            if(isset($this->settings['membermapMapService']) && $this->settings['membermapMapService'])
            {
                if(file_exists(IPSLib::getAppDir( 'membermap' ) . '/sources/classes/'.$this->settings['membermapMapService'].'.php'))
                {
                    require_once( IPSLib::getAppDir( 'membermap' ) . '/sources/classes/'.$this->settings['membermapMapService'].'.php' );
                    $this->mapFunctions = new membermapMapFunctions( $registry );

                    $key = "membermap".ucfirst($this->settings['membermapMapService'])."ApiKey";

                    if(isset($this->settings[$key]) && !empty($this->settings[$key]))
                    {
                        $this->mapFunctions->setApiKey( $this->settings[$key] );

                        $this->mapFunctions->setUpJsApi();

                        $query = array('select' => 'mm.*',
                                'from'   => array( 'member_map' => 'mm' ),
                            'add_join' => array(array('select' => 'm.member_id, m.members_display_name, m.members_seo_name',
                                                        'from'   => array( 'members' => 'm' ),
                                                        'where'  => "m.member_id=mm.member_id",
                                                        'type'   => 'left')));

                        $this->dB->build( $query );

                        $this->dB->execute();

                        while($mk = $this->dB->fetch())
                        {
                            $self = $this->memberData['member_id'] == $mk['member_id'] ? TRUE : FALSE;

                            $exists = $this->memberData['member_id'] == $mk['member_id'] ? TRUE : $exists;

                            $this->mapFunctions->addMarker($mk['lat'], $mk['lon'], $mk['member_id'], $self);
                        }

						// custom markers
						/* Process details for display */
						IPSText::getTextClass('bbcode')->parse_html = 0;
						IPSText::getTextClass('bbcode')->parse_nl2br = 1;
						IPSText::getTextClass('bbcode')->parse_bbcode = 1;
						IPSText::getTextClass('bbcode')->parse_smilies = 1;
						IPSText::getTextClass('bbcode')->parsing_section = 'maps_custommarkers';

						$query = array(	'select' => 'mcm.*',
										'from'   => array( 'member_map_cmarkers' => 'mcm' ),
										'add_join' => array(array('select' => 'mcmg.pin_colour, mcmg.pin_icon',
																	'from'   => array( 'member_map_cmarkers_groups' => 'mcmg' ),
																	'where'  => "mcm.g_id=mcmg.id",
																	'type'   => 'left')));

						$this->dB->build( $query );

                        $this->dB->execute();
						$customMarkers = array();
                        while($cm = $this->dB->fetch())
                        {
							$description = IPSText::getTextClass('bbcode')->preDisplayParse($cm['description']);
							$description = str_replace("\n", "", addslashes($description));
							$customMarkers[$cm['g_id']]['pinColour'] = $cm['pin_colour'];
							$customMarkers[$cm['g_id']]['pinIcon'] = $cm['pin_icon'];
							$customMarkers[$cm['g_id']]['markers'][] = array(	'lat' => $cm['lat'],
																				'lon' => $cm['lon'],
																				'title' => $cm['title'],
																				'description' => $description);
                        }

						$this->mapFunctions->setCustomMarkers($customMarkers);
						// custom markers

                        $canAdd = $this->registry->permissions->check( 'add', $perm );
                        $canEdit = $this->registry->permissions->check( 'edit', $perm );

						$cMarkers = $this->mapFunctions->getCustomMarkerJavascript();

						$map = $this->registry->getClass('output')->getTemplate('membermap')->mapFacebookTemplate( $canAdd, $canEdit, $exists, $this->mapFunctions->countMarkers(), $this->mapFunctions->countCMarkers(),
								 $this->mapFunctions->getMarkers(), $cMarkers, $this->message);
						$map = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $map );

						$map = str_replace('http://', 'https://', $map);
						echo $map; die;
                    }
                    else
                    {
                        $this->registry->getClass('output')->showError( 'noApiKey', 1902, FALSE );
                    }
                }
                else
                {
                    $this->registry->getClass('output')->showError( 'The map service ('.ucwords($this->settings['membermapMapService']).') selected does not exist.', 1903, FALSE );
                }
            }
        }
        else
        {
            $this->registry->getClass('output')->showError( 'noPermissionView', 1901, FALSE );
        }
    }

    /**
     * Load permissions for the application from the database
     *
     * @return  array   IPS Permission Array
     */
    private function _loadMapPermissions()
    {
        if(isset($this->_permissions) && is_array($this->_permissions))
        {
            return $this->_permissions;
        }

        $this->dB->build(array('select' => 'p.*',
                                'from'   => array( 'permission_index' => 'p' ),
                                'where'  => "p.perm_type='membermap' AND perm_type_id=1"));
        $this->dB->execute();

        $this->_permissions =  $this->dB->fetch();

        return $this->_permissions;
    }

    /**
     * Loops through users friends and fires a notification
     * @todo    Actually code the method...
     *
     * @param   integer     Members ID no.
     * @param   string      add or update
     */
    private function _sendNotificationToFriends($memberId, $type)
    {
		$friendArray = array_keys($this->memberData['_cache']['friends']);

		if(count($friendArray) > 0)
		{
			$friendData = IPSMember::load($friendArray);
			foreach($friendArray as $id)
			{
				$this->_sendNotification($friendData[$id], $type);
			}
		}
    }

    /**
     * Check for version 30001 or newer (IP.B 3.1+) then send notification using the new system.
     * @todo    Finish this method...
     *
     * @param   integer     User Id to send noticiation too.
     * @param   string      add or update, determines which notification template to use
     */
    private function _sendNotification($toMember, $type)
    {
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary = new $classToLoad( ipsRegistry::instance() );

		// You are using IP.Board 3.1 or newer! how cool are you ?
		$toMember['language'] = $toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $toMember['language'];

		$ndata = array('NAME'		=> $toMember['members_display_name'],
					   'OWNER'		=> $this->memberData['members_display_name'],
					   'URL'		=> $this->settings['base_url'] . 'app=core&amp;module=usercp&amp;tab=core&amp;area=notifications' );

		IPSText::getTextClass('email')->getTemplate( 'membermap_notification_'.$type, $toMember['language'], 'public_notifications', 'membermap' );

		IPSText::getTextClass('email')->buildMessage( $ndata );

		IPSText::getTextClass('email')->subject	= sprintf(
															IPSText::getTextClass('email')->subject,
															$this->registry->output->buildSEOUrl( 'showuser=' . $this->memberData['member_id'], 'public', $this->memberData['members_seo_name'], 'showuser' ),
															$this->memberData['members_display_name'],
															$this->registry->output->buildSEOUrl( 'app=membermap', 'public', FALSE, 'membermap' )
														);

		$notifyLibrary->setMember( $toMember );
		$notifyLibrary->setFrom( $this->memberData );
		$notifyLibrary->setNotificationKey( 'membermap_'.$type.'_location' );
		$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
		$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
		try
		{
			$notifyLibrary->sendNotification();
		}
		catch( Exception $e ){}
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
            $floatString = str_replace($localeInfo["thousands_sep"], "", $floatString);
            $floatString = str_replace($localeInfo["decimal_point"], ".", $floatString);
        }
        return $floatString;
    }

	/**
	 * Check if the user is a guest, give them an error if they are
	 */
	private function _guestCheck()
	{
		if($this->memberData['member_id'] == 0)
		{
			$this->message = $this->lang->words['guestsCannotAdd'];
			return FALSE;
		}
		return TRUE;
	}
}