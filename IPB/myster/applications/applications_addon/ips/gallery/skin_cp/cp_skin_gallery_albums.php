<?php
/**
 * @file		cp_skin_gallery.php 	IP.Gallery album templates
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-10-09 20:48:10 -0400 (Tue, 09 Oct 2012) $
 * @version		v5.0.5
 * $Revision: 11431 $
 */

/**
 *
 * @class		cp_skin_gallery_albums
 * @brief		IP.Gallery album templates
 */
class cp_skin_gallery_albums
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
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
	/**#@-*/
	
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
 * Albums page wrapper
 *
 * @param	string		$albums		Albums HTML
 * @param	string		$pages		Pagination HTML
 * @return	@e string	HTML
 */
public function albums( $albums, $pages='' ) {

$IPBHTML = "";
//--starthtml--//

$categories	= $this->registry->output->formDropdown( 'searchCat', $this->registry->gallery->helper('categories')->catJumpList( false, 'none', array(), $this->lang->words['searchcat_nocat'] ) );

$IPBHTML .= <<<HTML
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />
<script type='text/javascript' id='progressbarScript' src='{$this->settings['public_dir']}js/3rd_party/progressbar/progressbar.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.gallery.js'></script>
<script type="text/javascript">
	ACPGallery.section = 'albums';

	ipb.lang['js__resynch_albums']	= "{$this->lang->words['js__resynch_albums']}";
	ipb.lang['js__rebuild_images']	= "{$this->lang->words['js__rebuild_images']}";
	ipb.lang['js__reset_perms']		= "{$this->lang->words['js__reset_perms']}";
</script>

<div class='section_title'>
	<h2>{$this->lang->words['albums_page_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['albums_add_button']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='#' class='ipbmenu' id='albumTools'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['tools_button']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
				<ul class='ipbmenu_content' id='albumTools_menucontent' style='display: none'>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="thumbs">{$this->lang->words['albums_tool_images']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="resetpermissions">{$this->lang->words['albums_tool_perms']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="resyncalbums">{$this->lang->words['albums_tool_resync']}</a></li>
				</ul>
			</li>
		</ul>
	</div>
</div>

{$pages}
<div class='acp-box'>
	<h3>{$this->lang->words['acp_manage_albums']}</h3>

	<div class='header'>
		{$this->lang->words['filters_show_all_where']}
		<select id='searchType' name='searchType'>
			<option value='member' id='searchType_member'>{$this->lang->words['filters_owners_name']}</option>
			<option value='album' id='searchType_album'>{$this->lang->words['filters_albums_name']}</option>
		</select>
		<select id='searchMatch' name='searchMatch'>
			<option value='is' id='searchMatch_is'>{$this->lang->words['filters_is']}</option>
			<option value='contains' id='searchMatch_contains'>{$this->lang->words['filters_contains']}</option>
		</select>
		<input type='text' size='20' class='input_text' id='searchText' />
		{$this->lang->words['in_cat_pref']}
		{$categories}
		<select id='searchSort' name='searchSort'>
			<option value='date' id='searchSort_date'>{$this->lang->words['filters_sort_upload']}</option>
			<option value='name' id='searchSort_name'>{$this->lang->words['filters_sort_name']}</option>
			<option value='images' id='searchSort_images'>{$this->lang->words['filters_sort_images']}</option>
			<option value='comments' id='searchSort_comments'>{$this->lang->words['filters_sort_comments']}</option>
		</select>
		<select id='searchDir' name='searchDir'>
			<option value='desc' id='searchDir_desc'>{$this->lang->words['filters_desc']}</option>
			<option value='asc' id='searchDir_asc'>{$this->lang->words['filters_asc']}</option>
		</select>
		<input type='button' id='searchGo' value='{$this->lang->words['filters_update']}' class='mini_button' />
		<input type='button' id='clearResults' value='{$this->lang->words['album_search_clear']}' class='mini_button secondary' style='display:none;' />
	</div>
	<div id='galleryAlbumsHere'>
		{$albums}
	</div>
</div>
<br />
{$pages}
<div id='storedAlbums' style='display:none;'></div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Albums page wrapper
 *
 * @param	array		$albums		Albums data
 * @return	@e string	HTML
 */
public function ajaxAlbums( $albums ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<table class='ipsTable' id='albumsDragList'>
HTML;

if ( !empty($albums) AND is_array($albums) AND count($albums) )
{
	foreach( $albums as $albumId => $album )
	{	
		$cover		= $this->registry->getClass('gallery')->inlineResize( $album['thumb'], 30, 30 );
		$image		= '';
		$category	= $this->registry->gallery->helper('categories')->fetchCategory( $album['album_category_id'] );
		
		if ( $this->registry->getClass('gallery')->helper('albums')->isPrivate( $album ) )
		{
			$image = '<img src=\'' . $this->settings['skin_app_url'] . '/images/lock.png\' style="vertical-align: text-top" title=\'' . $this->lang->words['acp_private_album'] . '\' />&nbsp;';
		}
		else if ($this->registry->getClass('gallery')->helper('albums')->isFriends( $album ) )
		{
			$image = '<img src=\'' . $this->settings['skin_app_url'] . '/images/users.png\' style="vertical-align: text-top" title=\'' . $this->lang->words['acp_friends_album'] . '\' />&nbsp;';
		}

		$lastUpload = $this->lang->getDate( $album['album_last_img_date'], 'short' );
		
		$IPBHTML .= <<<HTML
	<tr id='albums_{$albumId}' class='ipsControlRow'>
		<td width='1%'><div class='ipsUserPhoto'>{$cover}</div></td>
		<td width='90%'>
			{$image}<strong>{$album['album_name']}</strong>
			<div class='desctext'>
				{$this->lang->words['album_owned_by']} <a href='#' class='searchByMember' data-album-owners-name='{$album['owners_members_display_name']}' title='{$this->lang->words['filters_find_all_by_members']}'>{$album['owners_members_display_name']}</a>
				{$this->lang->words['in_cat_pref']} <a href='#' class='searchByCategory' data-album-category-id='{$category['category_id']}' title='{$this->lang->words['filters_find_all_by_cat']}'>{$category['category_name']}</a>
			</div>
		</td>
		<td class='col_buttons desc lighter' style='width: 200px; min-width: 200px;'>
			<strong>{$album['album_count_imgs']} {$this->lang->words['images_lower']}<br />{$album['album_count_comments']} {$this->lang->words['comments_lower']}</strong><br />{$this->lang->words['last_upload']} {$lastUpload}
		</td>
		<td class='col_buttons'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'><a class='edit' href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;albumId={$albumId}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a></li>
				<li class='ipsControlStrip_more ipbmenu' id='menu_album_{$albumId}'><a href='#'>&nbsp;</a></li>
			</ul>
			<ul class='acp-menu' id='menu_album_{$albumId}_menucontent' style='display: none'>
				<li class='icon delete _albumDeleteDialogueTrigger' album-id='{$albumId}'><a href='#'>{$this->lang->words['delete']}...</a></li>
				<li class='icon delete'><a onclick="if ( !confirm('{$this->lang->words['albums_delete_confirm']}' ) ) { return false; }" href="{$this->settings['base_url']}{$this->form_code}do=emptyAlbum&amp;albumId={$album['album_id']}">{$this->lang->words['albums_link_empty']}</a></li>
				<li class='icon refresh ajaxWithDialogueTrigger' ajaxUrl="app=gallery&amp;module=ajax&amp;section=albums&amp;do=resyncAlbums&amp;albumId={$albumId}&amp;return=okresponse"><a href='#'>{$this->lang->words['albums_link_resynch']}</a></li>
				<li class='icon manage'><a href='#' album-id="{$albumId}" progress="thumbs">{$this->lang->words['albums_link_rebuild']}</a></li>
			</ul>
		</td>
	</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	</tr>
		<td class='no_messages' colspan='4'>
			{$this->lang->words['no_albums']}
		</td>
	</tr>
HTML;
}

$IPBHTML .= <<<HTML
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add/edit album form
 *
 * @param	array		$album		Album data
 * @param	array		$form		Form elements
 * @return	@e string	HTML
 */
public function albumForm( $album, $form ) 
{
$IPBHTML = "";
//--starthtml--//

$watermarkWarning	= ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? "<br /><div class='information-box'><strong>{$this->lang->words['warning_no_watermark']}</strong></div>" : "";

$_message	= sprintf( $this->lang->words['gal_album_memgalnote'], $this->registry->gallery->helper('categories')->fetchCategory( $this->settings['gallery_members_album'], 'category_name' ) );

$IPBHTML .= <<<HTML
<div class='information-box'>{$_message}</div><br />
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.gallery.js'></script>
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />
<script type="text/javascript">
	ACPGallery.section = 'form';
	ACPGallery.memberGallery	= '{$this->settings['gallery_members_album']}';
</script>
<div class='section_title'>
	<h2>{$form['title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['formcode']}' id='adminform' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<input type='hidden' name='albumId' value='{$album['album_id']}' />
	
	<div class='acp-box'>
		<h3>{$form['title']}</h3>
		
		<div>
			<table class="ipsTable double_pad">
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_name']}</strong></td>
					<td class='field_field'>{$form['album_name']}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_description']}</strong></td>
					<td class='field_field'>{$form['album_description']}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_parent']}</strong></td>
					<td class='field_field'>{$form['album_category_id']}<div class='desctext'>{$this->lang->words['cat_no_album_dis']}</div></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_is_public']}</strong></td>
					<td class='field_field'>
						<div id='album_category_select_wrap'>{$form['album_type']}</div>
						<div id='album_category_select_text'><em>{$this->lang->words['album_form_memgalnote']}</em></div>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_album_owner']}</strong></td>
					<td class='field_field'>{$form['album_owner_id__name']}<div class='desctext'>{$this->lang->words['cats_form_album_owner_desc']}</div></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['album_sort_options__key']}</strong></td>
					<td class='field_field'>{$form['album_sort_options__key']} {$form['album_sort_options__dir']}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['form_watermark_title']}</strong></td>
					<td class='field_field'>{$form['album_watermark']}<div class='desctext'>{$this->lang->words['form_watermark_desc']}</div>{$watermarkWarning}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_comments']}</strong></td>
					<td class='field_field'>{$form['album_allow_comments']}<div class='desctext'>{$this->lang->words['form_acomments_desc']}</div></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_ratings']}</strong></td>
					<td class='field_field'>{$form['album_allow_rating']}<div class='desctext'>{$this->lang->words['form_aratings_desc']}</div></td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['album_manage_show_after_forum']}</strong></td>
					<td class='field_field'>{$form['album_after_forum_id']}</td>
				</tr>
			</table>
		</div>
		
		<div class='acp-actionbar'>
			<input type='submit' name='submit' value='{$form['button']}' class='button primary' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Shows the form to configure album defaults
 *
 * @param	array		$form		Form elements
 * @return	@e string	HTML
 */
public function defaults( $form ) 
{
$IPBHTML = "";
//--starthtml--//

$watermarkWarning	= ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? "<br /><br /><div class='information-box'><strong>{$this->lang->words['warning_no_watermark']}</strong></div>" : "";

$IPBHTML .= <<<HTML
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />

<div class='section_title'>
	<h2>{$this->lang->words['configure_album_defaults']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveDefaults' id='adminform' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['configure_album_defaults']}</h3>
		
		<div>
			<table class="ipsTable double_pad">
				<tr>
					<td class='field_title'>
						<strong class='title'>
							{$this->lang->words['cats_form_is_public']}
							<input type='checkbox' name='album_type_edit' id='album_type_edit' value='1' {$form['album_type_edit']} class='checkbox_toggler' />
						</strong>
					</td>
					<td class='field_field'>
						{$form['album_type']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>
							{$this->lang->words['album_sort_options__key']}
							<input type='checkbox' name='album_sort_edit' id='album_sort_edit' value='1' {$form['album_sort_edit']} class='checkbox_toggler' />
						</strong>
					</td>
					<td class='field_field'>
						{$form['album_sort_options__key']} {$form['album_sort_options__dir']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>
							{$this->lang->words['form_watermark_title']}
							<input type='checkbox' name='album_watermark_edit' id='album_watermark_edit' value='1' {$form['album_watermark_edit']} class='checkbox_toggler' />
						</strong>
					</td>
					<td class='field_field'>
						{$form['album_watermark']}<div class='desctext'>{$this->lang->words['form_watermark_desc']}</div>{$watermarkWarning}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>
							{$this->lang->words['cats_form_comments']}
							<input type='checkbox' name='album_comments_edit' id='album_comments_edit' value='1' {$form['album_comments_edit']} class='checkbox_toggler' />
						</strong>
					</td>
					<td class='field_field'>
						{$form['album_allow_comments']}<div class='desctext'>{$this->lang->words['form_acomments_desc']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>
							{$this->lang->words['cats_form_ratings']}
							<input type='checkbox' name='album_ratings_edit' id='album_ratings_edit' value='1' {$form['album_ratings_edit']} class='checkbox_toggler' />
						</strong>
					</td>
					<td class='field_field'>
						{$form['album_allow_rating']}<div class='desctext'>{$this->lang->words['form_aratings_desc']}</div>
					</td>
				</tr>
			</table>
		</div>
		
		<div class='acp-actionbar'>
			<input type='submit' name='submit' value='{$this->lang->words['save_defaults_button']}' class='button primary' />
		</div>
	</div>
</form>
<style type='text/css'>
	.checkbox_toggler span {
		background-image: url( {$this->settings['skin_acp_url']}/images/toggle_sprite.png );
	}
</style>
<script type='text/javascript'>
	jQ('.checkbox_toggler').ipsToggler({
		on: {
			'cssClass': 'on',
			'title': '{$this->lang->words['album_defaults_unlocked']}'
		},
		off: {
			'cssClass': 'off',
			'title': '{$this->lang->words['album_defaults_locked']}'
		},
		baseClass: 'checkbox_toggler'
	});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Delete album popup
 *
 * @param	array		$data		Popup data
 * @return	@e string	HTML
 */
public function acpDeleteAlbumDialogue( $data=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action="{$this->settings['base_url']}app=gallery&amp;module=albums&amp;section=manage&amp;do=deleteAlbum&amp;albumId={$data['album_id']}&amp;secure_key={$this->member->form_hash}" method="post" id="albumDeleteForm_{$data['album_id']}">
	<input type='hidden' name='auth_key' value='{$this->member->form_hash}' />
	
	<h3>{$this->lang->words['delete_album']}</h3>
	<div class='pad center'>
	 {$this->lang->words['mod_alb_del_title']}
HTML;

if ( $data['album_options'] !== false OR $data['cat_options'] !== false )
{
	$IPBHTML .= <<<HTML
	<div style="width:auto; display:inline-block; margin: 0 auto; text-align: left;" class='pad'>
HTML;

	if ( $data['album_options'] !== false )
	{
		$IPBHTML .= <<<HTML
		<input type="radio" name="doDelete" value="0" checked="checked" /> {$this->lang->words['mod_alb_del_move']}
		<select name='move_to_album_id' id='move_to_album_id' class='input_select'>
			{$data['album_options']}
		</select>
		<br />
HTML;
	}

	if ( $data['cat_options'] !== false )
	{
		$IPBHTML .= <<<HTML
		<input type="radio" name="doDelete" value="-1" checked="checked" /> {$this->lang->words['mod_alb_del_movec']}
		{$data['cat_options']}
		<br />
HTML;
	}

	$IPBHTML .= <<<HTML
		<input type="radio" name="doDelete" value="1" /> {$this->lang->words['mod_alb_del_desc']}
	</div>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<input type="hidden" name="doDelete" value="1" />
HTML;
}

$IPBHTML .= <<<HTML
	 <input type='submit' class="button primary" value="{$this->lang->words['mod_alb_del_go']}" />
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

}