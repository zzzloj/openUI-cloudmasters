<?php
/**
 * <pre>
 * Invision Power Services
 * Revision management functions
 * Last Updated: $Date: 2011-11-29 17:56:12 -0500 (Tue, 29 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd September 2009
 * @version		$Revision: 9910 $
 */
 
class cp_skin_revisions
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
 * Show the list of revisions
 *
 * @access	public
 * @param	string		Content title
 * @param	array 		Revisions
 * @param	int			Content ID
 * @param	string		Content type
 * @return	string		HTML
 */
public function revisions( $title, $revisions, $id, $type )
{
$IPBHTML = "";
//--starthtml--//

$h3tag	= sprintf( $this->lang->words['allsavedrevisions'], $type );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['revisions_title_pre']} {$title}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;id={$this->request['id']}&amp;do=clearAll' );" title='{$this->lang->words['delete_all_revisions']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['delete_all_revisions']}</a></li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$h3tag}</h3>
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['th__revision_date']}</th>
			<th>{$this->lang->words['th__revision_member']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( !count( $revisions ) )
{
	$norevisions	= sprintf( $this->lang->words['nosavedrevisions'], $type );
	
	$IPBHTML .= <<<HTML
	<tr>
		<td colspan='3' class='no_messages'>
			{$norevisions}
		</td>
	</tr>
HTML;
}
else
{
	foreach( $revisions as $revision )
	{
		$date	= $this->registry->class_localization->getDate( $revision['revision_date'], 'LONG' );
		
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>{$date}</td>
			<td><span class='larger_text'><a href='{$this->settings['board_url']}/index.php?showuser={$revision['member_id']}'>{$revision['members_display_name']}</a></span></td>
			<td>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editRevision&amp;id={$revision['revision_id']}'>{$this->lang->words['edit_revision_menu']}</a>
					</li>
					<li class='ipsControlStrip_more ipbmenu' id='menu_folder{$revision['revision_id']}'>
						<a href='#'>{$this->lang->words['folder_options_alt']}</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu_folder{$revision['revision_id']}_menucontent'>
					<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=deleteRevision&amp;id={$revision['revision_id']}' );">{$this->lang->words['delete_revision_menu']}</a></li>
					<li class='icon manage'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=restoreRevision&amp;id={$revision['revision_id']}', '{$this->lang->words['restorerevisiononclick']}' );">{$this->lang->words['restore_revision_menu']}</a></li>
					<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=compareRevision&amp;id={$revision['revision_id']}'>{$this->lang->words['compare_revision_menu']}</a></li>
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
 * Show diff report
 *
 * @access	public
 * @param	string		Differences
 * @param	int			ID
 * @param	string		Type
 * @return	string		HTML
 */
public function compareRevisions( $differences, $id )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['revisionscomparetitle']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<div class='acp-box clear'>
	<h3>{$this->lang->words['diffinrevisions']}</h3>
	<table class='ipsTable'>
		<tr>
			<td>{$differences}</td>
		</tr>
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
 * Edit a revision
 *
 * @access	public
 * @param	array		Revision data
 * @param	string		Editor area
 * @return	string		HTML
 */
public function editRevision( $revision, $editor )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editrevisiontitle']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doEditRevision&amp;id={$revision['revision_id']}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['editrevisionh3title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td><div id='content-label'>{$editor}</div></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['button__save']}' class="button primary" />
		<input type='button' class='button redbutton' onclick="window.location='{$this->settings['base_url']}{$this->form_code}&amp;id={$revision['revision_type_id']}';" value='{$this->lang->words['button__cancel']}' />
	</div>
</div>	
</form>

HTML;
//--endhtml--//
return $IPBHTML;
}

}