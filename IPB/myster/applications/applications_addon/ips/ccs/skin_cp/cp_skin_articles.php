<?php
/**
 * <pre>
 * Invision Power Services
 * Article manager templates
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		19th January 2010
 * @version		$Revision: 10375 $
 */
 
class cp_skin_articles
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

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post' id='adminform' name='adform'>
	<input type='hidden' name='category' value='{$category['category_id']}' />
	<div class='acp-box'>
		<h3>{$text}</h3>
		<div id='tabstrip_article_category' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_1'>{$this->registry->getClass('class_localization')->words['tab__cat_main']}</li>
				<li id='tab_2'>{$this->registry->getClass('class_localization')->words['tab__cat_forum']}</li>
				<li id='tab_3'>{$this->registry->getClass('class_localization')->words['tab__cat_perms']}</li>
			</ul>
		</div>
		<div id='tabstrip_article_category_content' class='ipsTabBar_content'>
			<div id='tab_1_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_cat_details']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__name']}</strong></td>
						<td class='field_field'>
							{$name} &nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' id='page_name_as_title'{$_cptb} value='1' name='page_name_as_title' /> {$this->lang->words['catnameastitle']}
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
					<!--CAT_TEMPLATE-->
					<tr>
						<th colspan='2'>{$this->lang->words['catform_rss_opts']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__rssccat']}</strong></td>
						<td class='field_field'>
							{$rssTotal}<br />
							<span class='desctext'>{$this->lang->words['dbform__rssccat_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__rssecat']}</strong></td>
						<td class='field_field'>
							{$rssExclude}<br />
							<span class='desctext'>{$this->lang->words['dbform__arssecat_desc']}</span>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['catform_meta_tags']}</th>
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
						<td class='field_title'><strong class='title'>{$this->lang->words['catform__aoverride']}</strong></td>
						<td class='field_field'>{$override}</td>
					</tr>
				</table>
				<div style='padding-top:10px' id='ccs-perm-matrix'>
					{$matrix}
				</div>
			</div>
		</div>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button" />
			<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=articles&amp;section=categories&amp;do=list';" value='{$this->lang->words['button__cancel']}' />
		</div>
	</div>
</form>

<script type='text/javascript'>

	jQ("#tabstrip_article_category").ipsTabBar({ tabWrap: "#tabstrip_article_category_content" });

	$$('.article_expander').each( function(elem){
		var trigger = $( elem ).down('.trigger');
		var pane = $( elem ).down('.article_expander_pane');
		
		if( trigger && pane ){
			$( trigger ).observe('click', function(e){
				if( $( pane ).visible() ){
					new Effect.BlindUp( $( pane ), { duration: 0.3 } );
					$( elem ).removeClassName('open');
				} else {
					new Effect.BlindDown( $( pane ), { duration: 0.3 } );
					$( elem ).addClassName('open');
				}
			});
		}
	});

	document.observe("dom:loaded",function() 
	{
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
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=databases&do=checkCategoryKey&database={$database['database_id']}"
					}
			  );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Get category frontpage template option
 *
 * @access	public
 * @param	array 		Category data
 * @param	array		Templates
 * @param	array 		Containers
 * @return	string		HTML
 */
public function categoryTemplate( $category, $templates, $containers )
{
$IPBHTML = "";
//--starthtml--//

$template		= $this->registry->output->formDropdown( 'category_template', $templates, $category['category_template'], null, null, null, $containers );

$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['catform__template']}</strong></td>
				<td class='field_field'>
					{$template}<br />
					<span class='desctext'>{$this->lang->words['catform__template_desc']}</span>
				</td>
			</tr>
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
	<h2>{$this->lang->words['articles_cats_manage']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_cat_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['add_category_button']}</a></li>
			<li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recache' title='{$this->lang->words['recache_cats_button']}'> <img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='{$this->lang->words['icon']}' />{$this->lang->words['recache_cats_button']}</a></li>
		</ul>
	</div>
</div>
HTML;

if( $this->request['parent'] )
{
	$this->lang->words['all_created_cats_a']	= $this->lang->words['all_created_cats_t1'] . ' ' . $parent['category_name'];
}

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='acp-box'>
	<h3>{$this->lang->words['all_created_cats_a']}</h3>
	<table class='ipsTable' id='category_list'>
		<tr>
			<th style='width: 3%'>&nbsp;</th>
			<th style='width: 42%'>{$this->lang->words['cat_name_th']}</th>
			<th style='width: 12%' class='short'>{$this->lang->words['cat_records_th']}</th>
			<th style='width: 33%'>{$this->lang->words['cat_th_fp_t']}</th>
			<th style='width: 10%'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $categories ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='5' class='no_messages'>{$this->lang->words['no_acats_created_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['create_cat_now']}</a></td>
		</tr>
HTML;
}
else
{
	foreach( $categories as $category )
	{
		$name	= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;category={$category['category_id']}'><strong>{$category['category_name']}</strong></a>";
		
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
				$_c[]	= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=list&amp;parent={$child['category_parent_id']}'>{$child['category_name']}</a>";
			}
			
			$name	.= implode( ', ', $_c );
			$name	.= "</fieldset>";
		}

		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='category_{$category['category_id']}'>
			<td class='col_drag'><span class='draghandle'>&nbsp;</span></td>
			<td><span class='larger_text'>{$name}</span></td>
			<td class='short'>{$category['category_records']}</td>
			<td><span id='tname_for_{$category['category_id']}'><!--CAT_TEMPLATE_{$category['category_id']}--></span></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;category={$category['category_id']}' title='{$this->lang->words['edit_cat_menu']}'> {$this->lang->words['edit_cat_menu']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;category={$category['category_id']}' );"> {$this->lang->words['delete_cat_menu']}</a>
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

<script type="text/javascript">
jQ("#category_list").ipsSortable( 'table', { 
	url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
});
</script>

HTML;
//--endhtml--//
return $IPBHTML;
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
$templateL		= $this->registry->output->formDropdown( 'database_template_listing', $templates[6], $database['database_template_listing'], null, null, null, $categories );
$templateR		= $this->registry->output->formDropdown( 'database_template_display', $templates[5], $database['database_template_display'], null, null, null, $categories );
$templateC		= $this->registry->output->formDropdown( 'database_template_categories', $templates[7], $database['database_template_categories'], null, null, null, $categories );
$commentBump	= $this->registry->output->formYesNo( 'database_comment_bump', $database['database_comment_bump'] );
$approve		= $this->registry->output->formYesNo( 'database_record_approve', $database['database_record_approve'] );
$approveComment	= $this->registry->output->formYesNo( 'database_comment_approve', $database['database_comment_approve'] );
$revisions		= $this->registry->output->formYesNo( 'database_revisions', $database['database_revisions'] );
$search			= $this->registry->output->formYesNo( 'database_search', $database['database_search'] );
$perpage		= $this->registry->output->formInput( 'database_field_perpage', $database['database_field_perpage'] ? $database['database_field_perpage'] : 25 );
$_orders		= array( array( 'asc', $this->lang->words['db_records__asc'] ), array( 'desc', $this->lang->words['db_records__desc'] ) );
$fieldorder		= $this->registry->output->formDropdown( 'database_field_direction', $_orders, $database['database_field_direction'] ? $database['database_field_direction'] : 'desc' );
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
$matrix			= str_replace( "Records", $this->lang->words['matrix__articles'], $matrix );
$matrix			= str_replace( "Database", $this->lang->words['matrix__articles'], $matrix );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['article_configure_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['articlemanager_helpblurb']}
		</div>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doEdit' method='post' id='adminform' name='adform'>
	<input type='hidden' name='id' value='{$database['database_id']}' />
	<!-- Here we hard code some fields, to simplify the interface -->
	<input type='hidden' name='database_description' value="{$database['database_description']}" />
	<input type='hidden' name='database_key' value="{$database['database_key']}" />
	<input type='hidden' name='database_all_editable' value='0' />
	<input type='hidden' name='database_field_title' value="{$database['database_field_title']}" />
	<input type='hidden' name='database_field_content' value="{$database['database_field_content']}" />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['article_configure_title']}</h3>
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
						<th colspan='2'>{$this->lang->words['edit_article_main_details']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbaform__name']}</strong></td>
						<td class='field_field'>{$name}</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['dbform_templates_ae']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__cats']}</strong></td>
						<td class='field_field'>
							{$templateC}<br />
							<span class='desctext'>{$this->lang->words['dbform__acats_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__list']}</strong></td>
						<td class='field_field'>
							{$templateL}<br />
							<span class='desctext'>{$this->lang->words['dbform__alist_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__adisp']}</strong></td>
						<td class='field_field'>
							{$templateR}<br />
							<span class='desctext'>{$this->lang->words['dbform__adisp_desc']}</span>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['dbform__langbits']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_sing_low']}</strong></td>
						<td class='field_field'>{$singlow}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_plur_low']}</strong></td>
						<td class='field_field'>{$plurallow}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_sing_upp']}</strong></td>
						<td class='field_field'>{$singupper}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['langbits_plur_upp']}</strong></td>
						<td class='field_field'>{$pluralupper}</td>
					</tr>
				</table>
			</div>
	
			<div id='tab_2_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>{$this->lang->words['edit_article_extra_options']}</th>
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
							<span class='desctext'>{$this->lang->words['dbforum__arevise_desc']}</span>
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
						<td class='field_title'><strong class='title'>{$this->lang->words['dbaform__bcomments']}</strong></td>
						<td class='field_field'>
							{$commentBump}<br />
							<span class='desctext'>{$this->lang->words['dbaform__bcomments_desc']}</span>
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
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__afieldsort']}</strong></td>
						<td class='field_field'>
							{$fieldSort}<br />
							<span class='desctext'>{$this->lang->words['dbform__afieldsort_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__afieldorder']}</strong></td>
						<td class='field_field'>
							{$fieldorder}<br />
							<span class='desctext'>{$this->lang->words['dbform__afieldorder_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__aperpage']}</strong></td>
						<td class='field_field'>
							{$perpage}<br />
							<span class='desctext'>{$this->lang->words['dbform__aperpage_desc']}</span>
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
		</div>
	</div>	
