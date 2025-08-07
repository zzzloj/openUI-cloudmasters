<?php

/**
 * The library
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

class mapLib
{
	function __construct()
	{
		$this->registry =	ipsRegistry::instance();
		$this->request  =&	$this->registry->fetchRequest();
		$this->settings  =&	$this->registry->fetchSettings();
		$this->dB		=	$this->registry->DB();
	}

	/**
	 * Get permissions, if not loaded load them
	 *
	 * @return	array		IPS Permission array
	 */
	public function getPermissions()
	{
		if(isset($this->_permissions) && is_array($this->_permissions))
        {
            return $this->_permissions;
        }

		return $this->_loadPermissions();
	}

	public function getSingleMarker( $userId )
	{
		$mkr = $this->dB->buildAndFetch(array(	'select' => 'm.*',
												'from'   => array( 'member_map' => 'm' ),
												'where'  => "m.member_id={$userId}"));

		return $mkr;
	}

    /**
     * Load permissions for the application from the database
     *
     * @return  array   IPS Permission Array
     */
    private function _loadPermissions()
    {
		$this->dB->build(array(	'select' => 'p.*',
                                'from'   => array( 'permission_index' => 'p' ),
                                'where'  => "p.perm_type='membermap' AND perm_type_id=1"));
        $this->dB->execute();

        $this->_permissions =  $this->dB->fetch();

        return $this->_permissions;
    }
}
?>