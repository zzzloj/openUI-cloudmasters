<?php
/**
 * <pre>
 * Invision Power Services
 * Templates skin file
 * Last Updated: $Date: 2012-01-30 17:54:50 -0500 (Mon, 30 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10218 $
 */
 
class cp_skin_templates
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
 * Error shown in a modal
 *
 * @access	public
 * @param	string		Error message
 * @return	string		HTML
 */
public function modalError( $error )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['template_modal_error']}</h3>
	{$error}
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show pages using a template
 *
 * @access	public
 * @param	array		Template info
 * @param	array		Pages using this template
 * @return	string		HTML
 */
public function showPagesModal( $template, $pages=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['pages_using_template']} {$template['template_name']}</h3>
HTML;

if( !count($pages) )
{
	$IPBHTML .= <<<HTML
	<div class='pad'>
	{$this->lang->words['no_modaltemppages']}
	</div>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
HTML;
	
	foreach( $pages as $page )
	{
		$url	= $this->registry->ccsFunctions->returnPageUrl( $page );
		
		$IPBHTML .= <<<HTML
		<tr>
			<td>
				<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=quickEdit&amp;page={$page['page_id']}'><strong>{$page['page_folder']}/{$page['page_seo_name']}</strong></a> &nbsp;
				<span class='view-page'><a href='{$url}' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/icons/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>
			</td>
		</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
	</table>
HTML;
}

$IPBHTML .= <<<HTML
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show databases using a template
 *
 * @access	public
 * @param	array		Template info
 * @param	array		Databases using this template
 * @return	string		HTML
 */
public function showDatabasesModal( $template, $databases=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['dbs_using_template']} {$template['template_name']}</h3>
HTML;

if( !count($databases) )
{
	$IPBHTML .= <<<HTML
	<div class='pad'>
	{$this->lang->words['no_modaltempdbs']}
	</div>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
HTML;
	
	foreach( $databases as $database )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>
				<a href='{$this->settings['base_url']}&amp;module=databases&amp;section=databases&amp;do=edit&amp;id={$database['database_id']}'><strong>{$database['database_name']}</strong></a>
			</td>
		</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
	</table>
HTML;
}

$IPBHTML .= <<<HTML
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show category form
 *
 * @access	public
 * @param	string		Add or edit
 * @param	array 		Block data for edit
 * @return	string		HTML
 */
public function categoryForm( $type, $category=array() )
{
$IPBHTML = "";
//--starthtml--//

$title	= $type == 'add' ? $this->lang->words['add_template_cat__title'] : $this->lang->words['edit_template_cat__title'] . ' ' . $category['container_name'];
$do 	= $type == 'add' ? 'doAddCategory' : 'doEditCategory';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do={$do}&amp;type={$this->request['type']}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$category['container_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_cat_details']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['template_cat__title']}</strong></td>
			<td class='field_field'><input type='text' class='textinput' name='category_title' value='{$category['container_name']}' /></td>
		</tr>
	</table>

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

/**
 * Show AJAX add category form
 *
 * @access	public
 * @return	string		HTML
 */
public function ajaxCategoryForm()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=doAddCategory&amp;type={$this->request['type']}' method='post' id='adform' name='adform'>
<div>
	<h3 class='ipsBlock_title'>{$this->lang->words['specify_cat_details']}</h3>
	<div style='padding: 10px'>
		<strong>{$this->lang->words['template_cat__title']}:</strong>
		&nbsp;&nbsp;<input type='text' class='textinput no_width' name='category_title' />
		&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm template deletion
 *
 * @access	public
 * @param	integer		Template id
 * @param	array		Template data
 * @param	integer		Pages still using template
 * @return	string		HTML
 */
public function confirmDelete( $id, $template, $count )
{
$IPBHTML = "";
//--starthtml--//

$stillUsing	= sprintf( $this->lang->words['template_still_used'], $count );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['confirm_template_delete']}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;confirm=1&amp;type={$this->request['type']}' method='post'>
<input type='hidden' name='template' value='{$id}' />
<div class='acp-box page-template-form'>
	<h3>{$this->lang->words['confirm_to_continue_d']}</h3>
	<table class='ipsTable'>
		<tr>
			<td>{$stillUsing}</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__cd']}' class="button primary" />
		<input type='button' value='{$this->lang->words['button__canceld']}' class="realbutton redbutton" onclick='return acp.redirect("{$this->settings['base_url']}{$this->form_code}&amp;section=pages&amp;type={$this->request['type']}");' />
	</div>
</div>	
</form>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the listing of page templates
 *
 * @access	public
 * @param	array		Current templates
 * @param	array 		Categories
 * @return	string		HTML
 */
public function listTemplatesPage( $templates=array(), $categories=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript'>
	ipb.templates['cat_empty_page']	= new Template("<li class='no-records' id='record_00#{category}'><em>{$this->lang->words['no_templates_yet']}</em></li>");
</script>
<div class='section_title'>
	<h2>{$this->lang->words['page_templates_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['pagetemplates_helpblurb']}
		</div>
	</div>

	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;type={$this->request['type']}' title='{$this->lang->words['add_template_button']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_template_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=addCategory&amp;type={$this->request['type']}' title='{$this->lang->words['add_block_category']}' id='template-add-category' rel='{$this->request['type']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
					{$this->lang->words['add_block_category']}
				</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['page_templates_title']}</h3>
	<div id='category-containers-p'>
HTML;

//-----------------------------------------
// Now we loop over cats and put templates in,
// then put the rest in "Other templates"
//-----------------------------------------

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_templates_header'], 'noEdit' => true );

if( is_array($categories) AND count($categories) )
{
	$dragNDrop	= array();
	
	foreach( $categories as $category )
	{
		//-----------------------------------------
		// Don't show "other blocks" category if it's
		// empty, but we have blocks in other cats
		//-----------------------------------------
		
		$_class			= "isDraggable";
		
		if( $category['container_id'] == 0 AND !(is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ])) AND count($templates) )
		{
			continue;
		}
		else if( $category['container_id'] == 0 )
		{
			$_class		= '';
		}

		$dragNDrop[]	= "sortable_handle_p_{$category['container_id']}";

		$IPBHTML .= <<<HTML
			<div id='container_p_{$category['container_id']}' class='{$_class}'>
				<table class='ipsTable no_background'>
					<tr class='ipsControlRow isDraggable'>
HTML;
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
						<th class='subhead col_drag' width='4%'><span class='draghandle'>&nbsp;</span></th>
HTML;
					}
					else
					{
						$IPBHTML .= <<<HTML
						<th class='subhead' width='4%'>&nbsp;</th>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						<th class='subhead' width='88%'><span class='larger_text'>{$category['container_name']}</span></th>
						<th class='subhead col_buttons' style='width: 8%'>
HTML;
						
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=editCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['edit_template_category']}</a>
								</li>
								<li class='i_export'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_template_category']}</a>
								</li>
								<li class='i_delete'>
									<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=deleteCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template_category']}</a>
								</li>
							</ul>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						</th>
					</tr>
				</table>
				<ul id='sortable_handle_p_{$category['container_id']}' class='alternate_rows'>
				
HTML;
		
		if( is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ]) )
		{
			foreach( $templates[ $category['container_id'] ] as $template )
			{
				$template['pages']	= $this->registry->class_localization->formatNumber( intval($template['pages']) );
				
				$pages_in_use	= sprintf( $this->lang->words['used_by_s_pages'], $template['template_id'], $template['pages'] );
				$revisions		= sprintf( $this->lang->words['template_managerevisions'], $template['revisions'] );
				
				$IPBHTML .= <<<HTML
					<li class='record' id='record_{$template['template_id']}'>
						<table class='ipsTable no_background'>
							<tr class='ipsControlRow isDraggable'>
								<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
								<td width='66%'>
									<span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$template['template_name']}</a></span>
									<div class='desctext'>{$template['template_desc']}</div>
								</td>
								<td width='22%' class='desctext'>
									{$this->lang->words['last_modified_pre']} {$template['template_updated_formatted']}<br />
									{$pages_in_use}
								</td>
								<td class='col_buttons' style='width: 8%'>
									<ul class='ipsControlStrip'>
										<li class='i_edit'>
											<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$template['template_name']}</a>
										</li>
										<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$template['template_id']}'>
											<a href='#'>{$this->lang->words['folder_options_alt']}</a>
										</li>
									</ul>
									<ul class='acp-menu' id='menu_folder{$template['template_id']}_menucontent'>
										<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;template={$template['template_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template__link']}</a></li>
										<li class='icon view'><a href='{$this->settings['base_url']}module=templates&amp;section=revisions&amp;ttype={$this->request['type']}&amp;type=template&amp;id={$template['template_id']}'>{$revisions}</a></li>
										<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportTemplate&amp;id={$template['template_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_single_template']}</a></li>
									</ul>
								</td>
							</tr>
						</table>
					</li>
HTML;
			}
		}
		else
		{
			$IPBHTML .= <<<HTML
					<li id='record_00{$category['container_id']}'>
						<table class='ipsTable no_background'>
							<tr>
								<td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_now']}</a></td>
							</tr>
						</table>
					</li>
HTML;
		}
				
		$IPBHTML .= <<<HTML
				</ul>
			</div>
HTML;

	}
}

$_dragNDrop_Categories	= implode( "', '", $dragNDrop );

$IPBHTML .= <<<HTML
	</div>
</div>

<script type='text/javascript'>
	acp.ccs.initTemplateCategorization( new Array('{$_dragNDrop_Categories}') );
	ipb.templates['cat_empty_page']	= new Template("<li id='record_00#{category}'><table class='ipsTable'><tr><td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_now']}</a></td></tr></table></li>");
</script>
HTML;

$IPBHTML .= $this->importForm();

//--endhtml--//
return $IPBHTML;
}


/**
 * Show the listing of database templates
 *
 * @access	public
 * @param	array		Current templates
 * @param	array 		Categories
 * @return	string		HTML
 */
