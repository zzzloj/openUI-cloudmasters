<?php
/**
 * <pre>
 * Invision Power Services
 * Database categories skin file
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
 
class cp_skin_database_categories
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
 * Add or edit a category
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Category data
 * @param	array 		Categories
 * @param	string		Select options
 * @return	string		HTML
 */
public function categoryForm( $type, $database, $category, $categories, $selectOpts='' )
{
$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Mix in our submitted config
//-----------------------------------------

if( is_array($_POST) AND count($_POST) )
{
	$category		= array_merge( $category, $_POST );
}

$name			= $this->registry->output->formInput( 'category_name', $category['category_name'] );
$pageTitle		= $this->registry->output->formInput( 'category_page_title', $category['category_page_title'] );
$_cptb			= $category['category_page_title'] ? "" : " checked='checked'";
$furl			= $this->registry->output->formInput( 'category_furl_name', $category['category_furl_name'] );
$desc			= $this->registry->output->formTextarea( 'category_description', $category['category_description'], 40, 5, '', '', 'normal' );
$show			= $this->registry->output->formYesNo( 'category_show_records', $category['category_show_records'] );

$metak			= $this->registry->output->formTextarea( 'category_meta_keywords', $category['category_meta_keywords'], 40, 5, '', '', 'normal' );
$metad			= $this->registry->output->formTextarea( 'category_meta_description', $category['category_meta_description'], 40, 5, '', '', 'normal' );

$text			= $type == 'edit' ? $this->lang->words['editing_cat_title'] . ' ' . $category['category_name'] : $this->lang->words['adding_cat_title'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$tagsOverride	= $this->registry->output->formYesNo( 'category_tags_override', intval($category['category_tags_override']), 'category_tags_override', array( 'yes' => "onclick=\"\$('ccs-cat-tags').show();\"", 'no' => "onclick=\"\$('ccs-cat-tags').hide();\"" ) );
$tagsEnabled 	= $this->registry->output->formYesNo( "category_tags_enabled", $category['category_tags_enabled'] );
$tagsNoPrefix 	= $this->registry->output->formYesNo( "category_tags_noprefixes", $category['category_tags_noprefixes'] );
$tagsPredefined	= $this->registry->output->formTextarea( "category_tags_predefined", IPSText::stripslashes( $category['category_tags_predefined'] ), 40, 5, '', '', 'normal' );

//-----------------------------------------
// Forum posting options
//-----------------------------------------

$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );

$aff = new $classToLoad( $this->registry );
$aff->forumsInit();
$_forums = $aff->adForumsForumList(1);

$foverride		= $this->registry->output->formYesNo( 'category_forum_override', intval($category['category_forum_override']), 'category_forum_override', array( 'yes' => "onclick=\"\$('ccs-cat-forum').show();\"", 'no' => "onclick=\"\$('ccs-cat-forum').hide();\"" ) );
	
$fsynch			= $this->registry->output->formYesNo( 'category_forum_record', $category['category_forum_record'] );
$fcomments		= $this->registry->output->formYesNo( 'category_forum_comments', $category['category_forum_comments'] );
$fdelete		= $this->registry->output->formYesNo( 'category_forum_delete', $category['category_forum_delete'] );
$fforum			= $this->registry->output->formDropdown( 'category_forum_forum', $_forums, $category['category_forum_forum'] );
$fprefix		= $this->registry->output->formInput( 'category_forum_prefix', $category['category_forum_prefix'] );
$fsuffix		= $this->registry->output->formInput( 'category_forum_suffix', $category['category_forum_suffix'] );

//-----------------------------------------
// Permissions
//-----------------------------------------

$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
$permissions	= new $classToLoad( ipsRegistry::instance() );
$matrix			= $permissions->adminPermMatrix( 'categories', $category );

$override		= $this->registry->output->formYesNo( 'category_has_perms', intval($category['category_has_perms']), 'category_has_perms', array( 'yes' => "onclick=\"\$('ccs-perm-matrix').show();\"", 'no' => "onclick=\"\$('ccs-perm-matrix').hide();\"" ) );

$rssTotal		= $this->registry->output->formInput( 'category_rss', $category['category_rss'] );
$rssExclude		= $this->registry->output->formYesNo( 'category_rss_exclude', $category['category_rss_exclude'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}&amp;id={$database['database_id']}' method='post' id='adminform' name='adform'>
	<input type='hidden' name='category' value='{$category['category_id']}' />
	<div class='acp-box'>
		<h3>{$text}</h3>
		<div id='tabstrip_database_category' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_1'>{$this->registry->getClass('class_localization')->words['tab__cat_main']}</li>
				<li id='tab_2'>{$this->registry->getClass('class_localization')->words['tab__cat_forum']}</li>
				<li id='tab_3'>{$this->registry->getClass('class_localization')->words['tab__cat_perms']}</li>
			</ul>
		</div>
		<div id='tabstrip_database_category_content' class='ipsTabBar_content'>
			<div id='tab_1_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__name']}</strong></td>
						<td class='field_field'>
							{$name} &nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' id='page_name_as_title'{$_cptb} value='1' name='page_name_as_title' autocomplete='off' /> {$this->lang->words['catnameastitle']}
							<div class='field_subfield'>{$this->lang->words['catform__furl']}: {$furl}</div>
							<div id='page_title'>
								{$this->lang->words['page_title_ft']}: {$pageTitle}
								<div class='desctext'>{$this->lang->words['catnametitlehelptext']}</div>
							</div>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__desc']}</strong></td>
						<td class='field_field'>{$desc}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__parent']}</strong></td>
						<td class='field_field'>
							<select name='category_parent_id' id='category_parent_id'>
								<option value='0'>{$this->lang->words['select_cat_one']}</option>
								{$selectOpts}
							</select><br />
							<span class='desctext'>{$this->lang->words['catform__p_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__records']}</strong></td>
						<td class='field_field'>
							{$show}
							<div class='desctext'>{$this->lang->words['catform__records_desc']}</div>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__rssccat']}</strong></td>
						<td class='field_field'>{$rssTotal}<br /><span class='desctext'>{$this->lang->words['dbform__rsscat_desc']}</span></td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__rssecat']}</td>
						<td class='field_field'>{$rssExclude}<br /><span class='desctext'>{$this->lang->words['dbform__rssecat_desc']}</span></td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_keywords']}</strong></td>
						<td class='field_field'>{$metak}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_description']}</strong></td>
						<td class='field_field'>{$metad}</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_db_tag_opts']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform_cat_tags_over']}</strong></td>
						<td class='field_field'>{$tagsOverride}</td>
					</tr>
				</table>
				<table class='ipsTable double_pad' id='ccs-cat-tags'>
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
							<span class='desctext'>{$this->lang->words['dbcform__tagspredefined_info']}</span>
						</td>
					</tr>
				</table>
			</div>
	
			<div id='tab_2_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__foverride']}</strong></td>
						<td class='field_field'>{$foverride}</td>
					</tr>
				</table>
				<table class='ipsTable double_pad' id='ccs-cat-forum'>
					<tr>
						<th colspan='2'>{$this->lang->words['db_forum_options']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fsynch']}</strong></td>
						<td class='field_field'>
							{$fsynch}<br />
							<span class='desctext'>{$this->lang->words['dbform__fsynch_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fcomments']}</strong></td>
						<td class='field_field'>
							{$fcomments}<br />
							<span class='desctext'>{$this->lang->words['dbform__fcomments_desc']}</span>
							<div id='comment_change_warning' class='information-box'>{$this->lang->words['changing_forumcommentopt']}</div>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fforum']}</strong></td>
						<td class='field_field'>
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
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__fsuffix']}</strong></td>
						<td class='field_field'>
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
				</table>
			</div>
	
			<div id='tab_3_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__override']}</strong></td>
						<td class='field_field'>{$override}</td>
					</tr>
				</table>
				<div style='padding-top:10px' id='ccs-perm-matrix'>
					{$matrix}
				</div>
			</div>
			
		</div>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
			<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;id={$database['database_id']}';" value='{$this->lang->words['button__cancel']}' />
		</div>
	</div>	
