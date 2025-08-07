<?php

//-----------------------------------------------
// (DP32) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// ACP Skin Content
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 04 / 02 / 2011
// Updated on: 20 / 06 / 2011
//-----------------------------------------------
// Copyright (C) 2011 DawPi
// All Rights Reserved
//----------------------------------------------- 

class sidebars_cp extends output
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

public function sidebarsListView( $items, $apps ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sidebars_overview']}</h2>

	<ul class='context_menu'>						
		<li>
			<a href='#' class='ipbmenu' id='add_sidebar' title='{$this->lang->words['add_sidebar_select']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				{$this->lang->words['add_sidebar_select']}
				<img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' alt='' />				
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=updateAdvertsCache' title='{$this->lang->words['update_adverts_cache']}'>
				<img src='{$this->settings[ 'skin_app_url']}/images/arrow-circle-double-135.png' alt='' />
				{$this->lang->words['update_adverts_cache']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=updateCache' title='{$this->lang->words['update_sidebars_cache']}'>
				<img src='{$this->settings[ 'skin_app_url']}/images/arrow-circle-double-135.png' alt='' />
				{$this->lang->words['update_sidebars_cache']}
			</a>
		</li>
		<li class="closed">
			<a href='#' onclick='acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=removeall");' title='{$this->lang->words['removeall']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />
				{$this->lang->words['removeall']}
			</a>
		</li>			
		</ul>
		
		<ul class='ipbmenu_content' id='add_sidebar_menucontent' style='display: none'>	
			<li style='text-align:center;'>
				<strong>{$this->lang->words['add_default_sidebar_title']}</strong>
			</li>
			<li>
				<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType=all' title='{$this->lang->words['add_all_pages_sidebar']}'>
					{$this->lang->words['add_all_pages_sidebar']}
				</a>
			</li>				
			<li>
				<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType=index' title='{$this->lang->words['add_board_index_sidebar']}'>					
					{$this->lang->words['add_board_index_sidebar']}
				</a>
			</li>
			<li>
			<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType=forum' title='{$this->lang->words['add_forum_view_sidebar']}'>					
					{$this->lang->words['add_forum_view_sidebar']}
				</a>
			</li>
			<li>
			<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a  style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType=topic' title='{$this->lang->words['add_topic_view_sidebar']}'>					
					{$this->lang->words['add_topic_view_sidebar']}
				</a>
			</li>
			<li>
			<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a  style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType=standard' title='{$this->lang->words['add_standard_view_sidebar']}'>					
					{$this->lang->words['add_standard_view_sidebar']}
				</a>
			</li>				
HTML;
	if( count( $apps ) )
	{
$IPBHTML .= <<<HTML
			<li style='text-align:center;'>
				<strong>{$this->lang->words['add_custom_sidebar_title']}</strong>
			</li>	
HTML;
		foreach( $apps as $key => $name )
		{
$IPBHTML .= <<<HTML
			<li>
				<img src='{$this->settings['skin_acp_url']}/images/icons/pencil.png' alt='' />
				<a style='text-decoration: none' href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;rawType={$key}' title='{$this->lang->words['add_custom_sidebar']} {$name}'>
					{$this->lang->words['add_custom_sidebar']} <em>{$name}</em>
				</a>
			</li>	
HTML;
		}
	}
$IPBHTML .= <<<HTML
		</ul>
</div>
HTML;

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['sidebars_list']}</h3>
		<table class='ipsTable'>		
HTML;

if( count( $items ) AND is_array( $items ) )
{

        if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
        {
            $sNameWidth   = '24';
            $sNamePadding = '25';
            
        }
        else
        {
            $sNameWidth    = '14';
            $sNamePadding  = '15';
        }

$IPBHTML .= <<<HTML
			<tr>										
	            <th style="width: {$sNameWidth}%; text-align:left;padding-left:{$sNamePadding}px;">{$this->lang->words['s_name']}</th>
	            <th style='width: 8%; text-align:center;'>{$this->lang->words['s_count']}</th>  
	            <th style='width: 8%; text-align:center;'>{$this->lang->words['s_count_adv']}</th> 
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
                <th style='width: 10%; text-align:center;'>{$this->lang->words['s_count_nexus']}</th> 
HTML;
}                       
$IPBHTML .= <<<HTML
	            <th style='width: 10%; text-align:center;'>{$this->lang->words['s_type']}</th>  	            
	            <th style='width: 10%; text-align:center;'>{$this->lang->words['s_enabled']}</th>
				<th style='width: 10%; text-align:center;'>{$this->lang->words['s_wrapper']}</th>
				<th style='width: 10%; text-align:center;'>{$this->lang->words['s_wrapper_separate_th']}</th>
				<th style='width: 10%; text-align:center;'>{$this->lang->words['s_random']}</th>						                        
				<th style='width: 10%;'>&nbsp;</th>		
			</tr>		
HTML;

	foreach( $items as $item )
	{ 
		$enabled 	= ( $item['s_enabled'] ) 			? 'tick.png' : 'cross.png';
		$wrapper 	= ( $item['s_wrapper'] ) 			? 'tick.png' : 'cross.png';
		$wrapper2 	= ( $item['s_wrapper_separate'] ) 	? 'tick.png' : 'cross.png';
		$random		= ( $item['s_random'] ) 			? 'tick.png' : 'cross.png';

	      
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>	
			<td style='text-align: left;padding-left:25px;'>
HTML;

	if( $item['_s_type'] != 'standard' )
	{
$IPBHTML .= <<<HTML
            <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=manageAdverts&amp;s_id={$item['s_id']}' title='{$this->lang->words['manage_adverts_for_this_sidebar']}'>
HTML;
    }

$IPBHTML .= <<<HTML
            <strong>{$item['s_name']}</strong>
HTML;

	if( $item['_s_type'] != 'standard' )
	{
$IPBHTML .= <<<HTML
            </a>
HTML;
     }
     
$IPBHTML .= <<<HTML
            &nbsp; <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;s_id={$item['s_id']}' title='{$this->lang->words['edit_sidebar']}'><img src='{$this->settings['skin_app_url']}images/comment_edit.png' border='0' alt='{$this->lang->words['edit_sidebar']}'></a></td>
			<td style='text-align:center;'>{$item['s_count']}</td>
			<td style='text-align:center;'>{$item['s_count_adv']}</td>
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
            <td style='text-align:center;'>{$item['s_count_nexus']}</td>
HTML;
}                       
$IPBHTML .= <<<HTML
			<td style='text-align:center;'><em>{$item['s_type']}</em></td>
			<td style='text-align:center;'><strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggle&amp;s_id={$item['s_id']}&amp;st={$this->request['st']}' title='{$this->lang->words['toggle_sidebar']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$enabled}' border='0' class='ipbmenu' /></a></strong></td>
			<td style='text-align:center;'><strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleWrapper&amp;s_id={$item['s_id']}&amp;st={$this->request['st']}' title='{$this->lang->words['toggle_sidebar_wrapper']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$wrapper}' border='0' class='ipbmenu' /></a></strong></td>
			<td style='text-align:center;'><strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleWrapper2&amp;s_id={$item['s_id']}&amp;st={$this->request['st']}' title='{$this->lang->words['toggle_sidebar_wrapper2']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$wrapper2}' border='0' class='ipbmenu' /></a></strong></td>	
<td style='text-align:center;'><strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleRandom&amp;s_id={$item['s_id']}&amp;st={$this->request['st']}' title='{$this->lang->words['toggle_random']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$random}' border='0' class='ipbmenu' /></a></strong></td>						
			<td class='col_buttons'>
			    <ul class='ipsControlStrip'>
HTML;
	if( $item['_s_type'] != 'standard' )
	{
$IPBHTML .= <<<HTML
	    			<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=addAdvert&amp;s_id={$item['s_id']}' title='{$this->lang->words['r_addimg']}'>{$this->lang->words['add_advertt']}</a></li>
HTML;
	}
$IPBHTML .= <<<HTML
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;type=edit&amp;s_id={$item['s_id']}' title='{$this->lang->words['editt']}'>{$this->lang->words['editt']}</a></li>
					<li class='ipsControlStrip_more ipbmenu' id="stat_menu{$item['s_id']}">
						<a href='#'>&nbsp;</a>
					</li>
			    </ul>
			
				<ul class='acp-menu' id='stat_menu{$item['s_id']}_menucontent'>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;s_id={$item['s_id']}");' title='{$this->lang->words['removee']}'>{$this->lang->words['removee']}</a></li>
				</ul>
			</td>			
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>		
			<td colspan='6' class='no_messages'>
			     {$this->lang->words['no_sidebars']} 
            </td>							
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
 </table> 
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function sidebarForm( $form, $button, $st ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$form['formDo']} 
HTML;
	if( $form['_s_name'] )
	{
$IPBHTML .= <<<HTML
 - {$form['_s_name']}
HTML;
	}
	if( $form['sidebarType'] )
	{
$IPBHTML .= <<<HTML
	({$form['sidebarType']})
HTML;
	}	
$IPBHTML .= <<<HTML
	</h2>
</div>
HTML;

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=check&amp;type={$form['type']}&amp;rawType={$form['rawType']}&amp;s_id={$form['s_id']}&amp;st={$st}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$form['formDo']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_name']}</strong></td>
				<td class='field_field'>
					{$form['s_name']}<br />
					<span class='desctext'>{$this->lang->words['s_name_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_enabled']}</strong></td>
				<td class='field_field'>
					{$form['s_enabled']}<br />
					<span class='desctext'>{$this->lang->words['s_enabled_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_wrapper']}</strong></td>
				<td class='field_field'>
					{$form['s_wrapper']}<br />
					<span class='desctext'>{$this->lang->words['s_wrapper_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_wrapper_separate']}</strong></td>
				<td class='field_field'>
					{$form['s_wrapper_separate']}<br />
					<span class='desctext'>{$this->lang->words['s_wrapper_separate_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_random']}</strong></td>
				<td class='field_field'>
					{$form['s_random']}<br />
					<span class='desctext'>{$this->lang->words['s_random_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_limit_at_once']}</strong></td>
				<td class='field_field'>
					{$form['s_limit_at_once']}<br />
					<span class='desctext'>{$this->lang->words['s_limit_at_once_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_groups']}</strong></td>
				<td class='field_field'>
					{$form['s_groups']}<br />
					<span class='desctext'>{$this->lang->words['s_groups_desc']}</span>
				</td>	
			</tr>
HTML;
	if( $form['rawType'] == 'forum' )
	{
$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['disable_on_forums']}</strong></td>
				<td class='field_field'>
					{$form['s_forums']}<br />
					<span class='desctext'>{$this->lang->words['disable_on_forums_desc']}</span>
				</td>	
			</tr>
HTML;
	}
	
	if( $form['rawType'] == 'all' )
	{
$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['disable_on']}</strong></td>
				<td class='field_field'>
					{$form['disable_on']}<br />
					<span class='desctext'>{$this->lang->words['disable_on_desc']}</span>
				</td>	
			</tr>