</form>

<script type='text/javascript'>
	
	jQ("#tabstrip_database").ipsTabBar({ tabWrap: "#tabstrip_database_content" });
	
	$$('.article_expander').each( function(elem){
		var trigger = $( elem ).down('.trigger');
		var pane = $( elem ).down('.article_expander_pane');
		
		if( trigger && pane ){
			$( trigger ).observe('click', function(e){
				if( $( pane ).visible() ){
					new Effect.BlindUp( $( pane ), { duration: 0.3 } );
					$( elem ).removeClassName('open');
				} else {
					new Effect.BlindDown( $( pane ), { duration: 0.3 } );
					$( elem ).addClassName('open');
				}
			});
		}
	});
	
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add or edit a field
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Field data
 * @param	array 		Field types
 * @param	array 		Validators
 * @return	string		HTML
 */
public function fieldForm( $type, $database, $field, $types, $validators )
{
$IPBHTML = "";
//--starthtml--//

$name			= $this->registry->output->formInput( 'field_name', $field['field_name'], 'field_name' );
$key			= $this->registry->output->formInput( 'field_key', $field['field_key'], 'field_key' );
$desc			= $this->registry->output->formTextarea( 'field_description', IPSText::br2nl( $field['field_description'] ), 40, 5, 'field_description', '', 'normal' );
$extra			= $this->registry->output->formTextarea( 'field_extra', $field['field_extra'], 40, 9, 'field_extra', '', 'normal' );
$html			= $this->registry->output->formYesNo( 'field_html', $field['field_html'], 'field_html' );

$types			= $this->registry->output->formDropdown( 'field_type', $types, $field['field_type'], 'field_type' );

$required		= $this->registry->output->formYesNo( 'field_required', $field['field_required'], 'field_required' );
$userEdit		= $this->registry->output->formYesNo( 'field_user_editable', $field['field_user_editable'], 'field_user_editable' );
$length			= $this->registry->output->formInput( 'field_max_length', $field['field_max_length'], 'field_max_length' );
$numeric		= $this->registry->output->formYesNo( 'field_is_numeric', $field['field_is_numeric'], 'field_is_numeric' );
$truncate	 	= $this->registry->output->formInput( 'field_truncate', $field['field_truncate'], 'field_truncate' );
$default		= $this->registry->output->formTextarea( 'field_default_value', $field['field_default_value'], 40, 9, 'field_default_value', '', 'normal' );
$dis_list		= $this->registry->output->formYesNo( 'field_display_listing', $field['field_display_listing'], 'field_display_listing' );
$dis_dis		= $this->registry->output->formYesNo( 'field_display_display', $field['field_display_display'], 'field_display_display' );
$topicFormat	= $this->registry->output->formTextarea( 'field_topic_format', $field['field_topic_format'], 40, 9, 'field_topic_format', '', 'normal' );

$_available		= array(
						array( 'strtolower', $this->lang->words['ffo__strtolower'] ),
						array( 'strtoupper', $this->lang->words['ffo__strtoupper'] ),
						array( 'ucfirst', $this->lang->words['ffo__ucfirst'] ),
						array( 'ucwords', $this->lang->words['ffo__ucwords'] ),
						array( 'punct', $this->lang->words['ffo__removeexcesspunct'] ),
						array( 'numerical', $this->lang->words['ffo__numericalformat'] ),
						);
$formatopts		= $this->registry->output->formMultiDropdown( 'field_format_opts[]', $_available, explode( ',', $field['field_format_opts'] ) );

$_ex			= explode( ',', $field['field_extra'] );
$fieldtype	 	= $this->registry->output->formDropdown( 'field_rel_type', array( array( 'dropdown', $this->lang->words['relfield_type_d'] ), array( 'multiselect', $this->lang->words['relfield_type_m'] ), array( 'typeahead', $this->lang->words['relfield_type_t'] ) ), $_ex[2] );

$validatorOptions	= array( array( 'none', $this->lang->words['validator__none'] ) );
$_validator			= explode( ';_;', $field['field_validator'] );

foreach( $validators as $_valid )
{
	$validatorOptions[]	= array( $_valid['key'], $_valid['language'] );
}

$fieldvalid	 		= $this->registry->output->formDropdown( 'field_validator', $validatorOptions, $_validator[0] );
$fieldvalidCustom	= $this->registry->output->formInput( 'field_validator_custom', IPSText::htmlspecialchars( $_validator[1], ENT_QUOTES ) );
$fieldvalidError	= $this->registry->output->formInput( 'field_validator_error', $_validator[2] );
$rellink			= $this->registry->output->formYesNo( 'field_rel_link', $_ex[3], 'field_rel_link' );
$crosslink			= $this->registry->output->formYesNo( 'field_rel_crosslink', $_ex[4], 'field_rel_crosslink' );

$text			= $type == 'edit' ? $this->lang->words['editing_field_title'] . ' ' . $field['field_name'] : $this->lang->words['adding_field_title'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

//-----------------------------------------
// Restrict our default fields
//-----------------------------------------

$_restrictedText	= '';
$_restrictedDate	= '';
$_isRestricted		= false;

if( in_array( $field['field_key'], array( 'article_title', 'article_body', 'teaser_paragraph', 'article_image', 'article_date', 'article_expiry', 'article_cutoff' ) ) )
{
	$_restrictedText	= " style='display:none;'";
	$_isRestricted		= true;
}

if(  in_array( $field['field_key'], array( 'article_date', 'article_expiry', 'article_cutoff' ) ) )
{
	$_restrictedDate	= " style='display:none;'";
	
	$this->lang->words['fieldform__extra']		= $this->lang->words['fieldform__extra_date'];
	$this->lang->words['fieldform__extra_desc']	= $this->lang->words['fieldform__extra_desc_date'];
}
		
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<div class='information-box'>
	{$this->lang->words['field_js_extra_info']}
</div>
<br />

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post' id='adminform' name='adform'>
<input type='hidden' name='field' id='field_edit_id' value='{$field['field_id']}' />

<div class='acp-box'>
	<h3>{$text}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__name']}</strong></td>
			<td class='field_field'>
				{$name}

HTML;

if( $_isRestricted )
{
$IPBHTML .= <<<HTML
		<div class='field_subfield'>{$this->lang->words['fieldform__key']}: <input type='hidden' name='field_key' value='{$field['field_key']}' /><strong>{$field['field_key']}</strong></div>
HTML;
}
else
{
$IPBHTML .= <<<HTML
		<div class='field_subfield'>{$this->lang->words['fieldform__key']}: {$key}</div>
HTML;
}

$IPBHTML .= <<<HTML
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__desc']}</strong></td>
			<td class='field_field'>{$desc}</td>
		</tr>
		<tr {$_restrictedText}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__type']}</strong></td>
			<td class='field_field'>{$types}</td>
		</tr>
		<tr {$_restrictedText}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__useredit']}</strong></td>
			<td class='field_field'>
				{$userEdit}<br />
				<span class='desctext'>{$this->lang->words['fieldform__useredit_desc']}</span>
			</td>
		</tr>
		<tr {$_restrictedText}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__numeric']}</strong></td>
			<td class='field_field'>
				{$numeric}<br />
				<span class='desctext'>{$this->lang->words['fieldform__numeric_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__default']}</strong></td>
			<td class='field_field'>
				{$default}<br />
				<span class='desctext'>{$this->lang->words['fieldform__default_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_validator_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__validator']}</strong></td>
			<td class='field_field'>
				{$fieldvalid}<br />
				<div id='validator_custom'>
					<strong>{$this->lang->words['fieldform_validator_custom']}</strong> {$fieldvalidCustom} <br />
					<strong>{$this->lang->words['fieldform_validator_error']}</strong> {$fieldvalidError}
				</div><br />
				<span class='desctext'>{$this->lang->words['fieldform__validator_desc']}</span>
			</td>
		</tr>
		<tr {$_restrictedText}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__dislist']}</strong></td>
			<td class='field_field'>
				{$dis_list}<br />
				<span class='desctext'>{$this->lang->words['fieldform__dislist_desc']}</span>
			</td>
		</tr>
		<tr {$_restrictedText}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__disdisplay']}</strong></td>
			<td class='field_field'>
				{$dis_dis}<br />
				<span class='desctext'>{$this->lang->words['fieldform__disdisplay_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__topicformat']}</strong></td>
			<td class='field_field'>
				{$topicFormat}<br />
				<span class='desctext'>{$this->lang->words['fieldform__topicformat_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_html_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__html']}</strong></td>
			<td class='field_field'>
				{$html}<br />
				<span class='desctext'>{$this->lang->words['fieldform__html_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_required_li'>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__req']}</strong></td>
			<td class='field_field'>{$required}</td>
		</tr>
		<tr class='field_type_wrapper' id='field_length_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__length']}</strong></td>
			<td class='field_field'>
				{$length}<br />
				<span class='desctext'>{$this->lang->words['fieldform__length_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__extra']}</strong></td>
			<td class='field_field'>
				{$extra}<br />
				<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__extra_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_formatopts_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__formatopts']}</strong></td>
			<td class='field_field'>
				{$formatopts}<br />
				<span class='desctext'>{$this->lang->words['fieldform__formatopts_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_truncate_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__truncate']}</strong></td>
			<td class='field_field'>
				{$truncate}<br />
				<span class='desctext'>{$this->lang->words['fieldform__truncate_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_reltype_li'{$_restrictedDate}>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__fieldtype']}</strong></td>
			<td class='field_field'>{$fieldtype}</td>
		</tr>
		<tr class='field_type_wrapper' id='field_rellink_li'>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__rellink']}</strong></td>
			<td class='field_field'>{$rellink}<div class='desctext'>{$this->lang->words['fieldform__rellink_desc']}</div></td>
		</tr>
		<tr class='field_type_wrapper' id='field_crosslink_li'>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__crosslink']}</strong></td>
			<td class='field_field'>{$crosslink}<div class='desctext'>{$this->lang->words['fieldform__crosslink_desc']}</div></td>
		</tr>
		<tr class='field_type_wrapper' id='field_databases_li'>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__dbs']}</strong></td>
			<td class='field_field'>
				<select id='field_databases_select' name='field_database'></select><br />
				<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__dbs_desc']}</span>
			</td>
		</tr>
		<tr class='field_type_wrapper' id='field_fields_li'>
			<td class='field_title'><strong class='title'>{$this->lang->words['fieldform__flds']}</strong></td>
			<td class='field_field'>
				<select id='field_fields_select' name='field_fields'></select><br />
				<span class='desctext' id='extra_options_text'>{$this->lang->words['fieldform__flds_desc']}</span>
			</td>
		</tr>
	</table>

	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=articles&amp;section=fields&amp;do=fields';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
<script type='text/javascript'>
	var fieldDescriptions	= new Hash();
	fieldDescriptions.set( 'standard', "{$this->lang->words['fieldform__extra_desc']}" );
	fieldDescriptions.set( 'date', "{$this->lang->words['fieldform__extra_desc_date']}" );
	fieldDescriptions.set( 'upload', "{$this->lang->words['fieldform__extra_desc_upload']}" );

	ipb.lang['changejstitle']	= '{$this->lang->words['changejstitle']}';
	ipb.lang['changejsinit']	= '{$this->lang->words['changejsinit']}';
	
	var obj = new acp.liveedit( 
					$('field_name'),
					$('field_key'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=fields&do=checkKey&database={$database['database_id']}"
					}
			  );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of fields within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @return	string		HTML
 */
public function fields( $database, $fields )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['manage_article_fields']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['afields_helpblurb']}
		</div>
	</div>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_field_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_field_button']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
HTML;

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['all_article_fields']}</h3>
	<table class='ipsTable' id='fields_list'>
		<tr>
			<th>&nbsp;</th>
			<th>{$this->lang->words['field_name_th']}</th>
			<th>{$this->lang->words['field_type_th']}</th>
			<th>{$this->lang->words['field_required_th']}</th>
			<th>{$this->lang->words['field_useredit_th']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( ! count( $fields ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='5' class='no_messages'>
				{$this->lang->words['no_fields_created_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['create_field_now']}</a>
			</td>
		</tr>
HTML;
}
else
{
	$_dev	= IN_DEV;

	foreach( $fields as $field )
	{
		//-----------------------------------------
		// These fields we simply hide (no reason to edit)
		// Update - Users want to be able to rearrange field order
		//-----------------------------------------
		
		//if( !$_dev AND in_array( $field['field_key'], array( 'article_homepage', 'article_comments' ) ) )
		//{
		//	continue;
		//}
		
		//-----------------------------------------
		// These fields we don't allow much config
		//-----------------------------------------
		
		$_restricted	= false;
		
		if( in_array( $field['field_key'], array( 'article_title', 'article_body', 'teaser_paragraph', 'article_date', 'article_expiry', 'article_cutoff', 'article_image', 'article_homepage', 'article_comments' ) ) )
		{
			$_restricted	= true;
		}
		
		$prefix	= $_restricted ? '' : $this->lang->words['customprefix'] . ' ';

		$name	= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;field={$field['field_id']}'><strong>{$prefix}{$field['field_name']}</strong></a>";
		
		if( $field['field_description'] )
		{
			$name	.= "<div class='desctext'>{$field['field_description']}</div>";
		}
		
		$_required	= $field['field_required'] ? 'tick.png' : 'cross.png';
		$_useredit	= $field['field_user_editable'] ? 'tick.png' : 'cross.png';
		$_type		= $this->lang->words['field_type__' . $field['field_type'] ];
		
		$_delete	= !$_restricted ? "<li class='i_delete'><a href='#' onclick=\"return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;field={$field['field_id']}' );\">{$this->lang->words['delete_field_menu']}</a></li>" : '';
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='field_{$field['field_id']}'>
			<td class='col_drag'><span class='draghandle'>&nbsp;</span></td>
			<td><span class='larger_text'>{$name}</span></td>
			<td>{$_type}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_required}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_useredit}' alt='{$this->lang->words['icon']}' /></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;field={$field['field_id']}'>{$this->lang->words['edit_field_menu']}</a>
					</li>
					{$_delete}
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
	jQ("#fields_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Add or edit a moderator
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Moderator data
 * @return	string		HTML
 */
public function moderatorForm( $type, $database, $moderator )
{
$IPBHTML = "";
//--starthtml--//

$types			= array( array( 'group', $this->lang->words['mod_type_group'] ), array( 'member', $this->lang->words['mod_type_member'] ) );
$groups			= array();

foreach( $this->caches['group_cache'] as $id => $data )
{
	$groups[]	= array( $id, $data['g_title'] );
}

$_type			= $this->registry->output->formDropdown( 'moderator_type', $types, $moderator['moderator_type'] );
$group			= $this->registry->output->formDropdown( 'moderator_group', $groups, $moderator['moderator_type'] == 'group' ? $moderator['moderator_type_id'] : 0 );
$username		= $this->registry->output->formInput( 'moderator_member', $moderator['moderator_member'] );

$add			= $this->registry->output->formYesNo( 'moderator_add_record', $moderator['moderator_add_record'] );
$edit			= $this->registry->output->formYesNo( 'moderator_edit_record', $moderator['moderator_edit_record'] );
$delete			= $this->registry->output->formYesNo( 'moderator_delete_record', $moderator['moderator_delete_record'] );
$deletec		= $this->registry->output->formYesNo( 'moderator_delete_comment', $moderator['moderator_delete_comment'] );
$lock			= $this->registry->output->formYesNo( 'moderator_lock_record', $moderator['moderator_lock_record'] );
$unlock			= $this->registry->output->formYesNo( 'moderator_unlock_record', $moderator['moderator_unlock_record'] );
$approve		= $this->registry->output->formYesNo( 'moderator_approve_record', $moderator['moderator_approve_record'] );
$approvec		= $this->registry->output->formYesNo( 'moderator_approve_comment', $moderator['moderator_approve_comment'] );
$pin			= $this->registry->output->formYesNo( 'moderator_pin_record', $moderator['moderator_pin_record'] );
$editc			= $this->registry->output->formYesNo( 'moderator_edit_comment', $moderator['moderator_edit_comment'] );
$restorer		= $this->registry->output->formYesNo( 'moderator_restore_revision', $moderator['moderator_restore_revision'] );
$extras			= $this->registry->output->formYesNo( 'moderator_extras', $moderator['moderator_extras'] );
	
$text			= $type == 'edit' ? $this->lang->words['editing_editor'] : $this->lang->words['adding_editor'];
$action			= $type == 'edit' ? 'doEdit' : 'doAdd';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post' id='adminform' name='adform'>
<input type='hidden' name='moderator' value='{$moderator['moderator_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['editor_perms_head']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__etype']}</strong></td>
			<td class='field_field'>
				{$_type}
				<div class='field_subfield_wrap' id='mod_group'>{$group}</div>
				<div class='field_subfield_wrap' id='mod_user'>{$username}</div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__add']}</strong></td>
			<td class='field_field'>{$add}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__edit']}</strong></td>
			<td class='field_field'>{$edit}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__delete']}</strong></td>
			<td class='field_field'>{$delete}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__editc']}</strong></td>
			<td class='field_field'>{$editc}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__deletec']}</strong></td>
			<td class='field_field'>{$deletec}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__lock']}</strong></td>
			<td class='field_field'>{$lock}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__pin']}</strong></td>
			<td class='field_field'>{$pin}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__unlock']}</strong></td>
			<td class='field_field'>{$unlock}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__approve']}</strong></td>
			<td class='field_field'>{$approve}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__approvec']}</strong></td>
			<td class='field_field'>{$approvec}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__restorer']}</strong></td>
			<td class='field_field'>{$restorer}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['modform__extras']}</strong></td>
			<td class='field_field'>{$extras} <div class='desctext'>{$this->lang->words['modform__extras_art_desc']}</div></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=articles&amp;section=editors&amp;do=editors';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of moderators within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Moderators
 * @param	array 		Member data (for member moderators)
 * @return	string		HTML
 */
public function moderators( $database, $moderators, $members=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['article_mods_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['aeditors_helpblurb']}
		</div>
	</div>

	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_editor_button']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_editor_button']}
				</a>
			</li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='acp-box'>
	<h3>{$this->lang->words['manage_editor_tbl_head']}</h3>
	<table class='ipsTable'>
		<tr>
			<th style='width: 20%'>{$this->lang->words['editorname_th']}</th>
			<th style='width: 8%'>{$this->lang->words['moderator_col_add']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_edit_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_del_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_del_c_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_lock_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_pin_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_unlock_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_approve_th']}</th>
			<th style='width: 8%'>{$this->lang->words['mod_approvec_th']}</th>
			<th style='width: 8%' class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( !count( $moderators ) )
{
	$IPBHTML .= <<<HTML
				<tr>
					<td colspan='11' class='no_messages'>
						{$this->lang->words['no_editors_created_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['create_editor_now']}</a>
					</td>
				</tr>
HTML;
}
else
{
	foreach( $moderators as $moderator )
	{
		$_add		= $moderator['moderator_add_record'] ? 'tick.png' : 'cross.png';
		$_edit		= $moderator['moderator_edit_record'] ? 'tick.png' : 'cross.png';
		$_delete	= $moderator['moderator_delete_record'] ? 'tick.png' : 'cross.png';
		$_deleteC	= $moderator['moderator_delete_comment'] ? 'tick.png' : 'cross.png';
		$_lock		= $moderator['moderator_lock_record'] ? 'tick.png' : 'cross.png';
		$_pin		= $moderator['moderator_pin_record'] ? 'tick.png' : 'cross.png';
		$_unlock	= $moderator['moderator_unlock_record'] ? 'tick.png' : 'cross.png';
		$_approve	= $moderator['moderator_approve_record'] ? 'tick.png' : 'cross.png';
		$_approvec	= $moderator['moderator_approve_comment'] ? 'tick.png' : 'cross.png';

		if( $moderator['moderator_type'] == 'group' )
		{
			$name	= $this->lang->words['mod_g_prefix'] . ' ' . $this->caches['group_cache'][ $moderator['moderator_type_id'] ]['g_title'];
		}
		else
		{
			$name	= "<a href='{$this->settings['board_url']}/index.php?showuser={$moderator['moderator_type_id']}' target='_blank'>{$members[ $moderator['moderator_type_id'] ]['members_display_name']}</a>";
		}
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td><span class='larger_text'>{$name}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_add}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_edit}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_delete}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_deleteC}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_lock}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_pin}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_unlock}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_approve}' alt='{$this->lang->words['icon']}' /></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_approvec}' alt='{$this->lang->words['icon']}' /></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;moderator={$moderator['moderator_id']}'>{$this->lang->words['edit_editor_menu']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;moderator={$moderator['moderator_id']}' );">{$this->lang->words['delete_editor_menu']}</a>
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
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Front page form
 *
 * @access	public
 * @param	array 		Settings
 * @param	array 		Articles
 * @param	array 		Fields
 * @param	array 		Database data
 * @param	string 		Category data
 * @param	array 		Templates
 * @param	array 		Template containers
 * @return	string		HTML
 */

public function frontpageForm( $cache, $articles, $fields, $database, $categories, $templates, $containers )
{
$IPBHTML ="";
//--starthtml--//

$limit			= $this->registry->output->formInput( 'config_limit', $cache['limit'], 'config_limit', 6 );
$_orders		= array( array( 'asc', $this->lang->words['db_records__asc'] ), array( 'desc', $this->lang->words['db_records__desc'] ) );
$order			= $this->registry->output->formDropdown( 'config_order', $_orders, $cache['order'] );
$fieldSort		= $this->registry->output->formDropdown( 'config_sort', $fields, $cache['sort'] );
$pinned			= $this->registry->output->formYesNo( 'config_pinned', $cache['pinned'] );
$paginate		= $this->registry->output->formYesNo( 'config_paginate', $cache['paginate'] );
$template		= $this->registry->output->formDropdown( 'config_template', $templates, $cache['template'], null, null, null, $containers );
$catgroup		= $this->registry->output->formYesNo( 'config_subcats', $cache['exclude_subcats'] );


$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='section_title'>
	<h2>{$this->lang->words['front_page_manager']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['fpmanager_helpblurb']}
		</div>
	</div>
</div>

<div class='acp-box'>
	<h3>&nbsp;</h3>
	<div id='tabstrip_fp' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		
		<ul>
			<li id='tab_fp_1'>{$this->lang->words['fp_settings']}</li>
			<li id='tab_fp_2'>{$this->lang->words['title_fp_currentfpart']}</li>
		</ul>
	</div>
	<div id='tabstrip_fp_content' class='ipsTabBar_content'>
		<div id='tab_fp_1_content'>
			<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=config' method='post'>
			<table class='ipsTable double_pad'>
				<tr>
					<th colspan='2'>{$this->lang->words['title_fp_feed']}</th>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['config_fp_upto']}</strong></td>
					<td class='field_field'>
						{$limit} {$this->lang->words['config_fp_articles']}<br />
						<span class='desctext' style='font-size: 11px'>{$this->lang->words['config_fp_someless']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['config_fp_sort']}</strong></td>
					<td class='field_field'>
						{$fieldSort}
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['config_fp_order']}</strong></td>
					<td class='field_field'>
						{$order}
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['config_fp_pinned']}</strong></td>
					<td class='field_field'>{$pinned}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['config_fp_paginate']}</strong></td>
					<td class='field_field'>{$paginate}</td>
				</tr>
				<tr>
					<th colspan='2'>{$this->lang->words['title_fp_other']}</th>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['title_fp_template']}</strong></td>
					<td class='field_field'>
						{$template}
					</td>
				</tr>
				<tr>
					<th colspan='2'>{$this->lang->words['title_fp_cats']}</th>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['title_fp_catexclude']}</strong></td>
					<td class='field_field'>
						{$catgroup}
						<div class='desctext'>{$this->lang->words['title_fp_catexclude_desc']}</div>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['fp_save_settings']}' class="button primary" />
			</div>
			</form>
		</div>
		<div id='tab_fp_2_content'>
			<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=articles' method='post'>
			<table class='ipsTable'>
				<tr>
					<th style='width: 2%'></th>
					<th style='width: 40%'>{$this->lang->words['th_fp_article']}</th>
					<th style='width: 25%' class='short'>{$this->lang->words['th_fp_published']}</th>
					<th style='width: 14%' class='short'>{$this->lang->words['th_fp_hits']}</th>
					<th style='width: 14%' class='short'>{$this->lang->words['th_fp_comments']}</th>
					<th class='col_buttons'></th>
				</tr>