</form>
<script type="text/javascript">
document.observe("dom:loaded",function() 
{
	jQ("#tabstrip_database_category").ipsTabBar({ tabWrap: "#tabstrip_database_category_content" });

	if( !$('category_has_perms_yes').checked )
	{
		$('ccs-perm-matrix').hide();
	}

	if( !$('category_forum_override_yes').checked )
	{
		$('ccs-cat-forum').hide();
	}

	if( !$('category_tags_override_yes').checked )
	{
		$('ccs-cat-tags').hide();
	}
});
var noShowAlert	= true;

	ipb.lang['changejstitle']	= '{$this->lang->words['changejstitle']}';
	ipb.lang['changejsinit']	= '{$this->lang->words['changejsinit']}';
	
	var obj = new acp.liveedit( 
					$('category_name'),
					$('category_furl_name'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=databases&do=checkCategoryKey&database={$database['database_id']}",
						ajaxCallback: getParentId
					}
			  );

	function getParentId( url )
	{
		return url + "&category_parent_id=" + \$F('category_parent_id');
	}
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to move records when deleting a cat
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Category
 * @param	array 		Categories
 * @param	string		Select menu
 * @return	string		HTML
 */
public function categoryDeleteForm( $database, $category, $categories=array(), $selectOpts='' )
{
$IPBHTML = "";
//--endhtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['confirm_cat_delete']}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=delete' method='post'>
<input type='hidden' name='category' value='{$category['category_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['confirm_to_ccontinue_d']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['cat_to_mvoe_to']}</strong></td>
			<td class='field_field'><select name='move_to' id='move_to'>{$selectOpts}</select></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__notd']}' class="button primary" />
		<input type='button' value='{$this->lang->words['button__canceld']}' class="realbutton redbutton" onclick='return acp.redirect("{$this->settings['base_url']}{$this->form_code}");' />
	</div>
