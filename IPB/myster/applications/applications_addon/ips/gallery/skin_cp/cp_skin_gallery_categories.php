<?php
/**
 * @file		cp_skin_categories.php 	IP.Gallery categories skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-03-20 16:51:20 -0400 (Tue, 20 Mar 2012) $
 * @version		v5.0.5
 * $Revision: 10459 $
 */

/**
 *
 * @class		cp_skin_gallery_categories
 * @brief		IP.Gallery categories skin file
 */
class cp_skin_gallery_categories
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
 * Home screen
 *
 * @param	string		$categories		Categories data
 * @return	@e string	HTML
 */
public function mainScreen( $categories ) {

$IPBHTML = "";

$title = $this->request['parent'] ? sprintf( $this->lang->words['gallery_subcats_for'], $this->registry->gallery->helper('categories')->fetchCategory( $this->registry->gallery->helper('categories')->fetchCategory( $this->request['parent'], 'category_parent_id' ), 'category_name' ) ) : $this->lang->words['gallery_categories'];

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.categories.js'></script>
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />
<script type='text/javascript' id='progressbarScript' src='{$this->settings['public_dir']}js/3rd_party/progressbar/progressbar.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.gallery.js'></script>
<script type='text/javascript'>
	ACPGallery.section = 'albums';

	ipb.lang['js__resynch_cats']	= "{$this->lang->words['js__resynch_cats']}";
	ipb.lang['js__rebuild_images']	= "{$this->lang->words['js__rebuild_images']}";
	ipb.lang['js__reset_perms']		= "{$this->lang->words['js__reset_perms']}";
	ipb.lang['gal_confirm_delete_text']	= "{$this->lang->words['gal_js_confirm_delete']}";
	ipb.lang['gal_confirm_empty_text']	= "{$this->lang->words['gal_js_confirm_empty']}";
</script>
<div class='section_title'>
	<h2>{$this->lang->words['gal_categories_overview']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=add&amp;category_parent_id={$this->request['parent']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['gallery_addnewcat']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=modform'><img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='' /> {$this->lang->words['gallery_addmod']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='#' class='ipbmenu' id='albumTools'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['tools_button']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
				<ul class='ipbmenu_content' id='albumTools_menucontent' style='display: none'>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="thumbs">{$this->lang->words['albums_tool_images']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="resetpermissions">{$this->lang->words['albums_tool_perms']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' category-id="all" progress="resynccategories">{$this->lang->words['cat_tools_resync']}</a></li>
				</ul>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	<table class='ipsTable' id='gallery_categories'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th width='90%'>{$this->lang->words['gallery_catname']}</th>
			<th class="col_buttons">&nbsp;</th>
			<th class="col_buttons">&nbsp;</th>
		</tr>
EOF;

if ( $categories )
{
	$IPBHTML .= $categories;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4' class='no_messages center'>{$this->lang->words['gallery_nocats']}</th>
		</tr>
EOF;
}


$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

if ( $categories )
{
	$IPBHTML .= <<<EOF
<script type='text/javascript'>
	jQ("#gallery_categories").ipsSortable('table', { 
		url: "{$this->settings['base_url']}module=ajax&section=categories&do=reorder&md5check={$this->member->form_hash}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'cats[]' }
	} );
</script>
EOF;
}

return $IPBHTML;
}

/**
 * Subcategories HTML
 *
 * @param	array 		Categories
 * @return	@e string	HTML
 */
public function subCategories( $categories ) {

$sub		= array();
$IPBHTML	= "";

foreach( $categories as $id => $data )
{
	$sub[] = "<a href='{$this->settings['base_url']}{$this->form_code}parent={$data['category_id']}'>" . $this->registry->gallery->helper('categories')->fetchCategory( $data['category_id'], 'category_name' ) . "</a>";
}

$IPBHTML .= "<fieldset class='subforums'><legend>{$this->lang->words['gallery_subcats']}</legend>" . implode( ', ', $sub ) . "</legend></fieldset>";

return $IPBHTML;
}

/**
 * Display single category moderator entry
 *
 * @param	array		$data		Moderator data
 * @return	@e string	HTML
 */
public function renderModeratorEntry( $data=array() ) {

$IPBHTML = "";

if( count($data) )
{
	$c = count($data);
	
	$IPBHTML .= <<<HTML
<ul class='right multi_menu' id='modmenu{$data[0]['randId']}'>
	<li>
		<a href='#' class='ipsBadge badge_green'>{$c} {$this->lang->words['gallery_moderators']}</a>
		<ul class='acp-menu'>
HTML;
	
	foreach( $data as $i => $d )
	{
		$IPBHTML .= <<<HTML
			<li>
				<span class='clickable'>{$d['_fullname']} </span>
				<ul class='acp-menu'>
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=editmod&amp;modid={$d['mod_id']}'>{$this->lang->words['gal_mod_edit']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}category={$d['category_id']}&amp;do=delmod&amp;modid={$d['mod_id']}");'>{$this->lang->words['gal_mod_remove']}</a></li>
				</ul>
			</li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
		</ul>
	</li>
</ul>
<script type='text/javascript'>
	jQ("#modmenu{$data[0]['randId']}").ipsMultiMenu();
</script>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Category row
 *
 * @param	string		$content		Subcategories data
 * @param	array		$cat			Category data
 * @param	string		$modData		Moderators data
 * @return	@e string	HTML
 */
public function renderCategory( $content, $cat, $modData='' )
{
$bar_id		= $this->request['parent'] ? $this->registry->gallery->helper('categories')->fetchCategory( $this->request['parent'], 'category_parent_id' ) : 0;
$no_root	= count( $this->registry->gallery->helper('categories')->cat_cache[ $bar_id ] );

$cat['category_description']	= $cat['category_description'] ? "<br /><span class='desctext'>{$cat['category_description']}</span>" : '';
$images							= sprintf( $this->lang->words['galcat_images_info'], ( $cat['category_count_imgs'] + $cat['category_count_imgs_hidden'] ) );
$albums							= sprintf( $this->lang->words['galcat_albums_info'], ( $cat['category_public_albums'] + $cat['category_nonpublic_albums'] ) );
$memberAlbums					= ( $cat['category_id'] == $this->settings['gallery_members_album'] ) ? "<br /><br /><div class='desctext'><em>" . $this->lang->words['cat_mem_gallery'] . '<em></div>' : '';

$IPBHTML .= <<<EOF
<tr class='ipsControlRow isDraggable' id='cat_{$cat['category_id']}'>
	<td class='col_drag' title='{$this->lang->words['t_id']}: {$cat['category_id']}'>
		<span class='draghandle'>&nbsp;</span>
	</td>
	<td>
		{$modData}
		<strong class='title'>{$cat['category_name']}</strong>{$cat['category_description']}
		{$content}
		{$memberAlbums}
	</td>
	<td class='desc lighter'>
		<strong>{$images}<br />
		{$albums}</strong>
	</td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}do=add&amp;category_parent_id={$cat['category_id']}' title='{$this->lang->words['gal_cats_newcat']}'>{$this->lang->words['gal_cats_newcat']}</a></li>
			<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;category={$cat['category_id']}' title='{$this->lang->words['gal_cats_editsettings']}'>{$this->lang->words['gal_cats_editsettings']}</a></li>
			<li class='ipsControlStrip_more ipbmenu' id='menu_{$cat['category_id']}'><a href='#'>&nbsp;</a></li>
		</ul>						
		<ul class='acp-menu' id='menu_{$cat['category_id']}_menucontent' style='display: none'>
			<li class='icon password'><a href='{$this->settings['base_url']}{$this->form_code}do=modform&amp;category={$cat['category_id']}'>{$this->lang->words['gallery_addmod']}</a></li>
			<li class='icon refresh'><a href='{$this->settings['base_url']}{$this->form_code}do=resynch&amp;category={$cat['category_id']}'>{$this->lang->words['gal_cats_resynchcat']}</a></li>
			<li class='icon delete'><a href='#' onclick='ACPGalleryCategories.confirmEmpty({$cat['category_id']});'>{$this->lang->words['gal_cats_emptycat']}</a></li>
			<li class='icon delete'><a href='#' onclick='ACPGalleryCategories.confirmDelete({$cat['category_id']});'>{$this->lang->words['gal_cats_deletecat']}</a></li>
		</ul>
	</td>
</tr>
EOF;

return $IPBHTML;
}

/**
 * Form to add/edit a category
 *
 * @param	array		$category		Category data
 * @param	array		$form			Form elements
 * @param	integer		$max_upload		Max upload size
 * @param	array		$moreTabs		Additional tabs data from plugins
 * @param	string		$firstTab		First tab to highlight
 * @return	string	HTML
 */
public function categoryForm( $category, $form, $max_upload=0, $moreTabs=array(), $firstTab='' ) {

$IPBHTML = "";

$watermarkWarning	= ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? "<br /><div class='information-box'><strong>{$this->lang->words['warning_no_watermark']}</strong></div>" : "";

$hidden		= '';

if( $category['category_id'] == $this->settings['gallery_members_album'] )
{
	$hidden	= "<input type='hidden' name='category_parent_id' value='0' /><input type='hidden' name='category_type' value='1' />";
}

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.categories.js'></script>
<div class='section_title'>
	<h2>{$form['_formTitle']}</h2>
</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= "<div class='ipsSteps_wrap'>";
}

$IPBHTML .= <<<EOF
<form id='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['_formType']}' method='post'>
{$hidden}
<input type='hidden' name='category' value='{$category['category_id']}' />
EOF;

if( $form['_formType'] == 'doadd' )
{
	$steps = 1;

	// Basic settings
	$IPBHTML .= <<<HTML
		<div class='ipsSteps clearfix' id='steps_bar'>
			<ul>
				<li class='steps_active' id='step_basic'>
					<strong class='steps_title'>{$this->lang->words['admin_step']} 1</strong>
					<span class='steps_desc'>{$this->lang->words['galcat_basics']}</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='step_options'>
					<strong class='steps_title'>{$this->lang->words['admin_step']} 2</strong>
					<span class='steps_desc'>{$this->lang->words['galcat_catoptions']}</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='step_rules'>
					<strong class='steps_title'>{$this->lang->words['admin_step']} 3</strong>
					<span class='steps_desc'>{$this->lang->words['galcat_rules']}</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
				<li id='step_permissions'>
					<strong class='steps_title'>{$this->lang->words['admin_step']} 4</strong>
					<span class='steps_desc'>{$this->lang->words['galcat_permissions']}</span>
					<span class='steps_arrow'>&nbsp;</span>
				</li>
			</ul>
		</div>
HTML;
}

$IPBHTML .= <<<EOF
<div class='acp-box'>
EOF;

if( $form['_formType'] != 'doadd' )
{
$IPBHTML .= <<<EOF
	<h3>{$form['_formTitle']}</h3>
	
	<div id='tabstrip_categoryForm' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_Basic'>{$this->lang->words['galcat_basics']}</li>
			<li id='tab_Options'>{$this->lang->words['galcat_catoptions']}</li>
			<li id='tab_Rules'>{$this->lang->words['galcat_rules']}</li>
			<li id='tab_Permissions'>{$this->lang->words['galcat_permissions']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_categoryForm_content' class='ipsTabBar_content'>
EOF;
}

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	<div class='ipsSteps_wrapper' id='ipsSteps_wrapper'>
		<div id='step_basic_content' class='steps_content'>
			<div class='acp-box'>
				<h3>{$this->lang->words['galcat_basics']}</h3>
EOF;
}
else
{
	$IPBHTML .= "<div id='tab_Basic_content'>";
}

$IPBHTML .= <<<EOF
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_name']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_name']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_description']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_description']}
						<div class='desctext'>{$this->lang->words['galcat_form_description_desc']}</div>
					</td>
				</tr>
EOF;

if( $category['category_id'] != $this->settings['gallery_members_album'] )
{
	$IPBHTML .= <<<EOF
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_parent']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_parent_id']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_type']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_type']}
						<div class='desctext' style='margin-top: 2px;'>{$this->lang->words['galcat_type_display_desc']}</div>
					</td>
				</tr>
EOF;
}
else
{
	$options	= array(
						array( 1, $this->lang->words['memgal_display_albums'] ),
						array( 2, $this->lang->words['memgal_display_images'] ),
						array( 3, $this->lang->words['memgal_display_members'] ),
						);

	$display	= $this->registry->output->formDropdown( 'gallery_memalbum_display', $options, $this->settings['gallery_memalbum_display'] );

	$IPBHTML .= <<<EOF
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_memgal_display']}</strong>
					</td>
					<td class='field_field'>
						{$display}
						<div class='desctext'>{$this->lang->words['galcat_memgal_display_desc']}</div>
					</td>
				</tr>
EOF;
}

