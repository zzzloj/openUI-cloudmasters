<?php
/**
 * <pre>
 * Invision Power Services
 * Pages skin file
 * Last Updated: $Date: 2012-02-06 15:39:43 -0500 (Mon, 06 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10256 $
 */
 
class cp_skin_pages
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
 * Easy pages form (like CSS and JS)
 *
 * @access	public
 * @param	string		Form type (add|edit)
 * @param	string		Content type (css|js)
 * @param	array 		Page data
 * @return	string		HTML
 */
public function easyPageForm( $formType, $contentType, $page, $folders )
{
$IPBHTML = "";
//--starthtml--//

$url			= ( is_array($page) AND count($page) ) ? $this->registry->ccsFunctions->returnPageUrl( $page ) : '';

//-----------------------------------------
// Hopefully they don't name something "file.css.css" on purpose
//-----------------------------------------

$page['page_seo_name']	= str_replace( array( '.js', '.css' ), '', $page['page_seo_name'] );

$extension		= $formType == 'edit' ? $page['page_content_type'] : $this->request['fileType'];
$title			= $formType == 'edit' ? sprintf( $this->lang->words['edit_content_type_title'], $page['page_folder'], $page['page_seo_name'] . '.' . $extension ) : $this->lang->words['add_content_type_title'];

$seoName		= $this->registry->output->formInput( 'page_seo_name', $page['page_seo_name'] );
$inFolder		= urlencode( $this->request['in'] );

$_folders = array();

foreach( $folders as $id => $folder )
{
	$_folders[ $id ] = array( $folder[0], ltrim( $folder[1], '/' ) . '/' );
}

if( count($_folders) )
{
	$folders		=  array_merge( array( array( '', '' ) ), $_folders );
	$folder			= $this->registry->output->formDropdown( 'page_folder', $folders, $page['page_folder'] ? $page['page_folder'] : $this->request['in'] );
}
else
{
	$folder			= '';
	$this->lang->words['page_filename_desc']	= $this->lang->words['page_filename_descsf'];
}

$editor_area	= $this->registry->output->formTextarea( "content", IPSText::htmlspecialchars( $page['page_content'] ), 100, 30, "content", "style='width:100%;'" );

$urlBit			= $this->registry->ccsFunctions->returnUrlPreview();
$sep			= $urlBit['furl'] ? '/' : '';
$sep1			= $urlBit['furl'] ? '' : '&amp;page=';

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$title}
HTML;

if( $url )
{
	$IPBHTML .= <<<HTML
	<span class='view-page'><a href='{$url}' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>
HTML;
}

$IPBHTML .= <<<HTML
	</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveEasyPage' method='post' id='adform' name='adform'>
<input type='hidden' name='type' value='{$formType}' />
<input type='hidden' name='content_type' value='{$contentType}' />
<input type='hidden' name='page' value='{$page['page_id']}' />