public function listTemplatesDatabase( $templates=array(), $categories=array() )
{
$IPBHTML = "";
//--starthtml--//

$template['pages']	= $this->registry->class_localization->formatNumber( intval($template['pages']) );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['page_templates_dbtitle']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['dbtemplates_helpblurb']}
		</div>
	</div>

	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a class='ipbmenu clickable' title='{$this->lang->words['add_template_button']}' id='addContent'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_template_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=addCategory&amp;type={$this->request['type']}' title='{$this->lang->words['add_block_category']}' id='template-add-category' rel='{$this->request['type']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
					{$this->lang->words['add_block_category']}
				</a>
			</li>
		</ul>
	</div>
</div>
<ul class='acp-menu' id='addContent_menucontent'>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=1&amp;type={$this->request['type']}' title='{$this->lang->words['db_add_template_category']}'>{$this->lang->words['db_add_template_category']}</a></li>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=2&amp;type={$this->request['type']}' title='{$this->lang->words['db_add_template_listing']}'>{$this->lang->words['db_add_template_listing']}</a></li>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=3&amp;type={$this->request['type']}' title='{$this->lang->words['db_add_template_display']}'>{$this->lang->words['db_add_template_display']}</a></li>
</ul>

<div class='acp-box'>
	<h3>{$this->lang->words['page_templates_dbtitle']}</h3>
	<div id='category-containers-d'>
HTML;

//print_r($categories);
//print_r($templates);
//-----------------------------------------
// Now we loop over cats and put templates in,
// then put the rest in "Other templates"
//-----------------------------------------

$categories[]	= array( 'container_id' => 00, 'container_name' => $this->lang->words['current_templates_header'], 'noEdit' => true );

if( is_array($categories) AND count($categories) )
{
	$dragNDrop	= array();
	
	foreach( $categories as $category )
	{
		//-----------------------------------------
		// Don't show "other blocks" category if it's
		// empty, but we have blocks in other cats
		//-----------------------------------------
		
		$_class			= "isDraggable";
		
		if( $category['container_id'] == 0 AND !(is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ])) AND count($templates) )
		{
			continue;
		}
		else if( $category['container_id'] == 0 )
		{
			$_class		= '';
		}

		$dragNDrop[]	= "sortable_handle_d_{$category['container_id']}";
		
		$IPBHTML .= <<<HTML
			<div id='container_d_{$category['container_id']}' class='{$_class}'>
				<table class='ipsTable no_background'>
					<tr class='ipsControlRow isDraggable'>
HTML;
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
						<th class='subhead col_drag' width='4%'><span class='draghandle'>&nbsp;</span></th>
HTML;
					}
					else
					{
						$IPBHTML .= <<<HTML
						<th class='subhead' width='4%'>&nbsp;</th>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						<th class='subhead' width='88%'><span class='larger_text'>{$category['container_name']}</span></th>
						<th class='subhead col_buttons' style='width: 8%'>
HTML;
						
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=editCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['edit_template_category']}</a>
								</li>
								<li class='i_export'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_template_category']}</a>
								</li>
								<li class='i_delete'>
									<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=deleteCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template_category']}</a>
								</li>
							</ul>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						</th>
					</tr>
				</table>
				<ul id='sortable_handle_d_{$category['container_id']}' class='alternate_rows'>
HTML;

		if( is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ]) )
		{
			foreach( $templates[ $category['container_id'] ] as $template )
			{
				$pages_in_use	= sprintf( $this->lang->words['used_by_s_dbs'], $template['template_id'], $template['dbs'] );
				$revisions		= sprintf( $this->lang->words['template_managerevisions'], $template['revisions'] );
				
				$IPBHTML .= <<<HTML
					<li class='record' id='record_{$template['template_id']}'>
						<table class='ipsTable no_background'>
							<tr class='ipsControlRow isDraggable'>
								<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
								<td width='66%'>
									<span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$template['template_name']}</a></span>
									<div class='desctext'>{$template['template_desc']}</div>
								</td>
								<td width='22%' class='desctext'>
									{$this->lang->words['last_modified_pre']} {$template['template_updated_formatted']}<br />
									{$pages_in_use}
								</td>
								<td class='col_buttons' style='width: 8%'>
									<ul class='ipsControlStrip'>
										<li class='i_edit'>
											<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$this->lang->words['edit_template_link']}</a>
										</li>
										<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$template['template_id']}'>
											<a href='#'>{$this->lang->words['folder_options_alt']}</a>
										</li>
									</ul>
									<ul class='acp-menu' id='menu_folder{$template['template_id']}_menucontent'>
										<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;template={$template['template_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template__link']}</a></li>
										<li class='icon view'><a href='#' onclick="return acp.ccs.viewCompare( '{$this->settings['base_url']}module=ajax&amp;section=compare&amp;id={$template['template_id']}' );">{$this->lang->words['template_diff_report']}</a></li>
										<li class='icon view'><a href='{$this->settings['base_url']}module=templates&amp;section=revisions&amp;ttype={$this->request['type']}&amp;type=template&amp;id={$template['template_id']}'>{$revisions}</a></li>
										<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportTemplate&amp;id={$template['template_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_single_template']}</a></li>
									</ul>
								</td>
							</tr>
						</table>
					</li>
HTML;
			}
		}
		else
		{
			$IPBHTML .= <<<HTML
					<li id='record_000{$category['container_id']}'>
						<table class='ipsTable no_background'>
							<tr>
								<td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;database=1&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_dbcat_now']}</a></td>
							</tr>
						</table>
					</li>
HTML;
		}
		
		$IPBHTML .= <<<HTML
				</ul>
			</div>
HTML;
	}
}

$_dragNDrop_Categories	= implode( "', '", $dragNDrop );

$IPBHTML .= <<<HTML
	</div>
</div>

<script type='text/javascript'>
	acp.ccs.initTemplateCategorization( new Array('{$_dragNDrop_Categories}') );
	ipb.templates['cat_empty_db']	= new Template("<li id='record_000#{category}'><table class='ipsTable'><tr><td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_now']}</a></td></tr></table></li>");
</script>
HTML;

$IPBHTML .= $this->importForm();

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the listing of article templates
 *
 * @access	public
 * @param	array		Current templates
 * @param	array 		Categories
 * @return	string		HTML
 */
public function listTemplatesArticles( $templates=array(), $categories=array() )
{
$IPBHTML = "";
//--starthtml--//

$template['pages']	= $this->registry->class_localization->formatNumber( intval($template['pages']) );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['art_templates_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
		{$this->lang->words['arttemplates_helpblurb']}
		</div>
	</div>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a class='ipbmenu clickable' title='{$this->lang->words['add_template_button']}' id='addContent'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_template_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=addCategory&amp;type={$this->request['type']}' title='{$this->lang->words['add_block_category']}' id='template-add-category' rel='{$this->request['type']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
					{$this->lang->words['add_block_category']}
				</a>
			</li>
		</ul>
	</div>
</div>
<ul class='acp-menu' id='addContent_menucontent'>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=4&amp;type={$this->request['type']}' title='{$this->lang->words['art_add_template_frontpage']}'>{$this->lang->words['art_add_template_frontpage']}</a></li>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=5&amp;type={$this->request['type']}' title='{$this->lang->words['art_add_template_display']}'>{$this->lang->words['art_add_template_display']}</a></li>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=6&amp;type={$this->request['type']}' title='{$this->lang->words['art_add_template_archive']}'>{$this->lang->words['art_add_template_archive']}</a></li>
	<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;database=7&amp;type={$this->request['type']}' title='{$this->lang->words['art_add_template_categories']}'>{$this->lang->words['art_add_template_categories']}</a></li>
</ul>

<div class='acp-box'>
	<h3>{$this->lang->words['art_templates_title']}</h3>
	<div id='category-containers-d'>
HTML;

//print_r($categories);
//print_r($templates);
//-----------------------------------------
// Now we loop over cats and put templates in,
// then put the rest in "Other templates"
//-----------------------------------------

$categories[]	= array( 'container_id' => 00, 'container_name' => $this->lang->words['current_templates_header'], 'noEdit' => true );

if( is_array($categories) AND count($categories) )
{
	$dragNDrop	= array();
	
	foreach( $categories as $category )
	{
		//-----------------------------------------
		// Don't show "other blocks" category if it's
		// empty, but we have blocks in other cats
		//-----------------------------------------
		
		$_class			= "isDraggable";
		
		if( $category['container_id'] == 0 AND !(is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ])) AND count($templates) )
		{
			continue;
		}
		else if( $category['container_id'] == 0 )
		{
			$_class		= '';
		}

		$dragNDrop[]	= "sortable_handle_d_{$category['container_id']}";

		$IPBHTML .= <<<HTML
			<div id='container_d_{$category['container_id']}' class='{$_class}'>
				<table class='ipsTable no_background'>
					<tr class='ipsControlRow isDraggable'>
HTML;
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
						<th class='subhead col_drag' width='4%'><span class='draghandle'>&nbsp;</span></th>
HTML;
					}
					else
					{
						$IPBHTML .= <<<HTML
						<th class='subhead' width='4%'>&nbsp;</th>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						<th class='subhead' width='88%'><span class='larger_text'>{$category['container_name']}</span></th>
						<th class='subhead col_buttons' style='width: 8%'>
HTML;
						
					if ( !$category['noEdit'] )
					{
						$IPBHTML .= <<<HTML
							<ul class='ipsControlStrip'>
								<li class='i_edit'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=editCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['edit_template_category']}</a>
								</li>
								<li class='i_export'>
									<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_template_category']}</a>
								</li>
								<li class='i_delete'>
									<a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=deleteCategory&amp;id={$category['container_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template_category']}</a>
								</li>
							</ul>
HTML;
					}
					
					$IPBHTML .= <<<HTML
						</th>
					</tr>
				</table>
				<ul id='sortable_handle_d_{$category['container_id']}' class='alternate_rows'>
HTML;

		if( is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ]) )
		{
			foreach( $templates[ $category['container_id'] ] as $template )
			{
				$revisions		= sprintf( $this->lang->words['template_managerevisions'], $template['revisions'] );
				
				$IPBHTML .= <<<HTML
					<li class='record' id='record_{$template['template_id']}'>
						<table class='ipsTable no_background'>
							<tr class='ipsControlRow isDraggable'>
								<td class='col_drag' width='4%'><span class='draghandle'>&nbsp;</span></td>
								<td width='66%'>
									<span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$template['template_name']}</a></span>
									<div class='desctext'>{$template['template_desc']}</div>
								</td>
								<td width='22%' class='desctext'>
									{$this->lang->words['last_modified_pre']} {$template['template_updated_formatted']}
								</td>
								<td class='col_buttons' style='width: 8%'>
									<ul class='ipsControlStrip'>
										<li class='i_edit'>
											<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}&amp;type={$this->request['type']}'>{$this->lang->words['edit_template_link']}</a>
										</li>
										<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$template['template_id']}'>
											<a href='#'>{$this->lang->words['folder_options_alt']}</a>
										</li>
									</ul>
									<ul class='acp-menu' id='menu_folder{$template['template_id']}_menucontent'>
								<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;template={$template['template_id']}&amp;type={$this->request['type']}' );">{$this->lang->words['delete_template__link']}</a></li>
								<li class='icon view'><a href='#' onclick="return acp.ccs.viewCompare( '{$this->settings['base_url']}module=ajax&amp;section=compare&amp;id={$template['template_id']}' );">{$this->lang->words['template_diff_report']}</a></li>
								<li class='icon view'><a href='{$this->settings['base_url']}module=templates&amp;section=revisions&amp;ttype={$this->request['type']}&amp;type=template&amp;id={$template['template_id']}'>{$revisions}</a></li>
								<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=exportTemplate&amp;id={$template['template_id']}&amp;type={$this->request['type']}'>{$this->lang->words['export_single_template']}</a></li>
									</ul>
								</td>
							</tr>
						</table>
					</li>