HTML;

if( !count($articles) )
{
$IPBHTML .= <<<HTML
				<tr>
					<td colspan='6' class='no_messages'>{$this->lang->words['no_fp_articles_yet']}</td>
				</tr>
HTML;
}
else
{
foreach( $articles as $article )
{
	$IPBHTML .= <<<HTML
				<tr class='ipsControlRow'>
					<td class='short'><input type='checkbox' value='{$article['primary_id_field']}' name="articles[]" /></td>
					<td>
						<span class='larger_text'>{$article['title']}</span><br />
						<span class='desctext' style='font-size: 11px'>{$this->lang->words['fp_posted_in']} <strong>{$article['category']}</strong></span>
					</td>
					<td class='short'>{$article['date']}</td>
					<td class='short'>{$article['record_views']}</td>
					<td class='short'>{$article['record_comments']}</td>
					<td>
						<ul class='ipsControlStrip'>
							<li class='i_edit'>
								<a href='{$this->settings['base_url']}module=articles&amp;section=articles&amp;do=edit&amp;record={$article['primary_id_field']}' title='{$this->lang->words['fp_edit_article']}'> {$this->lang->words['fp_edit_article']}</a>
							</li>
							<li class='i_delete'>
								<a class='delete' href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;article={$article['primary_id_field']}', '{$this->lang->words['js_fp_rm_ar']}' );" title='{$this->lang->words['fp_rem_article']}'>{$this->lang->words['fp_rem_article']}</a>
							</li>
						</ul>
					</td>
				</tr>
HTML;
}
}