<div class='acp-box'>
	<h3>{$this->lang->words['config_content_header']}</h3>

	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['enter_page_filename']}</strong></td>
			<td class='field_field'>
				{$urlBit['url']}{$sep} {$folder} {$sep1} {$seoName} .{$extension}
				<br />
				<span class='desctext'>{$this->lang->words['page_filename_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['editor_content_for_ct']}</strong></td>
			<td class='field_field'><div id='content-label'>{$editor_area}</div></td>
		</tr>
	</table>

	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" /> 
		<input type='submit' name='save_and_reload' value='{$this->lang->words['button__reload']}' class="button primary" /> 
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=pages&amp;section=list&amp;do=viewdir&amp;dir={$inFolder}';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 1
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_1( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$pageName	= $this->registry->output->formInput( 'name', $session['wizard_name'] );
$pageTitle	= $this->registry->output->formInput( 'title', $session['wizard_page_title'] );
$seoName	= $this->registry->output->formInput( 'page_name', $session['wizard_seo_name'] );
$metak		= $this->registry->output->formTextarea( 'meta_keywords', $session['wizard_meta_keywords'], 50, 2 );
$metad		= $this->registry->output->formTextarea( 'meta_description', $session['wizard_meta_description'], 50, 2 );

$_folders = array();

foreach( $additional['folders'] as $id => $folder )
{
	$_folders[ $id ] = array( $folder[0], ltrim( $folder[1], '/' ) . '/' );
}

if( count($_folders) )
{
	$folders	=  array_merge( array( array( '', '' ) ), $_folders );
	$folder		= $this->registry->output->formDropdown( 'folder', $folders, $this->request['in'] ? urldecode($this->request['in']) : $session['wizard_folder'] );
}
else
{
	$folder			= '';
	$this->lang->words['page_filename_desc']	= $this->lang->words['page_filename_descsf'];
}

$pageTypes	= array( array( 'bbcode', $this->lang->words['pages__bbcode'] ), array( 'html', $this->lang->words['pages__html'] ), array( 'php', $this->lang->words['pages__php'] ) );
$pageType	= $this->registry->output->formDropdown( 'type', $pageTypes, $session['wizard_type'] ? $session['wizard_type'] : 'html' );

$templates	= array_merge( array( array( 'none', $this->lang->words['generic__none'], '_root_' ) ), $additional['templates'] );
$template	= $this->registry->output->formDropdown( 'template', $templates, $session['wizard_template'], null, null, null, $additional['categories'] );
$_onlyHide	= ( intval($session['wizard_template']) < 1 ) ? "display: none;" : '';

$content 	= $this->registry->output->formYesNo( 'content_only', $session['wizard_content_only'] );
$omitName 	= $this->registry->output->formCheckbox( 'omit_pagename', $session['wizard_omit_filename'], '1', 'omit_page_check' );
$ipbwrapper	= $this->registry->output->formYesNo( 'ipb_wrapper', $session['wizard_ipb_wrapper'] );
$allMasks 	= $this->registry->output->formYesNo( 'all_masks', $additional['all_masks'] );
$masks		= $this->registry->output->formMultiDropdown( 'masks[]', $additional['avail_masks'], $additional['masks'], 8 );
$_hide		= $additional['all_masks'] ? "display:none;" : '';

$steps = $this->wizard_stepbar( 1, $sessionId, (bool) $session['wizard_edit_id'] );

$_edit	= $session['wizard_edit_id'] ? $this->wizard_savebuttons( $sessionId ) : '';

if( !$_edit )
{
	$_edit = <<<EOF
<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=pages&amp;section=list&amp;do=viewdir&amp;dir={$this->request['in']}';" value='{$this->lang->words['button__cancel']}' />
EOF;
}

$page	= array();

foreach( $session as $k => $v )
{
	$page[ str_replace( 'wizard_', 'page_', $k ) ]	= $v;
}

$page['page_id']	= $page['page_edit_id'];

$url	= $this->registry->ccsFunctions->returnUrlPreview();
$sep	= $url['furl'] ? '/' : '';
$sep1	= $url['furl'] ? '' : '&amp;page=';

$this->lang->words['omit_name_url_desc']	= sprintf( $this->lang->words['omit_name_url_desc'], $this->settings['ccs_default_page'] );

$_link	= $session['wizard_edit_id'] ? " <span class='view-page'><a href='" . $this->registry->ccsFunctions->returnPageUrl( $page ) . "' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>" : '';
$_cptb	= $session['wizard_page_title'] ? "" : " checked='checked'";
$_spqn	= $session['wizard_page_quicknav'] ? " checked='checked'" : '';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['page_wizard']} {$_link}</h2>
</div>

<script type='text/javascript'>
	var defaultPageName = "{$this->settings['ccs_default_page']}";
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
	<input type='hidden' name='step' value='1' />
	<div class='ipsSteps_wrap'>
		{$steps}
		<div class='ipsSteps_wrapper'>
			<div class='steps_content'>
				<div class='acp-box'>
					<h3>{$this->lang->words['create_page_header']}</h3>
					<table class='ipsTable double_pad'>
						<tr>
							<th colspan='2'>{$this->lang->words['tab__details']}</th>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['give_page_name']}</strong></td>
							<td class='field_field'>
								{$pageName} 
								&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' id='page_name_as_title'{$_cptb} value='1' name='page_name_as_title' /> {$this->lang->words['pagenameastitle']}
								&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' id='page_quicknav' value='1'{$_spqn} name='page_quicknav' /> {$this->lang->words['show_page_in_quicknav']}
								<div id='page_title'>
									{$this->lang->words['page_title_ft']}: {$pageTitle}
									<div class='desctext'>{$this->lang->words['pagenametitlehelptext']}</div>
								</div>
							</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['enter_page_filename']}</strong></td>
							<td class='field_field'>{$url['url']}{$sep} {$folder} {$sep1} {$seoName}<br /><span class='desctext'>{$this->lang->words['page_filename_desc']}</span><br />

								<p id='omit_page' style='display: none'>
									<br />
									{$omitName} <label for='omit_page_check'>{$this->lang->words['omit_name_url']}</label>
									<br />
									&nbsp;&nbsp;&nbsp;&nbsp;<span class='desctext'>{$this->lang->words['omit_name_url_desc']}</span>
								</p>
							</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['meta_keywords_form']}</strong></td>
							<td class='field_field'>{$metak}</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['meta_description_form']}</strong></td>
							<td class='field_field'>{$metad}</td>
						</tr>
						<tr>
							<th colspan='2'>{$this->lang->words['tab__editing_options']}</th>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['how_to_edit_page']}</strong></td>
							<td class='field_field'>{$pageType}</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['template_to_start_with']}</strong></td>
							<td class='field_field'>{$template}</td>
						</tr>
						<tr style='{$_onlyHide}' id='only_page_content'>
							<td class='field_title'><strong class='title'>{$this->lang->words['only_edit_content']}</strong></td>
							<td class='field_field'>{$content}<br /><span class='desctext'>{$this->lang->words['only_edit_content_desc']}</span></td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['use_ipb_wrapper']}</strong></td>
							<td class='field_field'>{$ipbwrapper}<br /><span class='desctext'>{$this->lang->words['use_ipb_wrapper_desc']}</span></td>
						</tr>
						<tr>
							<th colspan='2'>{$this->lang->words['page_advanced_opts']}</th>
						</tr>
						<tr>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['cache_ttl_opt']}</strong>
							</td>
							<td class='field_field'>
								<input type='checkbox' id='cache_override' /> <label for='cache_override'>{$this->lang->words['cache_this_page']}</label> &nbsp;&nbsp;<a href='#' id='cache_help' style='font-size: 11px'>{$this->lang->words['cache_link']}</a>
								<p id='cache_edit' style='display: none'>
									<br />
									<input type='text' class='input_text' name='cache_ttl' id='cache_ttl' value='{$session['wizard_cache_ttl']}' size='5' /> {$this->lang->words['cache_minutes']} <br />
									<span class='desctext'>{$this->lang->words['page_cache_ttl_desc']}</span>
								</p>
							</td>
						</tr>
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['all_current_future_masks']}</strong></td>
							<td class='field_field'>{$allMasks}</td>
						</tr>
						<tr style='{$_hide}' id='not_all_masks'>
							<td class='field_title'><strong class='title'>{$this->lang->words['specific_perm_masks']}</strong></td>
							<td class='field_field'>{$masks}<div class='desctext'>{$this->lang->words['specific_perm_masks_desc']}</div></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
			{$_edit}
			<input type='submit' value='{$this->lang->words['button__continue']}' class="button right" />
		</div>
	</div>