HTML;
			}	
		}
		else
		{
			$IPBHTML .= <<<HTML
					<li id='record_000{$category['container_id']}'>
						<table class='ipsTable no_background'>
							<tr>
								<td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;database=4&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_now']}</a></td>
							</tr>
						</table>
					</li>
HTML;
		}
		
		$IPBHTML .= <<<HTML
				</ul>
			</div>
HTML;
	}
}

$_dragNDrop_Categories	= implode( "', '", $dragNDrop );

$IPBHTML .= <<<HTML
	</div>
</div>

<script type='text/javascript'>
	acp.ccs.initTemplateCategorization( new Array('{$_dragNDrop_Categories}') );
	ipb.templates['cat_empty_db']	= new Template("<li id='record_000#{category}'><table class='ipsTable'><tr><td class='no_messages'>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add&amp;type={$this->request['type']}' class='mini_button'>{$this->lang->words['create_pt_now']}</a></td></tr></table></li>");
</script>
HTML;

$IPBHTML .= $this->importForm();

//--endhtml--//
return $IPBHTML;
}

/**
 * Block to allow importing of templates
 *
 * @access	public
 * @return	string		HTML
 */
public function importForm()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=importTemplates&amp;type={$this->request['type']}' method='post' enctype='multipart/form-data'>
<div class='acp-box page-template-form'>
	<h3>{$this->lang->words['import_templates']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['import_template_upload']}</strong>
			</td>
			<td class='field_field'>
				<input class='textinput' type='file' name='FILE_UPLOAD' />
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__upload']}' class="button primary" />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to add or edit a template
 *
 * @access	public
 * @param	string		Type (add|edit)
 * @param	array		Current data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function templateForm( $type, $defaults, $form )
{
$IPBHTML = "";
//--starthtml--//

$title	= $type == 'add' ? $this->lang->words['adding_a_template'] : $this->lang->words['editing_a_template'];
$code	= $type == 'add' ? 'doAdd' : 'doEdit';

$_class1	= IPSCookie::get('hideContentHelp') ? "" : "withSidebar";
$_class2	= IPSCookie::get('hideContentHelp') ? " closed" : "";

if( $this->request['database'] OR $defaults['template_database'] )
{
	$IPBHTML .= <<<HTML
		<script type='text/javascript'>
			var dbType = "{$defaults['template_database']}";
		</script>
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
<link href="{$this->settings['public_dir']}style_css/prettify.css" type="text/css" rel="stylesheet" />
<script type='text/javascript'>
	ipb.lang['insert_tag']	= '{$this->lang->words['insert']}';
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.tagsidebar.js'></script>
<script type='text/javascript' src='{$this->settings['public_dir']}/js/3rd_party/prettify/prettify.js'></script>
HTML;

if( $this->request['type'] == 'article' )
{
	$IPBHTML .= <<<HTML
	<script type='text/javascript'>
	acp.ipcsidebar.isArticlesTemplate	= "{$defaults['template_database']}";
	</script>
HTML;
}

$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$code}&amp;type={$this->request['type']}' method='post'>
<input type='hidden' name='template' value='{$defaults['template_id']}' />
<input type='hidden' name='template_database' value='{$defaults['template_database']}' />
HTML;

if( !IN_DEV )
{
	$IPBHTML .= <<<HTML
		<input type='hidden' name='template_key' value='{$defaults['template_key']}' />
HTML;
}

$IPBHTML .= <<<HTML
<input type='hidden' name='step' value='3' />
<div class='acp-box'>
	<h3>{$this->lang->words['template_form_header']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['template_title']}</strong>
			</td>
			<td class='field_field'>
				{$form['name']}
			</td>
		</tr>
HTML;

if( IN_DEV )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['template_key']}</strong>
			</td>
			<td class='field_field'>
				{$form['key']}<br /><span class='desctext'>{$this->lang->words['template_key_desc']}</span>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['template_description']}</strong>
			</td>
			<td class='field_field'>
				{$form['description']}<br /><span class='desctext'>{$this->lang->words['template_description_desc']}</span>
			</td>
		</tr>
HTML;

if( $form['category'] )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['select_category']}</strong>
			</td>
			<td class='field_field'>
				{$form['category']}
			</td>
		</tr>
HTML;
}

$_diff		= '';

if( $defaults['template_database'] AND $defaults['template_id'] )
{
	$_diff = "<div class='clearfix template-buttons'>
		<a href='#' onclick=\"return acp.ccs.viewCompare( '{$this->settings['base_url']}module=ajax&amp;section=compare&amp;id={$defaults['template_id']}' );\" title='{$this->lang->words['template_diff_report']}'><img alt='{$this->lang->words['template_diff_report']}' title='{$this->lang->words['template_diff_report']}' src='{$this->settings['skin_app_url']}images/differences.png' /> {$this->lang->words['tdl__text']}</a>
		<a href='{$this->settings['base_url']}module=templates&amp;section=pages&amp;do=revert&amp;id={$defaults['template_id']}&amp;type={$this->request['type']}' title='{$this->lang->words['template_revert_link']}'><img alt='{$this->lang->words['template_revert_link']}' title='{$this->lang->words['template_revert_link']}' src='{$this->settings['skin_app_url']}images/revert.png' /> {$this->lang->words['template_revert_link']}</a>
	</div>";
}

if( $this->request['database'] OR $defaults['template_database'] )
{
	$templateTags	= $this->registry->ccsAcpFunctions->getTemplateTags( true, true );
}
else
{
	$templateTags	= $this->registry->ccsAcpFunctions->getTemplateTags( true, false, true );
}



$IPBHTML .= <<<HTML
		<tr>
			<th colspan='2'>
				{$this->lang->words['template_html']}
			</th>
		</tr>
		<tr>
			<td colspan='2' class='sidebarContainer'>
				<div id="template-tags" class="templateTags-container{$_class2}">{$templateTags}</div>
				<div id='content-label' class="{$_class1}">{$form['content']}</div>
				{$_diff}
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']} ' class="button primary" />
		<input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;type={$this->request['type']}';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * View a database template difference report
 *
 * @access	public
 * @param	array 		Template data
 * @param	string		Diff output
 * @return	string		HTML
 */
