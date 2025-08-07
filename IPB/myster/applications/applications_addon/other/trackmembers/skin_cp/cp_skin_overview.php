<?php

/**
 * Product Title:		(SOS34) Track Members
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */

class cp_skin_overview extends output
{

public function __destruct()
{
}

//===========================================================================
// Overview Index
//===========================================================================
function overviewIndex( $data, $members, $tracks ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['overview_title']}</h2>
</div>
<table class='ipsTable double_pad'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words['quick_stats']}</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th style='width: 60%'>{$this->lang->words['stats_stats']}</th>
						<th style='width: 40%'>{$this->lang->words['stats_value']}</th>
					</tr>
					<tr>
						<td width='50%'><strong>{$this->lang->words['stats_visible']}</strong></td>
						<td width='50%' align='center'>{$members}</td>
					</tr>
					<tr>
						<td width='50%'><strong>{$this->lang->words['stats_invisible']}</strong></td>
						<td width='50%' align='center'>{$tracks}</td>
					</tr>
				</table>
			</div>
		</td>
		<td width='50%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['upgrade_history']}</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th style='width: 60%'>{$this->lang->words['upgrade_version']}</th>
						<th style='width: 40%'>{$this->lang->words['upgrade_date']}</th>
					</tr>
HTML;
		
		if ( count( $data['upgrade'] ) )
		{
			foreach ( $data['upgrade'] as $upgrade )
			{
				$IPBHTML .= <<<HTML
					<tr>
						<td>{$upgrade['upgrade_version_human']} ({$upgrade['upgrade_version_id']})</td>
						<td>{$upgrade['_date']}</td>
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
HTML;

$IPBHTML .= <<<HTML
<br />
<table class='ipsTable double_pad'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words['mod']}: {$this->caches['app_cache']['trackmembers']['app_title']} {$this->caches['app_cache']['trackmembers']['app_version']}</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>General Information</th>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['autor']}</strong></td>
						<td align='center'>Adriano Faria</td>
					</tr>
					<tr>
						<td width='40%'><strong>{$this->lang->words['versao']}</strong></td>
						<td width='60%' align='center'><b>{$this->caches['app_cache']['trackmembers']['app_version']}</b></td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['site']}</strong></td>
						<td align='center'>SOS Invision</td>
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