$IPBHTML .= <<<EOF
	 		</table>
		</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	</div>
	<div id='step_options_content' style='display: none'>
		<div class='acp-box'>
			<h3>{$this->lang->words['galcat_catoptions']}</h3>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<div id='tab_Options_content'>
EOF;
}

$IPBHTML .= <<<EOF
			<table class='ipsTable double_pad'>
				<tr>
					<th colspan='2'>
						{$this->lang->words['galcat_ttl_sorting']}
					</th>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_sortkey']}</strong>
					</td>
					<td class='field_field'>
						<div id='category_type_images_wrap' style='display:none;'>{$form['category_sort_options__key']}</div>
						<div id='category_type_albums_wrap'>{$form['category_asort_options__key']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_sortorder']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_sort_options__dir']}
					</td>
				</tr>

				<tr>
					<th colspan='2'>
						{$this->lang->words['galcat_ttl_features']}
					</th>
				</tr>

				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_comments']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_allow_comments']}
						<div class='desctext'>{$this->lang->words['galcat_cascade_albums']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_rating']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_allow_rating']}
						<div class='desctext'>{$this->lang->words['galcat_cascade_albums']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_image_approval']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_approve_img']}
						<div class='desctext'>{$this->lang->words['galcat_form_image_approval_desc']}<br />{$this->lang->words['galcat_cascade_albums']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_comment_app']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_approve_com']}
						<div class='desctext'>{$this->lang->words['galcat_form_comment_app_desc']}<br />{$this->lang->words['galcat_cascade_albums']}</div>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_watermark']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_watermark']}
						<div class='desctext'>{$this->lang->words['galcat_cascade_albums']}</div>{$watermarkWarning}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['album_manage_show_after_forum']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_after_forum_id']}
					</td>
				</tr>

				<tr>
					<th colspan='2'>
						{$this->lang->words['galcat_ttl_tagging']}
					</th>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_tagging']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_can_tag']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_predefinedtags']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_preset_tags']}
						<div class="desctext">{$this->lang->words['galcat_form_pdt_desc']}</div>
					</td>
				</tr>
	 		</table>
		</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	</div>
	<div id='step_rules_content' style='display: none'>
		<div class='acp-box'>
			<h3>{$this->lang->words['galcat_rules']}</h3>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<div id='tab_Rules_content'>