</div>	
</form>
<div id='cache_help_content' class='acp-box' style='display: none'>
	<h3>{$this->lang->words['cache_help_title']}</h3>
	<div class='pad cache_help'>
		{$this->lang->words['cache_help_page']}
	</div>
</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 2
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_2( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$infoBox	= '';

if( $session['wizard_content_only'] AND $session['wizard_template'] )
{
	$text		= sprintf( $this->lang->words['info__content_only'], $this->settings['base_url'] );
	$infoBox	= <<<HTML
	<div class='information-box'>
		{$text}
	</div>
	<br />
HTML;
}
else if( $session['wizard_template'] )
{
	$infoBox = <<<HTML
	<div class='information-box'>
		{$this->lang->words['info__no_content_only']}
	</div>
	<br />
HTML;
}

$steps = $this->wizard_stepbar( 2, $sessionId, (bool) $session['wizard_edit_id'] );

$_edit	= $session['wizard_edit_id'] ? $this->wizard_savebuttons( $sessionId ) : '';

$page	= array();

foreach( $session as $k => $v )
{
	$page[ str_replace( 'wizard_', 'page_', $k ) ]	= $v;
}

$_link	= $session['wizard_edit_id'] ? " <span class='view-page'><a href='" . $this->registry->ccsFunctions->returnPageUrl( $page ) . "' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>" : '';

$_class1	= IPSCookie::get('hideContentHelp') ? "" : "withSidebar";
$_class2	= IPSCookie::get('hideContentHelp') ? " closed" : "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['page_wizard']} {$_link}</h2>
</div>

<script type='text/javascript'>
	ipb.lang['insert_tag']	= '{$this->lang->words['insert']}';
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.tagsidebar.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='2' />
	<div class='ipsSteps_wrap'>
		{$steps}
		<div class='ipsSteps_wrapper'>
			<div class='steps_content'>
				<div class='acp-box'>
					<h3>{$this->lang->words['edit_page_content_header']}</h3>
					<table class='ipsTable double_pad'>
						<tr>
							<td class='sidebarContainer'>
								{$infoBox}
HTML;

if( $session['wizard_type'] != 'php' )
{
	$IPBHTML .= <<<HTML
								<div id="template-tags" class="templateTags-container{$_class2}">{$additional['help']}</div>
HTML;
}
else
{
	$_class1	= 'noSidebar';
}

$IPBHTML .= <<<HTML
								<div id='content-label' class="{$_class1}">{$additional['editor']}</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div id='steps_navigation' class='clearfix' style='margin-top: 10px;'>
			{$_edit} <input type='submit' value='{$this->lang->words['button__continue']}' class="button right" />
		</div>
	</div>
</div>	
</form>
HTML;

if( $session['wizard_type'] == 'bbcode' )
{
	$IPBHTML .= $this->registry->output->loadTemplate('cp_skin_ccsglobal')->wysiwyg__ipbrte();
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 3 (Finished)
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_3( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$url	= $this->registry->ccsFunctions->returnPageUrl( array( 'page_folder' => $session['wizard_folder'], 'page_seo_name' => $session['wizard_seo_name'], 'page_id' => $session['page_id'] ) );

$page	= array();

foreach( $session as $k => $v )
{
	$page[ str_replace( 'wizard_', 'page_', $k ) ]	= $v;
}

$_link	= $session['wizard_edit_id'] ? " <span class='view-page'><a href='" . $this->registry->ccsFunctions->returnPageUrl( $page ) . "' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/images/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span>" : '';

$steps = $this->wizard_stepbar( 3, $session['wizard_edit_id'], true );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['page_wizard']} {$_link}</h2>
</div>

<div class='ipsSteps_wrap'>
	{$steps}
	<div class='ipsSteps_wrapper'>
		<div class='steps_content'>
			<div class='acp-box'>
				<h3>{$this->lang->words['congrats_block_done']}</h3>
				<table class='ipsTable'>
					<tr>
						<td>
							{$this->lang->words['page_done_visit_1']}
							<br /><br />
							<a href='{$url}' target='_blank'>{$url}</a>
							<br /><br />
							{$this->lang->words['page_done_visit_2']}
						</td>
					</tr>		
				</table>
			</div>	
		</div>
	</div>
</div>

HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * Get save and save/reload buttons
 *
 * @access	public
 * @param	string		Session ID
 * @return	string		HTML
 */
public function wizard_savebuttons( $sessionId )
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" name='save_button' /> 
<input type='submit' name='save_and_reload' value='{$this->lang->words['button__reload']}' class="button primary" /> 
<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}';" value='{$this->lang->words['button__cancel']}' />

HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard step bar
 *
 * @access	public
 * @param	int 		Current step number
 * @param	string		Wizard session id
 * @param	bool		Edit page process (instead of adding)
 * @return	string		HTML
 */
public function wizard_stepbar( $currentStep, $sessionId, $isEditPage=false )
{
$IPBHTML = "";
//--starthtml--//

$steps	= array(
				1	=> $this->lang->words['stepbar__pagedetails'],
				2	=> $this->lang->words['stepbar__pagecontent'], 
				3	=> $this->lang->words['stepbar__done']
			);

$IPBHTML .= <<<HTML
<div class='ipsSteps clearfix'>
	<ul>
HTML;

$_max		= max( array_keys( $steps ) );
$_noLinks	= false;
$_link		= "do=process&amp;wizard_session=" . $sessionId . '&amp;_jump=1&amp;step=';

if( strlen( $sessionId ) != 32 )
{
	$_link		= "do=editPage&amp;page=" . $sessionId . "&amp;_jump=";
}

// This code blocks the links if we are on the last step.  We
// decided to work around the "issue" present here instead of
// blocking users from being able to do it.
//if( $_max == $currentStep )
//{
//	$_noLinks	= true;
//}

foreach( $steps as $stepNumber => $stepTitle )
{
	$_title		= sprintf( $this->lang->words['jumptostep'], $stepNumber );
	$class		= $stepNumber == $currentStep ? "steps_active" : '';
	$_unlinked	= false;
	
	if( !$isEditPage )
	{
		if( $stepNumber >= $currentStep )
		{
			$_unlinked	= true;
		}
	}
	
	if( $stepNumber == $currentStep )
	{
		$_unlinked	= true;
	}
	
	if ( !$_noLinks AND !$_unlinked )
	{
		$class .= ' clickable';
		$js = "onclick='window.location = \"{$this->settings['base_url']}module=pages&amp;section=wizard&amp;{$_link}{$stepNumber}\";'";
	}

	
	$IPBHTML .= <<<HTML
		<li class='{$class}' {$js}>
			<strong class='steps_title'>{$this->registry->getClass('class_localization')->words['step_single']} {$stepNumber}</strong>
			<span class='steps_desc'>{$stepTitle}</span>
			<span class='steps_arrow'>&nbsp;</span>
		</li>
HTML;

}
	
$IPBHTML .= <<<HTML
	</ul>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

}