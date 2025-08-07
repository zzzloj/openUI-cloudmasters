<?php
/**
 * @file		cp_skin_idm_download_category_form.php 	IP.Downloads example category form skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-04-26 14:39:00 -0400 (Tue, 26 Apr 2011) $
 * @version		v2.5.4
 * $Revision: 8482 $
 */

/**
 *
 * @class		cp_skin_idm_group_form
 * @brief		IP.Downloads example category form skin file
 */
class cp_skin_idm_download_category_form
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}

/**
 * Main form to edit group settings
 *
 * @param	array		$category		Category data
 * @param	mixed		$tabId			Tab ID
 * @return	@e string	HTML
 */
public function acp_downloads_category_form_main( $category, $tabId ) {

$form					  = array();
$form['example_yesno']    = $this->registry->output->formYesNo( 'example_yesno', $category['example_yesno'] );
$form['example_input']    = $this->registry->output->formInput( 'example_input', $category['example_input'] );
$form['example_textarea'] = $this->registry->output->formTextarea( 'example_textarea', $category['example_textarea'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_CustomTab{$tabId}_content'>
	<table class='ipsTable double_pad'>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Yes/No field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_yesno']}
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Input field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_input']}<br />
				<span class='desctext'>Input field example description</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Textarea field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_textarea']}<br />
				<span class='desctext'>Textarea field example description</span>
		    </td>
	 	</tr>
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Tabs for the group form
 *
 * @param	array		$category		Category data
 * @param	mixed		$tabId			Tab ID
 * @return	@e string	HTML
 */
public function acp_downloads_category_form_tabs( $category, $tabId ) {

$IPBHTML = "<li id='tab_CustomTab{$tabId}'>" . IPSLib::getAppTitle('downloads') . "</li>";

return $IPBHTML;
}

}