HTML;
	}
	elseif( $form['rawType'] == 'standard' )
	{
$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['enable_on_desc']}</strong></td>
				<td class='field_field'>
					{$form['disable_on']}<br />
					<span class='desctext'>{$this->lang->words['enable_on']}</span>
				</td>	
			</tr>
HTML;
	}	 	
$IPBHTML .= <<<HTML
		</table>			

		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button' accesskey='s'> 
HTML;
	
	if( $form['rawType'] != 'standard' )
	{
$IPBHTML .= <<<HTML
			<input type='submit' value='{$button}{$this->lang->words['and_add_first_add']}' name='add_first_ad' class='button' accesskey='s'> 
HTML;
	}	 	
$IPBHTML .= <<<HTML
			{$this->lang->words['oror']} <input type='submit' value='{$this->lang->words['cancel_operation']}' name='cancel_operation' value='1' class='button' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


public function adsListView( $items, $sidebar ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['adverts_list']}: {$sidebar['s_name']}</h2>

	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=addAdvert&amp;s_id={$sidebar['s_id']}' title='{$this->lang->words['add_new_advert']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				{$this->lang->words['add_new_advert']}
			</a>
		</li>	
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=updateAdvertsCache&amp;s_id={$sidebar['s_id']}' title='{$this->lang->words['update_adverts_cache']}'>
				<img src='{$this->settings[ 'skin_app_url']}/images/arrow-circle-double-135.png' alt='' />
				{$this->lang->words['update_adverts_cache']}
			</a>
		</li>
		<li class="closed">
			<a href='#' onclick='acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=removeallAdverts&amp;s_id={$sidebar['s_id']}");' title='{$this->lang->words['removeall_ads_from_this_sidebar']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />
				{$this->lang->words['removeall_ads_from_this_sidebar']}
			</a>
		</li>			
	</ul>
</div>

HTML;

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['adverts_overview']}</h3>
		<table class='ipsTable' id='reordable_adverts'>			