</div>	
</form>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of categories within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Categories
 * @param	array 		Parent data (if viewing subcats)
 * @return	string		HTML
 */
public function categories( $database, $categories, $parent=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbcategoriess_title']} {$database['database_name']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=add&amp;id={$database['database_id']}' title='{$this->lang->words['add_cat_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_category_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=recache&amp;id={$database['database_id']}' title='{$this->lang->words['recache_cats_button']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['recache_cats_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=fields&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_fields']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/layout_content.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_fields']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_records']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/table_add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_records']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=moderators&amp;id={$database['database_id']}' title='{$this->lang->words['switch_manage_mods']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['switch_manage_mods']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

if( $this->request['parent'] )
{
	$this->lang->words['all_created_cats_t']	= $this->lang->words['all_created_cats_t1'] . ' ' . $parent['category_name'];
}

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='acp-box adv_controls'>
	<h3>{$this->lang->words['all_created_cats_t']}</h3>
	<table class='ipsTable' id='categories_list'>
		<tr>
			<th class='col_drag'>&nbsp;</th>
			<th>{$this->lang->words['cat_name_th']}</th>
			<th>{$this->lang->words['cat_records_th']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $categories ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' class='no_messages'>
				{$this->lang->words['no_cats_created_yet']} <a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=add&amp;id={$database['database_id']}' class='mini_button'>{$this->lang->words['create_cat_now']}</a>
			</td>
		</tr>
HTML;
}
else
{
	foreach( $categories as $category )
	{
		$name	= "<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=edit&amp;id={$database['database_id']}&amp;category={$category['category_id']}'><strong>{$category['category_name']}</strong></a>";
		
		if( $category['category_description'] )
		{
			$name	.= "<div class='desctext'>{$category['category_description']}</div>";
		}
		
		$children	= '';
		
		if( count($category['children'] ) )
		{
			$name	.= "<fieldset class='desctext' style='margin-top: 20px;'><legend>{$this->lang->words['subcat_prefix']}</legend> ";
			$_c		= array();
			
			foreach( $category['children'] as $child )
			{
				$_c[]	= "<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=list&amp;id={$database['database_id']}&amp;parent={$child['category_parent_id']}'>{$child['category_name']}</a>";
			}
			
			$name	.= implode( ', ', $_c );
			$name	.= "</fieldset>";
		}

		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='category_{$category['category_id']}'>
			<td class='col_drag'><span class='draghandle'>&nbsp;</span></td>
			<td style='width: 72%'><span class='larger_text'>{$name}</span></td>
			<td style='width: 20%'>{$category['category_records']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a class='edit' href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=edit&amp;id={$database['database_id']}&amp;category={$category['category_id']}' title='{$this->lang->words['edit_cat_menu']}'>{$this->lang->words['edit_cat_menu']}</a>
					</li>
					<li class='i_refresh'>
						<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=recache&amp;id={$database['database_id']}&amp;category={$category['category_id']}' title='{$this->lang->words['recache_cat_menu']}'>{$this->lang->words['recache_cat_menu']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=categories&amp;do=delete&amp;id={$database['database_id']}&amp;category={$category['category_id']}' );" title='{$this->lang->words['delete_cat_menu']}'>{$this->lang->words['delete_cat_menu']}</a>
					</li>
				</ul>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>

<script type='text/javascript'>
	jQ("#categories_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

HTML;
//--endhtml--//
return $IPBHTML;
}

}