$IPBHTML .= <<<HTML
			</table>
			<div class="acp-actionbar" style='text-align: left'>
				<input type='submit' value='{$this->lang->words['remove_sel_fp']}' class="button primary" />
			</div>
			</form>
		</div>
	</div>
</div>
<script type='text/javascript'>
	jQ("#tabstrip_fp").ipsTabBar({tabWrap: "#tabstrip_fp_content", defaultTab: "tab_fp_1" });
</script>	
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Add or edit a record
 *
 * @access	public
 * @param	string		add|edit
 * @param	array		Database data
 * @param	array 		Fields
 * @param	array 		Record data
 * @param	object		Field abstraction class
 * @param	string 		Categories
 * @param	bool		This is a revision
 * @return	string		HTML
 */
public function recordForm( $type, $database, $fields, $record, $fieldClass, $categories='', $revision=false )
{
$IPBHTML = "";
//--starthtml--//
$text			= $revision ? $this->lang->words['editing_revision_title'] : ( $type == 'edit' ? $this->lang->words['editing_record_title'] . ' ' . $record[ $database['database_field_title'] ] : $this->lang->words['adding_record_title'] );
$action			= $revision ? 'doEditRevision' : ( $type == 'edit' ? 'doEdit' : 'doAdd' );

$published		= $this->registry->output->formDropdown( "record_approved", array( array( 1, $this->lang->words['articles_filt_stat_p'] ), array( 0, $this->lang->words['articles_filt_stat_d'] ), array( -1, $this->lang->words['articles_filt_stat_h'] ) ), $record['record_approved'] );
$_member		= array( 'members_display_name' => '' );

if( $this->request['preview'] AND $this->request['record_members_display_name'] )
{
	$_member	= IPSMember::load( $this->request['record_members_display_name'], 'core', 'displayname' );
	$_showYou	= "display:none;";
	$_showThem	= "";
}
else if( $type == 'edit' AND $record['member_id'] != $this->memberData['member_id'] )
{
	$_member	= IPSMember::load( $record['member_id'] );
	$_showYou	= "display:none;";
	$_showThem	= "";
}
else
{
	$_showThem	= "display:none;";
	$_showYou	= "";
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$text}</h2>
</div>

<link rel='stylesheet' href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.liveedit.js'></script>

HTML;

if( $this->request['preview'] )
{
	$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['preview_record_details']}</h3>
	<table class='ipsTable double_pad'>
HTML;

foreach( $fields as $field )
{
	$_field	= $fieldClass->getFieldValuePreview( $field, $record );
	
	$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$field['field_name']}</strong></td>
			<td class='field_field'>{$_field}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;
}

