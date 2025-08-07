<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

class cp_skin_tools extends output
{

public function __destruct()
{
}

//===========================================================================
// Testemunhos Overview Index
//===========================================================================
function convertTopics($foruns)
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['tool_convert']}</h2>
</div>
<form id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=convertstep2' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['tool_convert']}</h3>
		<table width='100%' class='alternate_rows'>
			<tr>
				<td colspan='2' style='padding: 5px;'><b>{$this->lang->words['tool_convert_desc_desc']}</b><div style='color:gray'>{$this->lang->words['tool_convert_desc']}</div></td>
			</tr>
			<tr>
				<td align='right' width='50%'>Forum:</td>
				<td align='left'  width='50%'>{$foruns}</td>
			</tr>
			<tr>
				<td colspan='2' style='padding: 5px;'><div style='color:gray'>{$this->lang->words['tool_convert_desc_desc2']}</div></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['next']}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

function convertTopicsConfirm( $text, $pagination, $listtopics, $categories ) 
{

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript">  
    window.onload = function() {  
        //listener para click do checkbox que marca/desmarca todos  
        document.getElementById("checkAll").onclick = function() {  
            var form = document.getElementById("theAdminForm"); //formulário  
            //percorre todos os checkboxes e seta se está ou não checado, conforme o valor do check mandatório  
            var checks = form.getElementsByTagName("input");  
            for(var i=0; i<checks.length; i++)
			{  
                var chk = checks[i];  
                if(chk.name == "id[]")
                {
					chk.checked = this.checked;
				}
            }
        }      
    }
</script> 
<div class='section_title'>
	<h2>{$this->lang->words['tool_convert']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='doconvert' />
	<input type='hidden' name='forum' value='{$this->request['forum']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['tool_convert']}</h3>
		<table width='100%' class='alternate_rows'>
			<tr>
				<td style='padding: 10px;'><b>{$this->lang->words['tool_convert_desc_desc3']}</b><div style='color:gray'>{$text}</div></td>
			</tr>
			<tr>
			<td style='padding: 10px;'><b>Step 3: Select the category you want to move the selected topics</b><div style='color:gray'>{$categories}</div></td>
			</tr>
		</table>

		<table width='100%'>
			<tr>
			    <td valign='top'>
			        <div class="acp-box">
						<table class='alternate_rows double_pad' width='100%'>
							<tr>
								<th width='40%' style='padding: 5px;'>{$this->lang->words['titulo']}</th>
								<th width='9%' style='text-align:center; padding: 5px;'>{$this->lang->words['posts']}</th>
								<th width='25%' style='padding: 5px;'>{$this->lang->words['autor']}</th>
								<th width='25%' style='padding: 5px;'>{$this->lang->words['data']}</th>
								<th width='1%' align='center' style='padding: 5px;'><input type="checkbox" id="checkAll" /> </th>
							</tr>
							{$listtopics}
						</table>
					</div>
				</td>
			</tr>
		</table>

		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['proceed']}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form><br />
	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

function tosConfirmTool( $text ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['tools_page']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='dotool' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['tools_reset_members']}</h3>
		<table width='100%' class='alternate_rows'>
			<tr>
				<th style='padding: 5px;'><b>{$this->lang->words['tools_reset_members']}</b></th>
			</tr>
			<tr>
				<td class='acp-actionbar' style='text-align:left;'><b>{$this->lang->words['admin_tos']}</b>{$text}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['confirmar']}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form><br />

HTML;

//--endhtml--//
return $IPBHTML;
}

function toolSearchBanned( $users, $pagination ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_banned']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='search'  id='search'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='members' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class="acp-box">
		<h3>{$this->lang->words['search_users']}</h3>
		<p align="center">{$this->lang->words['search_where']}
		<select name="tipo">
			<option value="1">{$this->lang->words['search_user']}</option>
			<option value="2">{$this->lang->words['search_userid']}</option>
		</select>&nbsp;		
		<input type='text' name='string' id='string' value='' size='30'  class='textinput' />
		</p>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['search_search']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

	        <div class="acp-box" style='margin-top: 120px;'>
			    <h3>{$this->lang->words['resultado_users']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<th width='5%' style='text-align:center; padding: 5px;'>{$this->lang->words['search_r_userid']}</th>
						<th width='29%' style='padding: 5px;'>{$this->lang->words['search_r_username']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_grupo']}</th>
						<th width='5%' style='text-align:center;  padding: 5px;'>{$this->lang->words['posts']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_joined']}</th>
						<th width='20%' style='padding: 5px;'>{$this->lang->words['search_r_bannedon']}</th>
						<th width='1%'></th>
					</tr>
					<tr>
						{$users}
					</tr>
				</table>
			</div>

	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

function toolSearchResults( $logs, $pagination ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_logs']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&do=logs' method='post' name='search'  id='search'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='logs' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class="acp-box">
		<h3>{$this->lang->words['search_logs']}</h3>
		<p align="center">{$this->lang->words['search_where']}
		<select name="tipo">
			<option value="1">{$this->lang->words['search_name']}</option>
			<option value="2">{$this->lang->words['search_id']}</option>
			<option value="3">{$this->lang->words['search_tid']}</option>
			<option value="4">{$this->lang->words['search_title']}</option>
			<option value="5">{$this->lang->words['search_action']}</option>
		</select>&nbsp;		
		<input type='text' name='string' id='string' value='' size='30'  class='textinput' />
        
		</p>
<div class="acp-actionbar"><input value="{$this->lang->words['search_search']}" class="button primary" accesskey="s" type="submit" /></div>
	</div>      
</form>


	        <div class="acp-box" style='margin-top: 120px;'>
			    <h3>{$this->lang->words['resultado']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr style='padding: 10px;'>
						<th width='5%'  style='text-align:center; padding: 5px;'>{$this->lang->words['search_r_tid']}</th>
						<th width='30%' style='padding: 5px;'>{$this->lang->words['search_r_title']}</th>
						<th width='7%'  style='text-align:center; padding: 5px;'>{$this->lang->words['search_id']}</th>
						<th width='15%' style='padding: 5px;'>{$this->lang->words['search_name']}</th>
						<th width='10%' align='center' style='padding: 5px;'>{$this->lang->words['search_r_ip']}</th>
						<th width='15%' style='padding: 5px;'>{$this->lang->words['search_action']}</th>
					  	<th width='18%' align='center' style='text-align:center; padding: 5px;'>{$this->lang->words['data']}</th>
					</tr>
					<tr>
						{$logs}
					</tr>
				</table>
			</div>
   	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}


}
?>