HTML;

if( count( $items ) AND is_array( $items ) )
{
        if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
        {
            $aNameWidth = '25';
            
        }
        else
        {
            $aNameWidth = '35';
        }

		$IPBHTML .= <<<HTML
			<tr>	
				<th style='width: 5%;' class='col_drag'>&nbsp;</th>
				<th style="width: {$aNameWidth}%; text-align:left;padding-left:20px;">{$this->lang->words['a_name']}</th>
				<th style='width: 10%; text-align:center;'>{$this->lang->words['a_pinned']}</th> 
				<th style='width: 10%; text-align:center;'>{$this->lang->words['a_php_mode']}</th> 
				<th style='width: 10%; text-align:center;'>{$this->lang->words['a_raw_mode']}</th> 
				<th style='width: 10%; text-align:center;'>{$this->lang->words['s_enabled']}</th> 
				<th style='width: 10%; text-align:center;'>{$this->lang->words['is_advanced_advert']}</th>
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
                <th style='width: 10%; text-align:center;'>{$this->lang->words['is_nexus_advert']}</th> 
HTML;
}                       
$IPBHTML .= <<<HTML
				<th style='width: 10%;'>&nbsp;</th>		
			</tr>
HTML;
		
	foreach( $items as $item )
	{ 	
		$img	= ( $item['a_raw_mode'] ) 		? 'tick.png' : 'tick-red.png'; 
		$img2	= ( $item['a_enabled'] ) 		? 'tick.png' : 'tick-red.png'; 
		$img3	= ( $item['a_php_mode'] ) 		? 'tick.png' : 'tick-red.png';
		$img4	= ( $item['a_is_advanced'] ) 	? 'tick.png' : 'tick-red.png'; 
		$img5	= ( $item['a_pinned'] ) 		? 'tick.png' : 'tick-red.png'; 
        $img6	= ( $item['a_is_nexus'] ) 	    ? 'tick.png' : 'tick-red.png';
				
		$IPBHTML .= <<<HTML
			<tr class='ipsControlRow isDraggable' id='adverts_{$item['a_id']}'>
			    <td style=''><span class='draghandle'>&nbsp;</span></td>						
				<td style='text-align:left;padding-left:20px;'>
HTML;

	if( $item['a_duplicate_id'] )
	{ 	
		$IPBHTML .= <<<HTML
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editAdvert&amp;a_id={$item['a_duplicate_id']}&amp;s_id={$item['a_duplicate_sid']}' title='{$this->lang->words['advert_is_duplicated']}'><img src='{$this->settings['skin_acp_url']}/images/icons/bullet_error.png' alt='' /></a>
HTML;
	}
		$IPBHTML .= <<<HTML
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editAdvert&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title='{$this->lang->words['edit_advert']}'><strong>{$item['a_name']}</strong></a></td>
				<td style='text-align:center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=togglePinned&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title="{$this->lang->words['toggle_pinned_mode']}"><img src='{$this->settings[ 'skin_app_url']}/images/{$img5}' border='0' class='ipbmenu' /></a></td>
				<td style='text-align:center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleAdvert&amp;type=php&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title="{$this->lang->words['toggle_php_mode']}"><img src='{$this->settings[ 'skin_app_url']}/images/{$img3}' border='0' class='ipbmenu' /></a></td>						
				<td style='text-align:center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleAdvert&amp;type=raw&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title="{$this->lang->words['toggle_raw_advert_mode']}"><img src='{$this->settings[ 'skin_app_url']}/images/{$img}' border='0' class='ipbmenu' /></a></td>
				<td style='text-align:center;'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleAdvert&amp;type=enabled&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title="{$this->lang->words['toggle_advert_enabled_mode']}"><img src='{$this->settings[ 'skin_app_url']}/images/{$img2}' border='0' class='ipbmenu' /></a></td>	
				<td style='text-align:center;'><img src='{$this->settings[ 'skin_app_url']}/images/{$img4}' border='0' class='ipbmenu' /></td>
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
                <td style='text-align:center;'><img src='{$this->settings[ 'skin_app_url']}/images/{$img6}' border='0' class='ipbmenu' /></td>
HTML;
          }                       