public function viewDiffReport( $template, $output )
{
$IPBHTML = "";
//--starthtml--//

$output	= $output ? $output : IPSText::htmlspecialchars($template['template_content']);
$_date	= $this->registry->class_localization->getDate( $template['template_updated'], 'SHORT' );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>
		{$this->lang->words['template_diff_title']} {$template['template_name']}
		<div style='float:right; margin-right: 20px;'><span class='desctext'>{$this->lang->words['row_modified']} {$_date}</span></div>
	</h3>
	<div class='pad fixed_inner clear'><pre>{$output}</pre></div>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the template tags inline
 *
 * @access	public
 * @param	array 		Categories
 * @param	array		Current template tags (blocks)
 * @param	array 		Databases
 * @param	bool		Force special tags
 * @return	string		HTML
 */
public function inlineTemplateTags( $categories, $blocks, $databases, $showSpecial=false, $tabDatabase=false )
{
$IPBHTML = "";
//--starthtml--//

$_rel	= $showSpecial ? 'yes' : 'no';

$IPBHTML .= <<<HTML
<div class='templateTags'>
	<h4>
		<div class='right'>
			<span id='close-tags-link' title='{$this->lang->words['template_tag_help_close']}'>&times;</span>
		</div>
		{$this->lang->words['template_tag_header']}
	</h4>
	<div class='ipsTabBar' id='tag_tabbar'>
		<ul id='tags_tabs'>
			<li id='tab_templates' class='active'>{$this->lang->words['tags_templates']}</li>
HTML;

	if( $tabDatabase )
	{
		$IPBHTML .= <<<HTML
			<li id='tab_databases'>{$this->lang->words['tags_databases']}</li>
HTML;
	}

	$IPBHTML .= <<<HTML
			<li id='tab_media'>{$this->lang->words['tags_media']}</li>
		</ul>
	</div>
	<div id='templateTags_inner'>
		<div id='tab_media_pane' class='tag_pane' style='display: none'>
				
		</div>

		<div id='tab_databases_pane' class='tag_pane' style='display: none'>
			<div id='tab_database_overview'></div>
		</div>

		<div id='tab_templates_pane' class='tag_pane'>
			<ul>
HTML;

if( $this->request['do'] == 'showtags' OR $showSpecial )
{
	$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['special_tags_cat']}
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag='{ccs special_tag=&quot;page_content&quot;}'>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tag__page_content']}</h5>
				<p class='template-tag'>{ccs special_tag="page_content"}</p>
				<div class='tag_help' style='display: none'>
					{$this->lang->words['tagh_content']}
					<div class='code_sample'>
						<strong>{$this->lang->words['html_sample']}</strong>
						<pre class='prettyprint'>
&lt;div id='content'>
   {ccs special_tag="page_content"}
&lt;/div></pre>
					</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag='{ccs special_tag=&quot;page_title&quot;}'>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tag__page_title']}</h5>
				<p class='template-tag'>{ccs special_tag="page_title"}</p>
				<div class='tag_help' style='display: none'>
					{$this->lang->words['tagh_title']}

					<div class='code_sample'>
						<strong>{$this->lang->words['html_sample']}</strong>
						<pre class='prettyprint'>
&lt;title>{ccs special_tag="page_title"}&lt;/title></pre>
					</div>
				</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag='{ccs special_tag=&quot;meta_tags&quot;}'>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tag__meta_tags']}</h5>
				<p class='template-tag'>{ccs special_tag="meta_tags"}</p>
				<div class='tag_help' style='display: none'>
					{$this->lang->words['tagh_metatags']}

					<div class='code_sample'>
						<strong>{$this->lang->words['html_sample']}</strong>
						<pre class='prettyprint'>
&lt;head>
   &lt;!--Other head items go here-->
   {ccs special_tag="meta_tags"}
&lt;/head></pre>
					</div>
				</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag='{ccs special_tag=&quot;navigation&quot;}'>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tag__navigation']}</h5>
				<p class='template-tag'>{ccs special_tag="navigation"}</p>
				<div class='tag_help' style='display: none'>
					{$this->lang->words['tagh_navigation']}

					<div class='code_sample'>
						<strong>{$this->lang->words['html_sample']}</strong>
						<pre class='prettyprint'>
&lt;div class='my_navigation'>
   {ccs special_tag="navigation"}
&lt;/div></pre>
					</div>
				</div>
			</li>
HTML;
}

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_blocks_header'] );

if( is_array($categories) AND count($categories) )
{
	foreach( $categories as $category )
	{
		$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$category['container_name']}
			</li>
HTML;
		if( is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ]) )
		{
			foreach( $blocks[ $category['container_id'] ] as $block )
			{
				$tag	= '{parse block="' . $block['block_key'] . '"}';
				$safe_tag = str_replace("'", "&#39;", str_replace('"', "&quot;", $tag));

				$IPBHTML .= <<<HTML
					<li class='tag_row ipsControlRow' title='{$block['block_description']}' data-tag='{$safe_tag}'>
						<ul class='ipsControlStrip'>
							<li class='i_view'><a href='#' title='{$this->lang->words['block_preview_alt']}' class='block-preview-link' id='{$block['block_id']}-blockPreview'>...</a></li>
							<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
						</ul>
						<h5>{$block['block_name']}</h5>
						<p class='template-tag'>{$tag}</p>
					</li>
HTML;
			}
		}
	}
}

if( is_array($databases) AND count($databases) AND !( $this->request['type'] == 'database' OR $this->request['type'] == 'article' ) )
{
	$IPBHTML .= <<<HTML
		<li class='template-tag-cat'>
			{$this->lang->words['template_tag_dbs']}
		</li>
HTML;

	foreach( $databases as $database )
	{
		if( !$database['database_is_articles'] )
		{
			continue;
		}
		
		$tag	= '{parse articles}';
		
		$IPBHTML .= <<<HTML
			<li class='tag_row ipsControlRow' data-tag='{parse articles}'>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$database['database_name']}</h5>
				<p class='template-tag'>{$tag}</p>
			</li>
HTML;
	}

	foreach( $databases as $database )
	{
		if( $database['database_is_articles'] )
		{
			continue;
		}
		
		$tag	= '{parse database="' . $database['database_key'] . '"}';
		
		$IPBHTML .= <<<HTML
			<li class='tag_row ipsControlRow' data-tag='{$tag}'>
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$database['database_name']}</h5>
				<p class='template-tag'>{$tag}</p>
			</li>
HTML;
	}
}

$IPBHTML .= <<<HTML
			</ul>
		</div>
	</div>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the databases for db tag help
 *
 * @access	public
 * @param	array 		Databases
 * @return	string		HTML
 */
public function listDatabases( $databases )
{
$IPBHTML = "";
//--starthtml--//

if( is_array($databases) AND count($databases) )
{
	foreach( $databases as $database )
	{
		$IPBHTML .= <<<HTML
			<li class='tag_row database_link goto_db' title='{$database['database_description']}' data-id='{$database['database_id']}' data-type='{$this->request['type']}'>
				<h5>{$database['database_name']}</h5>
			</li>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</ul>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the database tag help
 *
 * @access	public
 * @param	array 		Database
 * @param	array 		Fields
 * @return	string		HTML
 */
public function listDatabaseTags( $database, $fields )
{
$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Databases and articles
//-----------------------------------------

$_catsHide		= ( $this->request['type'] != 1 AND $this->request['type'] != 7 ) ? "style='display: none;'" : '';
$_listHide		= ( $this->request['type'] != 2 AND $this->request['type'] != 6 ) ? "style='display: none;'" : '';
$_showHide		= ( $this->request['type'] != 3 AND $this->request['type'] != 5 ) ? "style='display: none;'" : '';

$_fpHide		= $this->request['type'] != 4 ? "style='display: none;'" : '';
//$_viewHide		= $this->request['type'] != 5 ? "style='display: none;'" : '';
//$_archHide		= $this->request['type'] != 6 ? "style='display: none;'" : '';
//$_catlHide		= $this->request['type'] != 7 ? "style='display: none;'" : '';

$_viewing			= sprintf( $this->lang->words['currently_viewing_db'], $database['database_name'] );

if( $this->request['type'] < 4 )
{
$IPBHTML .= <<<HTML
		<ul>
			<li class='tag_row database_link go_back' title='{$database['database_description']}' data-id='{$database['database_id']}' data-type='{$this->request['type']}'>
				<h5>{$this->lang->words['back_to_dbs']} {$_viewing}</h5>
			</li>
		</ul>
HTML;
}

$IPBHTML .= <<<HTML
		<ul {$_catsHide}>
			<li class='template-tag-cat'>
				{$this->lang->words['taghelp__database_tags']}
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbname']}</h5>
				<p class='template-tag'>&#36;data['database']['database_name']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['base_link']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_baselink']}</h5>
				<p class='template-tag'>&#36;data['database']['base_link']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_baselink']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_add']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canadd']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_add']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canadd']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_rate']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canrate']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_rate']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canrate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_comment']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_cancomment']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_comment']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_cancomment']}</div>
			</li>
			
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modedit']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_editc']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modeditc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_editc']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modeditc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_delete']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddel']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_delete']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddel']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_deletec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddelc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_deletec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddelc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_lock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_lock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_unlock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modunlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_unlock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modunlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_pin']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modpin']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_pin']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modpin']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modapp']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modapp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approvec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modappc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approvec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modappc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_restorer']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modrevs']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_restorer']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modrevs']}</div>
			</li>
			
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbid']}</h5>
				<p class='template-tag'>&#36;data['database']['database_id']</p>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbkey']}</h5>
				<p class='template-tag'>&#36;data['database']['database_key']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_database']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_db']}</h5>
				<p class='template-tag'>&#36;data['database']['database_database']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_db']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_desc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_description']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fields']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fields']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_record_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_records']}</h5>
				<p class='template-tag'>&#36;data['database']['database_record_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_categories']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tplc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_categories']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tplc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_listing']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpll']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_listing']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpll']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_display']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpld']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_display']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpld']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_wiki']}</h5>
				<p class='template-tag'>&#36;data['database']['database_all_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_wiki']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_open']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_revisions']}</h5>
				<p class='template-tag'>&#36;data['database']['database_revisions']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_revisions']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_ft']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_ft']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_sort']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fs']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_sort']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fs']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_direction']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_order']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_direction']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_order']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_perpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_pp']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_perpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_pp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_comment_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_comma']}</h5>
				<p class='template-tag'>&#36;data['database']['database_comment_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_comma']}</div>
			</li>
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_cats']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_catsdesc']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;category['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_cat_cid']}</h5>
				<p class='template-tag'>&#36;category['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_dbid']}</h5>
				<p class='template-tag'>&#36;category['category_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_cname']}</h5>
				<p class='template-tag'>&#36;category['category_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_cname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_parent_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_parent']}</h5>
				<p class='template-tag'>&#36;category['category_parent_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_parent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_last']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_last']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_ldate']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_ldate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_member']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmem']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_member']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmemname']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmemname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_seo_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_seoname']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_seo_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_seoname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_desc']}</h5>
				<p class='template-tag'>&#36;category['category_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_pos']}</h5>
				<p class='template-tag'>&#36;category['category_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_records']}</h5>
				<p class='template-tag'>&#36;category['category_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records_queued']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_recordsq']}</h5>
				<p class='template-tag'>&#36;category['category_records_queued']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_recordsq']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_show_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_showr']}</h5>
				<p class='template-tag'>&#36;category['category_show_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_showr']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_has_perms']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_perms']}</h5>
				<p class='template-tag'>&#36;category['category_has_perms']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_perms']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_image']}</h5>
				<p class='template-tag'>&#36;category['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_image']}</div>
			</li>
		</ul>

		<ul {$_listHide}>
			<li class='template-tag-cat'>
				{$this->lang->words['taghelp__database_tags']}
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbname']}</h5>
				<p class='template-tag'>&#36;data['database']['database_name']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['base_link']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_baselink']}</h5>
				<p class='template-tag'>&#36;data['database']['base_link']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_baselink']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_add']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canadd']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_add']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canadd']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canedit']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_rate']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canrate']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_rate']</p>
				<div class='tag_help' style='display: none'>
					{$this->lang->words['tagh_db_canrate']}

					<div class='code_sample'>
						<strong>{$this->lang->words['html_logic_sample']}</strong>
						<pre class='prettyprint'>