$furl		= $this->registry->output->formInput( 'record_static_furl', $record['record_static_furl'] );
$metak		= $this->registry->output->formTextarea( 'record_meta_keywords', $record['record_meta_keywords'], 40, 5, '', '', 'normal' );
$metad		= $this->registry->output->formTextarea( 'record_meta_description', $record['record_meta_description'], 40, 5, '', '', 'normal' );
$pin		= $this->registry->output->formYesNo( 'record_pinned', $record['record_pinned'] );

$templates	= array( array( 0, $this->lang->words['artman_def_templ'], '_root_' ) );
$containers	= array();

$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_database=5' ) );
$this->DB->execute();

while( $r = $this->DB->fetch() )
{
	$templates[]	= array( $r['template_id'], $r['template_name'], $r['template_category'] );
}

$this->DB->build( array( 'select' => 'container_id, container_name', 'from' => 'ccs_containers', 'where' => "container_type='arttemplate'", 'order' => 'container_order ASC' ) );
$this->DB->execute();

while( $r = $this->DB->fetch() )
{
	$containers[ $r['container_id'] ]	= $r['container_name'];
}
		
$template	= $this->registry->output->formDropdown( 'record_template', $templates, $record['record_template'], null, null, null, $containers );

$title		= '';
$editor		= '';
$teaser		= '';
$publish	= '';
$frontpage	= '';
$expiry		= '';
$comments	= '';
$ccutoff	= '';
$_fields	= '';

foreach( $fields as $field )
{
	$_default	= $record['field_' . $field['field_id'] ];
	
	if( isset($_POST['field_' . $field['field_id'] ]) )
	{
		if( is_array($_POST['field_' . $field['field_id'] ]) )
		{
			$_default	= ',' . implode( ',', $_POST['field_' . $field['field_id'] ] ) . ',';
		}
		else
		{
			$_default	= $field['field_type'] == 'input' ? IPSText::textToForm( $_POST['field_' . $field['field_id'] ] ) : $_POST['field_' . $field['field_id'] ];
		}
	}

	$_field	= $fieldClass->getAcpField( $field, $_default );
	
	//-----------------------------------------
	// Special fields
	//-----------------------------------------
	
	if( $field['field_key'] == 'article_title' )
	{
		$title		= $_field;
		continue;
	}
	else if( $field['field_key'] == 'teaser_paragraph' )
	{
		$teaser		= $_field;
		continue;
	}
	else if( $field['field_key'] == 'article_body' )
	{
		$editor		= $_field;
		continue;
	}
	else if( $field['field_key'] == 'article_date' )
	{
		$publish	= $_field;
		continue;
	}
	else if( $field['field_key'] == 'article_homepage' )
	{
		// @link http://community.invisionpower.com/resources/bugs.html/_/ip-content/artiles-changing-field-types-not-honored-in-acp-r40010
		if( $field['field_type'] == 'checkbox' )
		{
			$frontpage		= $this->registry->output->formCheckbox( 'field_' . $field['field_id'] . '[]', IPSText::cleanPermString( $_default ) );
		}
		else
		{
			$frontpage	= $_field;
		}
		continue;
	}
	else if( $field['field_key'] == 'article_expiry' )
	{
		$expiry	= $_field;
		continue;
	}
	else if( $field['field_key'] == 'article_comments' )
	{
		// @link http://community.invisionpower.com/resources/bugs.html/_/ip-content/artiles-changing-field-types-not-honored-in-acp-r40010
		if( $field['field_type'] == 'radio' )
		{
			$comments	= $this->registry->output->formYesNo( 'field_' . $field['field_id'], $_default );
		}
		else
		{
			$comments	= $_field;
		}
		continue;
	}
	else if( $field['field_key'] == 'article_cutoff' )
	{
		$ccutoff	= $_field;
		continue;
	}
	
	$_desc	= $field['field_description'] ? " <br /><span class='desctext'>{$field['field_description']}</div>" : '';

	$_fields	.= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$field['field_name']}</strong></td>
			<td class='field_field'>{$_field}{$_desc}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post' id='adminform' name='adform' enctype='multipart/form-data'>
<input type='hidden' name='record' value='{$record['primary_id_field']}' />
<input type='hidden' name='post_key' value='{$record['post_key']}' />
<!--<div class='article_box'>-->
	<div class='acp-box'>
		<h3>{$this->lang->words['edit_record_details']}</h3>
		<div id='tabstrip_articleform' class='ipsTabBar'>
			<ul>
				<li id='tab_general'>{$this->lang->words['artman_general_tab']}</li>
				<li id='tab_settings'>{$this->lang->words['artman_settings_tab']}</li>
				<li id='tab_meta'>{$this->lang->words['artman_override_meta']}</li>
			</ul>
		</div>
		<div id='tabstrip_articleform_content' class='ipsTabBar_content'>
			<div id='tab_general_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_title']}</strong></td>
						<td class='field_field'>
							{$title}
							<div class='field_subfield'><div class='field_subfield_inner' id='furl_wrap'>{$this->lang->words['record_form_furl']}: {$furl} <a href='#' class='action_link' id='cancel_furl_wrap'>{$this->lang->words['furltitlejs_cancel']}</a></div></div>
						</td>
					</tr>
HTML;

	if( $record['_tagBox'] )
	{
		$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['record_form_tags']}</strong></td>
				<td class='field_field'>{$record['_tagBox']}</td>
			</tr>
HTML;
	}

		if( $categories )
		{
			$IPBHTML .= <<<HTML
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['record_form_cat']}</strong></td>
					<td class='field_field'><select name='category_id' id='category_id'>{$categories}</select></td>
				</tr>
HTML;
		}

		$IPBHTML .= <<<HTML
					<tr>
						<th colspan='2'>{$this->lang->words['artbody_subteaser']}</th>
					</tr>
					<tr>
						<td colspan='2'>
							{$teaser}
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['artbody_subtitle']}</th>
					</tr>
					<tr>
						<td colspan='2'>
							{$editor}
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['artman_fields']}</th>
					</tr>
					{$_fields}
				</table>
			</div>

			<div id='tab_settings_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>{$this->lang->words['artman_publish_settings']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['art_is_published_now']}</strong></td>
						<td class='field_field'>
							{$published}
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_publish_date']}</strong></td>
						<td class='field_field'>
							{$publish}
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_expiry_date']}</strong></td>
						<td class='field_field'>
							{$expiry}
							<div class='desctext'>{$this->lang->words['artman_no_expiry']}</div>
						</td>
					</tr>

					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_change_author']}</strong></td>
						<td class='field_field'>
							<div id='default_author_display' style='{$_showYou}'>{$this->memberData['members_display_name']} &nbsp;&nbsp;<a href='#' class='action_link' id='change_author'>{$this->lang->words['author_change_art']}</a></div>
							<div id='change_author_display' style='{$_showThem}'>
								<input type='text' size='20' id='record_members_display_name' name='record_members_display_name' value='{$_member['members_display_name']}' />
							</div>
						</td>
					</tr>

					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_art_templ']}</strong></td>
						<td class='field_field'>
							{$template}
							<div class='desctext'>{$this->lang->words['artman_template_exp']}</div>
						</td>
					<tr>
						<th colspan='2'>{$this->lang->words['artman_comment_pref']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_allow_comments']}</strong></td>
						<td class='field_field'td>{$comments}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_comment_cutoff']}</strong></td>
						<td class='field_field'>
							{$ccutoff}
							<div class='desctext'>{$this->lang->words['artman_no_ccutoff']}</div>
						</td>
					</tr>
					<tr>
						<th colspan='2'>{$this->lang->words['artman_fp_settings']}</th>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_showon_home']}</strong></td>
						<td class='field_field'>{$frontpage}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['artman_pin_article']}</strong></td>
						<td class='field_field'>{$pin}</td>
					</tr>
				</table>
			</div>
			<div id='tab_meta_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_keywords']}</strong></td>
						<td class='field_field'>
							{$metak}<br />
							<span class='desctext'>{$this->lang->words['meta_tags__override']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['dbform__meta_description']}</strong></td>
						<td class='field_field'>
							{$metad}<br />
							<span class='desctext'>{$this->lang->words['meta_tags__override']}</span>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<br class='clear' /><br />
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='submit' name='save_and_reload' value='{$this->lang->words['button__reload']}' class="button primary" />
		<input type='submit' name='preview' value='{$this->lang->words['button__preview']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=articles&amp;section=articles&amp;do=articles';" value='{$this->lang->words['button__cancel']}' />
	</div>