$IPBHTML .= <<<HTML
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editAdvert&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}' title='{$this->lang->words['edit_advertt']}'>{$this->lang->words['edit_advertt']}</a></li>
						<li class='i_delete'><a href='#' onclick='acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=removeAdvert&amp;a_id={$item['a_id']}&amp;s_id={$sidebar['s_id']}");' title='{$this->lang->words['remove_advertt']}'>{$this->lang->words['remove_advertt']}</a></li>
					</ul>
				</td>				
			</tr>				
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
			<tr>
				<td colspan='7' class='no_messages'>
					{$this->lang->words['no_ads']} 			
	            </td>							
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
</div>
<script type='text/javascript'>
	jQ("#reordable_adverts").ipsSortable( 'table', {url: "{$this->settings['base_url']}&app=gsidebars&module=manage&section=manage&do=reorderAdverts&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function advertForm( $form, $button, $st ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript">
        function checkBoxToggle()
        {
                if ( $('use_exists_advert').checked )
                {
                        $('existsAdvertId').style.display = '';
                        $('existsAdvertId2').style.display = 'none';
                } else {
                        $('existsAdvertId2').style.display = '';
                        $('existsAdvertId').style.display = 'none';
                }
        };
        
        function checkAdvBoxToggle()
        {
                if ( $('use_adv_advert').checked )
                {
                        $('adv_advert').style.display       = '';
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
                        $('hide_nexus').style.display       = 'none';
HTML;
}                       
$IPBHTML .= <<<HTML
                        $('hide_exists').style.display      = 'none';
                        $('existsAdvertId2').style.display  = 'none';
                } else {
                        $('adv_advert').style.display       = 'none';
HTML;
          if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
          {              
$IPBHTML .= <<<HTML
                        $('hide_nexus').style.display       = '';
HTML;
}                       
$IPBHTML .= <<<HTML
                        $('hide_exists').style.display     = '';
                        $('existsAdvertId2').style.display  = '';                       
                }
        };
        
        function checkNexusBoxToggle()
        {
                if ( $('use_nexus_advert').checked )
                {
                        $('nexus_advert').style.display     = '';
                        $('hide_adv').style.display         = 'none';
                        $('hide_exists').style.display      = 'none';
                        $('existsAdvertId2').style.display  = 'none';
                } else {
                        $('nexus_advert').style.display     = 'none';
                        $('hide_adv').style.display         = '';
                        $('hide_exists').style.display      = '';
                        $('existsAdvertId2').style.display  = '';                        
                }
        };
                        
        document.observe("dom:loaded", function() {
                if ( $('use_exists_advert') )
                {
                        checkBoxToggle();  
                        $('use_exists_advert').observe( 'click', checkBoxToggle );
                }
                
                if ( $('use_adv_advert') )
                {
                        checkAdvBoxToggle();  
                        $('use_adv_advert').observe( 'click', checkAdvBoxToggle );
                }
                 
                if ( $('use_nexus_advert') )
                {
                        checkNexusBoxToggle();  
                        $('use_nexus_advert').observe( 'click', checkNexusBoxToggle );
                }                                
        } );
</script>
<div class='section_title'>
	<h2>{$form['formDo']} 
HTML;
	if( $form['_a_name'] )
	{
$IPBHTML .= <<<HTML
 - {$form['_a_name']}
HTML;
	}	
$IPBHTML .= <<<HTML
	</h2>
</div>
HTML;

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=checkAdvert&amp;type={$form['type']}&amp;a_id={$form['a_id']}&amp;s_id={$form['s_id']}&amp;st={$st}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$form['formDo']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['main_informations']}</th>
			</tr>
