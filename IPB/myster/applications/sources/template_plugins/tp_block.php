<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Template Pluging: CCS block parsing
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 */

/**
* Main loader class
*/
class tp_block extends output implements interfaceTemplatePlugins
{
	/**
	 * Prevent our main destructor being called by this class
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __destruct()
	{
	}
	
	/**
	 * Run the plug-in
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		if( !$data )
		{
			return;	
		}
		
		$_phpCode	= '<php>if( !( $this->registry->isClassLoaded(\'pageBuilder\') ) )' . "\n{\n";
		$_phpCode	.= "\t" . '$pluginLibHook = IPSLib::loadLibrary( IPSLib::getAppDir(\'ccs\') . \'/sources/pages.php\', \'pageBuilder\', \'ccs\' );' . "\n";
		$_phpCode	.= "\t" . '$this->registry->setClass(\'pageBuilder\', new $pluginLibHook( $this->registry ) );' . "\n";
		$_phpCode	.= "\t" . '$pluginLibHook = IPSLib::loadLibrary( IPSLib::getAppDir(\'ccs\') . \'/sources/functions.php\', \'ccsFunctions\', \'ccs\' );' . "\n";
		$_phpCode	.= "\t" . '$this->registry->setClass(\'ccsFunctions\', new $pluginLibHook( $this->registry ) );' . "\n}\n";
		$_phpCode	.= 'if( !$this->settings[\'disable_js_injection\'] AND !IPS_IS_AJAX ) {
			if( isset($documentHeadItems) ) { $documentHeadItems["raw"]["jsinjection"] = "<script type=\'text/javascript\'>" . $this->registry->getClass(\'ccsFunctions\')->injectBlockFramework(\'\', false, true ) . "</script>"; }
			else { $IPBHTML .= "<script type=\'text/javascript\'>" . $this->registry->getClass(\'ccsFunctions\')->injectBlockFramework(\'\', false, true ) . "</script>"; } }';
		$_phpCode	.= "</php>";
		$_phpCode	.= '" . $this->registry->getClass(\'pageBuilder\')->getBlock(\'' . $data . '\')  . "';
		
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return $_phpCode;
	}
	
	/**
	 * Return information about this modifier
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'block',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse block="a_block_key"}',
					  'options' => array() );
	}
}