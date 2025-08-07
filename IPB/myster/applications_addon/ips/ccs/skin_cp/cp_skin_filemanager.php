<?php
/**
 * <pre>
 * Invision Power Services
 * File manager skin file
 * Last Updated: $Date: 2011-12-16 20:26:41 -0500 (Fri, 16 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10017 $
 */
 
class cp_skin_filemanager
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
 * Button to download .htaccess from advanced settings page
 *
 * @access	public
 * @return	string		HTML
 */
public function downloadHtaccess()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton ipbmenu' id='htaccess_menu'>
				<a href='#' title='{$this->lang->words['download_htaccess']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/disk.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['download_htaccess']}
				</a>
			</li>
		</ul>
	</div>

<ul class='acp-menu' id='htaccess_menu_menucontent'>
HTML;

$_urls	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );

foreach( $_urls as $k => $v )
{
$IPBHTML .= <<<HTML
	<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=settings&amp;section=settings&amp;do=download&amp;index={$k}' title='{$this->lang->words['download_htaccess_link']} {$v}'>{$this->lang->words['download_htaccess_link']} {$v}</a></li>
HTML;
}

$IPBHTML .= <<<HTML
</ul>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Form to specify where to move items to
 *
 * @access	public
 * @param	string		Start point for items we are moving
 * @param	array 		Folders we can omit as option to move to
 * @param	array 		Folders we can move to
 * @param	array 		Pages we are moving
 * @return	string		HTML
 */
public function moveToForm( $startPoint, $ignorable, $folders, $pages )
{
$IPBHTML = "";
//--starthtml--//

$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
$IPBHTML .= $_global->getJsLangs();

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['move_files']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['move_to_form_header']}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=multi&amp;action=move' method='post'>
	<input type='hidden' name='return' value='{$this->request['return']}' />
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['move_to_form_header']}</strong></td>
			<td class='field_field'>
				<input type='radio' name='moveto' value='/' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> /<br />
HTML;

	foreach( $folders as $folder )
	{
		if( $folder == '/' OR $folder == $startPoint OR in_array( $folder, $ignorable ) )
		{
			continue;
		}

		$IPBHTML .= <<<HTML
				<input type='radio' name='moveto' value='{$folder}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$folder}<br />
HTML;
	}

$IPBHTML .= <<<HTML
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['moved_files_summary']}</strong></td>	
			<td class='field_field'>
HTML;

if( is_array($this->request['folders']) AND count($this->request['folders']) )
{

	foreach( $this->request['folders'] as $folder )
	{
		$paths	= explode( '/', urldecode($folder) );
		$path	= array_pop( $paths );
		
		$IPBHTML .= <<<HTML
				<input type='checkbox' checked='checked' name='folders[]' value='{$folder}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$path} <br />
HTML;
	}
	
}

if( is_array($pages) AND count($pages) )
{
	foreach( $pages as $page )
	{
		$IPBHTML .= <<<HTML
				<input type='checkbox' checked='checked' name='pages[]' value='{$page['page_id']}' /> <img src='{$this->settings['skin_acp_url']}/images/ccs/file.png' alt='{$this->lang->words['file_alt']}' /> {$page['page_folder']}/{$page['page_seo_name']}<br />
HTML;

	}
}
	
$IPBHTML .= <<<HTML
			</td>
		</tr>	
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['move__button']}' class="button" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}module=pages';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Create or edit directory
 *
 * @access	public
 * @param	string		Add/edit
 * @return	string		HTML
 */
public function directoryForm( $type )
{
$IPBHTML = "";
//--starthtml--//

$text	= $type == 'add' ? $this->lang->words['adding_a_folder'] : $this->lang->words['renaming_a_folder'];

$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
$IPBHTML .= $_global->getJsLangs();

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<div class='acp-box'>
	<h3>{$text}</h3>
HTML;

if( $type == 'add' )
{
	$formField		= $this->registry->output->formInput( 'folder_name' );
	
	$IPBHTML .= "		<input type='hidden' name='do' value='doCreateFolder' />
		<input type='hidden' name='parent' value='{$this->request['in']}' />";
}
else
{
	$folders		= explode ( '/', urldecode($this->request['dir']) );
	$folderName		= array_pop( $folders );
	$formField		= $this->registry->output->formInput( 'folder_name', $folderName );

	$IPBHTML .= "		<input type='hidden' name='do' value='doRenameFolder' />
		<input type='hidden' name='current' value='{$this->request['dir']}' />";
}

$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['set_folder_name']}</strong></td>
			<td class='field_field'>{$formField}</td>
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