HTML;
			
	if( $form['integration_enabled'] )
	{
			$_visible = $form['advert_is_set'] ? '' : 'none';
			
$IPBHTML .= <<<HTML
			<tr id='hide_adv'>
				<td class='field_title'><strong class='title'>{$this->lang->words['add_adv_advert']}</strong></td>
				<td class='field_field'>
					{$form['use_adv_advert']}<br />
					<span class='desctext'>{$this->lang->words['add_adv_advert_desc']}</span>
				</td>	
			</tr>
			<tr id='adv_advert' style="display:{$_visible};">
				<td class='field_title'><strong class='title'>{$this->lang->words['select_adv_advert']}</strong></td>
				<td class='field_field'>
					{$form['adv_advert']}<br />
					<span class='desctext'>{$this->lang->words['select_adv_advert_desc']}</span>
				</td>	
			</tr>
HTML;
	}
    
	if( $form['nexusStuff']['nexus_integration_enabled'] )
	{
			$__visible = $form['nexusStuff']['nexus_advert_is_set'] ? '' : 'none';
			
$IPBHTML .= <<<HTML
			<tr id='hide_nexus'>
				<td class='field_title'><strong class='title'>{$this->lang->words['add_nexus_advert']}</strong></td>
				<td class='field_field'>
					{$form['nexusStuff']['use_nexus_advert']}<br />
					<span class='desctext'>{$this->lang->words['add_nexus_advert_desc']}</span>
				</td>	
			</tr>
			<tr id='nexus_advert' style="display:{$__visible};">
				<td class='field_title'><strong class='title'>{$this->lang->words['select_nexus_advert']}</strong></td>
				<td class='field_field'>
					{$form['nexusStuff']['nexus_advert']}<br />
					<span class='desctext'>{$this->lang->words['select_nexus_advert_desc']}</span>
				</td>	
			</tr>
HTML;
	}    	