</form>

<script type='text/javascript'>
	jQ("#tabstrip_articleform").ipsTabBar({ tabWrap: "#tabstrip_articleform_content" });

	$('change_author').observe( 'click', function(e){
		new Effect.Parallel([
			new Effect.BlindUp( $('default_author_display'), { sync: true } ),
			new Effect.BlindDown( $('change_author_display'), { sync: true } )
		], { afterFinish: function(){
			autocomplete	= new ipb.Autocomplete( $('record_members_display_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
		} } );
		
		Event.stop(e);
		return false;
	});

	
	$$('.article_expander').each( function(elem){
		var trigger = $( elem ).down('.trigger');
		var pane = $( elem ).down('.article_expander_pane');
		
		if( trigger && pane ){
			$( trigger ).observe('click', function(e){
				if( $( pane ).visible() ){
					new Effect.BlindUp( $( pane ), { duration: 0.3 } );
					$( elem ).removeClassName('open');
				} else {
					new Effect.BlindDown( $( pane ), { duration: 0.3 } );
					$( elem ).addClassName('open');
				}
			});
		}
	});

	var obj = new acp.liveedit( 
					$('field_1'),
					$('record_static_furl'),
					{
						template:	new Template( "<div id='#{id}' class='field_subfield_wrap'>{$this->lang->words['furl_url_ttitle']} <span id='#{displayid}'><em>{$this->lang->words['changejsinit']}</em></span> &nbsp;&nbsp;<a href='#' class='action_link' id='#{changeid}'>{$this->lang->words['changejstitle']}</a> &nbsp;&nbsp;<a href='#' class='action_link' id='furl_help'>{$this->lang->words['changejshelp']}</a></div>" ),
						wrapper:	'furl_wrap',
						isFurl:		true
						//url: "{$this->settings['base_url']}app=ccs&module=ajax&section=databases&do=checkCategoryKey&database={$database['database_id']}"
					}
			  );
</script>
<div id='furl_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['furl_help_title']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['record_form_furl_desc']}
	</div>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Get sort column name
 *
 * @access	protected
 * @param	string		Database column
 * @param	array 		Fields
 * @return	string		Friendly name
 */
protected function _getColumn( $column, $fields )
{
	if( !$column )
	{
		return 'date';
	}
	
	switch( $column )
	{
		case 'r.primary_id_field':
		case 'primary_id_field':
			return 'id';
		break;
		
		case 'r.record_comments':
		case 'record_comments':
			return 'comments';
		break;

		case 'r.record_views':
		case 'record_views':
			return 'hits';
		break;

		case 'r.record_approved':
		case 'record_approved':
			return 'status';
		break;

		case 'c.category_name':
		case 'category_name':
			return 'category';
		break;
	}
	
	foreach( $fields as $field )
	{
		if( 'field_' . $field['field_id'] == $column OR 'r.field_' . $field['field_id'] == $column )
		{
			if( $field['field_key'] == 'article_title' )
			{
				return 'title';
			}
			else if( $field['field_key'] == 'article_date' )
			{
				return 'date';
			}
		}
	}

	return 'date';
}
	
/**
 * Show the list of records within a database
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Records
 * @param	string		Pages
 * @param	object		Field abstraction class
 * @param	string		Query string for page links
 * @param	string		Category filter HTML (if categories defined)
 * @param	int			Number of queued comments
 * @return	string		HTML
 */
public function records( $database, $fields, $records, $pages, $fieldClass, $queryString, $categoryFilter='', $qc=0 )
{
$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Sort/filter options
//-----------------------------------------

$phrase		= $this->request['search_value'] ? $this->request['search_value'] : $this->lang->words['articles_search_phrase'];
$class 		= ( $this->request['search_value'] AND $this->request['search_value'] != $this->lang->words['articles_search_phrase'] ) ? '' : " class='inactive'";
$pinned		= $this->request['article_pinned'] ? " checked='checked'" : '';
$frontpage	= $this->request['article_homepage'] ? " checked='checked'" : '';
$statYes	= $this->request['record_approved'] == 'yes' ? " selected='selected'" : '';
$statNo		= $this->request['record_approved'] == 'no' ? " selected='selected'" : '';
$statHide	= $this->request['record_approved'] == 'hide' ? " selected='selected'" : '';
$commYes	= $this->request['has_comments'] == 'yes' ? " selected='selected'" : '';
$commNo		= $this->request['has_comments'] == 'no' ? " selected='selected'" : '';

//-----------------------------------------
// Sorting defaults
// Force to sort by date by default
//-----------------------------------------

$order			= $this->_getColumn( $this->request['sort_col'] ? $this->request['sort_col'] : '', $fields );

$direction		= ( $this->request['sort_order'] && in_array( $this->request['sort_order'], array( 'asc', 'desc' ) ) ) ? $this->request['sort_order'] : $database['database_field_direction'];

$sort_id			= $order == 'id' ? " active" : '';
$sort_title			= $order == 'title' ? " active" : '';
$sort_date			= ( $order == 'date' OR !$order ) ? " active" : '';
$sort_hits			= $order == 'hits' ? " active" : '';
$sort_comments		= $order == 'comments' ? " active" : '';
$sort_category		= $order == 'category' ? " active" : '';
$sort_status		= $order == 'status' ? " active" : '';

//-----------------------------------------
// Languages
//-----------------------------------------

$_global = $this->registry->output->loadTemplate( 'cp_skin_ccsglobal' );
$IPBHTML .= $_global->getJsLangs();
		
$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.tablesorter.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['articles_manage_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['articles_helpblurb']}
		</div>
	</div>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_record_alt']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_record_button']}
				</a>
			</li>
		</ul>
	</div>
</div>

HTML;

if( $qc )
{
	$message = sprintf( $this->lang->words['queued_comm_link'], $this->settings['base_url'] . $this->form_code, $qc );
	$IPBHTML .= <<<HTML
	<div class='information-box'>
		{$message}
	</div>
	<br />
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
<div class='filter_box'>
	<div>
		<div class='right'><input type='submit' value='{$this->lang->words['art_sort_apply']}' /></div>
		<strong>{$this->lang->words['articles_filter_art']}</strong>&nbsp;&nbsp;
		<input name='search_value' type='text' {$class} value='{$phrase}' size='18' id='filter_search' />&nbsp;
		<select name='category' id='category'>
			<option value='0'>{$this->lang->words['db_records__cf_all']}</option>
			<option value='-'>---------</option>
			{$categoryFilter}
		</select>
		&nbsp;
		<select name='record_approved' id='record_approved'>
			<option value='0'>{$this->lang->words['articles_filt_stat']}</option>
			<option value='0'>---------</option>
			<option value='yes'{$statYes}>{$this->lang->words['articles_filt_stat_p']}</option>
			<option value='no'{$statNo}>{$this->lang->words['articles_filt_stat_d']}</option>
			<option value='hide'{$statHide}>{$this->lang->words['articles_filt_stat_h']}</option>
		</select>
		&nbsp;
		<select name='has_comments' id='has_comments'>
			<option value='0'>{$this->lang->words['articles_filt_comments']}</option>
			<option value='0'>---------</option>
			<option value='yes'{$commYes}>{$this->lang->words['articles_filt_comments_p']}</option>
			<option value='no'{$commNo}>{$this->lang->words['articles_filt_comments_d']}</option>
		</select>
		&nbsp;
		<input type='checkbox' name='article_homepage' value='1'{$frontpage} id='filter_fp' />
		<label for='filter_fp'>{$this->lang->words['articles_filt_fp']}</label>
		&nbsp;&nbsp;
		<input type='checkbox' name='article_pinned' value='1'{$pinned} id='filter_pinned' />
		<label for='filter_pinned'>{$this->lang->words['articles_filt_pinned']}</label>
	</div>
</div>
</form>

<div class='acp-box clear'>
	<h3>{$this->lang->words['all_created_records_ta']}</h3>
	<table class='ipsTable' id='article_manager'>
		<thead>
			<tr>
				<th id='sort_id' class='sort{$sort_id} sortable' style='width: 4%'>
					<p>&nbsp;<span></span></p>
				</th>
				<th id='sort_title' class='sort{$sort_title} sortable' style='width: 31%'>
					<p>
						{$this->lang->words['ma_th_title']}
						<span></span>
					</p>	
				</th>
				<th id='sort_date' class='sort{$sort_date} sortable' style='width: 16%'>
					<p>{$this->lang->words['ma_th_date']}<span></span></p>
				</th>
				<th id='sort_hits' class='sort{$sort_hits} sortable' style='width: 6%'>
					<p>{$this->lang->words['ma_th_hits']}<span></span></p>
				</th>
				<th id='sort_comments' class='sort{$sort_comments} sortable' style='width: 6%;'>
					<p>{$this->lang->words['ma_th_com']}<span></span></p>
				</th>
				<th id='sort_category' class='sort{$sort_category} sortable' style='width: 20%'>
					<p>{$this->lang->words['ma_th_category']}<span></span></p>
				</th>
				<th id='sort_status' class='sort short{$sort_status} sortable' style='width: 14%'>
					<p>{$this->lang->words['ma_th_status']}<span></span></p>
				</th>
				<th style='width: 5%' class='col_buttons'></th>
			</tr>
		</thead>
		<tbody>
HTML;

if ( ! count( $records ) )
{
	$IPBHTML .= <<<HTML
	<tr>
		<td colspan='8' class='no_messages'>
			{$this->lang->words['no_records_created_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['create_record_now']}</a>
		</td>
	</tr>
HTML;
}
else
{
	foreach( $records as $record )
	{
		$IPBHTML .= $this->articleRow( $record, $database, $fields, $fieldClass );
	}
}
			
$IPBHTML .= <<<HTML
		</tbody>
	</table>
</div>
<br />
<div id='pages_bottom'>{$pages}</div>


<script type='text/javascript'>
	ipb.lang['js_error_enc']	= "{$this->lang->words['js_error_enc']}";
	
	var obj = new acp.tablesorter( 
					$('article_manager'),
					{
						url: "{$this->settings['base_url']}app=ccs&module=ajax&section=articles&do=sort",
						startOrder: '{$direction}',
						startColumn: '{$order}'
					}, 
					{
						afterUpdate: function( sorter ){
							// We need to build menus
							var menus = $( sorter.table ).select('.ipbmenu');

							if( menus.length )
							{
								for( i=0;i<menus.length;i++ )
								{
									var menuid = $( menus[i] ).id;
									var menucontent = $( menuid + '_menucontent' );

									if( $( menucontent ) ){
										new ipb.Menu( $(menuid), $(menucontent) );
									}
								}
							}
						}
					}
			  );

	$('filter_search').observe('focus', function(e){
		if( $( this ).hasClassName('inactive') ){
			$( this ).removeClassName('inactive').value = '';
		}
	});
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show article approved status
 *
 * @access	public
 * @return	string		HTML
 */
public function ajaxRecordHidden()
{
	return "<span class='draft'>{$this->lang->words['ma_hidden']}</span>";
}

/**
 * Show article approved status
 *
 * @access	public
 * @return	string		HTML
 */
public function ajaxRecordDraft()
{
	return "<span class='draft'>{$this->lang->words['ma_draft']}</span>";
}

/**
 * Show article approved status
 *
 * @access	public
 * @return	string		HTML
 */
public function ajaxRecordApproved()
{
	return "<span class='published'>{$this->lang->words['ma_published']}</span>";
}

/**
 * Articles row
 *
 * @access	public
 * @param	array 		Record
 * @param	array 		Database info
 * @param	array 		Fields
 * @param	obj			Fields class
 * @return	string		HTML
 */
public function articleRow( $record, $database, $fields, $fieldClass )
{
$IPBHTML = "";
//--starthtml--//

$this->_cssClass	= $record['record_approved'] == 1 ? ( ( $this->_cssClass == 'row1' ) ? 'row2' : 'row1' ) : '_red';
$status				= $record['record_approved'] == 1 ? $this->ajaxRecordApproved() : ( $record['record_approved'] == -1 ? $this->ajaxRecordHidden() : $this->ajaxRecordDraft() );

foreach( $fields as $field )
{
	if( $field['field_key'] == 'article_title' )
	{
		$record['title']	= $fieldClass->getFieldValue( $field, $record, 100 );
	}
	else if( $field['field_key'] == 'article_date' )
	{
		$record['date']	= $fieldClass->getFieldValue( $field, $record, 100 );
	}
	else if( $field['field_key'] == 'article_homepage' )
	{
		$record['frontpage']	= $fieldClass->getFieldValue( $field, $record, 100 );
	}
}

$_pinned	= '';

if( $record['frontpage'] )
{
	$_pinned	.= "<span class='ipsBadge ipsBadge_lightgrey' title='{$this->lang->words['frontpage_badge_ttl']}'>{$this->lang->words['frontpage_badge']}</span> ";
}

if( $record['record_pinned'] )
{
	$_pinned	.= $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->lang->words['pre_pinned'] );
}

if( !empty($record['tags']['formatted']['prefix']) )
{
	$_pinned	.= $record['tags']['formatted']['prefix'] . ' ';
}

if( isset($record['tags']) AND $record['tags'] )
{
	$_pinned	= "<div class='right'><img src='{$this->settings['img_url']}/icon_tag.png' /> <span class='desc lighter blend_links'>{$record['tags']['formatted']['truncatedWithLinks']}</span></div>" . $_pinned;
}

$_pinned	= preg_replace( '#<!--hook\.([^\>]+?)-->#', '', ipsRegistry::getClass('output')->templateHooks( $_pinned ) );
		
$revisions	= '';
$comments	= '';

if( $database['database_revisions'] )
{
	$revisions	= "<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=revisions&amp;record={$record['primary_id_field']}'>" . sprintf( $this->lang->words['record_revisions_menu'], intval($record['_revisions']) ) . "</a></li>";
}

if( trim( $database['perm_5'], ' ,' ) )
{
	$comments	= "<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=comments&amp;record={$record['primary_id_field']}'>" . sprintf( $this->lang->words['record_comments_menu'], $record['record_comments'] ) . "</a></li>";
}

if( $record['record_locked'] )
{
	$locked	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=lock&amp;record={$record['primary_id_field']}'>{$this->lang->words['unlock_record__menu']}</a></li>";
}
else
{
	$locked	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=lock&amp;record={$record['primary_id_field']}'>{$this->lang->words['lock_record__menu']}</a></li>";
}

if( $record['record_pinned'] )
{
	$pinned	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=pin&amp;record={$record['primary_id_field']}'>{$this->lang->words['unpin_record__menu']}</a></li>";
}
else
{
	$pinned	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=pin&amp;record={$record['primary_id_field']}'>{$this->lang->words['pin_record__menu']}</a></li>";
}

if( $record['record_approved'] == 1 )
{
	$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=approve&amp;record={$record['primary_id_field']}'>{$this->lang->words['hide_record__menu']}</a></li>";
}
else if( $record['record_approved'] == -1 )
{
	$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=approve&amp;record={$record['primary_id_field']}'>{$this->lang->words['unhide_record__menu']}</a></li>";
}
else
{
	$approved	= "<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=approve&amp;record={$record['primary_id_field']}'>{$this->lang->words['app_record__menu']}</a></li>";
}
	
$IPBHTML .= <<<HTML
<tr id='result_{$record['primary_id_field']}' class='ipsControlRow {$this->_cssClass}'>
	<td class='short' style='color: #525252; font-size: 11px;'>{$record['primary_id_field']}</td>
	<td>{$_pinned}<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;record={$record['primary_id_field']}' title='{$this->lang->words['edit_record_menu']}'>{$record['title']}</a></td>
	<td>{$record['date']}</td>
	<td class='short'>{$record['record_views']}</td>
	<td class='short'>{$record['record_comments']}</td>
	<td>{$record['category_name']}</td>
	<td class='short toggle_article_status' style='cursor: pointer' rel='{$record['primary_id_field']}'>
		{$status}
	</td>
	<td>
		<ul class='ipsControlStrip'>
			<li class='i_edit'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;record={$record['primary_id_field']}' title='{$this->lang->words['edit_record_menu']}'> {$this->lang->words['edit_record_menu']}</a>
			</li>
			<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$record['primary_id_field']}'>
				<a href='#'>{$this->lang->words['folder_options_alt']}</a>
			</li>
		</ul>
		<ul class='acp-menu' id='menu_folder{$record['primary_id_field']}_menucontent'>
			<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;record={$record['primary_id_field']}' );">{$this->lang->words['delete_record_menu']}</a></li>
			{$approved}
			{$locked}
			{$pinned}
			{$revisions}
			{$comments}
		</ul>
	</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of revisions for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Record data
 * @param	array 		Revisions for this record
 * @return	string		HTML
 */
public function revisions( $database, $fields, $record, $revisions )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbrevisions_title']} {$record['title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;record={$this->request['record']}&amp;do=clearRevisions' );" title='{$this->lang->words['delete_all_revisions']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['delete_all_revisions']}</a></li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['all_saved_revisions']}</h3>
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['th__revision_date']}</th>
			<th>{$this->lang->words['th__revision_member']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( !count( $revisions ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3' class='no_messages'>
				{$this->lang->words['no_revisions_saved_yet']}
			</td>
		</tr>
HTML;
}
else
{
	foreach( $revisions as $revision )
	{
		$data	= unserialize($revision['revision_data']);
		$date	= $this->registry->class_localization->getDate( $revision['revision_date'], 'LONG' );
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>{$date}</td>
			<td><a href='{$this->settings['board_url']}/index.php?showuser={$revision['member_id']}'>{$revision['members_display_name']}</a></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editRevision&amp;id={$database['database_id']}&amp;revision={$revision['revision_id']}'>{$this->lang->words['edit_revision_menu']}</a>
					</li>
					<li class='ipsControlStrip_more ipbmenu' id='menu{$revision['revision_id']}'>
						<a href='#'>{$this->registry->getClass('class_localization')->words['options']}</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu{$revision['revision_id']}_menucontent'>
					<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=deleteRevision&amp;revision={$revision['revision_id']}' );">{$this->lang->words['delete_revision_menu']}</a></li>
					<li class='icon manage'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=restoreRevision&amp;revision={$revision['revision_id']}', '{$this->lang->words['restore_are_you_sure']}' );">{$this->lang->words['restore_revision_menu']}</a></li>
					<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=compareRevision&amp;revision={$revision['revision_id']}'>{$this->lang->words['compare_revision_menu']}</a></li>
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
 * Show the list of revisions for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @param	array 		Differences data
 * @return	string		HTML
 */
public function compareRevisions( $database, $fields, $differences )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['dbview_revision']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['revision_comparison_list']}</h3>
	<table class='ipsTable'>
HTML;

if( count( $fields ) )
{
	foreach( $fields as $_field )
	{
		$differences[ $_field['field_id'] ] = str_replace( '&nbsp;', ' ', $differences[ $_field['field_id'] ] );
		$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$_field['field_name']}</strong></td>
			<td class='field_field'>{$differences[ $_field['field_id'] ]}</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div style='padding:4px;margin:4px;'>
	<span class='diffred'>{$this->lang->words['rev_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['rev_addedhtml']}</span>
</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the list of comments for a particular record (stored as replies to a topic)
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Record data
 * @param	array 		Comments
 * @param	string		Page links
 * @return	string		HTML
 */
public function topicComments( $database, $record, $comments, $pagelinks )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title']} {$record['title']}</h2>
	</div>
HTML;

$IPBHTML .= <<<HTML
<div>{$pagelinks}</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<link rel="stylesheet" href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />
<div class='acp-box clear'>
	<h3>{$this->lang->words['all_saved_comments']}</h3>
	<table class='form_table'>
HTML;

if( !count( $comments ) )
{
	$IPBHTML .= <<<HTML
	<tr>
		<td class='no_messages'>
			{$this->lang->words['no_comments_saved_yet']}
		</td>
	</tr>
HTML;
}
else
{
	foreach( $comments as $comment )
	{
		$date			= $this->registry->class_localization->getDate( $comment['post_date'], 'SHORT' );
		$_commentText	= !$comment['queued'] ? $this->lang->words['unapp_comment_menu'] : ( $comment['queued'] == 2 ? $this->lang->words['unhide_comment_menu'] : $this->lang->words['app_comment_menu'] );
		$_cssClass		= !$comment['queued'] ? ( ( $_cssClass == 'row1' ) ? 'row2' : 'row1' ) : '_red';

		$IPBHTML .= <<<HTML
		<tr class='{$_cssClass} ipsControlRow'>
			<td style='width: 170px; text-align: right;'>
				<img src='{$comment['pp_small_photo']}' width='{$comment['pp_small_width']}' height='{$comment['pp_small_height']}' class='photo' />
				<br />
				<span class='larger_text'>
HTML;

		if( $comment['member_id'] )
		{
			$IPBHTML .= <<<HTML
					<a href="{$this->settings['board_url']}/index.php?showuser={$comment['member_id']}">{$comment['members_display_name']}</a>
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
					{$comment['author_name']}
HTML;
		}

		$IPBHTML .= <<<HTML
				</span>
				<br />
				<br />
				<div class='posted_date desctext ipsType_smaller'>
					{$date}
					<br />
					{$this->lang->words['comment_ip_addy']} <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$comment['ip_address']}'>{$comment['ip_address']}</a>
				</div>
			</td>
			<td class='col_buttons' style='vertical-align:top;'>
				<div class='right'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=editComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}'>{$this->lang->words['edit_comment_menu']}</a>
						</li>
						<li class='i_cog'>
							<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=toggleComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}'>{$_commentText}</a>
						</li>
						<li class='i_delete'>
							<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=databases&amp;section=records&amp;do=deleteComment&amp;id={$database['database_id']}&amp;pid={$comment['pid']}' );">{$this->lang->words['delete_comment_menu']}</a>
						</li>
					</ul>
				</div>
				<div id='comment_{$comment['comment_id']}' class='comment_content'>
					{$comment['post']}
				</div>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div>{$pagelinks}</div>

HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * Show the list of comments for a particular record
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Record data
 * @param	array 		Comments
 * @param	string		Page links
 * @return	string		HTML
 */
public function comments( $database, $record, $comments, $pagelinks )
{
$IPBHTML = "";
//--starthtml--//

if( $this->request['filter'] == 'queued' )
{
	$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title_q']}</h2>
	</div>
HTML;

	$subtitle	= $this->lang->words['all_q_comm_f'];
}
else
{
	$IPBHTML .= <<<HTML
	<div class='section_title'>
		<h2>{$this->lang->words['dbcomments_title']} {$record['primary_id_field']}</h2>
	</div>
HTML;
	
	$subtitle	= $this->lang->words['all_saved_comments'];
}

$IPBHTML .= <<<HTML
<div>{$pagelinks}</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<link rel="stylesheet" href="{$this->settings['css_base_url']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_common.css" />
<div class='acp-box clear'>
	<h3>{$subtitle}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table'>
HTML;

if( !count( $comments ) )
{
	$_noneText	= $this->request['filter'] == 'queued' ? $this->lang->words['nomore_q_comments'] : $this->lang->words['no_comments_saved_yet'];
	
	$IPBHTML .= <<<HTML
	<tr>
		<td style='width:100%;'>
			<div style='padding: 20px; text-align: center'>
				<em>{$_noneText}</em>
			</div>
		</td>
	</tr>
HTML;
}
else
{
	foreach( $comments as $comment )
	{
		$date			= $this->registry->class_localization->getDate( $comment['comment_date'], 'LONG' );
		$_commentText	= $comment['comment_approved'] ? $this->lang->words['unapp_comment_menu'] : ( $comment['comment_approved'] == -1 ? $this->lang->words['unhide_comment_menu'] : $this->lang->words['app_comment_menu'] );
		$_cssClass		= $comment['comment_approved'] ? ( $_cssClass == 'row1' ? 'row2' : 'row1' ) : '_red';
		
		$_extraLink		= '';
		
		if( $this->request['filter'] == 'queued' )
		{
			$_extraLink	= "<div class='q-c-link'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;record={$comment['comment_record_id']}'>{$this->lang->words['qc_edit_record']}</a></div>";
		}
	
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow {$_cssClass}'>
			<td style='width: 170px; text-align: right;'>
				<img src='{$comment['pp_small_photo']}' width='{$comment['pp_small_width']}' height='{$comment['pp_small_height']}' class='photo' />
				<br />
				<span class='larger_text'>
HTML;

		if( $comment['member_id'] )
		{
			$IPBHTML .= <<<HTML
					<a href="{$this->settings['board_url']}/index.php?showuser={$comment['member_id']}">{$comment['members_display_name']}</a>
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
					{$comment['comment_author']}
HTML;
		}

		$IPBHTML .= <<<HTML
				</span>
				<br />
				<br />

				<div class='posted_date desctext ipsType_smaller'>
					{$date}
					<br />
					{$this->lang->words['comment_ip_addy']} <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$comment['comment_ip_address']}'>{$comment['comment_ip_address']}</a>
				</div>
			</td>
			<td class='col_buttons' style='vertical-align:top;'>
				<div class='right'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}'>{$this->lang->words['edit_comment_menu']}</a>
						</li>
						<li class='i_cog'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}'>{$_commentText}</a>
						</li>
						<li class='i_delete'>
							<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=deleteComment&amp;comment={$comment['comment_id']}&amp;filter={$this->request['filter']}' );">{$this->lang->words['delete_comment_menu']}</a>
						</li>
					</ul>
				</div>
				<div id='comment_{$comment['comment_id']}' class='comment_content'>
					{$comment['comment_post']}
				</div>
				{$_extraLink}
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<div>{$pagelinks}</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Edit a comment
 *
 * @access	public
 * @param	array		Database data
 * @param	array 		Comment data
 * @param	string		Editor HTML
 * @return	string		HTML
 */
public function commentForm( $database, $comment, $editor )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editing_a_comment']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doEditComment' method='post' id='adminform' name='adform'>
<input type='hidden' name='filter' value='{$this->request['filter']}' />
<input type='hidden' name='comment_id' value='{$comment['comment_id']}' />
<div class='acp-box'>
	{$editor}
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="history.go(-1);" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}