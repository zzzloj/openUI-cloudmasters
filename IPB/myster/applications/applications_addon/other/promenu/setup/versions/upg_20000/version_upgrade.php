<?php
/**
 * ProMenu
 * Provisionists LLC
 *  
 * @ Package : 			ProMenu
 * @ File : 			version_upgrade.php
 * @ Last Updated : 	Apr 17, 2012
 * @ Author :			Robert Simons
 * @ Copyright :		(c) 2011 Provisionists, LLC
 * @ Link	 :			http://www.provisionists.com/
 * @ Revision : 		2
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry = $registry;
		
		/* Let's remove the old hooks files to avoid confusion */
		$hooksPath = IPSLib::getAppDir('promenu') . '/xml/hooks/';
		
		# v1.0.0 - v1.0.3
		@unlink( $hooksPath . 'Default.Application.Settings.xml' );
		@unlink( $hooksPath . 'Default.Menu.Removal.xml' );
		@unlink( $hooksPath . 'Display.ProMenu.xml' );
		# v1.0.4 - current
		@unlink( $hooksPath . 'ProMenu.Default.Application.xml' );
		
		$this->registry->output->addMessage("Custom upgrade script run");
		
		return true;
	}
}