/**
 * Show the main screen
 *
 * @access	public
 * @param	string		Current path
 * @param	array 		Folders in the path
 * @param	array 		Files in the path
 * @return	string		HTML
 */
public function overview( $path, Array $folders, Array $files )
{
$IPBHTML = "";
//--starthtml--//

$urlencodePath	= urlencode($path);

$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
$IPBHTML .= $_global->getJsLangs();

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.pagemanager.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['page_and_file_man']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['pagemanager_helpblurb']}
		</div>
	</div>

	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton ipbmenu' id='menu_page_rootmain'>
				<a href='#' title='{$this->lang->words['add_content_button']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_content_button']}' />
					{$this->lang->words['add_content_button']}
				</a>
			</li>
			<li class='ipsActionButton i_new_folder'>
				<a id='rootmain_add_folder' rel='{$this->request['dir']}' href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=newDir&amp;in={$urlencodePath}' title='{$this->lang->words['add_folder_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/folder_add.png' alt='{$this->lang->words['add_folder_alt']}' />
					{$this->lang->words['add_folder_alt']}
				</a>
			</li>
		</ul>
	</div>
</div>

<form action='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=multi' method='post' id='multi-action-form'>
<input type='hidden' name='return' value='{$urlencodePath}' />
<div class='acp-box adv_controls ipsTreeTable' id='page_manager'>
	<h3>
		<div id='page_search' class='ipsInlineTreeSearch'>
			<input type='text' value='{$this->lang->words['pages__filterpages']}' id='page_search_box' spellcheck='false' size='16' /> <a href='#' class='cancel' id='cancel_filter'><img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='{$this->lang->words['cancel']}' /></a>
		</div>
		{$this->lang->words['pages__yourfoldspages']}
	</h3>
	<div class='row root ipsControlRow' style='position: relative'>
		<span id='page_subtitle'>
			<ul class='ipsControlStrip'>
				<li class='i_new_folder'>
					<a rel='{$this->request['dir']}' id='root_add_folder' href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=newDir&amp;in={$urlencodePath}' title='{$this->lang->words['add_folder_alt']}'> {$this->lang->words['add_folder_alt']}</a>
				</li>
				<li class='i_page_add ipbmenu' id='menu_page_root'>
					<a href='#' title='{$this->lang->words['add_content_button']}'>{$this->lang->words['add_content_button']}</a>
				</li>
			</ul>
			<img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$this->lang->words['pages__siteroot']}
		</span>
	</div>
	<div id='filter_results'></div>
HTML;
	
	if( !count( $folders ) && !count( $files ) )
	{
		$IPBHTML .= <<<HTML
		<div class='no_messages'>
			{$this->lang->words['no_pages_created']} <a class="mini_button" href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;in=%2F'>{$this->lang->words['create_one_now']}</a>
		</div>
HTML;
	}
	else
	{

		$IPBHTML .= <<<HTML
		<div id='f_wrap_root'>
HTML;

		foreach( $folders as $object )
		{
			$IPBHTML .= $this->showFolder( $object );
		}
	
		foreach( $files as $object )
		{
			$IPBHTML .= $this->showFile( $object );
		}
		
		$IPBHTML .= <<<HTML
	</div>
HTML;

}

$IPBHTML .= <<<HTML
	<div class="acp-actionbar" id='manage-opts'>
		<div>
			{$this->lang->words['with_selected__form']} 
			<select name='action' id='multi-action'>
				<option value='move'>{$this->lang->words['form__move_items']}</option>
				<option value='delete'>{$this->lang->words['form__delete_items']}</option>
			</select>
			<input type="submit" value="{$this->lang->words['form__go']}" class="button primary" />
		</div>
	</div>
</div>
</form>

<ul class='acp-menu' id='menu_page_root_menucontent'>
	<li class='icon ccs-file'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;in={$urlencodePath}' title='{$this->lang->words['add_page_button']}'>{$this->lang->words['add_page_button']}</a></li>
	<li class='icon ccs-css'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=css&amp;in={$urlencodePath}' title='{$this->lang->words['add_css_button']}'>{$this->lang->words['add_css_button']}</a></li>
	<li class='icon ccs-js'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=js&amp;in={$urlencodePath}' title='{$this->lang->words['add_js_button']}'>{$this->lang->words['add_js_button']}</a></li>
</ul>
<ul class='acp-menu' id='menu_page_rootmain_menucontent'>
	<li class='icon ccs-file'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;in={$urlencodePath}' title='{$this->lang->words['add_page_button']}'>{$this->lang->words['add_page_button']}</a></li>
	<li class='icon ccs-css'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=css&amp;in={$urlencodePath}' title='{$this->lang->words['add_css_button']}'>{$this->lang->words['add_css_button']}</a></li>
	<li class='icon ccs-js'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=js&amp;in={$urlencodePath}' title='{$this->lang->words['add_js_button']}'>{$this->lang->words['add_js_button']}</a></li>
</ul>
<script type='text/javascript'>
	ipb.templates['ccs_folder_wrap'] = new Template("<div id='f_wrap_#{id}' class='parent_wrap' style='display: none'><div>#{content}</div></div>");
	ipb.templates['ccs_new_folder'] = new Template("<div><h3 class='ipsBlock_title'>{$this->lang->words['jst_new_folder']}</h3><div class='pad'><strong>{$this->lang->words['jst_foldername']}</strong>&nbsp;&nbsp;<input type='text' class='input_text' size='25' id='folder_name_#{id}' />&nbsp;&nbsp;<input type='submit' class='realbutton' value='{$this->lang->words['jst__create']}' id='create_folder_#{id}' /></div></div>");
	ipb.templates['ccs_delete_folder'] = new Template("<div><div class='pad center'><strong>{$this->lang->words['jst_delete_fold']}</strong><br /><br /><input type='submit' class='realbutton redbutton' id='del_#{id}' value='{$this->lang->words['jst_confirm']}' /> <input type='submit' class='realbutton' value='{$this->lang->words['jst_cancel']}' id='cancel_del_#{id}' /></div></div>");
	ipb.templates['ccs_empty_folder'] = new Template("<div><div class='pad center'><strong>{$this->lang->words['jst_empty_fold']}</strong><br /><br /><input type='submit' class='realbutton redbutton' id='empty_#{id}' value='{$this->lang->words['jst_confirm']}' /> <input type='submit' class='realbutton' value='{$this->lang->words['jst_cancel']}' id='cancel_empty_#{id}' /></div></div>");
	
	// Filter templates
	ipb.templates['ccs_filter_row'] = new Template("<div class='filter_row'><img src='{$this->settings['skin_acp_url']}/images/icons/#{type}.png' class='icon' alt='{$this->lang->words['folder_alt']}' /><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=quickEdit&amp;page=#{id}'>#{path}#{name}</a><br /><span class='desctext infotext'>#{title}</span></div>");
	ipb.templates['ccs_filter_none'] = new Template("<div class='filter_row no_results'>{$this->lang->words['jst_filternoma']}</div>");
	
	ipb.lang['filter_matches']			= "{$this->lang->words['js__pagefiltersubtitle']}";
	ipb.lang['confirm_submit_del']		= "{$this->lang->words['js__confirmsubmit']}";
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Show a single folder
 *
 * @access	public
 * @param	array 		Folders data
 * @return	string		HTML
 */
public function showFolder( $object )
{
$IPBHTML = "";
//--starthtml--//

$path	= urlencode( $object['full_path'] );
$mtime	= $this->registry->getClass('class_localization')->getDate( $object['last_modified'], 'SHORT', 1 );
$id		= md5( $path );
$_css	= $object['name'] == '..' ? ' nochildren' : '';

$IPBHTML .= <<<HTML
	<div class='row parent{$_css} ipsControlRow' id='f_{$id}'>
HTML;

	$name	= $object['name'] != '..' ?
			"<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=editFolder&amp;dir={$path}' class='path' rel=\"{$path}\"><img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' class='icon' alt='{$this->lang->words['folder_alt']}' /></a>" :
			"<img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' alt='{$this->lang->words['folder_alt']}' />";

	if( $object['name'] != '..' )
	{
		$IPBHTML .= <<<HTML
			<ul class='ipsControlStrip'>
				<li class='i_new_folder'>
					<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=newDir&amp;in={$path}' title='{$this->lang->words['add_folder_alt']}'> {$this->lang->words['add_folder_alt']}</a>
				</li>
				<li class='i_page_add ipbmenu' id='menu_page{$id}'>
					<a href='#' class='page_add' title='{$this->lang->words['add_page_button']}'>{$this->lang->words['add_content_button']}</a>
				</li>
				<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$id}'>
					<a href='#'>{$this->lang->words['folder_options_alt']}</a>
				</li>
			</ul>
			<ul class='acp-menu' id='menu_folder{$id}_menucontent' style='display: none'>
				<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=editFolder&amp;dir={$path}'>{$this->lang->words['edit_folder_name']}</a></li>
				<li class='icon delete'><a href='#' class='empty_folder' id='empty_folder_{$id}' rel="{$path}">{$this->lang->words['empty_folder']}</a></li>
				<li class='icon delete'><a href='#' class='delete_folder' id='delete_folder_{$id}' rel="{$path}">{$this->lang->words['delete_folder_link']}</a></li>
				<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=multi&amp;action=move&amp;folders[]={$path}'>{$this->lang->words['move_folder_link']}</a></li>
			</ul>
			<ul class='acp-menu' id='menu_page{$id}_menucontent' style='display: none'>
				<li class='icon ccs-file'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;in={$path}' title='{$this->lang->words['add_page_button']}'>{$this->lang->words['add_page_button']}</a></li>
				<li class='icon ccs-css'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=css&amp;in={$path}' title='{$this->lang->words['add_css_button']}'>{$this->lang->words['add_css_button']}</a></li>
				<li class='icon ccs-js'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;fileType=js&amp;in={$path}' title='{$this->lang->words['add_js_button']}'>{$this->lang->words['add_js_button']}</a></li>
			</ul>

			&nbsp;{$name}<input type='checkbox' name='folders[]' value='{$path}' class='check' />&nbsp;<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=viewdir&amp;dir={$path}'>{$object['name']}</a></strong>
			<span class='desctext infotext'>{$mtime}</span>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
				{$name}&nbsp;<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=viewdir&amp;dir={$path}'>{$object['name']}</a></strong>
HTML;
	}

	$IPBHTML .= <<<HTML
	</div>
HTML;

if( $this->request['module'] == 'ajax' AND $object['name'] != '..' )
{
	$IPBHTML .= <<<HTML
	<script type='text/javascript'>
		new ipb.Menu( $('menu_page{$id}'), $( "menu_page{$id}_menucontent" ) );
		new ipb.Menu( $('menu_folder{$id}'), $( "menu_folder{$id}_menucontent" ) );
	</script>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a single file
 *
 * @access	public
 * @param	string		Current path
 * @param	array 		Folders in the path
 * @param	array 		Files in the path
 * @param	array		Unfinished pages
 * @return	string		HTML
 */
public function showFile( $object )
{
$IPBHTML = "";
//--starthtml--//

$path	= urlencode( $object['full_path'] );
$icon	= $object['icon'] ? $object['icon'] . '.png' : 'file.png';
$size	= $object['directory']	? ''		: IPSLib::sizeFormat( $object['size'] );
$mtime	= $this->registry->getClass('class_localization')->getDate( $object['last_modified'], 'SHORT', 1 );
$id		= md5( $path );

$url	= $this->registry->ccsFunctions->returnPageUrl( array( 'page_folder' => $object['path'], 'page_seo_name' => $object['name'], 'page_id' => $object['page_id'] ) );

$texts	= array(
				'edit'		=> $this->lang->words['edit_page_link'],
				'delete'	=> $this->lang->words['delete_page_link'],
				'move'		=> $this->lang->words['move_page_link'],
				);

if( $object['icon'] != 'page' )
{
	$texts	= array(
					'edit'		=> $this->lang->words['edit_pagefile_link'],
					'delete'	=> $this->lang->words['delete_file_link'],
					'move'		=> $this->lang->words['move_pagefile_link'],
					);
}

$revisions	= sprintf( $this->lang->words['page_managerevisions'], $object['revisions'] );

$IPBHTML .= <<<HTML
<div class='child row ipsControlRow' id='record_{$object['page_id']}'>
	
	<ul class='ipsControlStrip'>
		<li class='i_edit'>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=quickEdit&amp;page={$object['page_id']}' title='{$texts['edit']}'>{$texts['edit']}</a>
		</li>		
		<li class='ipsControlStrip_more ipbmenu' id='menu_{$id}'>
			<a href='#'>{$this->lang->words['folder_options_alt']}</a>
		</li>
	</ul>
	<ul class='acp-menu' id='menu_{$id}_menucontent' style='display: none'>
		<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=manage&amp;do=multi&amp;action=move&amp;pages[]={$object['page_id']}'>{$texts['move']}</a></li>
		<li class='icon view'><a href='{$this->settings['base_url']}module=pages&amp;section=revisions&amp;type=page&amp;id={$object['page_id']}'>{$revisions}</a></li>
		<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;page={$object['page_id']}' );">{$texts['delete']}</a></li>
	</ul>
	
	&nbsp;<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=quickEdit&amp;page={$object['page_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$icon}' class='icon' alt='{$this->lang->words['file_alt']}' /></a><input type='checkbox' name='pages[]' value='{$object['page_id']}' class='check' />
	<strong><a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=quickEdit&amp;page={$object['page_id']}'>{$object['name']}</a></strong> <span class='view-page'><a href='{$url}' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/icons/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>

	<br />
	<span class='desctext infotext'>{$mtime}  ({$size})</span>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}


}