$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['a_name']}</strong></td>
				<td class='field_field'>
					{$form['a_name']}<br />
					<span class='desctext'>{$this->lang->words['a_name_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['s_enabled']}</strong></td>
				<td class='field_field'>
					{$form['a_enabled']}<br />
					<span class='desctext'>{$this->lang->words['a_enabled_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['a_raw_mode']}</strong></td>
				<td class='field_field'>
					{$form['a_raw_mode']}<br />
					<span class='desctext'>{$this->lang->words['a_raw_mode_desc']}</span>
				</td>	
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['a_php_mode']}</strong></td>
				<td class='field_field'>
					{$form['a_php_mode']}<br />
					<span class='desctext'>{$this->lang->words['a_php_mode_desc']}</span>
				</td>	
			</tr>
HTML;
			
	if( $form['existsAdvert'] )
	{
			if( $form['exists_advert_is_set'] )
			{
				$visible 	= '';
				$visible2 	= 'display:none';
			}
			else
			{
				$visible 	= 'none';
				$visible2	= 'display:';
			}

			
$IPBHTML .= <<<HTML
			<tr id='hide_exists'>
				<td class='field_title'><strong class='title'>{$this->lang->words['use_exists_advert']}</strong></td>
				<td class='field_field'>
					{$form['use_exists_advert']}<br />
					<span class='desctext'>{$this->lang->words['']}</span>
				</td>	
			</tr>
			<tr id='existsAdvertId' style="display:{$visible};">
				<td class='field_title'><strong class='title'>{$this->lang->words['existsAdvert']}</strong></td>
				<td class='field_field'>
					{$form['existsAdvert']}
HTML;
			
	if( $form['exists_advert_id'] )
	{
		$IPBHTML .= <<<HTML
		&nbsp;&nbsp;(<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editAdvert&amp;a_id={$form['exists_advert_id']}&amp;s_id={$form['exists_sidebar_id']}'>{$this->lang->words['edit_this_advert']}</a>)
HTML;
	}
	
$IPBHTML .= <<<HTML
					<br />
					<span class='desctext'>{$this->lang->words['existsAdvert_desc']}</span>
				</td>	
			</tr>
HTML;
	}	
$IPBHTML .= <<<HTML
			<tr>
				<th colspan='2'>{$this->lang->words['content_settings']}</th>
			</tr>
			<tr id='existsAdvertId2' style="{$visible2}">
				<td class='field_field' colspan='2'>
					{$form['a_content']}
				</td>	
			</tr>																											
		</table>

		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s'> {$this->lang->words['oror']} <input type='submit' value='{$this->lang->words['cancel_operation']}' name='cancel_operation' value='1' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}// End of class