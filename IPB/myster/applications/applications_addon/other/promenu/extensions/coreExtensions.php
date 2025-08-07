<?php
/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
$_PERM_CONFIG = array('Menu');

class promenuPermMappingMenu {

	private $mappings = array('view' => 'perm_view');
	private $perm_names = array('view' => 'Display Menu');
	private $perm_colours = array('view' => '#fff0f2');

	public function getMapping() {
		return $this->mappings;
	}

	public function getPermNames() {
		return $this->perm_names;
	}

	public function getPermColors() {
		return $this->perm_colours;
	}

}

class publicSessions__promenu {

	public function getSessionVariables() {
		return array();
	}

	//return rows untouched.
	public function parseOnlineEntries($rows) {
		return $rows;
	}

}