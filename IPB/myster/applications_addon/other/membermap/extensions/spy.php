<?php

/**
 * Content Spy Plugin
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

class spyPluginMembermap implements spyPlugin
{
	public $preParseData	=	array();

	private $_access			=	FALSE;

	function __construct($registry)
	{
		$this->registry = $registry;
		$this->dB       = $this->registry->DB();
		$this->lang		= $this->registry->getClass('class_localization');
		$this->settings =& $this->registry->fetchSettings();

        if ( $this->registry->permissions->check( 'view', $this->_loadMapPermissions() ) )
        {
			$this->_access = TRUE;
		}

		$this->registry->class_localization->loadLanguageFile( array( 'public_map' ), 'membermap' );
	}

	public function checkPermissions($data)
	{
		return $this->_access;
	}

	public function preParseData($data)
	{
		$this->preParsedData[] = $data;
	}

	public function parseData($data)
	{
		$data['where'] = IPSLib::getAppTitle('membermap');
		$data['where_link'] = ipsRegistry::getClass('output')->buildSEOUrl( 'app=membermap', 'public', 'none', 'membermap' );
		$data['replies'] = '&nbsp;';
		$data['what'] = $data['data_type'] == 'add_location' ? $this->lang->words['membermap_spy_add_parsed'] : $this->lang->words['membermap_spy_update'];

		$data['data_type_clean'] = $this->dataTypes($data['data_type']);

		return $data;
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

	private function dataTypes($type)
	{
		$types = array(	'add_location' =>	$this->lang->words['membermap_spy_add'],
						'update_location'	=>	$this->lang->words['membermap_spy_update']);

		return $types[$type];
	}
}

?>