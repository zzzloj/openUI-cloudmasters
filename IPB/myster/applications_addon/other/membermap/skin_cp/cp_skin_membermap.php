<?php

/**
 * ACP Skin
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

class cp_skin_membermap extends output
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

public function membermapPermissions($form)
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Manage Member Map Permissions</h2>
</div>
<form id='adminform' action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='acp-box'>
 		{$form['perm_matrix']}
 		<div class='acp-actionbar'>
 			<div class='centeraction'>
 				<input type='submit' class='button primary' value='Save' />
 			</div>
 		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function membermapCustomMarkerTop()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Custom Markers</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=newmarkergroup' style='text-decoration:none'><img src='{$this->settings['skin_app_url']}/appIcon.png' alt='' /> New Marker Group</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=newmarker' style='text-decoration:none'><img src='{$this->settings['skin_app_url']}/icons/map_add.png' alt='' /> New Custom Marker</a>
		</li>
	</ul>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function membermapCustomMarkerTable($markers, $pages)
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 	<h3>Your Custom Markers</h3>
	<table class="ipsTable">
		<tr>
			<th colspan='3'>Marker Title</th>
		</tr>
	</table>
	<ul class='alternate_rows'>
HTML;

foreach($markers as $id => $v)
{
$IPBHTML .= <<<HTML
	<li>
		<table width='100%' class='tablesubsubheader double_pad'>
			<tr class="ipsControlRow">
				<td class='tablesubsubheader' style='width: 3%; text-align: center;'><img src='http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld={$v['pinIcon']}|{$v['pinColour']}' /></td>
				<td class='tablesubsubheader' style='width: 94%'><em><strong>{$v['gTitle']}</strong></em></td>
				<td class="col_buttons" nowrap="true">
					<ul class="ipsControlStrip">
						<li class="i_edit">
							<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=editmarkergroup&amp;do={$id}'>Edit Marker Group</a>
						</li>
						<li class="i_delete">
							<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=removemarkergroup&amp;do={$id}'>Remove Marker Group</a>
						</li>
					</ul>
				</td>
			</tr>
		</table>			
	</li>

HTML;
if(is_array($v['markers']))
{
foreach( $v['markers'] as $marker )
	{

$IPBHTML .= <<<EOF
		<li style='width:100%;'>
			<table width='100%' class='double_pad'>
				<tr class="ipsControlRow">
					<td style='width: 3%'>&nbsp;
					</td>
					<td style='width: 94%'>
						{$marker['title']}
					</td>
					<td class="col_buttons" nowrap="true">
						<ul class="ipsControlStrip">
							<li class="i_edit">
								<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=editmarker&amp;do={$marker['id']}'>Edit Marker</a>
							</li>
							<li class="i_delete">
								<a href='{$this->settings['base_url']}module=membermap&amp;section=cmarkers&amp;action=removemarker&amp;do={$marker['id']}'>Remove Marker</a>
							</li>
						</ul>
					</td>
				</tr>
			</table>
		</li>
EOF;

	}
	}
}
	$IPBHTML .= <<<EOF
   </ul>
<div class='acp-actionbar'>
		<div class='rightaction'>
			{$pages}
		</div>
	</div>
</div>
	   	
EOF;
//--endhtml--//
return $IPBHTML;
}

public function membermapMarkerGroupForm($markers, $type='add')
{
$IPBHTML = "";
//--starthtml--//

$form['pin_icon']	= $this->registry->output->formDropdown( 'pin_icon', $markers, $_POST['pin_icon'] ? $_POST['pin_icon'] : '' );
$form['pin_colour'] = $this->registry->output->formInput(    'pin_colour', $_POST['pin_colour'] ? $_POST['pin_colour'] : '', "", "30", 'text', "", "color" );
$form['g_title']	= $this->registry->output->formInput(    'g_title', $_POST['g_title'] ? $_POST['g_title'] : '' );

if($type == 'add')
{
	$title = 'Add New';
	$formcode = 'section=cmarkers&amp;action=newmarkergroup';
}
else
{
	$title = 'Edit';
	$formcode = "section=cmarkers&amp;action=editmarkergroup&amp;do={$this->request['do']}";
}
$version = IPSLib::fetchVersionNumber();

if($version['long'] >= 31000)
{
	$IPBHTML .= "<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/colorpicker/jscolor.js'></script>";
}
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title} Custom Marker Group</h2>
</div>

<form action='{$this->settings['base_url']}{$formcode}' method='post'>
	<div class='acp-box'>
		<h3>{$title} Group</h3>
		<table class="ipsTable">
			<tbody>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Group Title</strong>
					</td>
					<td class="field_field">
						{$form['g_title']}
					</td>
				</tr>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Icon</strong>
					</td>
					<td class="field_field">
						{$form['pin_icon']}
					</td>
				</tr>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Colour</strong>
					</td>
					<td class="field_field">
						#{$form['pin_colour']}
					</td>
				</tr>
			</tbody>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$title} Group' />
			</div>
		</div>
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

public function membermapMarkerGroupDeleteForm($moveTo)
{
$IPBHTML = "";
$form['move_to']	= $this->registry->output->formDropdown( 'move_to', $moveTo, $_POST['move_to'] ? $_POST['move_to'] : '' );
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Delete Custom Marker Group</h2>
</div>
<form action='{$this->settings['base_url']}section=cmarkers&amp;action=removemarkergroup&amp;do={$this->request['do']}' method='post'>
	<div class='acp-box'>
		<h3>Remove Group</h3>
		<table class="ipsTable">
			<tbody>
HTML;
if(count($moveTo) > 0)
{
$IPBHTML .= <<<HTML
				<tr>
					<td class="field_title">
						<strong class="title">Move Child Markers To</strong>
					</td>
					<td class="field_field">
						{$form['move_to']}
					</td>
				</tr>
HTML;
}
else
{
$IPBHTML .= <<<HTML
 				<tr>
					<td>
						<label>Are you sure you want to remove this group?</label>
					</td>
				</tr>
HTML;
}
$IPBHTML .= <<<HTML
			</tbody>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button redbutton' value='Remove Group' />
			</div>
		</div>
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

public function membermapMarkerDeleteForm()
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Delete Custom Marker</h2>
</div>
<form action='{$this->settings['base_url']}section=cmarkers&amp;action=removemarker&amp;do={$this->request['do']}&amp;delete=true' method='post'>
	<div class='acp-box'>
		<h3>Remove Marker</h3>
		<table class="ipsTable">
			<tbody>
 				<tr>
					<td>
						<label>Are you sure you want to remove this marker?</label>
					</td>
				</tr>
			</tbody>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button redbutton' value='Remove Marker' />
			</div>
		</div>
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

public function membermapMarkerForm($groups, $type='add', $disabled=FALSE, $editor='')
{
$IPBHTML = "";
$form['g_id']	= $this->registry->output->formDropdown( 'g_id', $groups, $_POST['g_id'] ? $_POST['g_id'] : '' );
//$form['description']	= $this->registry->output->formTextarea(    'description', $_POST['description']         ? str_replace("<br />", "\r\n", $_POST['description']) : '' );
$form['title']	= $this->registry->output->formInput(    'title', $_POST['title']         ? $_POST['title'] : '' );
$form['lat']	= $this->registry->output->formInput(    'lat', $_POST['lat'] ? $_POST['lat'] : '', '', '30', 'hidden' );
$form['lon']	= $this->registry->output->formInput(    'lon', $_POST['lon']         ? $_POST['lon'] : '', '', '30', 'hidden' );
$form['addressHide']	= $this->registry->output->formInput(    'addressHide', $_POST['addressHide']         ? $_POST['addressHide'] : '', '', '80', 'hidden' );
if($type == 'add')
{
	$title = 'Add New';
	$address = 'Add Address';
	$formcode = 'section=cmarkers&amp;action=newmarker';
}
else
{
	$title = 'Edit';
	$address = 'Update Address';
	$formcode = "section=cmarkers&amp;action=editmarker&amp;do={$this->request['do']}";
}
if($disabled)
{
	$dis = 'disabled="disabled" ';
}
///--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title} Marker</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_app_url']}membermap.js'></script>
<form action='{$this->settings['base_url']}{$formcode}' method='post'>
	<div class='acp-box'>
		<h3>{$title} Marker</h3>
		<table class="ipsTable">
			<tbody>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Title</strong>
					</td>
					<td class="field_field">
						{$form['title']}
					</td>
				</tr>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Description</strong>
					</td>
					<td class="field_field">
						{$editor}
					</td>
				</tr>
				<tr>
					<td class="field_title">
						<strong class="title">Address</strong>
					</td>
					<td class="field_field">
						<span id='selectedAddress'>{$_POST['addressHide']}</span> (<a href='#' id='findAddress'>{$address}</a>)
						{$form['lat']}
						{$form['lon']}
						{$form['addressHide']}
					</td>
				</tr>
				<tr>
					<td class="field_title">
						<strong class="title">Marker Group</strong>
					</td>
					<td class="field_field">
						{$form['g_id']}
					</td>
				</tr>
			</tbody>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$title} Marker' {$dis}/>
			</div>
		</div>
	</div>
</form>
<script type='text/javascript'>
var findAddress = new Template("<div class='acp-box'><h3>Find Address</h3><form id='findAddressForm' "+
"action='{$this->settings['base_url']}app=membermap&module=ajax&section=ajax&action=find&secure_key={$this->member->form_hash}' "+
"method='post' onsubmit='return false;' ><fieldset class='row1'><ul><li class='field'><input type='text' size='50' name='do' id='location' "+
"class='input_text' value='' style='width: 98%;'/></li></ul></fieldset><ul id='addMapList' style='display:none;margin:10px;'><li>"+
"</li></ul><div class='acp-actionbar'><input type='submit' accesskey='s' value='Search' id='findAddressSubmit' class='realbutton'>"+
"</div></form></div>");
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}
}
?>