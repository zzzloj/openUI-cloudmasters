<?php
/**
 * <pre>
 * Invision Power Services
 * Databases skin file
 * Last Updated: $Date: 2012-02-21 08:12:11 -0500 (Tue, 21 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd September 2009
 * @version		$Revision: 10329 $
 */
 
class cp_skin_databases
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
 * Add or edit a database
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Available templates
 * @param	array 		Available fields
 * @param	array 		Template categories
 * @return	string		HTML
 */
public function databaseForm( $type, $database, $templates, $fields, $categories )
{
$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Mix in our submitted config
//-----------------------------------------

if( is_array($_POST) AND count($_POST) )
{
	$database		= array_merge( $database, $_POST );
}

$name			= $this->registry->output->formInput( 'database_name', $database['database_name'] );
$key			= $this->registry->output->formInput( 'database_key', $database['database_key'] );
$desc			= $this->registry->output->formTextarea( 'database_description', $database['database_description'], 40, 5, '', '', 'normal' );

$templateL		= $this->registry->output->formDropdown( 'database_template_listing', $templates[2], $database['database_template_listing'], null, null, null, $categories );
$templateR		= $this->registry->output->formDropdown( 'database_template_display', $templates[3], $database['database_template_display'], null, null, null, $categories );
$templateC		= $this->registry->output->formDropdown( 'database_template_categories', $templates[1], $database['database_template_categories'], null, null, null, $categories );

$wikiEdit		= $this->registry->output->formYesNo( 'database_all_editable', $database['database_all_editable'] );
$commentBump	= $this->registry->output->formYesNo( 'database_comment_bump', $database['database_comment_bump'] );
$approve		= $this->registry->output->formYesNo( 'database_record_approve', $database['database_record_approve'] );
$approveComment	= $this->registry->output->formYesNo( 'database_comment_approve', $database['database_comment_approve'] );
$revisions		= $this->registry->output->formYesNo( 'database_revisions', $database['database_revisions'] );
$search			= $this->registry->output->formYesNo( 'database_search', $database['database_search'] );

$perpage		= $this->registry->output->formInput( 'database_field_perpage', $database['database_field_perpage'] ? $database['database_field_perpage'] : 25 );
$_orders		= array( array( 'asc', $this->lang->words['db_records__asc'] ), array( 'desc', $this->lang->words['db_records__desc'] ) );
$fieldorder		= $this->registry->output->formDropdown( 'database_field_direction', $_orders, $database['database_field_direction'] ? $database['database_field_direction'] : 'desc' );

$fieldTitle		= $this->registry->output->formDropdown( 'database_field_title', $fields, $database['database_field_title'] ? $database['database_field_title'] : 'primary_id_field' );
$fieldContent	= $this->registry->output->formDropdown( 'database_field_content', $fields, $database['database_field_content'] ? $database['database_field_content'] : 'primary_id_field' );
$fieldSort		= $this->registry->output->formDropdown( 'database_field_sort', $fields, $database['database_field_sort'] ? $database['database_field_sort'] : 'record_updated' );

$rssTotal		= $this->registry->output->formInput( 'database_rss', $database['database_rss'] );

$singlow		= $this->registry->output->formInput( 'database_lang_sl', $database['database_lang_sl'] ? $database['database_lang_sl'] : $this->lang->words['database_lang_sl'] );
$plurallow		= $this->registry->output->formInput( 'database_lang_pl', $database['database_lang_pl'] ? $database['database_lang_pl'] : $this->lang->words['database_lang_pl'] );
$singupper		= $this->registry->output->formInput( 'database_lang_su', $database['database_lang_su'] ? $database['database_lang_su'] : $this->lang->words['database_lang_su'] );
$pluralupper	= $this->registry->output->formInput( 'database_lang_pu', $database['database_lang_pu'] ? $database['database_lang_pu'] : $this->lang->words['database_lang_pu'] );

$tagsEnabled 	= $this->registry->output->formYesNo( "database_tags_enabled", $database['database_tags_enabled'] );
$tagsNoPrefix 	= $this->registry->output->formYesNo( "database_tags_noprefixes", $database['database_tags_noprefixes'] );
$tagsPredefined	= $this->registry->output->formTextarea( "database_tags_predefined", IPSText::stripslashes( $database['database_tags_predefined'] ), 40, 5, '', '', 'normal' );

//-----------------------------------------
// Forum posting options
//-----------------------------------------

$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );

$aff = new $classToLoad( $this->registry );
$aff->forumsInit();
$_forums = $aff->adForumsForumList(1);
		
$fsynch			= $this->registry->output->formYesNo( 'database_forum_record', $database['database_forum_record'] );
$fcomments		= $this->registry->output->formYesNo( 'database_forum_comments', $database['database_forum_comments'] );
$fdelete		= $this->registry->output->formYesNo( 'database_forum_delete', $database['database_forum_delete'] );
$fforum			= $this->registry->output->formDropdown( 'database_forum_forum', $_forums, $database['database_forum_forum'] );
$fprefix		= $this->registry->output->formInput( 'database_forum_prefix', $database['database_forum_prefix'] );
$fsuffix		= $this->registry->output->formInput( 'database_forum_suffix', $database['database_forum_suffix'] );

$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
$permissions	= new $classToLoad( ipsRegistry::instance() );
$matrix			= $permissions->adminPermMatrix( 'databases', $database );
		
$text			= $type == 'edit' ? $this->lang->words['editing_db_title'] . ' ' . $database['database_name'] : $this->lang->words['adding_db_title'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}&amp;id={$database['database_id']}' method='post' id='adminform' name='adform'>
	<div class='acp-box'>
		<h3>{$this->lang->words['database_title_configu']}</h3>
		<div id='tabstrip_database' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_1'>{$this->registry->getClass('class_localization')->words['tab__db_main']}</li>
				<li id='tab_2'>{$this->registry->getClass('class_localization')->words['tab__db_extra']}</li>
				<li id='tab_3'>{$this->registry->getClass('class_localization')->words['tab__db_forum']}</li>
				<li id='tab_4'>{$this->registry->getClass('class_localization')->words['tab__db_perms']}</li>
			</ul>
		</div>
		<div id='tabstrip_database_content' class='ipsTabBar_content'>

			<div id='tab_1_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_main_details']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__name']}</strong></td>
						<td class='field_field'>
							{$name}
							<div class='field_subfield'>{$this->lang->words['dbform__key']}: {$key}</div>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__desc']}</strong></td>
						<td class='field_field'>{$desc}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__cats']}</strong></td>
						<td class='field_field'>
							{$templateC}<br />
							<span class='desctext'>{$this->lang->words['dbform__cats_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__list']}</strong></td>
						<td class='field_field'>
							{$templateL}<br />
							<span class='desctext'>{$this->lang->words['dbform__list_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__disp']}</strong></td>
						<td class='field_field'>
							{$templateR}<br />
							<span class='desctext'>{$this->lang->words['dbform__disp_desc']}</span>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['dbform__langbits']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_sing_low']}</strong></td>
						<td>{$singlow}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_plur_low']}</strong></td>
						<td>{$plurallow}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_sing_upp']}</strong></td>
						<td>{$singupper}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_plur_upp']}</strong></td>
						<td>{$pluralupper}</td>
					</tr>
				</table>
			</div>
			
			<div id='tab_2_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__wikiedit']}</strong></td>
						<td class='field_field'>
							{$wikiEdit}<br />
							<span class='desctext'>{$this->lang->words['dbform__wikiedit_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__recapprove']}</strong></td>
						<td class='field_field'>
							{$approve}<br />
							<span class='desctext'>{$this->lang->words['dbform__recapprove_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__revise']}</strong></td>
						<td class='field_field'>
							{$revisions}<br />
							<span class='desctext'>{$this->lang->words['dbforum__revise_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__search']}</strong></td>
						<td class='field_field'>
							{$search}<br />
							<span class='desctext'>{$this->lang->words['dbform__search_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__acomments']}</strong></td>
						<td class='field_field'>
							{$approveComment}<br />
							<span class='desctext'>{$this->lang->words['dbform__acomments_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__bcomments']}</strong></td>
						<td class='field_field'>
							{$commentBump}<br />
							<span class='desctext'>{$this->lang->words['dbform__bcomments_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__rssc']}</strong></td>
						<td class='field_field'>
							{$rssTotal}<br />
							<span class='desctext'>{$this->lang->words['dbform__rssc_desc']}</span>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_db_tag_opts']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__tagsenabled']}</strong></td>
						<td class='field_field'>
							{$tagsEnabled}
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__tagsnoprefixes']}</strong></td>
						<td class='field_field'>
							{$tagsNoPrefix}
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__tagspredefined']}</strong></td>
						<td class='field_field'>
							{$tagsPredefined}<br />
							<span class='desctext'>{$this->lang->words['dbform__tagspredefined_info']}</span>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_db_field_opts']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fieldtitle']}</strong></td>
						<td class='field_field'>
							{$fieldTitle}<br />
							<span class='desctext'>{$this->lang->words['dbform__fieldtitle_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fieldcontent']}</strong></td>
						<td class='field_field'>
							{$fieldContent}<br />
							<span class='desctext'>{$this->lang->words['dbform__fieldcontent_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fieldsort']}</strong></td>
						<td class='field_field'>
							{$fieldSort}<br />
							<span class='desctext'>{$this->lang->words['dbform__fieldsort_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fieldorder']}</strong></td>
						<td class='field_field'>
							{$fieldorder}<br />
							<span class='desctext'>{$this->lang->words['dbform__fieldorder_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__perpage']}</strong></td>
						<td class='field_field'>
							{$perpage}<br />
							<span class='desctext'>{$this->lang->words['dbform__perpage_desc']}</span>
						</td>
					</tr>
				</table>
			</div>
			
			<div id='tab_3_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fsynch']}</strong></td>
						<td class='field_field'>
							{$fsynch}<br />
							<span class='desctext'>{$this->lang->words['dbform__fsynch_desc']}</span>
						</td>
					</tr>
					<!-- We have to manually set BG color as the CSS alt-row stuff doesn't work so well embedded inside tbody -->
					<tbody id='forum_opts'>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fcomments']}</strong></td>
							<td class='field_field'>
								{$fcomments}<br />
								<span class='desctext'>{$this->lang->words['dbform__fcomments_desc']}</span>
								<div id='comment_change_warning' class='information-box'>{$this->lang->words['changing_forumcommentopt']}</div>
							</td>
						</tr>
						<tr>
							<td class='field_title' style='background-color:#F5F8FA;'><strong class='title'>{$this->lang->words['dbform__fforum']}</strong></td>
							<td class='field_field' style='background-color:#F5F8FA;'>
								{$fforum}<br />
								<span class='desctext'>{$this->lang->words['dbform__fforum_desc']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fprefix']}</strong></td>
							<td class='field_field'>
								{$fprefix}<br />
								<span class='desctext'>{$this->lang->words['dbform__fprefix_desc']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title' style='background-color:#F5F8FA;'><strong class='title'>{$this->lang->words['dbform__fsuffix']}</strong></td>
							<td class='field_field' style='background-color:#F5F8FA;'>
								{$fsuffix}<br />
								<span class='desctext'>{$this->lang->words['dbform__fsuffix_desc']}</span>
							</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fdelete']}</strong></td>
							<td class='field_field'>
								{$fdelete}<br />
								<span class='desctext'>{$this->lang->words['dbform__fdelete_desc']}</span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			
			<div id='tab_4_content'>
				{$matrix}
			</div>
			
		</div>
		
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
			<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=databases&amp;databases';" value='{$this->lang->words['button__cancel']}' />
		</div>
	
	</div>	
</form>

<script type='text/javascript'>
	
	jQ("#tabstrip_database").ipsTabBar({ tabWrap: "#tabstrip_database_content" });

	ipb.lang['changejstitle']	= '{$this->lang->words['changejstitle']}';
	ipb.lang['changejsinit']	= '{$this->lang->words['changejsinit']}';
	
	var obj = new acp.liveedit( 
					$('database_name'),
					$('database_key'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=databases&do=checkKey"
					}
			  );

</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of created databases
 *
 * @access	public
 * @param	array 		Databases
 * @return	string		HTML
 */
public function databases( $databases )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['databases_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['databases_helpblurb']}
		</div>
	</div>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=add' title='{$this->lang->words['add_db_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/table_add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_database_button']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box'>
	<h3>{$this->lang->words['all_created_dbs_title']}</h3>
	<table class='ipsTable'>
		<tr>
			<th style='width: 48%'>{$this->lang->words['db_name_th']}</th>
			<th style='width: 10%'>{$this->lang->words['db_records_th']}</th>
			<th style='width: 10%'>{$this->lang->words['db_fields_th']}</th>
			<th style='width: 10%'>{$this->lang->words['db_cats_th']}</th>
			<th style='width: 10%'>{$this->lang->words['db_mods_th']}</th>
			<th style='width: 10%'>{$this->lang->words['db_open_th']}</th>
			<th class='col_buttons' style='width: 2%'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $databases ) )
{
	$IPBHTML .= <<<HTML
	<tr>
		<td colspan='7' class='no_messages'>
			{$this->lang->words['no_dbs_created_yet']} <a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=add' class='mini_button'>{$this->lang->words['create_db_now']}</a>
		</td>
	</tr>
HTML;
}
else
{
	foreach( $databases as $db )
	{
		$name		= "<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=edit&amp;id={$db['database_id']}'><strong>{$db['database_name']}</strong></a>";
		$desc		= $db['database_description'] ? "<div class='desctext'>{$db['database_description']}</div>" : '';
		$_open		= trim( $db['perm_view'], ' ,' ) ? 'tick.png' : 'cross.png';
		
		$IPBHTML .= <<<HTML
	<tr class='ipsControlRow'>
		<td><span class='larger_text'>{$name}</span>{$desc}</td>
		<td><a href='{$this->settings['base_url']}module=databases&amp;section=records&amp;id={$db['database_id']}'>{$db['database_record_count']}</a></td>
		<td><a href='{$this->settings['base_url']}module=databases&amp;section=fields&amp;id={$db['database_id']}'>{$db['database_field_count']}</a></td>
		<td><a href='{$this->settings['base_url']}module=databases&amp;section=categories&amp;id={$db['database_id']}'>{$db['_categories']}</a></td>
		<td><a href='{$this->settings['base_url']}module=databases&amp;section=moderators&amp;id={$db['database_id']}'>{$db['_moderators']}</a></td>
		<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_open}' alt='{$this->lang->words['icon']}' /></td>
		<td>
			<ul class='ipsControlStrip'>
				<li class='i_edit'>
					<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=edit&amp;id={$db['database_id']}'>{$this->lang->words['edit_database_menu']}</a>
				</li>
				<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$db['database_id']}'>
					<a href='#'>{$this->lang->words['folder_options_alt']}</a>
				</li>
			</ul>
			<ul class='acp-menu' id='menu_folder{$db['database_id']}_menucontent'>
				<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=delete&amp;id={$db['database_id']}' );">{$this->lang->words['delete_database_menu']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=visit&amp;id={$db['database_id']}' title='{$this->lang->words['db_takeme_to_pagedesc']}' target='_blank'>{$this->lang->words['db_takeme_to_page']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;id={$db['database_id']}'>{$this->lang->words['fields_database_menu']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;id={$db['database_id']}'>{$this->lang->words['records_database_menu']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;id={$db['database_id']}'>{$this->lang->words['cats_database_menu']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;id={$db['database_id']}'>{$this->lang->words['manage_moderators_menu']}</a></li>
			</ul>
		</td>
	</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Shared media bbcode tag
 *
 * @param	array 		Field data
 * @param	string		Default field value
 * @param	string		Parsed field value
 * @return	string		HTML
 */
public function sharedMediaField( $field, $default, $display )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<link rel='stylesheet' href='{$this->settings['skin_app_url']}css/sharedmedia.css' />
<input class='button primary' type="button" id="field_{$field['field_id']}_button" value='{$this->lang->words['sm_button_add']}' /> &nbsp;
<input class='button secondary' type="button" id="field_{$field['field_id']}_reset" value='{$this->lang->words['sm_button_clear']}' /> 
<textarea name='field_{$field['field_id']}' id='field_{$field['field_id']}' style='display:none;'>{$default}</textarea>

<div id='field_{$field['field_id']}_preview' style='margin-top: 10px;'>
	{$display}
</div>

<script type='text/javascript'>
var defaultField{$field['field_id']}Value	= "{$this->lang->words['no_sm_field_selected']}";

$('field_{$field['field_id']}_button').observe( 'click', function(e){
	Event.stop(e);
	
	if ( ! Object.isUndefined(mediaField.popup ) )
	{
		mediaField.popup.kill();
	}

	var url = ipb.vars['front_url'] + "app=core&module=ajax&section=media&secure_key=" + ipb.vars['secure_hash'];

	new Ajax.Request(	url.replace(/&amp;/g, '&'),
						{
							method:		'get',
							evalJSON:	'force',
							onSuccess:	function(t)
							{
								mediaField.popup	= new ipb.Popup( 'my_media_inline_{$field['field_id']}', {	type: 'pane',
																										initial: t.responseJSON['html'].replace( /CKEDITOR\.plugins\.ipsmedia/g, "mediaField" ),
																										hideAtStart: false,
																										hideClose: true,
																										defer: false,
																										modal: true,
																										w: '800px',
																										h: '410'
																									 } );
							}
						});
	
	return false;
});

$('field_{$field['field_id']}_reset').observe( 'click', function(e){
	Event.stop(e);
	
	$('field_{$field['field_id']}').update( '' );
	$('field_{$field['field_id']}_preview').update( defaultField{$field['field_id']}Value );
	
	return false;
});


mediaField = {
	init: function()
	{
		
	},
	
	loadTab: function( app, plugin )
	{
		$$("#mymedia_tabs li").each( function(elem) {
			$(elem).removeClassName('active');
		});

		$(app + '_' + plugin).addClassName('active');
		$('mymedia_toolbar').show();
		
		$('sharedmedia_search_app').value		= app;
		$('sharedmedia_search_plugin').value	= plugin;
		
		var searchstring	= $('sharedmedia_search').value;
		
		if( searchstring == ipb.vars['sm_init_value'] )
		{
			searchstring	= '';
		}

		var url				= ipb.vars['front_url'] + "app=core&module=ajax&section=media&do=loadtab&tabapp=" + app + "&tabplugin=" + plugin;

		new Ajax.Request(	url.replace(/&amp;/g, '&'),
							{
								method:		'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash'],
									search:		searchstring
								},
								onSuccess:	function(t)
								{
									$('mymedia_content').update( t.responseText.replace( /CKEDITOR\.plugins\.ipsmedia/g, "mediaField" ) );
								}
							});

		return false;
	},
	
	insert: function( insertCode )
	{
		if( \$F('field_{$field['field_id']}') )
		{
			$('field_{$field['field_id']}').update( \$F('field_{$field['field_id']}') + ' ' + '[sharedmedia=' + insertCode + ']' );
		}
		else
		{
			$('field_{$field['field_id']}').update( '[sharedmedia=' + insertCode + ']' )
		}
		
		$('mymedia_inserted').show().fade({duration: 0.3, delay: 2});
		
		new Ajax.Request(	ipb.vars['front_url'] + "app=ccs&module=ajax&section=media&do=preview".replace(/&amp;/g, '&'),
							{
								method:		'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash'],
									value:		\$F('field_{$field['field_id']}')
								},
								onSuccess:	function(t)
								{
									$('field_{$field['field_id']}_preview').update( t.responseText );
								}
							});
							
		return false;
	},
	
	search: function()
	{
		var searchstring	= $('sharedmedia_search').value;
		
		var url				= ipb.vars['front_url'] + "app=core&module=ajax&section=media&do=loadtab&tabapp=" + $('sharedmedia_search_app').value + "&tabplugin=" + $('sharedmedia_search_plugin').value;

		new Ajax.Request(	url.replace(/&amp;/g, '&'),
							{
								method:		'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash'],
									search:		searchstring
								},
								onSuccess:	function(t)
								{
									$('mymedia_content').update( t.responseText.replace( /CKEDITOR\.plugins\.ipsmedia/g, "mediaField" ) );
								}
							});

		return false;
	},
	
	searchinit: function()
	{
		$('sharedmedia_submit').observe( 'click', function(e) {
			Event.stop(e);
			
			mediaField.search();
			
			return false;
		});

		$('sharedmedia_reset').observe( 'click', function(e) {
			Event.stop(e);
			
			$('sharedmedia_search').value	= '';
			mediaField.search();
			
			$('sharedmedia_search').addClassName('inactive').value	= ipb.vars['sm_init_value'];
			
			return false;
		});
		
		$('sharedmedia_search').observe( 'focus', function(e) {
			if( $('sharedmedia_search').value == ipb.vars['sm_init_value'] )
			{
				$('sharedmedia_search').removeClassName('inactive').value	= '';
			}
		});
	}
}

mediaField.init();
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

}