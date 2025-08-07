<?php

/**
* Tracker 2.1.0
* 
* Global skin
* Last Updated: $Date: 2012-05-10 05:06:08 +0100 (Thu, 10 May 2012) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminSkin
* @link			http://ipbtracker.com
* @version		$Revision: 1364 $
*/

class cp_skin_tracker extends output {

function tools() {
return <<<EOF
<form action='{$this->settings['base_url']}module=tools&section=tools&do=rebuildcache' method='post'>
	<div class="acp-box">
		<h3>Rebuild Caches</h3>
		<table class="ipsTable double_pad">
			<tbody><tr>
				<td colspan="2">
					This will rebuild the caches that contain commonly used data throughout Tracker.
				</td>
			</tr></tbody>
		</table>
		<div class="acp-actionbar">
			<input type="submit" value="Rebuild Caches" class="button" accesskey="s">
		</div>
	</div>
</form>
<br />
<form action='{$this->settings['base_url']}module=tools&section=tools&do=doresyncissues' method='post'>
	<div class="acp-box">
		<h3>Resynchronize Issues</h3>
		<table class="ipsTable double_pad">
			<tbody><tr>
				<td width='60%'>This will recount posts and the last reply information for all your issues.</td>
				<td width='40%'><input type='text' name='pergo' value='50' size='5' /> Per Cycle</td>
			</tr></tbody>
		</table>
		<div class="acp-actionbar">
			<input type="submit" value="Resynchronize Issues" class="button" accesskey="s">
		</div>
	</div>
</form>
<br />
<form action='{$this->settings['base_url']}module=tools&section=tools&do=dorebuildposts' method='post'>
	<div class="acp-box">
		<h3>Upgrade from 1.2.0: Rebuild Post Content</h3>
		<table class="ipsTable double_pad">
			<tbody><tr>
				<td width='60%'>
					This will rebuild the submitted content including BBCode, custom bbcode, HTML (where allowed) and emoticons. Useful if you've changed a lot of custom bbcodes, emoticons or the emoticon paths.<br />
					<strong>You should only ever need to run this once after upgrading from Tracker version 1.2.0 or lower.</strong>
				</td>
				<td width='40%'><input type='text' name='pergo' value='100' size='5' /> Per Cycle</td>
			</tr></tbody>
		</table>
		<div class="acp-actionbar">
			<input type="submit" value="Rebuild Post Content" class="button" accesskey="s">
		</div>
	</div>
</form>
EOF;
}

//===========================================================================
// GLOBAL: DOWN BUTTON
//===========================================================================
function down_button( $req, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a href='{$this->settings['base_url']}module=projects&amp;section={$req}&op=down&id={$data}' title='Move down in position'><img src='{$this->settings['skin_acp_url']}/images/arrow_down.png' width='12' height='12' border='0' style='vertical-align:middle' /></a>&nbsp;&nbsp;&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: UP BUTTON
//===========================================================================
function up_button( $req, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a href='{$this->settings['base_url']}module=projects&amp;section={$req}&op=up&id={$data}' title='Move up in position'><img src='{$this->settings['skin_acp_url']}/images/arrow_up.png' width='12' height='12' border='0' style='vertical-align:middle' /></a>&nbsp;&nbsp;&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: BLANK IMG
//===========================================================================
function blank_img( $width, $height ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/blank.gif' width='{$width}' height='{$height}' border='0' style='vertical-align:middle' />&nbsp;&nbsp;&nbsp;
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: START FIELDSET
//===========================================================================
function start_fieldset( $title ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <tr>
  <td width='100%' class='tablerow1' colspan='2'>
   <fieldset>
    <legend><strong>{$title}</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// GLOBAL: END FIELDSET
//===========================================================================
function end_fieldset() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
    </table>
   </fieldset>
  </td>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CATEGORY OVERVIEW MENU JS
//===========================================================================
function category_overview_js() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=categories&amp;do=add">Add new status...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CATEGORY MENU JS
//===========================================================================
function category_menu_js( $category_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$category_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$category_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=categories&amp;do=edit&amp;cat_id={$category_id}">Edit status...</a></li>
	<li class='icon delete'><a href="{$this->settings['base_url']}module=projects&amp;section=categories&amp;do=delete&amp;cat_id={$category_id}">Delete status...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CUSTOMFIELDS OVERVIEW MENU JS
//===========================================================================
function customfields_overview_js() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=customfields&amp;do=add">Add new field...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// CUSTOMFIELDS MENU JS
//===========================================================================
function customfields_menu_js( $field_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$field_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$field_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=customfields&amp;do=edit&amp;field_id={$field_id}">Edit field...</a></li>
	<li class='icon delete'><a href="{$this->settings['base_url']}module=projects&amp;section=customfields&amp;do=delete&amp;field_id={$field_id}">Delete field...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATORS: START JS (For find names)
//===========================================================================
function moderatorsJS() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.moderators.js'></script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATOR MENU JAVASCRIPT
//===========================================================================
function moderator_menu_js( $moderator_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$moderator_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$moderator_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=edit&amp;modid={$moderator_id}">Edit Moderator...</a></li>
	<li class='icon delete'><a href='{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=delete&amp;modid={$moderator_id}' class='delete_mod'>Delete Moderator...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATOR OVERVIEW MENU JS
//===========================================================================
function moderator_overview_js_1() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=addmod&amp;mod_type=member">Add new member...</a></li>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=addmod&amp;mod_type=group">Add new group...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATOR OVERVIEW MENU JS
//===========================================================================
function moderator_overview_js_2() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main2" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main2_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=addmod&amp;mod_type=template">Add new template...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PROJECT: START JS (For find names)
//===========================================================================
function project_start_js() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['public_url']}/jscripts/ipb_xhr_findnames.js'></script>
<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:165px;display:none;z-index:1'></div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PROJECT: END FORM
//===========================================================================
function project_end_form( $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
</form>
<script type="text/javascript">init_js('theAdminForm','entered_name','get-member-names'); setTimeout('main_loop()',10);</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PROJECT OVERVIEW MENU JS
//===========================================================================
function project_overview_js( $parent ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;parent={$parent}&amp;do=add">Add new project...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PROJECT MENU JAVASCRIPT
//===========================================================================
function project_menu_js( $project_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$project_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$project_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;do=edit&amp;project_id={$project_id}">Edit project...</a></li>
	<li class='icon delete'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;do=delete&amp;project_id={$project_id}">Delete project...</a></li>
	<li class='icon view'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;do=mods&amp;pid={$project_id}">Manage moderators...</a></li>
	<li class='icon view'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;do=fields&amp;pid={$project_id}">Manage custom fields...</a></li>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=projects&amp;parent={$project_id}&amp;do=add">Add sub-project...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATOR MENU JAVASCRIPT
//===========================================================================
function project_field_menu_js( $field_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$field_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$field_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=customfields&amp;do=edit_pid&amp;field_id={$field_id}&amp;pid={$this->request['pid']}">Edit custom field...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// MODERATOR MENU JAVASCRIPT
//===========================================================================
function project_mod_menu_js( $moderator_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_{$moderator_id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_{$moderator_id}_menucontent'>
	<li class='icon edit'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=edit&amp;modid={$moderator_id}&amp;pid={$this->request['pid']}">Edit moderator...</a></li>
	<li class='icon delete'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=delete&amp;modid={$moderator_id}&amp;pid={$this->request['pid']}">Delete moderator...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// MODERATOR OVERVIEW MENU JS
//===========================================================================
function project_mod_overview_js() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img class='ipbmenu' id="menu_main" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='Options' />
<ul class='acp-menu' id='menu_main_menucontent'>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=addmod&amp;mod_type=member&amp;pid={$this->request['pid']}">Add new member...</a></li>
	<li class='icon add'><a href="{$this->settings['base_url']}module=projects&amp;section=moderators&amp;do=addmod&amp;mod_type=group&amp;pid={$this->request['pid']}">Add new group...</a></li>
</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// SEVERITIES START JS
//===========================================================================
function severity_start_js( $skin_js ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<input type='hidden' name='initcol' value='' />
<input type='hidden' name='initformval' value='' />
<script type='text/javascript'>
{$skin_js}
function updatecolor( id )
{
	itm = my_getbyid( id );

	if ( itm )
	{
		eval("newcol = document.theAdminForm.f"+id+".value");
		itm.style.backgroundColor = newcol;
	}

}
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// SEVERITIES ROW
//===========================================================================
function severity_row( $skinid, $sev, $cache ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<fieldset>
<legend><strong>Text</strong></legend>
	<input type='text' id='fskin{$skinid}_sev{$sev}_color' name='fskin{$skinid}_sev{$sev}_color' value='{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['color']}' size='7' class='textinput'>&nbsp;
	<input type='text' id='skin{$skinid}_sev{$sev}_color' onclick="updatecolor('skin{$skinid}_sev{$sev}_color')" size='1' style='border:1px solid black;background-color:{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['color']}' readonly='readonly'>&nbsp;
</fieldset>
<fieldset>
<legend><strong>Background</strong></legend>
	<input type='text' id='fskin{$skinid}_sev{$sev}_backgroundcolor' name='fskin{$skinid}_sev{$sev}_backgroundcolor' value='{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['back']}' size='7' class='textinput'>&nbsp;
	<input type='text' id='skin{$skinid}_sev{$sev}_backgroundcolor'  onclick="updatecolor('skin{$skinid}_sev{$sev}_backgroundcolor')" size='1' style='border:1px solid black;background-color:{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['back']}' readonly='readonly'>&nbsp;
</fieldset>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_forum_permissions( $global=array(), $content="", $title='Permission Access Levels' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
//<![CDATA[

var formobj = document.getElementById('theAdminForm');

//----------------------------------
// Check all column
//----------------------------------

function check_all( permtype )
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'SHOW_ALL') && (e.id != 'READ_ALL') && (e.id != 'START_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if (a == permtype)
			{
				e.checked = true;
			}
		}
	}

	if ( document.getElementById( permtype + '_ALL' ).checked )
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}

	return false;
}

//----------------------------------
// Object has been checked
//----------------------------------

function obj_checked( permtype, pid )
{
	var totalboxes = 0;
	var total_on   = 0;

	if ( pid )
	{
		document.getElementById( permtype+'_'+pid ).checked = document.getElementById( permtype+'_'+pid ).checked ? false : true;
	}

	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'SHOW_ALL') && (e.id != 'READ_ALL') && (e.id != 'START_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if ( a == permtype )
			{
				totalboxes++;

				if ( e.checked )
				{
					total_on++;
				}
			}
		}
	}

	if ( totalboxes == total_on )
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}

	return false;
}

//----------------------------------
// Check column
//----------------------------------

function checkcol( permtype ,status)
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'SHOW_ALL') && (e.id != 'READ_ALL') &&  (e.id != 'START_ALL') && (e.id != 'REPLY_ALL') && (e.id != 'UPLOAD_ALL') && (e.id != 'DOWNLOAD_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if ( a == permtype )
			{
				if ( status == 1 )
				{
					e.checked = true;
					document.getElementById( permtype + '_ALL' ).checked = true;
				}
				else
				{
					e.checked = false;
					document.getElementById( permtype + '_ALL' ).checked = false;
				}
			}
		}
	}

	return false;
}

//----------------------------------
// Remote click box
//----------------------------------

function toggle_box( compiled_permid )
{
	if ( document.getElementById( compiled_permid ).checked )
	{
		document.getElementById( compiled_permid ).checked = false;
	}
	else
	{
		document.getElementById( compiled_permid ).checked = true;
	}

	obj_checked( compiled_permid.replace( /^(.+?)_.+?$/, "$1" ) , '');

	return false;
}

//----------------------------------
// INIT
//----------------------------------

function init_perms()
{
	var tds = formobj.getElementsByTagName('td');

	for ( var i = 0 ; i <= tds.length ; i++ )
	{
		var thisobj   = tds[i];

		if ( thisobj && thisobj.id )
		{
			var name      = thisobj.id;
			var firstpart = name.replace( /^(.+?)_.+?$/, "$1" );

			if ( firstpart == 'clickable' )
			{
				try
				{
					document.getElementById( tds[i].id ).style.cursor = "pointer";
				}
				catch(e)
				{
					document.getElementById( tds[i].id ).style.cursor = "hand";
				}
			}
		}
	}
}

//----------------------------------
// Check row
//----------------------------------

function checkrow( permid, status )
{
	document.getElementById( "SHOW"     + '_' + permid ).checked = status ? true : false;
	document.getElementById( "READ"     + '_' + permid ).checked = status ? true : false;
	document.getElementById( "START"    + '_' + permid ).checked = status ? true : false;
	document.getElementById( "REPLY"    + '_' + permid ).checked = status ? true : false;
	document.getElementById( "UPLOAD"   + '_' + permid ).checked = status ? true : false;
	document.getElementById( "DOWNLOAD" + '_' + permid ).checked = status ? true : false;

	obj_checked("SHOW");
	obj_checked("READ");
	obj_checked("START");
	obj_checked("REPLY");
	obj_checked("UPLOAD");
	obj_checked("DOWNLOAD");

	return false;
}
//]]>
</script>
<div class='tableborder'>
 <div class='tableheaderalt'>{$title}</div>
 <table cellpadding='5' cellspacing='0' border='0' width='100%'>
  <tr>
   <td class='tablesubheader' width='15%'>&nbsp;</td>
   <td class='tablesubheader' width='14%' align='center'>Show Project</td>
   <td class='tablesubheader' width='14%' align='center'>Read Issues</td>
   <td class='tablesubheader' width='14%' align='center'>Create Issues</td>
   <td class='tablesubheader' width='14%' align='center'>Reply to Issues</td>
   <td class='tablesubheader' width='14%' align='center'>Upload</td>
   <td class='tablesubheader' width='14%' align='center'>Download</td>
  </tr>
  <tr>
   <td colspan='7' class='tablerow1'>
    <fieldset>
     <legend><strong>Global Permissions</strong> (All current and future permission masks)</legend>
     <table cellpadding='4' cellspacing='0' border='0' class='tdrow1' width='100%'>
      <tr>
       <td class='tablerow2' width='15%'>&nbsp;</td>
       <td class='tablerow1' width='14%' style='background-color:#ecd5d8' onclick='check_all("SHOW")'><center><div class='red-perm'>Show Project</div> {$global['html_show']}</center></td>
       <td class='tablerow1' width='14%' style='background-color:#dbe2de' onclick='check_all("READ")'><center><div class='green-perm'>Read Issues</div> {$global['html_read']}</center></td>
       <td class='tablerow1' width='14%' style='background-color:#dbe6ea' onclick='check_all("START")'><center><div class='yellow-perm'>Create Issues</div> {$global['html_start']}</center></td>
       <td class='tablerow1' width='14%' style='background-color:#d2d5f2' onclick='check_all("REPLY")'><center><div class='blue-perm'>Post Replies</div> {$global['html_reply']}</center></td>
       <td class='tablerow1' width='14%' style='background-color:#ece6d8' onclick='check_all("UPLOAD")'><center><div class='orange-perm'>Upload</div> {$global['html_upload']}</center></td>
       <td class='tablerow1' width='14%' style='background-color:#dfdee9' onclick='check_all("DOWNLOAD")'><center><div class='purple-perm'>Download</div> {$global['html_download']}</center></td>
      </tr>
     </table>
    </fieldset>
   </td>
  </tr>
{$content}
  <tr>
   <td class='tablerow2'>&nbsp;</td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("SHOW",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("SHOW",0)' /></center></td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("READ",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("READ",0)' /></center></td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("START",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("START",0)' /></center></td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("REPLY",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("REPLY",0)' /></center></td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("UPLOAD",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("UPLOAD",0)' /></center></td>
   <td class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("DOWNLOAD",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("DOWNLOAD",0)' /></center></td>
  </tr>
 </table>
</div>
<script type='text/javascript'>
//<![CDATA[
init_perms();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_forum_permissions_row( $perm=array(), $data=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <tr>
  <td colspan='7' class='tablerow1'>
   <fieldset>
    <legend><strong>{$data['perm_name']}</strong></legend>
    <table cellpadding='4' cellspacing='0' border='0' class='tdrow1' width='100%'>
     <tr>
      <td class='tablerow2' width='15%'><input type='button' id='button' value='+' onclick='checkrow({$data['perm_id']},1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkrow({$data['perm_id']},0)' /></td>
      <td class='tablerow1' width='14%' style='background-color:#ecd5d8' id='clickable_{$data['perm_id']}' onclick="toggle_box('SHOW_{$data['perm_id']}')"><center><div class='red-perm'>Show Project</div> {$perm['html_show']}</center></td>
      <td class='tablerow1' width='14%' style='background-color:#dbe2de' id='clickable_{$data['perm_id']}' onclick="toggle_box('READ_{$data['perm_id']}')"><center><div class='green-perm'>Read Issues</div> {$perm['html_read']}</center></td>
      <td class='tablerow1' width='14%' style='background-color:#dbe6ea' id='clickable_{$data['perm_id']}' onclick="toggle_box('START_{$data['perm_id']}')"><center><div class='yellow-perm'>Create Issues</div> {$perm['html_start']}</center></td>
      <td class='tablerow1' width='14%' style='background-color:#d2d5f2' id='clickable_{$data['perm_id']}' onclick="toggle_box('REPLY_{$data['perm_id']}')"><center><div class='blue-perm'>Post Replies</div> {$perm['html_reply']}</center></td>
      <td class='tablerow1' width='14%' style='background-color:#ece6d8' id='clickable_{$data['perm_id']}' onclick="toggle_box('UPLOAD_{$data['perm_id']}')"><center><div class='orange-perm'>Upload</div> {$perm['html_upload']}</center></td>
      <td class='tablerow1' width='14%' style='background-color:#dfdee9' id='clickable_{$data['perm_id']}' onclick="toggle_box('DOWNLOAD_{$data['perm_id']}')"><center><div class='purple-perm'>Download</div> {$perm['html_download']}</center></td>
     </tr>
    </table>
   </fieldset>
  </td>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_field_permissions( $global=array(), $content="", $title='Permission Access Levels', $global_permissions="", $type="" ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
//<![CDATA[

var formobj = document.getElementById('theAdminForm');

//----------------------------------
// Check all column
//----------------------------------

function check_all( permtype )
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'VIEW_ALL') && (e.id != 'SUBMIT_ALL') && (e.id != 'MODIFY_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if (a == permtype)
			{
				e.checked = true;
			}
		}
	}

	if ( document.getElementById( permtype + '_ALL' ).checked )
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}

	return false;
}

//----------------------------------
// Object has been checked
//----------------------------------

function obj_checked( permtype, pid )
{
	var totalboxes = 0;
	var total_on   = 0;

	if ( pid )
	{
		document.getElementById( permtype+'_'+pid ).checked = document.getElementById( permtype+'_'+pid ).checked ? false : true;
	}

	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'VIEW_ALL') && (e.id != 'SUBMIT_ALL') && (e.id != 'MODIFY_ALL')&& (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if ( a == permtype )
			{
				totalboxes++;

				if ( e.checked )
				{
					total_on++;
				}
			}
		}
	}

	if ( totalboxes == total_on )
	{
		document.getElementById( permtype + '_ALL' ).checked = true;
	}
	else
	{
		document.getElementById( permtype + '_ALL' ).checked = false;
	}

	return false;
}

//----------------------------------
// Check column
//----------------------------------

function checkcol( permtype ,status)
{
	var checkboxes = formobj.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];

		if ( e && (e.id != 'VIEW_ALL') &&  (e.id != 'SUBMIT_ALL') && (e.id != 'MODIFY_ALL') && (e.type == 'checkbox') && (! e.disabled) )
		{
			var s = e.id;
			var a = s.replace( /^(.+?)_.+?$/, "$1" );

			if ( a == permtype )
			{
				if ( status == 1 )
				{
					e.checked = true;
					document.getElementById( permtype + '_ALL' ).checked = true;
				}
				else
				{
					e.checked = false;
					document.getElementById( permtype + '_ALL' ).checked = false;
				}
			}
		}
	}

	return false;
}

//----------------------------------
// Remote click box
//----------------------------------

function toggle_box( compiled_permid )
{
	if ( document.getElementById( compiled_permid ).checked )
	{
		document.getElementById( compiled_permid ).checked = false;
	}
	else
	{
		document.getElementById( compiled_permid ).checked = true;
	}

	obj_checked( compiled_permid.replace( /^(.+?)_.+?$/, "$1" ) , '');

	return false;
}

//----------------------------------
// INIT
//----------------------------------

function init_perms()
{
	var tds = formobj.getElementsByTagName('td');

	for ( var i = 0 ; i <= tds.length ; i++ )
	{
		var thisobj   = tds[i];

		if ( thisobj && thisobj.id )
		{
			var name      = thisobj.id;
			var firstpart = name.replace( /^(.+?)_.+?$/, "$1" );

			if ( firstpart == 'clickable' )
			{
				try
				{
					document.getElementById( tds[i].id ).style.cursor = "pointer";
				}
				catch(e)
				{
					document.getElementById( tds[i].id ).style.cursor = "hand";
				}
			}
		}
	}
}

//----------------------------------
// Check row
//----------------------------------

function checkrow( permid, status )
{
	document.getElementById( "VIEW"     + '_' + permid ).checked = status ? true : false;
	if ( permid < 3 || permid > 3 ) 
	{
		document.getElementById( "SUBMIT"    + '_' + permid ).checked = status ? true : false;
	}
	document.getElementById( "MODIFY"    + '_' + permid ).checked = status ? true : false;

	obj_checked("VIEW");
	obj_checked("SUBMIT");
	obj_checked("MODIFY");

	return false;
}
//]]>
</script>
<div class='tableborder'>
 <div class='tableheaderalt'>{$title}</div>
 <table cellpadding='5' cellspacing='0' border='0' width='100%'>
{$global_permissions}
  <tr>
   <td colspan='4' class='tablerow1'>
    <fieldset>
     <legend><strong>Global Permissions</strong></legend>
     <table cellpadding='4' cellspacing='0' border='0' class='tdrow1' width='100%'>
      <tr>
       <td class='tablerow2' width='25%'>&nbsp;</td>
       <td class='tablerow1' width='25%' style='background-color:#ecd5d8' onclick='check_all("VIEW")'><center><div class='red-perm'>View '{$type}'</div> {$global['html_view']}</center></td>
       <td class='tablerow1' width='25%' style='background-color:#dbe2de' onclick='check_all("SUBMIT")'><center><div class='green-perm'>Submit '{$type}'</div> {$global['html_submit']}</center></td>
       <td class='tablerow1' width='25%' style='background-color:#dfdee9' onclick='check_all("MODIFY")'><center><div class='purple-perm'>Modify '{$type}'</div> {$global['html_modify']}</center></td>
      </tr>
     </table>
    </fieldset>
   </td>
  </tr>
{$content}
  <tr>
   <td width='25%' class='tablerow2'>&nbsp;</td>
   <td width='25%' class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("VIEW",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("VIEW",0)' /></center></td>
   <td width='25%' class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("SUBMIT",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("SUBMIT",0)' /></center></td>
   <td width='25%' class='tablerow1'><center><input type='button' id='button' value='+' onclick='checkcol("MODIFY",1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkcol("MODIFY",0)' /></center></td>
  </tr>
 </table>
</div>
<script type='text/javascript'>
//<![CDATA[
init_perms();
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Forum: Build Permissions
//===========================================================================
function render_field_permissions_row( $perm=array(), $data=array(), $type="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <tr>
  <td colspan='4' class='tablerow1'>
   <fieldset>
    <legend><strong>{$data['perm_name']}</strong></legend>
    <table cellpadding='4' cellspacing='0' border='0' class='tdrow1' width='100%'>
     <tr>
      <td class='tablerow2' width='25%'><input type='button' id='button' value='+' onclick='checkrow({$data['perm_id']},1)' />&nbsp;<input type='button' id='button' value='-' onclick='checkrow({$data['perm_id']},0)' /></td>
      <td class='tablerow1' width='25%' style='background-color:#ecd5d8' id='clickable_{$data['perm_id']}' onclick="toggle_box('VIEW_{$data['perm_id']}')"><center><div class='red-perm'>View '{$type}'</div> {$perm['html_view']}</center></td>
      <td class='tablerow1' width='25%' style='background-color:#dbe2de' id='clickable_{$data['perm_id']}' onclick="toggle_box('SUBMIT_{$data['perm_id']}')"><center><div class='green-perm'>Submit '{$type}'</div> {$perm['html_submit']}</center></td>
      <td class='tablerow1' width='25%' style='background-color:#dfdee9' id='clickable_{$data['perm_id']}' onclick="toggle_box('MODIFY_{$data['perm_id']}')"><center><div class='purple-perm'>Modify '{$type}'</div> {$perm['html_modify']}</center></td>
     </tr>
    </table>
   </fieldset>
  </td>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// PROJECT: END FORM
//===========================================================================
function field_end_form( $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Forum: Build Permissions
//===========================================================================
function field_global_perms( $tds = array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <tr>
  <td colspan='4' class='tablerow1'>
    <table cellpadding='4' cellspacing='0' border='0' class='tdrow1' width='100%'>
     <tr>
      <td class='tablerow1' width='40%'>{$tds[0]}</td>
      <td class='tablerow2' width='60%'>{$tds[1]}</td>
     </tr>
    </table>
  </td>
 </tr>
EOF;

//--endhtml--//
return $IPBHTML;
}
}

?>