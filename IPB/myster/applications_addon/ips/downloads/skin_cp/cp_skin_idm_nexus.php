<?php
/**
 * @file		cp_skin_idm_nexus.php 	Nexus skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-04-22 11:14:40 -0400 (Fri, 22 Apr 2011) $
 * @version		v2.5.4
 * $Revision: 8449 $
 */

/**
 *
 * @class		cp_skin_idm_nexus
 * @brief		Nexus skin file
 */
class cp_skin_idm_nexus
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
 * Return the form to add a file for an invoice
 *
 * @param	integer		$invoice	Invoice ID
 * @param	array		$files		Array of possible files
 */
public function add( $invoice, $files ) {

$icon		= ipsRegistry::$settings['base_acp_url'] . '/' . IPSLib::getAppFolder( 'downloads' ) . '/downloads/skin_cp/images/nexus_icons/file.png';
$formFile	= ( empty( $files ) ) ? $this->registry->output->formInput( 'file_name' ) : $this->registry->output->formDropdown( 'file_id', $files );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['nexus_addfile']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['nexus_addfile']}</h3>
	<form action='{$this->settings['base_url']}app=nexus&amp;module=payments&amp;section=invoices&amp;do=save_item&amp;item_app=downloads&amp;item_type=file' method='post'>
		<input type='hidden' name='invoice' value='{$invoice}' />
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['nexus_filename']}</strong>
				</td>
				<td class='field_field'>
					{$formFile}
				</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['nexus_additem']}' class='button primary'>
		</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}