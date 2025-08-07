<?php

/**
 * Google Maps Driver
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

class membermapMapFunctions
{
    /**
     * Registry reference
     *
     * @access	public
     * @var		object
     */
    public      $registry;
    public		$dB;

    /**
     * protected variables
     */
    protected   $_apiKey;
    private     $_markers = array();
	private		$_memberIds = array();
	private		$_customMarkers = array();

    /**
     * URL to Google's API
     *
     * @var string Js Api URL 
     */
    protected $_jsUrl = 'http://maps.google.com/maps?file=api&amp;v=2&amp;key=';

    /**
     * CONSTRUCTOR
     *
     * @access	public
     * @return	void
     **/
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->dB       = $this->registry->DB();
    }

    /**
     * Set Api Key variable
     *
     * @param   string  Google Maps api key
     */
    public function setApiKey( $key )
    {
        $this->_apiKey = $key;
    }

    /**
     * Include required Google Maps Javascript
     */
    public function setUpJsApi()
    {
        $this->registry->output->addToDocumentHead('javascript', $this->_jsUrl . $this->_apiKey);
    }

    /**
     * Build Geocode request URI with Api Key and Address
     *
     * @param   string  address to geocode
     * @return  string  geocode request uri
     */
    private function _getGeocodeUri($address)
    {
        $address = urlencode($address);

        return "http://maps.google.com/maps/geo?q={$address}&output=xml&key={$this->_apiKey}";
    }

    /**
     * Add marker for location
     *
     * @param string    HTML to be included in infoWindow
     * @param integer   Latitude
     * @param integer   Longitude
     * @param integer   Member ID of marker owner
     * @param boolean   TRUE for own marker
     */
    public function addMarker($lat, $lon, $memberId, $self=FALSE)
    {
        $mrkr = array(  'lat' => $lat,
                        'lon' => $lon,
                        'self' => $self,
                        'memberId' => $memberId);

        $this->_memberIds[] = $memberId;

        $this->_markers[$memberId] = $mrkr;
    }

    /**
     * Fetch the array of markers for the map
     *
     * @return  array   Marks for the map!
     */
    public function getMarkers()
    {
        if(count($this->_memberIds) > 0)
        {
            $data = IPSMember::load($this->_memberIds, 'profile_portal,groups');
            foreach ($data as $key => $val)
            {
                $this->_markers[$key]['memberData'] = $val;
            }
        }

        return $this->_markers;
    }

    /**
     * Count markers
     *
     * @return  array   Count of markers for the map!
     */
    public function countMarkers()
    {
        return count($this->_markers);
    }

    /**
     * Count custom markers
     *
     * @return  array   Count of markers for the map!
     */
    public function countCMarkers()
    {
		$count = 0;

		foreach($this->_customMarkers as $c)
		{
			$count = $count + count($c['markers']);
		}
        return $count;
    }

	/**
	 * Get the custom markers so we can insert them
	 * into the template..
	 *
	 * @param	array	custom marker array
	 */
	public function setCustomMarkers($markers)
	{
		$this->_customMarkers = $markers;
	}

    /**
     * Return array of Geocode matches.
     *
     * @param   string          address to geocode
     * @return  array|boolean   array of address matches, or error string
     */
    public function geocode($address)
    {
        $uri = $this->_getGeocodeUri($address);

        // get the IPS filemanagement class.
        require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
        $fileManagement = new classFileManagement;

        // fetch the remote contents, order: cUrl, Sockets, fOpen
        $geocodeXml = $fileManagement->getFileContents($uri);

        if($geocodeXml === FALSE)
        {
            return 'There was an error communicating with Google, Please contact an administrator.';
        }

        /**
        * Include the new classXML and start in UTF-8
        */
        require_once( IPS_KERNEL_PATH.'classXML.php' );
        $xml = new classXML('utf-8');

        $xml->loadXML($geocodeXml);

        $xmlArray = $xml->fetchXMLAsArray();

        $returnArray = array('status' => $xmlArray['kml']['Response']['Status']['code']['#alltext']);

        if($returnArray['status'] == 200)
        {
            if(!isset($xmlArray['kml']['Response']['Placemark']['address']))
            {
                foreach($xmlArray['kml']['Response']['Placemark'] as $key => $placemark)
                {
                    $this->_addAddressToArray(  $key,
                                                $placemark['address']['#alltext'],
                                                $placemark['Point']['coordinates']['#alltext']);
                }
            }
            else
            {
                $this->_addAddressToArray(  0,
                                            $xmlArray['kml']['Response']['Placemark']['address']['#alltext'],
                                            $xmlArray['kml']['Response']['Placemark']['Point']['coordinates']['#alltext']);
            }
            unset($xmlArray);

            return $this->_addressResults;
        }
        else
        {
           return 'There was a Google Maps error: '. $this->_getGeocodeApiError($returnArray['status']);
       }
    }

    private function _addAddressToArray($id, $address, $latLon)
    {
        $latLon = explode(',', $latLon);

        $this->_addressResults[$id] = array(    'address' => $address,
                                                'longitude' => $latLon[0],
                                                'latitude' => $latLon[1]);
    }

    /**
     * Find out the error from the status code
     *
     * @param   integer HTTP status code from geocode request
     * @return  string  error reason string
     */
    private function _getGeocodeApiError($code)
    {
        switch($code)
        {
            case 601:
                return 'An incomplete address was entered.';
            break;
            case 602:
                return 'The address could not be found.';
            break;
            case 603:
                return 'Google are not permitted to display this address.';
            break;
            case 620:
                return 'The request was throttled please try again in a moment.';
            break;
            default:
                return 'There was an unspecified problem locating the address, please try again.';
            break;
        }
    }

    /**
     * Add the markers to the template, and add the template to the output.
     */
    public function addMapJsToTemplate()
    {
        $this->registry->output->addContent( $this->registry->getClass('output')->getTemplate('membermap')->mapGoogleJavascript( $this->getMarkers() ) );

		$this->registry->output->addContent( $this->getCustomMarkerJavascript() );
	}

	public function getCustomMarkerJavascript()
	{
		$html = '';
		if(count($this->_customMarkers))
		{
			foreach($this->_customMarkers as $mkrs)
			{
				$html .= $this->registry->getClass('output')->getTemplate('membermap')->mapGoogleCustomMarkers( $mkrs['pinIcon'], $mkrs['pinColour'], $mkrs['markers'] );
			}
		}
		return $html;
	}
}

?>