&lt;if test="&#36;data['database']['_can_rate']">
   &lt;p>User can rate!&lt;/p>
   &lt;!--Insert rating html here-->
&lt;/if></pre>
					</div>
				</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_comment']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_cancomment']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_comment']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_cancomment']}</div>
			</li>
			
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modedit']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_editc']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modeditc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_editc']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modeditc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_delete']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddel']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_delete']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddel']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_deletec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddelc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_deletec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddelc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_lock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_lock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_unlock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modunlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_unlock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modunlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_pin']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modpin']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_pin']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modpin']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modapp']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modapp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approvec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modappc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approvec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modappc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_restorer']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modrevs']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_restorer']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modrevs']}</div>
			</li>
			
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbid']}</h5>
				<p class='template-tag'>&#36;data['database']['database_id']</p>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbkey']}</h5>
				<p class='template-tag'>&#36;data['database']['database_key']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_database']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_db']}</h5>
				<p class='template-tag'>&#36;data['database']['database_database']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_db']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_desc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_description']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fields']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fields']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_record_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_records']}</h5>
				<p class='template-tag'>&#36;data['database']['database_record_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_categories']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tplc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_categories']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tplc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_listing']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpll']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_listing']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpll']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_display']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpld']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_display']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpld']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_wiki']}</h5>
				<p class='template-tag'>&#36;data['database']['database_all_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_wiki']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_open']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_revisions']}</h5>
				<p class='template-tag'>&#36;data['database']['database_revisions']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_revisions']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_ft']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_ft']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_sort']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fs']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_sort']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fs']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_direction']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_order']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_direction']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_order']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_perpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_pp']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_perpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_pp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_comment_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_comma']}</h5>
				<p class='template-tag'>&#36;data['database']['database_comment_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_comma']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_cats']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_subcatsdesc']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;category['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_cat_cid']}</h5>
				<p class='template-tag'>&#36;category['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_dbid']}</h5>
				<p class='template-tag'>&#36;category['category_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_cname']}</h5>
				<p class='template-tag'>&#36;category['category_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_cname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_parent_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_parent']}</h5>
				<p class='template-tag'>&#36;category['category_parent_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_parent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_last']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_last']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_ldate']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_ldate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_member']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmem']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_member']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmemname']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmemname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_last_record_seo_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_seoname']}</h5>
				<p class='template-tag'>&#36;category['category_last_record_seo_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_seoname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_desc']}</h5>
				<p class='template-tag'>&#36;category['category_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_pos']}</h5>
				<p class='template-tag'>&#36;category['category_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_records']}</h5>
				<p class='template-tag'>&#36;category['category_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records_queued']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_recordsq']}</h5>
				<p class='template-tag'>&#36;category['category_records_queued']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_recordsq']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_show_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_showr']}</h5>
				<p class='template-tag'>&#36;category['category_show_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_showr']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_has_perms']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_perms']}</h5>
				<p class='template-tag'>&#36;category['category_has_perms']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_perms']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_image']}</h5>
				<p class='template-tag'>&#36;category['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_image']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_curcat']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_curcat_desc']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['parent']['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_cat_cid']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_dbid']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_cname']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_cname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_parent_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_parent']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_parent_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_parent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_last_record_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_last']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_last_record_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_last']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_last_record_date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_ldate']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_last_record_date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_ldate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_last_record_member']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmem']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_last_record_member']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_last_record_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmemname']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_last_record_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmemname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_last_record_seo_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_seoname']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_last_record_seo_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_seoname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_desc']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_pos']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_records']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records_queued']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_recordsq']}</h5>
				<p class='template-tag'>&#36;category['category_records_queued']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_recordsq']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_show_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_showr']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_show_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_showr']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['category_has_perms']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_perms']}</h5>
				<p class='template-tag'>&#36;data['parent']['category_has_perms']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_perms']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['parent']['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_image']}</h5>
				<p class='template-tag'>&#36;data['parent']['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_image']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_fields']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_fields_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_id']}</h5>
				<p class='template-tag'>&#36;field['field_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_dbid']}</h5>
				<p class='template-tag'>&#36;field['field_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_title']}</h5>
				<p class='template-tag'>&#36;field['field_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_title']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_desc']}</h5>
				<p class='template-tag'>&#36;field['field_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_type']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_type']}</h5>
				<p class='template-tag'>&#36;field['field_type']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_type']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_required']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_req']}</h5>
				<p class='template-tag'>&#36;field['field_required']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_req']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ue']}</h5>
				<p class='template-tag'>&#36;field['field_user_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ue']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_pos']}</h5>
				<p class='template-tag'>&#36;field['field_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_max_length']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_max']}</h5>
				<p class='template-tag'>&#36;field['field_max_length']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_max']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_extra']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ex']}</h5>
				<p class='template-tag'>&#36;field['field_extra']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ex']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_html']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_html']}</h5>
				<p class='template-tag'>&#36;field['field_html']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_html']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_records']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_records_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['primary_id_field']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_id']}</h5>
				<p class='template-tag'>&#36;record['primary_id_field']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['member_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_mem']}</h5>
				<p class='template-tag'>&#36;record['member_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_mem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_saved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_saved']}</h5>
				<p class='template-tag'>&#36;record['record_saved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_saved']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_updated']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_updated']}</h5>
				<p class='template-tag'>&#36;record['record_updated']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_updated']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['post_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pk']}</h5>
				<p class='template-tag'>&#36;record['post_key']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pk']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_real']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingreal']}</h5>
				<p class='template-tag'>&#36;record['rating_real']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingreal']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_hits']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratinghits']}</h5>
				<p class='template-tag'>&#36;record['rating_hits']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratinghits']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingttl']}</h5>
				<p class='template-tag'>&#36;record['rating_value']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingttl']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="&#36;record['category_id']">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_rec_catid']}</h5>
				<p class='template-tag'>&#36;record['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_locked']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_locked']}</h5>
				<p class='template-tag'>&#36;record['record_locked']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_locked']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_comments']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_comments']}</h5>
				<p class='template-tag'>&#36;record['record_comments']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_comments']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_approved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_visible']}</h5>
				<p class='template-tag'>&#36;record['record_approved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_visible']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_pinned']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pinned']}</h5>
				<p class='template-tag'>&#36;record['record_pinned']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pinned']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_views']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_views']}</h5>
				<p class='template-tag'>&#36;record['record_views']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_views']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['_isRead']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_isread']}</h5>
				<p class='template-tag'>&#36;record['_isRead']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_isread']}</div>
			</li>
HTML;

