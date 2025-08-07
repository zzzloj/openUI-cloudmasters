<?php

class cp_skin_manage extends output
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

public function converterForm() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['converter_title']}</h2>
</div>

<form id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=converter_step1' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3></h3>
        
		<table class='ipsTable double_pad'>
            <tr>
                <td colspan'2'><strong class='title'>{$this->lang->words['convert_forms']}</strong><br />{$this->lang->words['convert_forms_desc']}<br /><span class='desctext'>{$this->lang->words['convert_forms_desc2']}</span></td>
            </tr>
            <tr>
                <td colspan'2'><strong class='title'>{$this->lang->words['convert_contact_form']}</strong><br />{$this->lang->words['convert_contact_form_desc']}<br /><span class='desctext'>{$this->lang->words['convert_contact_form_desc2']}</span></td>
            </tr>            
        </table>        
        
    </div>    
 		
	<div class='acp-actionbar'>
        <strong style='color:red;'>{$this->lang->words['converter_warning']}</strong><br /><br />
		<select name='conversion_type' class='input_select'><option value='multi'>{$this->lang->words['convert_forms']}</option><option value='single'>{$this->lang->words['convert_contact_form']}</option></select> <input type='submit' class='button primary' value='{$this->lang->words['start_conversion']}' />
	</div>
    
    </div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function converterDone() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['converter_title']}</h2>
</div>
	
	<div class='acp-box'>
		<h3></h3>
        
		<table class='ipsTable double_pad short'>
            <tr>
                <td class='short'><strong class='title'>{$this->lang->words['converter_finished']}</strong><br />{$this->lang->words['converter_finished_desc']}</td>
            </tr>          
        </table>        
        
    </div>    
    
    </div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}