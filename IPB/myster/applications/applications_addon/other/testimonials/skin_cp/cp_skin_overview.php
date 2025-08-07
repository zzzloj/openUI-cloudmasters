<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */
 
class cp_skin_overview extends output
{

public function __destruct()
{
}

//===========================================================================
// Testemunhos Overview Index
//===========================================================================
function testemunhosOverviewIndex( $cache, $data ) {

$IPBHTML = "";
//--starthtml--//
if ( $this->settings['testemunhos_systemon'] == 0 )
{
$IPBHTML .= <<<HTML
	<div class='warning'>
		<h4><img src='{$this->settings['skin_acp_url']}/_newimages/icons/bullet_error.png' border='0' alt='{$this->lang->words['cp_error']}' /> {$this->lang->words["testemunhos_offline"]}!</h4>
	{$this->lang->words['testemunhos_offline_desc']}
	</div>
	<br />
HTML;
}

		$totalCats = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total',
													  'from'   => 'testemunhos_cats') );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['overview_title']}</h2>
</div>
<table width='100%'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words['quick_stats']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<td width='50%'><b>{$this->lang->words['total_testemunhoscats']}:</b></td>
						<td width='50%' align='center'><b>{$this->lang->formatNumber( intval($totalCats['total']) )}</b></td>
					</tr>
					<tr>
						<td width='50%'><b>{$this->lang->words['total_testemunhos']}:</b></td>
						<td width='50%' align='center'><b>{$this->lang->formatNumber( intval($cache['approved']) )}</b></td>
					</tr>
					<tr>
						<td width='50%'><b>{$this->lang->words['total_testemunhos_unapproved']}:</b></td>
						<td width='50%' align='center'><b>{$this->lang->formatNumber( intval($cache['unapproved']) )}</b></td>
					</tr>
					<tr>
						<td width='50%'><b>{$this->lang->words['total_comentarios']}:</b></td>
						<td width='50%' align='center'><b>{$this->lang->formatNumber( intval($cache['comments']) )}</b></td>
					</tr>
					<tr>
						<td width='50%'><b>{$this->lang->words['total_views']}:</b></td>
						<td width='50%' align='center'><b>{$this->lang->formatNumber( intval($cache['views']) )}</b></td>
					</tr>
				</table>
			</div>
		</td>
		<td width='50%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['upgrade_history']}</h3>
				<table width='100%'>
					<tr>
						<th style='width: 60%; padding: 10px;'><b>{$this->lang->words['upgrade_version']}</b></th>
						<th style='width: 40%; padding: 10px;'><b>{$this->lang->words['upgrade_date']}</b></th>
					</tr>
HTML;
		
		if ( count( $data['upgrade'] ) )
		{
			foreach ( $data['upgrade'] as $upgrade )
			{
				$IPBHTML .= <<<HTML
					<tr>
						<td style='width: 60%; padding: 10px;'><b>{$upgrade['upgrade_version_human']} ({$upgrade['upgrade_version_id']})</b></td>
						<td style='width: 40%; padding: 10px;'><b>{$upgrade['_date']}</b></td>
					</tr>
HTML;
			}
		}
			
		$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
	</tr>
</table>
<br />
HTML;

$IPBHTML .= <<<HTML
<table width='100%'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words['mod']}: {$this->caches['app_cache']['testimonials']['app_title']} {$this->caches['app_cache']['testemunhos']['app_version']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<td><b>{$this->lang->words['autor']}</b></td>
						<td align='center'><b>-RAW-</b></td>
					</tr>
					<tr>
						<td width='40%'><b>{$this->lang->words['versao']}</b></td>
						<td width='60%' align='center'><b>{$this->caches['app_cache']['testimonials']['app_version']}</b></td>
					</tr>
					<tr>
						<td><b>{$this->lang->words['site']}</b></td>
						<td align='center'><b><a href="http://rawcoding.net" target="_blank">Rawcoding.net</a></b></td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<form target="_blank" action='https://www.paypal.com/cgi-bin/webscr' method='post'>
							         <input type='hidden' name='cmd' value='_xclick' />
							         <input type='hidden' name='business' value='payments@rawcoding.net' />
							         <input type='hidden' name='item_name' value='Donation for (RC33) Testimonials' />
							         <input type='hidden' name='no_note' value='1' />
							         <input type='hidden' name='currency_code' value='USD' />
							         <input type='hidden' name='on0' value='Name' />
							         <input type='hidden' name='on1' value='Forum' />
							         <input type='hidden' name='os0' value='{$this->memberData['members_display_name']}' />
							         <input type='hidden' name='os1' value='{$this->settings['board_name']}' />
							         <input type='hidden' name='cancel_return' value='http://www.rawcoding.net/index.php?act=idx' />
							         <input type='image' src='https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif' name='submit' alt='Donate to Rawcoding.net.' style='border:0px; background:transparent' />
							 </form>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

function tosTool() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['tools_page']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='confirmtool' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['tools_reset_members']}</h3>
		<table width='100%' class='alternate_rows'>
			<tr>
				<td><b>{$this->lang->words['tools_reset_members']}</b><div style='color:gray'>{$this->lang->words['tools_reset_members_desc']}</div></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['executar']}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form><br />

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
				<th><b>{$this->lang->words['tools_reset_members']}</b></th>
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

function toolSearchResults( $membros, $pagination ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_member']}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='search'  id='search'>
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='search' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class="acp-box">
		<h3 style='overflow:hidden; font-size:17px; font-weight:normal; padding:8px; margin:0px; -moz-border-radius:5px 5px 0px 0px;'>{$this->lang->words['search_main']}</h3>
		<p align="center">{$this->lang->words['search_where']}:
		<select name="tipo">
			<option value="1">{$this->lang->words['search_dn']}</option>
			<option value="2">{$this->lang->words['search_ln']}</option>
			<option value="3">{$this->lang->words['search_id']}</option>
			<option value="4">{$this->lang->words['search_email']}</option>
		</select>&nbsp;		
		<input type='text' name='string' id='string' value='' size='30'  class='textinput' />
		</p>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['search_search']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>
<br /><br />
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='upate' id='upate' >
	<input type='hidden' name='module' value='tools' />
	<input type='hidden' name='section' value='tools' />
	<input type='hidden' name='do' value='update' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

<table width='100%'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words['resultado']}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<th width='5%'>{$this->lang->words['overview_id']}</th>
						<th width='15%'>{$this->lang->words['overview_membro']}</th>
						<th width='15%'>{$this->lang->words['overview_lname']}</th>
						<th width='15%'>{$this->lang->words['search_email']}</th>
						<th width='20%'>{$this->lang->words['overview_grupo']}</th>
					  	<th width='5%' align='center' style='text-align:center'>{$this->lang->words['overview_posts']}</th>
					  	<th width='20%' align='center' style='text-align:center'>{$this->lang->words['overview_reg']}</th>
					  	<th width='5%' align='center' style='text-align:center'>&nbsp;</th>
					</tr>
					<tr>
						{$membros}
					</tr>
					<tr>
						<td colspan='8' width='100%' class="acp-actionbar">
							<center><input type='submit' value='{$this->lang->words['update_selected']}' class='button primary' accesskey='s' />
							</center>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</form>
	<div>{$pagination}</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}
?>