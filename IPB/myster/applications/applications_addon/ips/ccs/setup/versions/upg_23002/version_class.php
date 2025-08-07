<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2011-05-24 15:40:20 -0400 (Tue, 24 May 2011) $
 * </pre>
 * 
 * @author		bfarber
 * @version		$Rev: 8884 $
 * @since		29th December 2011
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Content
 */ 

class version_class_ccs_23002
{
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @return	string	 HTML block
	 */
	public function preInstallOptionsForm()
	{
$_formHtml	= <<<EOF
	<br /><br />
	<h2>Upgrade default templates</h2>
	<table width='100%'>
		<tr>
			<td width='40%'>
				<input type='checkbox' name='ccs_upgrade_templates' value='1' checked='checked' id='ccs_upgrade_templates' />
			</td>
			<td valign='top'>
				<label for='ccs_upgrade_templates' style='font-size: 12px; font-weight: bold;'>Upgrade database and article templates?</label>
				<div style='font-size:10px;'>Selecting this option will update your database and article templates to the new defaults during the upgrade.  
					This overwrite any customizations you have made to the templates, but will ensure they are up to date.</div>
			</td>
		</tr>
	</table>
EOF;

		return $_formHtml;
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @return	array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
		/* Return */
		$_return	= array(
							'updateTemplates'	=> intval($_REQUEST['ccs_upgrade_templates']),
							);

		return $_return;
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @return	array	 Array of notices
	 */
	public function postInstallNotices()
	{
		return array();
	}
}