foreach( $fields as $field )
{
	$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
	$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
	
	$IPBHTML .= <<<HTML
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['field_{$field['field_id']}']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$field['field_name']}</h5>
				<p class='template-tag'>&#36;record['field_{$field['field_id']}']</p>
				<div class='tag_help' style='display: none'>{$_field_field}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['field_{$field['field_id']}_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$field['field_name']} {$this->lang->words['field_help_formatted']}</h5>
				<p class='template-tag'>&#36;record['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;record['{$field['field_key']}']</p>
				<div class='tag_help' style='display: none'>{$_field_value}</div>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_misc']}
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['pages']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_pagelinks']}</h5>
				<p class='template-tag'>&#36;data['pages']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_pagelinks']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['show']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_show_rec']}</h5>
				<p class='template-tag'>&#36;data['show']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_show_rec']}</div>
			</li>
HTML;

if( $this->request['type'] == 6 )
{
	$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['tagh3_specialtags']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh3_specialtagsdesc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articletitle']}</h5>
				<p class='template-tag'>&#36;data['special']['title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articletitle']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['content']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecontent']}</h5>
				<p class='template-tag'>&#36;data['special']['content']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecontent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['frontpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlefp']}</h5>
				<p class='template-tag'>&#36;data['special']['frontpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlefp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articledate']}</h5>
				<p class='template-tag'>&#36;data['special']['date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articledate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['expiry']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleexpiry']}</h5>
				<p class='template-tag'>&#36;data['special']['expiry']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleexpiry']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_allowed']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleca']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_allowed']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleca']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_cutoff']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecc']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_cutoff']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleimage']}</h5>
				<p class='template-tag'>&#36;data['special']['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleimage']}</div>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
		</ul>

		<ul {$_showHide}>
			<li class='template-tag-cat'>
				{$this->lang->words['taghelp__database_tags']}
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbname']}</h5>
				<p class='template-tag'>&#36;data['database']['database_name']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['base_link']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_baselink']}</h5>
				<p class='template-tag'>&#36;data['database']['base_link']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_baselink']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_add']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canadd']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_add']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canadd']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canedit']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_rate']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canrate']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_rate']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canrate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_comment']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_cancomment']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_comment']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_cancomment']}</div>
			</li>
			
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modedit']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_editc']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modeditc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_editc']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modeditc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_delete']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddel']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_delete']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddel']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_deletec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddelc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_deletec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddelc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_lock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_lock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_unlock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modunlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_unlock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modunlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_pin']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modpin']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_pin']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modpin']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modapp']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modapp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approvec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modappc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approvec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modappc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_restorer']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modrevs']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_restorer']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modrevs']}</div>
			</li>
			
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbid']}</h5>
				<p class='template-tag'>&#36;data['database']['database_id']</p>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbkey']}</h5>
				<p class='template-tag'>&#36;data['database']['database_key']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_database']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_db']}</h5>
				<p class='template-tag'>&#36;data['database']['database_database']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_db']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_desc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_description']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fields']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fields']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_record_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_records']}</h5>
				<p class='template-tag'>&#36;data['database']['database_record_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_categories']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tplc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_categories']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tplc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_listing']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpll']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_listing']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpll']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_display']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpld']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_display']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpld']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_wiki']}</h5>
				<p class='template-tag'>&#36;data['database']['database_all_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_wiki']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_open']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_revisions']}</h5>
				<p class='template-tag'>&#36;data['database']['database_revisions']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_revisions']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_ft']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_ft']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_sort']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fs']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_sort']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fs']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_direction']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_order']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_direction']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_order']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_perpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_pp']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_perpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_pp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_comment_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_comma']}</h5>
				<p class='template-tag'>&#36;data['database']['database_comment_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_comma']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_fields']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_fields_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_id']}</h5>
				<p class='template-tag'>&#36;field['field_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_dbid']}</h5>
				<p class='template-tag'>&#36;field['field_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_title']}</h5>
				<p class='template-tag'>&#36;field['field_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_title']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_desc']}</h5>
				<p class='template-tag'>&#36;field['field_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_type']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_type']}</h5>
				<p class='template-tag'>&#36;field['field_type']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_type']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_required']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_req']}</h5>
				<p class='template-tag'>&#36;field['field_required']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_req']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ue']}</h5>
				<p class='template-tag'>&#36;field['field_user_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ue']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_pos']}</h5>
				<p class='template-tag'>&#36;field['field_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_max_length']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_max']}</h5>
				<p class='template-tag'>&#36;field['field_max_length']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_max']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_extra']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ex']}</h5>
				<p class='template-tag'>&#36;field['field_extra']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ex']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_html']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_html']}</h5>
				<p class='template-tag'>&#36;field['field_html']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_html']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_records']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_record_view']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['primary_id_field']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_id']}</h5>
				<p class='template-tag'>&#36;data['record']['primary_id_field']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['member_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_mem']}</h5>
				<p class='template-tag'>&#36;data['record']['member_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_mem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_saved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_saved']}</h5>
				<p class='template-tag'>&#36;data['record']['record_saved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_saved']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_updated']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_updated']}</h5>
				<p class='template-tag'>&#36;data['record']['record_updated']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_updated']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['post_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pk']}</h5>
				<p class='template-tag'>&#36;data['record']['post_key']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pk']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['rating_real']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingreal']}</h5>
				<p class='template-tag'>&#36;data['record']['rating_real']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingreal']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['rating_hits']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratinghits']}</h5>
				<p class='template-tag'>&#36;data['record']['rating_hits']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratinghits']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['rating_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingttl']}</h5>
				<p class='template-tag'>&#36;data['record']['rating_value']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingttl']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_catid']}</h5>
				<p class='template-tag'>&#36;data['record']['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_locked']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_locked']}</h5>
				<p class='template-tag'>&#36;data['record']['record_locked']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_locked']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_comments']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_comments']}</h5>
				<p class='template-tag'>&#36;data['record']['record_comments']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_comments']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_approved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_visible']}</h5>
				<p class='template-tag'>&#36;data['record']['record_approved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_visible']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_pinned']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pinned']}</h5>
				<p class='template-tag'>&#36;data['record']['record_pinned']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pinned']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['record']['record_views']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_views']}</h5>
				<p class='template-tag'>&#36;data['record']['record_views']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_views']}</div>
			</li>
HTML;

