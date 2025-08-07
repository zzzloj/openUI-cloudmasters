<?php

/**
 * Membermap Ajax
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

class admin_membermap_ajax_ajax extends ipsAjaxCommand
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
        switch($this->request['action'])
        {
            case 'find':
                $this->doFind();
            break;
        }
    }

    /**
     * ADD LOCATION
     *
     * Check permissions and return array of possible address matches
     * Or Javascript alert with the error string
     */
    public function doFind()
    {
		$address = $this->_getMapGeocodeFunctions();

		if(is_array($address))
		{
			$this->returnJsonArray($address);
		}
		else
		{
			$this->returnJsonArray(array('error' => TRUE, 'message' => $address));
		}
    }

    /**
     * Sends geocode request off to chosen provider.
     *
     * @return  array|string    array of results or error string
     */
    private function _getMapGeocodeFunctions()
    {
        $this->request['do'] = $this->convertAndMakeSafe($this->request['do']);
        // Google is only supported at the moment, and let's face it,
        // It's not like I've announced that it's abstracted so we could use things like Bing! etc ;-)
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

                    $addressData = $this->mapFunctions->geocode($this->request['do']);

                    return $addressData;
                }
            }
        }
    }
}