EOF;
}

$IPBHTML .= <<<EOF
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_rules_title']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_rules__title']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['galcat_form_rules_text']}</strong>
					</td>
					<td class='field_field'>
						{$form['category_rules__desc']}
					</td>
				</tr>
	 		</table>
		</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	</div>
	<div id='step_permissions_content' style='display: none'>
		<div class='acp-box'>
			<h3>{$this->lang->words['galcat_permissions']}</h3>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<div id='tab_Permissions_content'>
EOF;
}

$IPBHTML .= <<<EOF
			{$form['_permissions']}
		</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	</div>
EOF;
}

$IPBHTML .= <<<EOF
	</div>
EOF;

if( $form['_formType'] == 'doadd' )
{
	$IPBHTML .= <<<EOF
	<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
		<input type='button' class='realbutton left' value='{$this->lang->words['wiz_prev']}' id='prev' />
		<input type='button' class='realbutton right' value='{$this->lang->words['wiz_next']}' id='next' />
		<p class='right' id='finish' style='display: none'>
			<input type='submit' class='realbutton' value='{$this->lang->words['category_save_button']}' />
		</p>
	</div>
	<script type='text/javascript'>
		jQ("#steps_bar").ipsWizard( { allowJumping: true, allowGoBack: false } );
	</script>
EOF;
}
else
{
$IPBHTML .= <<<EOF
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['category_save_button']}' class='button primary' />
	</div>
	<script type='text/javascript'>
		jQ("#tabstrip_categoryForm").ipsTabBar({ tabWrap: "#tabstrip_categoryForm_content", defaultTab: "tab_Basic" });
	</script>
