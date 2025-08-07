<?php
/**
 * (e$30) Custom Sidebar Blocks
 */

$_PERM_CONFIG = array( 'Block' );

class customSidebarBlocksPermMappingBlock
{
	/**
	 * Mapping of keys to columns
	 */
	private $mapping = array( 'view' => 'perm_view' );

	/**
	 * Mapping of keys to names
	 */
	private $perm_names = array( 'view' => 'View Block' );

	/**
	 * Mapping of keys to background colors for the form
	 */
	private $perm_colors = array( 'view' => '#fff0f2' );

	/**
	 * Method to pull the key/column mapping
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}
}