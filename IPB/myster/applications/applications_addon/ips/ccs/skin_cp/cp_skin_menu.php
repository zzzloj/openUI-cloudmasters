<?php
/**
 * <pre>
 * Invision Power Services
 * Menu management skin functions
 * Last Updated: $Date: 2011-12-13 22:42:43 -0500 (Tue, 13 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		21 Dec 2011
 * @version		$Revision: 9995 $
 */
 
class cp_skin_menu
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
 * Show the list of applications and menu items
 *
 * @param	array 		Applications
 * @param	array 		Menu items
 * @return	string		HTML
 */
public function listMenuItems( $applications, $menu )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['menu_items_h2']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['menuhelper_helpblurb']}
		</div>
	</div>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=add' title='{$this->lang->words['add_menu_item']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_menu_item']}
				</a>
			</li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<iframe id='menu_preview' src='{$this->settings['public_url']}app=ccs&amp;module=pages&amp;section=menu' style='width: 100%; border: 1px solid #000;'></iframe>

<div class='acp-box adv_controls'>
	<h3>{$this->lang->words['all_menu_items_sub']}</h3>
	<table class='ipsTable' id='menu_items_list'>
HTML;

foreach( $applications as $app_key => $application )
{
	if( !$application['app_enabled'] )
	{
		continue;
	}

	$name	= IPSLib::getAppTitle( $app_key, true );
	$desc	= sprintf( $this->lang->words['go_to_prefix'], IPSLib::getAppTitle( $app_key, true ) );
	$safe	= str_replace( '_', 'zzxzz', $app_key );
	
	/* Are there any menu items configured to show before this app? */
	$IPBHTML .=	$this->_addMenuItems( $app_key, $menu );

	$IPBHTML .= <<<HTML
	<tr class='ipsControlRow isDraggable' id='menu_{$safe}'>
		<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
		<td>
			<span class='larger_text'>{$this->lang->words['menu_app_type']} <strong><a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=editApp&amp;appkey={$app_key}'>{$name}</a></strong></span>
			<div class='desctext'>{$desc}</div>
		</td>
		<td>
HTML;

/* Show a status, if appropriate */
if( $application['app_hide_tab'] )
{
	$IPBHTML .= <<<HTML
	<strong>{$this->lang->words['menu_app__hidden']}</strong>
	<div class='desctext'>{$this->lang->words['menu_app__hidden_desc']}</div>
HTML;
}
else if( $application['app_tab_groups'] )
{
	$_disGroups	= array();
	
	foreach( $application['app_tab_groups'] as $_groupId )
	{
		$_disGroups[]	= $this->caches['group_cache'][ $_groupId ]['g_title'];
	}
	
	$text	= sprintf( $this->lang->words['menu_app__restrict_desc'], implode( ', ', $_disGroups ) );
	
	$IPBHTML .= <<<HTML
	<div class='desctext'>{$text}</div>
HTML;
}

$IPBHTML .= <<<HTML
		</td>
		<td class='col_buttons'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=editApp&amp;appkey={$app_key}'>{$this->lang->words['edit_app_properties']}</a>
				</li>
			</ul>
		</td>
	</tr>
HTML;
}

/* Show the menu items now */
foreach( $menu as $menuItem )
{
	if( !in_array( preg_replace( "/_(\d+)$/", '', $menuItem['menu_position'] ), array_keys( $applications ) ) )
	{
		$IPBHTML .= $this->menuItemRow( $menuItem );
	}
}

/* And show disabled applications at the end */
foreach( $applications as $app_key => $application )
{
	if( $application['app_enabled'] )
	{
		continue;
	}

	$name	= IPSLib::getAppTitle( $app_key, true );
	$desc	= sprintf( $this->lang->words['go_to_prefix'], IPSLib::getAppTitle( $app_key, true ) );
	$safe	= str_replace( '_', 'zzxzz', $app_key );
	
	/* Are there any menu items configured to show before this app? */
	$IPBHTML .=	$this->_addMenuItems( $app_key, $menu );

	$IPBHTML .= <<<HTML
	<tr class='ipsControlRow isDraggable' id='menu_{$safe}'>
		<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
		<td>
			<span class='larger_text'>{$this->lang->words['menu_app_type']} <strong><a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=editApp&amp;appkey={$app_key}'>{$name}</a></strong></span>
			<div class='desctext'>{$desc}</div>
		</td>
		<td>
			<strong>{$this->lang->words['menu_app__disabled']}</strong>
			<div class='desctext'>{$this->lang->words['menu_app__disabled_desc']}</div>
		</td>
		<td class='col_buttons'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=editApp&amp;appkey={$app_key}'>{$this->lang->words['edit_app_properties']}</a>
				</li>
			</ul>
		</td>
	</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<script type='text/javascript'>
	jQ("#menu_items_list").ipsSortable('table', { 
		url: "{$this->settings['base_url']}module=ajax&section=menu&do=reorder&md5check={$this->member->form_hash}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'menuitems[]' },
		postUpdateProcess: function() {
			if(navigator.appName == "Microsoft Internet Explorer")
			{
				jQ('#menu_preview').contentWindow.location.reload(true);
			}
			else
			{
				jQ('#menu_preview').attr('src', jQ('#menu_preview').attr('src'));
			}
		}
	} );
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Look for menu items set to show before specified app
 *
 * @param	string 		Application key
 * @param	array 		Menu items
 * @return	string		HTML
 */
public function _addMenuItems( $key, $menu )
{
$IPBHTML = "";
//--starthtml--//

	foreach( $menu as $menuItem )
	{
		if( substr( $menuItem['menu_position'], 0, strlen($key) ) == $key )
		{
			$IPBHTML	.= $this->menuItemRow( $menuItem );
		}
	}
	
//--endhtml--//
return $IPBHTML;
}

/**
 * Show a menu item row
 *
 * @param	array 		Menu item
 * @return	string		HTML
 */
public function menuItemRow( $menu )
{
$IPBHTML = "";
//--starthtml--//

	$IPBHTML .= <<<HTML
	<tr class='ipsControlRow isDraggable' id='menu_{$menu['menu_id']}'>
		<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
		<td>
			<span class='larger_text'><strong><a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=edit&amp;id={$menu['menu_id']}'>{$menu['menu_title']}</a></strong></span>
			<div class='desctext'>{$menu['menu_description']}</div>
		</td>
		<td>
HTML;

/* Show a status, if appropriate */
if( $menu['menu_permissions'] )
{
	$_groups	= explode( ',', $menu['menu_permissions'] );
	$_disGroups	= array();
	
	foreach( $_groups as $_groupId )
	{
		$_disGroups[]	= $this->caches['group_cache'][ $_groupId ]['g_title'];
	}
	
	$text	= sprintf( $this->lang->words['menu_app__restrict_desc'], implode( ', ', $_disGroups ) );
	
	$IPBHTML .= <<<HTML
	<div class='desctext'>{$text}</div>
HTML;
}

$IPBHTML .= <<<HTML
		</td>
		<td class='col_buttons'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a href='{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=edit&amp;id={$menu['menu_id']}'>{$this->lang->words['edit_menu_properties']}</a>
				</li>
				<li class='i_delete'>
					<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=settings&amp;section=menu&amp;do=delete&amp;id={$menu['menu_id']}' );">{$this->lang->words['del_menu_item']}</a>
				</li>
			</ul>
		</td>
	</tr>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Add or edit a menu item
 *
 * @param	string		add|edit
 * @param	array		Menu data
 * @param	array 		Submenu data
 * @return	string		HTML
 */
public function menuForm( $type, $menu, $submenus=array() )
{
$IPBHTML = "";
//--starthtml--//

$groups			= array();
$_groupOpts		= '';

foreach( $this->caches['group_cache'] as $id => $data )
{
	$groups[]	= array( $id, $data['g_title'] );
	$_groupOpts	.= "<option value='{$id}'>{$data['g_title']}</option>";
}

$permissions	= $this->registry->output->formMultiDropdown( 'menu_permissions[]', $groups, explode( ',', $menu['menu_permissions'] ) );
$title			= $this->registry->output->formInput( 'menu_title', $menu['menu_title'] );
$url			= $this->registry->output->formInput( 'menu_url', $menu['menu_url'] );
$description	= $this->registry->output->formTextarea( 'menu_description', $menu['menu_description'], 40, 5, '', '', 'normal' );
$attributes		= $this->registry->output->formTextarea( 'menu_attributes', IPSText::textToForm( $menu['menu_attributes'] ), 40, 5, '', '', 'normal' );
$submenu		= $this->registry->output->formYesNo( 'submenu_items', count($submenus) ? 1 : 0 );
$menutrigger	= $this->registry->output->formDropdown( 'menu_submenu', array( array( 1, $this->lang->words['menuitem_sub__over'] ), array( 2, $this->lang->words['menuitem_sub__click'] ) ), $menu['menu_submenu'] );


$text			= $type == 'edit' ? $this->lang->words['editing_menu_item'] : $this->lang->words['adding_menu_item'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$submenuItems	= IPSText::simpleJsonEncode( $submenus );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}module=settings&amp;section=menu&amp;do={$action}' method='post' id='adminform' name='adform'>
<input type='hidden' name='id' value='{$menu['menu_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['menu_item_details']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_title']}</strong></td>
			<td class='field_field'>
				{$title}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_url']}</strong></td>
			<td class='field_field'>
				{$url}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_desc']}</strong></td>
			<td class='field_field'>
				{$description}
				<div class='desctext'>{$this->lang->words['menuitem_desc_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_permissions']}</strong></td>
			<td class='field_field'>
				{$permissions}
				<div class='desctext'>{$this->lang->words['menuitem_perms_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_attributes']}</strong></td>
			<td class='field_field'>
				{$attributes}
				<div class='desctext'>{$this->lang->words['menuitem_attributes_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_submenu']}</strong></td>
			<td class='field_field'>
				{$submenu}
				<div class='desctext'>{$this->lang->words['menuitem_submenu_desc']}</div>
			</td>
		</tr>
		<tr id='submenu_items_method'>
			<td class='field_title'><strong class='title'>{$this->lang->words['menuitem_submenu_type']}</strong></td>
			<td class='field_field'>
				{$menutrigger}
				<div class='desctext'>{$this->lang->words['menuitem_submenu_type_desc']}</div>
			</td>
		</tr>
		<tr id='submenu_items_wrapper'>
			<td class='field_field' colspan='2'>
				<div class='acp-box'>
					<table class='ipsTable'>
						<tr>
							<th>{$this->lang->words['submenu_th_title']}</th>
							<th>{$this->lang->words['submenu_th_url']}</th>
							<th>{$this->lang->words['submenu_th_desc']}</th>
							<th>{$this->lang->words['submenu_th_attr']}</th>
							<th>{$this->lang->words['submenu_th_perms']}</th>
						</tr>
						<tbody id='submenu_items_body'></tbody>
						<tr>
							<td class='subhead' colspan='5'>
								<div class='right'><input type='button' class='button primary' id='submenu_another' value='{$this->lang->words['submenu_button_add']}' /></div>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
	</div>
</div>	
</form>
<script type='text/javascript'>
	ipb.templates['submenu_item']	= new Template("<tr id='submenu_item_#{trid}' class='submenu_item_row'><td><a href='#' class='delete_menu_row'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' /></a> <input type='hidden' name='edit_map[]' value='#{trid}=#{id}' /><input class='input_text' type='text' size='15' value='#{title_value}' name='submenu_title_#{trid}' /></td><td><input class='input_text' type='text' size='15' value='#{url_value}' name='submenu_url_#{trid}' /></td><td><textarea class='multitext normal' wrap='soft' rows='2' cols='20' name='submenu_description_#{trid}'>#{value_description}</textarea></td><td><textarea class='multitext normal' wrap='soft' rows='2' cols='20' name='submenu_attributes_#{trid}'>#{value_attributes}</textarea></td><td> <input type='checkbox' id='nosubmenu_perms_#{trid}' checked='checked' /> {$this->lang->words['menu_show_everyone']}<div id='submenu_permissions_#{trid}' style='display:none; margin-top: 4px;'>{$this->lang->words['hide_sel_subgroup']}<br /><select multiple='multiple' size='3' name='submenu_permissions_#{trid}[]' id='submenu_permissions_#{trid}_menu'>{$_groupOpts}</select></div></td></tr>");
	
	acp.ccs.initSubmenuItems( {$submenuItems} );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Edit an application's tab properties
 *
 * @param	array		App data
 * @return	string		HTML
 */
public function applicationForm( $application )
{
$IPBHTML = "";
//--starthtml--//

$groups			= array();

foreach( $this->caches['group_cache'] as $id => $data )
{
	$groups[]	= array( $id, $data['g_title'] );
}

$permissions	= $this->registry->output->formMultiDropdown( 'app_permissions[]', $groups, explode( ',', $application['app_tab_groups'] ) );
$title			= $this->registry->output->formInput( 'app_public_title', $application['app_public_title'] );
$tabHidden		= $this->registry->output->formYesNo( 'app_hide_tab', $application['app_hide_tab'] );
$description	= $this->registry->output->formTextarea( 'app_tab_description', $application['app_tab_description'], 40, 5, '', '', 'normal' );
$attributes		= $this->registry->output->formTextarea( 'app_tab_attributes', IPSText::textToForm( $application['app_tab_attributes'] ), 40, 5, '', '', 'normal' );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['edit_app_h2']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}module=settings&amp;section=menu&amp;do=saveApp' method='post' id='adminform' name='adform'>
<input type='hidden' name='appkey' value='{$application['app_directory']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['app_edit_details']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_title']}</strong></td>
			<td class='field_field'>
				{$title}
				<div class='desctext'>{$this->lang->words['appitem_title_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_url']}</strong></td>
			<td class='field_field'>
				{$this->registry->output->buildSEOUrl( "app={$application['app_directory']}", 'public', 'false', "app={$application['app_directory']}" )}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_desc']}</strong></td>
			<td class='field_field'>
				{$description}
				<div class='desctext'>{$this->lang->words['appitem_desc_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_hidetab']}</strong></td>
			<td class='field_field'>
				{$tabHidden}
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_permissions']}</strong></td>
			<td class='field_field'>
				{$permissions}
				<div class='desctext'>{$this->lang->words['appitem_perms_desc']}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['appitem_attributes']}</strong></td>
			<td class='field_field'>
				{$attributes}
				<div class='desctext'>{$this->lang->words['appitem_attributes_desc']}</div>
			</td>
		</tr>

	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}