<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class proPerms {

	/**
	 * Registry Object Shortcuts
	 * @var object $registry
	 */
	protected $registry;

	/**
	 * Constructor
	 * @return @e void
	 */
	public function __construct(ipsRegistry $registry) {
		$this->registry = $registry;

		require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );

		$this->permissions = new classPublicPermissions($this->registry);
	}

	/**
	 * getPermissionsMatrix
	 * @param array $menu
	 * @return string
	 */
	public function getPermissionsMatrix($menu) {
		$matrix_html = $this->permissions->adminPermMatrix('menu', $menu);

		return $matrix_html;
	}

	/**
	 * savePermssionMatric
	 * @param string $menu
	 * @param array $perms
	 * @return @e void
	 */
	public function savePermissionsMatrix($menu, $perms) {
		$this->permissions->savePermMatrix($perms, $menu, 'menu', 'promenu');
	}

}