EOF;
}

$IPBHTML .= <<<EOF
</div>
</form>
EOF;

return $IPBHTML;
}

/**
 * Delete album popup
 *
 * @param	array		$category	Category data
 * @param	array		$jumpList	Array of category options
 * @return	@e string	HTML
 */
public function ajaxDeleteDialog( $category, $jumpList ) {
$IPBHTML = "";
//--starthtml--//

$_moveTo	= $this->registry->output->formDropdown( 'move_to', $jumpList, 0 );

$IPBHTML .= <<<HTML
<form action="{$this->settings['base_url']}app=gallery&amp;module=categories&amp;section=manage&amp;do=delete&amp;category={$category['category_id']}" method="post">
	<input type='hidden' name='auth_key' value='{$this->member->form_hash}' />
	
	<h3>{$this->lang->words['delete_categories_modal']}</h3>
	<div class='pad center'>
		{$this->lang->words['category_delete_perm']}

		<div style="width:auto; display:inline-block; margin: 0 auto; text-align: left; clear: both;" class='pad'>
HTML;

		switch( $category['category_type'] )
		{
			case 1:
		$IPBHTML .= <<<HTML
			<input type="radio" name="doDelete" value="0" checked="checked" /> {$this->lang->words['category_move_contentsa']}
			{$_moveTo}
			<br />
			<input type="radio" name="doDelete" value="1" /> {$this->lang->words['category_delete_contents']}
HTML;
			break;

			case 2:
		$IPBHTML .= <<<HTML
			<input type="radio" name="doDelete" value="0" checked="checked" /> {$this->lang->words['category_move_contentsi']}
			{$_moveTo}
			<br />
			<input type="radio" name="doDelete" value="1" /> {$this->lang->words['category_delete_contents']}
HTML;
			break;
		}

		if( count( $this->registry->gallery->helper('categories')->getChildren( $category['category_id'] ) ) )
		{
			$_moveToToo	= $this->registry->output->formDropdown( 'move_cats_to', $jumpList, 0 );

			if( $category['category_type'] > 0 )
			{
				$IPBHTML .= "<br />";
			}

			$IPBHTML .= <<<HTML
			{$this->lang->words['category_move_othercats']} {$_moveToToo}
HTML;
		}

$IPBHTML .= <<<HTML
		</div>

	 	<input type='submit' class="button primary" value="{$this->lang->words['delete_category_go']}" />
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit moderator
 *
 * @param	array		$form		Form elements
 * @return	@e string	HTML
 */
public function moderatorForm( $form ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div class='information-box'>{$this->lang->words['galcatmods_apply_albums']}</div><br />

<div class='section_title'>
	<h2>{$this->lang->words['galmod_form_title']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.categories.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}do={$form['_code']}' method='post'>
	<input type='hidden' name='modid' value='{$this->request['modid']}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['galmod_h3_modsettings']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_type']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_type']}
					<div class='desctext'>{$this->lang->words['galmod_form_type_desc']}</div>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_gom']}</strong>
				</td>
				<td class='field_field'>
					<div id='mod_type_group'>{$form['_modgid']}</div>
					<div id='mod_type_member'>{$form['_modmid']}</div>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_categories']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_categories']}
				</td>
			</tr>

			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_canapprove']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_approve']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_canedit']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_edit']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_canhide']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_hide']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_candel']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_delete']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_canmove']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_move']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_cancover']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_set_cover_image']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_commapprove']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_approve_comments']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_commedit']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_edit_comments']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['galmod_form_commdelete']}</strong>
				</td>
				<td class='field_field'>
					{$form['mod_can_delete_comments']}
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['galmod_form_submit']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

return $IPBHTML;
}

}