foreach( $fields as $field )
{
	$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
	$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
	
	$IPBHTML .= <<<HTML
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['record']['field_{$field['field_id']}']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$field['field_name']}</h5>
				<p class='template-tag'>&#36;data['record']['field_{$field['field_id']}']</p>
				<div class='tag_help' style='display: none'>{$_field_field}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['record']['field_{$field['field_id']}_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$field['field_name']} {$this->lang->words['field_help_formatted']}</h5>
				<p class='template-tag'>&#36;data['record']['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;data['record']['{$field['field_key']}']</p>
				<div class='tag_help' style='display: none'>{$_field_value}</div>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_misc']}
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['comments']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_comments']}</h5>
				<p class='template-tag'>&#36;data['comments']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_comments']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['follow_data']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_follow_data']}</h5>
				<p class='template-tag'>&#36;data['follow_data']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_follow_data']}</div>
			</li>
HTML;

if( $this->request['type'] == 5 )
{
	$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['tagh3_specialtags']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh3_specialtagsdesc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articletitle']}</h5>
				<p class='template-tag'>&#36;data['special']['title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articletitle']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['content']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecontent']}</h5>
				<p class='template-tag'>&#36;data['special']['content']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecontent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['frontpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlefp']}</h5>
				<p class='template-tag'>&#36;data['special']['frontpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlefp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articledate']}</h5>
				<p class='template-tag'>&#36;data['special']['date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articledate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['expiry']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleexpiry']}</h5>
				<p class='template-tag'>&#36;data['special']['expiry']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleexpiry']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_allowed']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleca']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_allowed']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleca']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_cutoff']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecc']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_cutoff']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleimage']}</h5>
				<p class='template-tag'>&#36;data['special']['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleimage']}</div>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
		</ul>

		<ul {$_fpHide}>
			<li class='template-tag-cat'>
				{$this->lang->words['taghelp__database_tags']}
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbname']}</h5>
				<p class='template-tag'>&#36;data['database']['database_name']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['base_link']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_baselink']}</h5>
				<p class='template-tag'>&#36;data['database']['base_link']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_baselink']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_add']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canadd']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_add']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canadd']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canedit']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_rate']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_canrate']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_rate']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_canrate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['_can_comment']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_cancomment']}</h5>
				<p class='template-tag'>&#36;data['database']['_can_comment']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_cancomment']}</div>
			</li>
			
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_edit']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modedit']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_edit']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modedit']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_delete']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddel']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_delete']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddel']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_deletec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_moddelc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_deletec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_moddelc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_lock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_lock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_unlock']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modunlock']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_unlock']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modunlock']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_pin']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modpin']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_pin']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modpin']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modapp']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modapp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['moderate_approvec']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_modappc']}</h5>
				<p class='template-tag'>&#36;data['database']['moderate_approvec']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_modappc']}</div>
			</li>
			
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbid']}</h5>
				<p class='template-tag'>&#36;data['database']['database_id']</p>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_dbkey']}</h5>
				<p class='template-tag'>&#36;data['database']['database_key']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_database']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_db']}</h5>
				<p class='template-tag'>&#36;data['database']['database_database']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_db']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['database']['database_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_db_desc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_description']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fields']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fields']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_record_count']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_records']}</h5>
				<p class='template-tag'>&#36;data['database']['database_record_count']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_categories']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tplc']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_categories']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tplc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_listing']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpll']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_listing']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpll']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_template_display']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_tpld']}</h5>
				<p class='template-tag'>&#36;data['database']['database_template_display']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_tpld']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_wiki']}</h5>
				<p class='template-tag'>&#36;data['database']['database_all_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_wiki']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_open']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_revisions']}</h5>
				<p class='template-tag'>&#36;data['database']['database_revisions']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_revisions']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_ft']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_ft']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_sort']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_fs']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_sort']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_fs']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_direction']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_order']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_direction']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_order']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_field_perpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_pp']}</h5>
				<p class='template-tag'>&#36;data['database']['database_field_perpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_pp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['database']['database_comment_approve']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_db_comma']}</h5>
				<p class='template-tag'>&#36;data['database']['database_comment_approve']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_db_comma']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__categoriesfp']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__categoriesfpdesc']}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;data['category']['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$this->lang->words['tagt_cat_cid']}</h5>
				<p class='template-tag'>&#36;data['category']['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_dbid']}</h5>
				<p class='template-tag'>&#36;data['category']['category_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_cname']}</h5>
				<p class='template-tag'>&#36;data['category']['category_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_cname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_parent_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_parent']}</h5>
				<p class='template-tag'>&#36;data['category']['category_parent_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_parent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_last_record_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_last']}</h5>
				<p class='template-tag'>&#36;data['category']['category_last_record_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_last']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_last_record_date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_ldate']}</h5>
				<p class='template-tag'>&#36;data['category']['category_last_record_date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_ldate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_last_record_member']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmem']}</h5>
				<p class='template-tag'>&#36;data['category']['category_last_record_member']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_last_record_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_lmemname']}</h5>
				<p class='template-tag'>&#36;data['category']['category_last_record_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_lmemname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_last_record_seo_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_seoname']}</h5>
				<p class='template-tag'>&#36;data['category']['category_last_record_seo_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_seoname']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_desc']}</h5>
				<p class='template-tag'>&#36;data['category']['category_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_pos']}</h5>
				<p class='template-tag'>&#36;data['category']['category_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_records']}</h5>
				<p class='template-tag'>&#36;data['category']['category_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_records']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;category['category_records_queued']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_recordsq']}</h5>
				<p class='template-tag'>&#36;category['category_records_queued']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_recordsq']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_show_records']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_showr']}</h5>
				<p class='template-tag'>&#36;data['category']['category_show_records']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_showr']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['category_has_perms']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_perms']}</h5>
				<p class='template-tag'>&#36;data['category']['category_has_perms']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_perms']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['category']['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_cat_image']}</h5>
				<p class='template-tag'>&#36;data['category']['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_cat_image']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_fields']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_fields_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_id']}</h5>
				<p class='template-tag'>&#36;field['field_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_database_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_dbid']}</h5>
				<p class='template-tag'>&#36;field['field_database_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_dbid']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_name']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_title']}</h5>
				<p class='template-tag'>&#36;field['field_name']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_title']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_description']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_desc']}</h5>
				<p class='template-tag'>&#36;field['field_description']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_type']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_type']}</h5>
				<p class='template-tag'>&#36;field['field_type']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_type']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_required']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_req']}</h5>
				<p class='template-tag'>&#36;field['field_required']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_req']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_user_editable']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ue']}</h5>
				<p class='template-tag'>&#36;field['field_user_editable']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ue']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_position']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_pos']}</h5>
				<p class='template-tag'>&#36;field['field_position']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_pos']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_max_length']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_max']}</h5>
				<p class='template-tag'>&#36;field['field_max_length']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_max']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_extra']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_ex']}</h5>
				<p class='template-tag'>&#36;field['field_extra']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_ex']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;field['field_html']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_field_html']}</h5>
				<p class='template-tag'>&#36;field['field_html']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_field_html']}</div>
			</li>
			
			<li class='template-tag-cat'>
				{$this->lang->words['tagh__database_records']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh__database_records_desc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['primary_id_field']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_id']}</h5>
				<p class='template-tag'>&#36;record['primary_id_field']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_id']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['member_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_mem']}</h5>
				<p class='template-tag'>&#36;record['member_id']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_mem']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_saved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_saved']}</h5>
				<p class='template-tag'>&#36;record['record_saved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_saved']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_updated']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_updated']}</h5>
				<p class='template-tag'>&#36;record['record_updated']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_updated']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['post_key']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pk']}</h5>
				<p class='template-tag'>&#36;record['post_key']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pk']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_real']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingreal']}</h5>
				<p class='template-tag'>&#36;record['rating_real']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingreal']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_hits']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratinghits']}</h5>
				<p class='template-tag'>&#36;record['rating_hits']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratinghits']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['rating_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_ratingttl']}</h5>
				<p class='template-tag'>&#36;record['rating_value']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_ratingttl']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['category_id']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_catid']}</h5>
				<p class='template-tag'>&#36;record['category_id']</p>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_locked']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_locked']}</h5>
				<p class='template-tag'>&#36;record['record_locked']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_locked']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_comments']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_comments']}</h5>
				<p class='template-tag'>&#36;record['record_comments']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_comments']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_approved']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_visible']}</h5>
				<p class='template-tag'>&#36;record['record_approved']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_visible']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_pinned']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_pinned']}</h5>
				<p class='template-tag'>&#36;record['record_pinned']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_pinned']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['record_views']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_views']}</h5>
				<p class='template-tag'>&#36;record['record_views']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_views']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;record['_isRead']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_rec_isread']}</h5>
				<p class='template-tag'>&#36;record['_isRead']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_rec_isread']}</div>
			</li>
HTML;

foreach( $fields as $field )
{
	$_field_field	= sprintf( $this->lang->words['tagh_rec_fieldf'], $field['field_name'] );
	$_field_value	= sprintf( $this->lang->words['tagh_rec_fieldv'], $field['field_name'] );
	
	$IPBHTML .= <<<HTML
			<li class='tag_row ipsControlRow' data-tag="{&#36;record['field_{$field['field_id']}']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$field['field_name']}</h5>
				<p class='template-tag'>&#36;record['field_{$field['field_id']}']</p>
				<div class='tag_help' style='display: none'>{$_field_field}</div>
			</li>
			<li class='tag_row ipsControlRow' data-tag="{&#36;record['field_{$field['field_id']}_value']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<h5>{$field['field_name']} {$this->lang->words['field_help_formatted']}</h5>
				<p class='template-tag'>&#36;record['field_{$field['field_id']}_value'] &nbsp;&nbsp;<u>{$this->lang->words['or']}</u>&nbsp;&nbsp; &#36;record['{$field['field_key']}']</p>
				<div class='tag_help' style='display: none'>{$_field_value}</div>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$this->lang->words['tagh3_specialtags']} <div class='tag_help' style='display: none'>{$this->lang->words['tagh3_specialtagsdesc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['title']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articletitle']}</h5>
				<p class='template-tag'>&#36;data['special']['title']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articletitle']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['content']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecontent']}</h5>
				<p class='template-tag'>&#36;data['special']['content']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecontent']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['frontpage']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlefp']}</h5>
				<p class='template-tag'>&#36;data['special']['frontpage']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlefp']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['date']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articledate']}</h5>
				<p class='template-tag'>&#36;data['special']['date']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articledate']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['expiry']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleexpiry']}</h5>
				<p class='template-tag'>&#36;data['special']['expiry']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleexpiry']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_allowed']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleca']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_allowed']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleca']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['comments_cutoff']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articlecc']}</h5>
				<p class='template-tag'>&#36;data['special']['comments_cutoff']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articlecc']}</div>
			</li>
			<li class='tag_row expandable closed ipsControlRow' data-tag="{&#36;data['special']['image']}">
				<ul class='ipsControlStrip'>
					<li class='i_add'><a href='#' title='{$this->lang->words['insert']}' class='insert_tag'>{$this->lang->words['insert']}</a></li>
				</ul>
				<a href='#' class='tag_toggle'>{$this->lang->words['toggle']}</a>
				<h5>{$this->lang->words['tagt_articleimage']}</h5>
				<p class='template-tag'>&#36;data['special']['image']</p>
				<div class='tag_help' style='display: none'>{$this->lang->words['tagh_articleimage']}</div>
			</li>
		</ul>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to add or edit a block template
 *
 * @access	public
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function blockTemplateForm( $title, $code='doadd', $form )
{
$IPBHTML = "";
//--starthtml--//
$_class1	= IPSCookie::get('hideContentHelp') ? "" : "withSidebar";
$_class2	= IPSCookie::get('hideContentHelp') ? " closed" : "";

$templateTags	= $this->registry->ccsAcpFunctions->getBlockTags( '', '' );

if( $form['protected'] )
{
	$form['protected'] = "&nbsp;&nbsp;&nbsp;" . $form['protected'] . " <label for='tpb_protected'>{$this->lang->words['bt_make_protected']}</label>";
}

$IPBHTML .= <<<HTML
<link href="{$this->settings['public_dir']}style_css/prettify.css" type="text/css" rel="stylesheet" />
<script type='text/javascript'>
	ipb.lang['insert_tag']	= '{$this->lang->words['insert']}';
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.tagsidebar.js'></script>

<div class='section_title'>
	<h2>{$title}</h2>
HTML;

if( $this->request['duplicate'] )
{
	$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<div class='pad'>
			{$this->lang->words['duplicate_block_helptext']}
		</div>
	</div><br />
HTML;
}

$IPBHTML .= <<<HTML
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$code}' method='post' enctype='multipart/form-data'>
	<input type='hidden' name='template' value='{$this->request['template']}' />
	<input type='hidden' name='duplicate' value='{$this->request['duplicate']}' />
	<input type='hidden' name='_assetdir' value='{$form['_assetdir']}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['template_form_header']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['btemplate_title']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field'>
					{$form['name']} {$form['protected']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['btemplate_desc']}</strong>
				</td>
				<td class='field_field'>
					{$form['description']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['btemplate_type']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field btemplate_dd'>
					{$form['app_types']} &nbsp;&nbsp;<img src='{$this->settings['skin_acp_url']}/images/rarrow.png' />&nbsp;&nbsp; {$form['content_types']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['btemplate_image']}</strong>
				</td>
				<td class='field_field'>
HTML;
			
			if( ( $code == 'doedit' OR $this->request['duplicate'] ) && $form['image'] )
			{
				$IPBHTML .= "<img src='{$form['image']}' class='left' style='margin-right: 10px; width: 150px; height: 100px;' />";
			}

			$IPBHTML .= "<div>";

			if( ( $code == 'doedit' OR $this->request['duplicate'] ) && $form['image'] )
			{
				$IPBHTML .= "<input type='checkbox' value='1' name='remove_thumb' id='remove_thumb' /> <label for='remove_thumb'>{$this->lang->words['bt_remove_image']}</label><br /><br />";
			}
			
			$IPBHTML .= <<<HTML
						{$form['upload']}<br />
						<span class='desctext'>{$this->lang->words['btemplate_image_desc']}</span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan='2' class='sidebarContainer'>
HTML;
					if( $form['assets_dir'] )
					{
						$IPBHTML .= "<div class='information-box'>{$this->lang->words['btemplate_asset_msg']} /" . $form['assets_dir'] . "/</div><br />";
					}

					$IPBHTML .= <<<HTML
					<div id="template-tags" class="templateTags-container{$_class2}">{$templateTags}</div>
					<div id='content-label' class="{$_class1}">{$form['content']}</div>
					{$_diff}
				</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
			<input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']}' class="button primary" />
			<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=templates&amp;section=blocks';" value='{$this->lang->words['button__cancel']}' />
		</div>
	</div>
</form>
<script type='text/javascript'>
	var appTypes = {};

	$('tpb_app_types').observe('change', function(e){
		var val = $('tpb_app_types').value;

		if( val == '' ){
			$('tpb_content_types').update('').disable();

			acp.ccs.loadBlockTemplateTags();
		}
		else
		{
			if( appTypes[ val ] ){
				$('tpb_content_types').update( buildOptions(appTypes[ val ]) ).enable();

				acp.ccs.loadBlockTemplateTags();
			}
			else
			{
				var req	= ipb.vars['base_url'] + "app=ccs&module=ajax&section=templates&do=getcontenttypes&type=" + val;
				
				Debug.write( req.replace(/&amp;/g, '&') + '&secure_key=' + ipb.vars['md5_hash'] );

				new Ajax.Request(	req.replace(/&amp;/g, "&"),
									{
										method: 'post',
										parameters: { secure_key: ipb.vars['md5_hash'] },
										onSuccess: function(t)
										{
											if( t.responseJSON['error'] )
											{
												alert( t.responseJSON['error'] );
												return;
											}

											$('tpb_content_types').update( buildOptions( t.responseJSON ) ).enable();
											appTypes[ val ] = t.responseJSON;

											acp.ccs.loadBlockTemplateTags();
										}
									});
			}
		}
	});

	function buildOptions( opts )
	{
		var html = '';

		for( i=0; i < opts.length; i++ )
		{
			html += "<option value='" + opts[i][0] + "'>" + opts[i][1] + "</option>";
		}

		return html;
	}

	acp.ccs.loadBlockTemplateTags();
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * List block templates
 *
 * @access	public
 * @param	array		Array of blocks
 * @return	string		HTML
 */
public function blockTemplateList( $groups, $groupNames )
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['block_template_title']} <a class='help_blurb' href='#'>{$this->lang->words['inlinehelp_blurblink']}</a></h2>
	<div class='acp-box' id='help_blurb_text' style='display:none;'>
		<div class='pad'>
			{$this->lang->words['block_templates_helpblurb']}
		</div>
	</div>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_btemplate_button']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['add_btemplate_button']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='#' id='import_btemplate' title='{$this->lang->words['import_btemplate']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['import_btemplate']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recachejs&amp;rtype={$this->request['type']}' title='{$this->lang->words['rebuild_btemplate']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='{$this->lang->words['icon']}' />
					{$this->lang->words['rebuild_btemplate']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='#' title='{$this->lang->words['bt_revert_all_dev']}' class='ipbmenu' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=revert&amp;revert=_all_', '{$this->lang->words['confirm_revert_all']}' );">
					<img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='{$this->lang->words['bt_revert_all_dev']}' />
					{$this->lang->words['bt_revert_all_dev']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=export&amp;export=_defaults_' title='{$this->lang->words['export_block']}' class='ipbmenu'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/plugin.png' alt='{$this->lang->words['export_block']}' />
					{$this->lang->words['bt_export_all_dev']}
				</a>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['btemplate_list_title']}</h3>
	<div id='tabstrip_btemplates' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
HTML;

	foreach( $groupNames as $key => $value )
	{
		$IPBHTML .= "<li id='tab_{$key}'>{$value} <span class='desctext'>(" . count( $groups[ $key ] ) . ")</span></li> ";
	}

	$IPBHTML .= <<<HTML
		</ul>
	</div>
	<div id='tabstrip_btemplates_content' class='ipsTabBar_content'>
HTML;

	foreach( $groupNames as $key => $value )
	{
		$IPBHTML .= <<<HTML
		<div id='tab_{$key}_content'>
			<div class='block_template_manager'>
HTML;
			if( count( $groups[ $key ] ) )
			{
				$IPBHTML .= <<<HTML
				<ul class='clearfix'>
HTML;
				foreach( $groups[ $key ] as $block )
				{
					$protected = "";
					$block['tpb_desc'] = str_replace("'", "&#39;", $block['tpb_desc']);

					if( !$block['tpb_image'] )
					{
						$block['tpb_image'] = $this->settings['upload_url'] . '/content_templates/default.png';
					}

					$revisions		= sprintf( $this->lang->words['template_managerevisions'], $block['revisions'] );

					$IPBHTML .= <<<HTML
					<li class='block_template_item ipsControlRow'>
HTML;

					if( $block['tpb_protected'] AND !IN_DEV )
					{
						$IPBHTML .= <<<HTML
						<a href='#' title='{$block['tpb_human_name']} - {$block['tpb_desc']}' id='duplicate_{$block['tpb_id']}' class='duplicate_block'>
HTML;
					}
					else
					{
						$IPBHTML .= <<<HTML
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$block['tpb_id']}' title='{$block['tpb_human_name']} - {$block['tpb_desc']}'>
HTML;
					}

					$IPBHTML .= <<<HTML
							<img src='{$block['tpb_image']}' />
						</a>
						<br />
HTML;

					if( !$block['tpb_protected'] || IN_DEV )
					{
						$IPBHTML .= <<<HTML
						<ul class='ipsControlStrip'>
							<li class='ipsControlStrip_more ipbmenu' id='btemplate_{$block['tpb_id']}'>
								<a href='#'>{$this->lang->words['folder_options_alt']}</a>
							</li>
						</ul>
						<ul class='acp-menu' id='btemplate_{$block['tpb_id']}_menucontent' style='display: none'>
HTML;
						if( $block['tpb_protected'] )
						{
							$IPBHTML .= <<<HTML
								<li class='icon refresh'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=revert&amp;tpb_id={$block['tpb_id']}', '{$this->lang->words['confirm_revert']}' );">{$this->lang->words['btemplate_revert']}</a></li>
HTML;
						}

						$protected	= '';

						if( IN_DEV AND $block['tpb_protected'])
						{
							$protected = " {$this->lang->words['blocktemplate_protectedsuffix']}";
						}

						$IPBHTML .= <<<HTML
							<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;tpb_id={$block['tpb_id']}' );">{$this->lang->words['delete_template__link']}{$protected}</a></li>
HTML;

						$IPBHTML .= <<<HTML
							<li class='icon export'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=export&amp;tpb_id={$block['tpb_id']}'>{$this->lang->words['export_single_template']}</a></li>
							<li class='icon view'><a href='{$this->settings['base_url']}module=templates&amp;section=revisions&amp;ttype=blocks&amp;type=blocktemplate&amp;id={$block['tpb_id']}'>{$revisions}</a></li>
						</ul>
HTML;
					}

					$IPBHTML .= <<<HTML
						<h5 title='{$block['tpb_human_name']} - {$block['tpb_desc']}'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$block['tpb_id']}'>{$block['tpb_human_name']}</a></h5><br />
						<span class='desctext'>({$block['used_count']} {$this->lang->words['btemplate_count']})</span>
					</li>
HTML;
				}

				$IPBHTML .= <<<HTML
					<li class='block_template_add'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;tpb_app_types={$key}'>
							<img src='{$this->settings['skin_app_url']}images/add_btemplate.png' />
						</a>
						<br />
						<h5><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;tpb_app_types={$key}'>{$this->lang->words['add_btemplate_short']}</a></h5>
					</li>
				</ul>
HTML;
			}
			else
			{
				$IPBHTML .= <<<HTML
				<ul class='clearfix'>
					<li class='block_template_add'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;tpb_app_types={$key}'>
							<img src='{$this->settings['skin_app_url']}images/add_btemplate.png' />
						</a>
						<br />
						<h5><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add&amp;tpb_app_types={$key}'>{$this->lang->words['add_btemplate_short']}</a></h5>
					</li>
					
				</ul>
HTML;
			}

			$IPBHTML .= <<<HTML
			</div>
		</div>
HTML;
	}

	$IPBHTML .= <<<HTML
	</div>