function newLink( $link ) {
$IPBHTML = "";
//--starthtml--//

$action  = $this->request['do'] == 'new' ? 'doadd' : 'doedit';

if ( $this->request['do'] == 'edit' )
{
	$checked  = $link['visivel']    ? "checked='checked'" : '';
	$checked1 = $link['novajanela'] ? "checked='checked'" : '';
	$text     = 'newlink_updatelink';
	$text2    = 'newlink_update';
}
else
{
	$checked  = "checked='checked'";
	$checked1 = "";
	$text 	  = 'newlink_addlink';
	$text2    = 'newlink_add';
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words[ $text2 ]}</h2>
</div>
<form action='{$this->settings['base_url']}' method='post' name='theAdminForm' id='theAdminForm' >
<input type='hidden' name='module' value='overview' />
<input type='hidden' name='do' value='{$action}' />
<input type='hidden' name='id' value='{$this->request['id']}' />


<table width='100%'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>{$this->lang->words[ $text2 ]}</h3>
				<table class='alternate_rows double_pad' width='100%'>
					<tr>
						<td width='20%'><strong>{$this->lang->words['newlink_title']}:</strong></td>
						<td width='80%'><input type='text' name='title' id='title' value='{$link['titulo']}' size='25' class='textinput' />
						</td>
					</tr>
					<tr>
						<td width='20%'><strong>{$this->lang->words['newlink_link']}:</strong></td>
						<td width='80%'><input type='text' name='link' id='link' value='{$link['link']}' size='75' maxlength="255" class='textinput' />
</td>
					</tr>
					<tr>
						<td width='20%'><strong>{$this->lang->words['newlink_imglink']}:</strong></td>
						<td width='80%'><input type='text' name='img' id='img' value='{$link['imglink']}' size='75' maxlength="255" class='textinput' />
</td>
					</tr>
					<tr>
						<td width='20%'><strong>{$this->lang->words['newlink_visivel']}</strong></td>
						<td width='80%'><input type='checkbox' name='visivel' id='visivel' {$checked} />
</td>
					</tr>
					<tr>
						<td width='20%'><strong>{$this->lang->words['newlink_novajanela']}</strong></td>
						<td width='80%'><input type='checkbox' name='blank' id='blank' {$checked1} />
</td>
					</tr>
					<tr>
						<td colspan='2' width='100%'><center><input type='submit' value='{$this->lang->words[ $text ]}' class='button primary' accesskey='s' />
</center></td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function showLinks( $visible, $invisible )
{
	$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>Gerenciar Links</h2>
</div>
<br />
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['showlinks_newlink']}'>
			<img src='{$this->settings['img_url']}/add.png' alt='{$this->lang->words['showlinks_newlink']}' />
			{$this->lang->words['showlinks_newlink']}
			</a>
		</li>
		<li class='closed'>
			<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=prune", "{$this->lang->words['showlinks_prunelinksconfirm']}");' title='{$this->lang->words['showlinks_prunelinks']}'>
			<img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />
			{$this->lang->words['showlinks_prunelinks']}
			</a>
		</li>
	</ul>
<br /><br />

<div class='acp-box'>
	<h3>{$this->lang->words['app_general_title']}</h3>
	<div>
	<table class='ipsTable double_pad'>
		<tr>
			<th class='tablesubheader' style='width: 5%'>&nbsp;</th>
			<th class='tablesubheader' style='width: 10%'>{$this->lang->words['showlinks_image']}</th>
			<th class='tablesubheader' style='width: 15%'>{$this->lang->words['newlink_title']}</th>
			<th class='tablesubheader' style='width: 55%;'>{$this->lang->words['newlink_link']}</th>
			<th class='tablesubheader' style='width: 20%; text-align: center;'>{$this->lang->words['showlinks_action']}</th>
		</tr>
	</table>
	</div>
	<ul id='handle_shown' class='alternate_rows'>
		<div class='acp-actionbar'>
			<strong>{$this->lang->words['stats_visible']}</strong>
		</div>

EOF;
		if (count($visible))
		{
		foreach( $visible as $customv )
		{
			$IPBHTML .= <<<EOF
			<li class='isDraggable' style='width:100%;' id='cid_{$customv['id']}'>
				<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
					<tr>
						<td style='width: 5%'><div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div></td>
						<td style='width: 10%'><img src='{$customv['imglink']}'></td>
						<td style='width: 15%'>{$customv['titulo']}</td>
						<td style='width: 55%;'>{$customv['link']}</td>
						<td style='width: 20%; text-align: center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$customv['id']}' title='{$this->lang->words['showlinks_editlink']}'>
								<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' class='ipd' /></a>&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleState&amp;id={$customv['id']}' title='{$this->lang->words['showlinks_alterlinkstate']}'>
								<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' class='ipd' />
							</a>&nbsp;<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$customv['id']}", "{$this->lang->words['showlinks_removelinkconfirm']}");' title='{$this->lang->words['showlinks_removelink']}'>
								<img src='{$this->settings['img_url']}/delete.png' class='ipd' />
							</a>
						</td>
					</tr>
				</table>
			</li>
EOF;
		}
		}
		else
		{
			$IPBHTML .= <<<EOF
				<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
					<tr>
						<td style='width: 100%'>
							<em>{$this->lang->words['showlinks_novisiblelinks']}</em>
						</td>
					</tr>
				</table>
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</ul>

		<script type="text/javascript">
		//<![CDATA[
		dropItLikeItsHot = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'handle_shown', { tag: 'li', name: 'cf' } )
						};
						
			new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=position&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );
			
			return false;
		};
		
		Sortable.create( 'handle_shown', { only: 'isDraggable', revert: true, format: 'cid_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
		//]]>
	</script>

	<ul class='alternate_rows'>
		<div class='acp-actionbar'>
			<strong>{$this->lang->words['stats_invisible']}</strong>
		</div>

EOF;
		if (count($invisible))
		{
		foreach( $invisible as $customv )
		{
			$IPBHTML .= <<<EOF
			<li>
				<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
					<tr>
						<td style='width: 5%'>&nbsp;</td>
						<td style='width: 10%'><img src='{$customv['imglink']}'></td>
						<td style='width: 15%'>{$customv['titulo']}</td>
						<td style='width: 55%;'>{$customv['link']}</td>
						<td style='width: 20%; text-align: center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$customv['id']}' title='{$this->lang->words['showlinks_editlink']}'>
								<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' class='ipd' /></a>&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleState&amp;id={$customv['id']}' title='{$this->lang->words['showlinks_alterlinkstate']}'>
								<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' class='ipd' />
							</a>&nbsp;<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$customv['id']}", "{$this->lang->words['showlinks_removelinkconfirm']}");' title='{$this->lang->words['showlinks_removelink']}'>
								<img src='{$this->settings['img_url']}/delete.png' class='ipd' />
							</a>
						</td>
					</tr>
				</table>
			</li>
EOF;
		}
		}
		else
		{
			$IPBHTML .= <<<EOF
				<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
					<tr>
						<td style='width: 100%'>
							<em>{$this->lang->words['showlinks_noinvisiblelinks']}</em>
						</td>
					</tr>
				</table>
EOF;
		}
			$IPBHTML .= <<<EOF
	</ul>
	<div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

}
?>