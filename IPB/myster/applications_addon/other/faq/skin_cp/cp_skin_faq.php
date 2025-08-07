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

class cp_skin_faq extends output
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

public function __construct()
{
    $this->registry = ipsRegistry::instance();
    $this->lang = $this->registry->getClass('class_localization');
    $this->request =& $this->registry->fetchRequest();
    $this->settings =& $this->registry->fetchSettings();
}

//==================================================================
// Name: showPermissions
//==================================================================
public function showPermissions($matrix_html) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Permissions</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=save' method='post' name='adminform'  id='adminform'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
<div>
{$matrix_html}
</div>
<div class='acp-actionbar'>
    <div class='centeraction'>
        <input type='submit' value='Save' class='button primary' accesskey='s'>
    </div>
</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

}