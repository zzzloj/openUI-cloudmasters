<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Moderator Plugin module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class tracker_admin_moderator_form__severity_field_severity extends iptCommand implements tracker_admin_moderator_form
{
	public function doExecute( ipsRegistry $registry ) {}

	/**
	 * Returns HTML table content for the page.
	 *
	 * @param array $moderator Moderator data
	 * @return string HTML for the form
	 * @access public
	 * @since 2.0.0
	 */
	public function getDisplayContent( $moderator=array() )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->html = ipsRegistry::getClass('tracker')->modules()->loadTemplate('cp_skin_module_severity_moderator_form', 'severity');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_module_severity' ), 'tracker' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		return $this->html->acp_moderator_form_main( $moderator );
	}

	/**
	 * Process the entries for saving and return
	 *
	 * @return array Array of keys => values for saving
	 * @access public
	 * @since 2.0.0
	 */
	public function getForSave()
	{
		$return = array(
			'severity_field_severity_show'     => intval(ipsRegistry::$request['severity_field_severity_show']),
			'severity_field_severity_submit'   => intval(ipsRegistry::$request['severity_field_severity_submit']),
			'severity_field_severity_update'   => intval(ipsRegistry::$request['severity_field_severity_update'])
		);

		return $return;
	}
}

?>