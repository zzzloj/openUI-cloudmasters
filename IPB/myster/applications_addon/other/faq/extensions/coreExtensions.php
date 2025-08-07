<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$_PERM_CONFIG = array('question');

class faqPermMappingQuestion
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	private
	 * @var		array
	 */
	private $mapping = array(
								'view'     => 'perm_view',
								'add'      => 'perm_2',
								'moderate'   => 'perm_3',
                                'approve' => 'perm_4',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_names = array(
								'view'     => 'Show FAQ',
								'add'    => 'Add Questions',
								'moderate'  => 'Moderate Questions',
                                'approve' => 'Approve Questions',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	private
	 * @var		array
	 */
	private $perm_colors = array(
								'view'     => '#fff0f2',
								'add'    => '#effff6',
								'moderate'    => '#edfaff',
                                'approve' => '#f0f1ff',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}
}