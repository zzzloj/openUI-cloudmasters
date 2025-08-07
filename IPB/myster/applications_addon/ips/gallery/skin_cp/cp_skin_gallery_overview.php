<?php
/**
 * @file		cp_skin_gallery.php 	IP.Gallery overview templates
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-05-22 11:04:13 -0400 (Tue, 22 May 2012) $
 * @version		v5.0.5
 * $Revision: 10780 $
 */

/**
 *
 * @class		cp_skin_gallery_overview
 * @brief		IP.Gallery overview templates
 */
class cp_skin_gallery_overview
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
 * Gallery overview
 *
 * @param	array		$warnings		Warnings
 * @param	array		$versions		Upgrade records
 * @param	array		$stats			Statistics data
 * @return	@e string	HTML
 */
public function galleryOverview( $warnings=array(), $stats=array() ) {
$IPBHTML = "";
//--starthtml--//

if( count( $warnings ) )
{
$IPBHTML .= <<<HTML
<div class='warning'>
	<span style='font-size:20px;font-weight:bold'>{$this->lang->words['gal_possibleerrors']}</span>
	<br /><br />
	<table width='100%' style='border:1px solid black;'>
		<tr>
			<th width='25%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_problem']}</strong></th>
			<th width='25%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_affectedsetting']}</strong></th>
			<th width='50%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_possiblefixes']}</strong></th>
		</tr>
HTML;

	foreach( $warnings as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td style='border:1px solid black;padding:5px;'>{$r[0]}</td>
			<td style='border:1px solid black;padding:5px;'><a href='{$r[1]}'>{$r[2]}</a></td>
			<td style='border:1px solid black;padding:5px;'>{$r[3]}</td>
		</tr>
HTML;
	}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['overview_page_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=update_stats'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['overview_rebuild_stats']}</a>
			</li>
		</ul>
	</div>
</div>

<table width='100%'>
	<tr>
		<td width='39%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['gal_quickstats']}</h3>
				<table class='ipsTable'>
					<tr>
						<td width='60%'><strong class='title'>{$this->lang->words['gal_totalimages']}</strong></td>
						<td width='40%'>{$stats['images']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totaldiskspace']}</strong></td>
						<td>{$stats['diskspace']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalviews']}</strong></td>
						<td>{$stats['views']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalcomments']}</strong></td>
						<td>{$stats['comments']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalcategories']}</strong></td>
						<td>{$stats['categories']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalalbums']}</strong></td>
						<td>{$stats['albums']}</td>
					</tr>
				</table>			
			</div>
		</td>
		<td width='1%'>&nbsp;</td>
		<td width='60%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['overview_quick_searches']}</h3>
				<table class='ipsTable'>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_member_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='domemsrch' />
								<input type='text' name='search_term' id='membersearch' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_member_search_submit']}' accesskey='s' class='button primary' />
							</form>
						</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_group_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='dogroupsrch' />
								<input type='text' name='search_term' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_group_search_submit']}' class='button primary' accesskey='s' />
							</form>
						</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_file_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='dofilesrch' />
								<input type='text' name='search_term' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_file_search_submit']}' class='button primary' accesskey='s' />
							</form>
						</td>
					</tr>
				</table>
			</div>
			<script type='text/javascript'>
				Event.observe( window, "load", function(){
					var search = new ipb.Autocomplete( $('membersearch'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
				});
			</script>
		</td>
	</tr>
</table>
<br />

<table width='100%'>
	<tr>
		<td width='100%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['gal_groupoverview']}</h3>
				<table class='ipsTable'>
HTML;
	
	foreach( $this->cache->getCache('group_cache') as $r )
	{
		$r['g_title']	= IPSMember::makeNameFormatted( $r['g_title'], $r['g_id'] );
		$r['_setUp']	= ( ! $r['g_create_albums'] OR ! $r['g_gallery_use'] ) ? "<div class='desctext'>{$this->lang->words['overview_g_nosetup']}</div>" : '';
		
		$IPBHTML .= <<<HTML
					<tr class='ipsControlRow'>
						<td>{$r['g_title']}{$r['_setUp']}</td>
						<td class='col_buttons'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'><a href='{$this->settings['base_url']}app=members&amp;module=groups&amp;section=groups&amp;do=edit&amp;id={$r['g_id']}&amp;_initTab=gallery'>{$this->lang->words['gal_group_edit']}</a></li>
							</ul>
						</td>
					</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
	</tr>
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

}