</div>
HTML;

if( $this->request['type'] )
{
	if( $this->request['type'] == '*' )
	{
		$_defaultTab = ", defaultTab: 'tab___all'";
	}
	else
	{
		$_defaultTab = ", defaultTab: 'tab_{$this->request['type']}'";
	}
}

$IPBHTML .= <<<HTML
<script type='text/javascript'>
	jQ("#tabstrip_btemplates").ipsTabBar({tabWrap: "#tabstrip_btemplates_content" {$_defaultTab} });

	ipb.templates['install_btemplate'] = new Template("<div class='acp-box'><h3 class='ipsBlock_title'>{$this->lang->words['import_btemplate']}</h3><form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=import' enctype='multipart/form-data'><table class='ipsTable double_pad'><tr><td class='field_title'><strong class='title'>{$this->lang->words['import_btemplate_xml']}</strong></td><td class='field_field'><input type='file' name='FILE_UPLOAD' /></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['import_btemplate']}' class='button primary' /></div>");
	ipb.templates['duplicate_block'] = new Template( "<div class='acp-box'><h3 class='ipsBlock_title'>{$this->lang->words['duplicate_template_popt']}</h3> <div class='ipsPad'>{$this->lang->words['duplicate_bt_popdesc']} <div class='center'><input type='button' onclick=\"window.location='{$this->settings['base_url']}{$this->form_code_js}&do=add&duplicate=#{block_id}';\" value='{$this->lang->words['duplicate_btbutton']}' class='button primary' /></div> </div>");

	function install_popup(e){
		Event.stop(e);
		if ( ! Object.isUndefined(acp.ccs.popups['install']) ){
			acp.ccs.popups['install'].show();
		} else {
			acp.ccs.popups['install'] = new ipb.Popup( 'install', {
																	type: 'modal',
																	initial: ipb.templates['install_btemplate'].evaluate(),
																	w: '550px',
																	stem: false,
																	hideAtStart: false
																});
		}
	}

	ipb.delegate.register("#import_btemplate", install_popup);
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Offer an option when deleting: update to new template, or copy to blocks
 *
 * @access	public
 * @param	array		Array of blocks
 * @return	string		HTML
 */
public function deleteTemplateForm( $count, $id, $alternatives )
{
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['delete_btemplate_title']}</h2>
</div>
<div class='information-box'>
	{$this->lang->words['delete_btemplate_message']}
</div>
<br />

<form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=predelete' enctype='multipart/form-data'>
	<input type='hidden' name='tpb_id' value='{$id}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['delete_btemplate_action']}</h3>
		<table class='ipsTable no_background'>
			<tr>
				<td>
					<input type='radio' name='type' value='update' id='update_update' /> &nbsp;&nbsp;<label for='update_update'>{$this->lang->words['delete_btemplate_update']}</label>&nbsp;&nbsp;
HTML;
					if( count( $alternatives ) === 1 )
					{
						$IPBHTML .= "<strong>{$alternatives[0]['tpb_human_name']}</strong><input type='hidden' name='update_to' value='{$alternatives[0]['tpb_id']}' />";
					}
					else
					{
						$IPBHTML .= "<select name='update_to'>";

						foreach( $alternatives as $alt )
						{
							$IPBHTML .= "<option value='{$alt['tpb_id']}'>{$alt['tpb_human_name']}</option>";
						}
						
						$IPBHTML .= "</select>";
					}

$IPBHTML .= <<<HTML
				</td>
			</tr>
			<tr>
				<td>
					<input type='radio' name='type' value='copy' /> &nbsp;&nbsp;{$this->lang->words['delete_btemplate_copy']}
				</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['delete_btemplate_continue']}' class="button primary" />
		</div>
	</div>
</form>		
HTML;
//--endhtml--//
return $IPBHTML;
}

}