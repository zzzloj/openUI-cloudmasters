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
* @subpackage	Module-Privacy
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class tracker_admin_moderator_form__privacy_field_privacy extends iptCommand implements tracker_admin_moderator_form
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
		$this->html = ipsRegistry::getClass('tracker')->modules()->loadTemplate('cp_skin_module_privacy_moderator_form', 'privacy');

		//-----------------------------------------
		// Load lang
		//-----------------------------------------
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_module_privacy' ), 'tracker' );

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
			'privacy_field_privacy_show'     => intval(ipsRegistry::$request['privacy_field_privacy_show']),
			'privacy_field_privacy_submit'   => intval(ipsRegistry::$request['privacy_field_privacy_submit']),
			'privacy_field_privacy_update'   => intval(ipsRegistry::$request['privacy_field_privacy_update'])
		);

		return $return;
	}
}

?>