<?php
/**
 * @file		cp_skin_gallery.php 	IP.Gallery statistic templates
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
 * @class		cp_skin_gallery_statistics
 * @brief		IP.Gallery statistic templates
 */
class cp_skin_gallery_statistics
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
 * Stats overview
 *
 * @param	array		$overall			Overall data
 * @param	array		$groups_disk		Groups disk usage
 * @param	array		$users_disk			Users disk usage
 * @param 	array		$groups_bandwidth	Groups bandwidth usage
 * @param	array		$users_bandwidth	Users bandwidth usage
 * @param	array		$files_bandwidth	Files bandwidth usage
 * @return	@e string	HTML
 */
public function statsOverview( $overall, $groups_disk, $users_disk, $groups_bandwidth, $users_bandwidth, $files_bandwidth ) {
$IPBHTML = "";
//--starthtml--//
$pasthours = sprintf( $this->lang->words['stats_td_transfer'], $this->settings['gallery_bandwidth_period'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='25%'><strong class='title'>{$this->lang->words['gal_totaldisk']}</strong></td>
			<td width='25%'>{$overall['total_diskspace']}</td>
			<td width='25%'><strong class='title'>{$this->lang->words['gal_totalupload']}</strong></td>
			<td width='25%'>{$overall['total_uploads']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$pasthours}</strong></td>
			<td>{$overall['total_transfer']}</td>
			<td><strong class='title'>{$this->lang->words['gal_totalviews']}</strong></td>
			<td>{$overall['total_views']}</td>
		</tr>
	</table>
</div>

<center><img alt='{$this->lang->words['gal_statchart']}' src='{$this->settings['base_url']}&amp;module=stats&amp;section=stats&amp;do=get_chart' /></center>
<br />

<div class='section_title'>
	<h2>{$this->lang->words['gal_diskspaceusage']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['gal_groupoverview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_group']}</th>
			<th width='15%'>{$this->lang->words['gal_diskspaceusage']}</th>
			<th width='20%'>{$this->lang->words['gal_percentusage']}</th>
			<th width='15%'>{$this->lang->words['gal_uploadedfiles']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoffiles']}</th>
		</tr>
HTML;

if( count( $groups_disk ) )
{
	foreach( $groups_disk as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=members&amp;module=list&amp;section=view&amp;filter={$r['g_id']}' target='_blank'>{$r['g_title']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$r['g_id']}' title='{$this->lang->words['gal_viewgreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewgreport']}' /></a></td>
			<td>{$r['diskspace']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['uploads']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5disk']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_member']}</th>
			<th width='15%'>{$this->lang->words['gal_diskspaceusage']}</th>
			<th width='20%'>{$this->lang->words['gal_percentusage']}</th>
			<th width='15%'>{$this->lang->words['gal_uploadedfiles']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoffiles']}</th>
		</tr>
HTML;

if( count( $users_disk ) )
{
	foreach( $users_disk as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?showuser={$r['mid']}' target='_blank'>{$r['members_display_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$r['mid']}' title='{$this->lang->words['gal_viewmreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewmreport']}' /></a></td>
			<td>{$r['diskspace']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['uploads']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />


<div class='section_title'>
	<h2>{$this->lang->words['stats_h_bandwidth']}</h2>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['stats_group_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_group']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $groups_bandwidth ) )
{
	foreach( $groups_bandwidth as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=members&amp;module=list&amp;section=view&amp;filter={$r['g_id']}' target='_blank'>{$r['g_title']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$r['g_id']}' title='{$this->lang->words['gal_viewgreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewgreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5bw']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_member']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $users_bandwidth ) )
{
	foreach( $users_bandwidth as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}' target='_blank'>{$r['members_display_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$r['member_id']}' title='{$this->lang->words['gal_viewmreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewmreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5files']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_file']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $files_bandwidth ) )
{
	foreach( $files_bandwidth as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=gallery&amp;image={$r['image_id']}' target='_blank'>{$r['image_file_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dofilesrch&amp;viewfile={$r['image_id']}' title='{$this->lang->words['gal_viewfreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png'alt='{$this->lang->words['gal_viewfreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
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
 * Stat search result
 *
 * @param	string		$title		Page title
 * @param	array		$rows		Results data
 * @return	@e string	HTML
 */
public function statSearchResults( $title, $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_results_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<table class='ipsTable'>
HTML;

if( count( $rows ) )
{	
	foreach( $rows as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$r['thumb']}<a href='{$r['url']}'>{$r['name']}</a></td>
		</tr>
HTML;
	}
}	
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td class='no_messages'>{$this->lang->words['stats_mem_results_none']}</td>
		</tr>
HTML;
}		
$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group file report
 *
 * @param	array		$stats		Stats data
 * @param	array		$bw			Bandwidth data
 * @param	integer		$comments	Comments count
 * @param	array		$rate		Ratings data
 * @param	string		$title		Page title
 * @return	@e string	HTML
 */
public function groupFileReport( $stats, $bw, $comments, $rate, $title ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_mem_disk_over']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_td_diskspace']}</strong></td>
			<td width='25%'>{$stats['group_size']}</td>
			<td width='25%'><strong>{$this->lang->words['stats_td_disk_percent']}</strong></td>
			<td width='25%'>{$stats['dp_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_uploads']}</strong></td>
			<td>{$stats['group_uploads']}</td>
			<td><strong>{$this->lang->words['stats_td_ups_percent']}</strong></td>
			<td>{$stats['up_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_average_all']}</strong></td>
			<td>{$stats['total_avg_size']}</td>
			<td><strong>{$this->lang->words['stats_td_average']}</strong></td>
			<td>{$stats['group_avg_size']}</td>
		</tr>
HTML;

if( $this->settings['gallery_detailed_bandwidth'] )
{
	$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$bw['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_transfer_nop']}</strong></td>
			<td>{$stats['group_transfer']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_tpercent']}</strong></td>
			<td>{$bw['tr_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_views']}</strong></td>
			<td>{$stats['group_viewed']}</td>
			<td><strong>{$this->lang->words['stats_td_views_percent']}</strong></td>
			<td>{$bw['vi_percent']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_other_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='16%'><strong>{$this->lang->words['stats_td_comments']}</strong></td>
			<td width='16%'>{$comments}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_ttlg_rating']}</strong></td>
			<td width='16%'>{$rate['total_rates']}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_avgg_rating']}</strong></td>
			<td width='16%'>{$rate['avg_rate']}</td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member file report
 *
 * @param	integer		$mid		Member ID
 * @param	array		$stats		Stats data
 * @param	integer		$comments	Comments count
 * @param	array		$rate		Ratings data
 * @param	string		$title		Page title
 * @return	@e string	HTML
 */
public function memberFileReport( $mid, $stats, $comments, $rate, $title ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>

	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_mem_disk_over']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_td_diskspace']}</strong></td>
			<td width='25%'>{$stats['user_size']}</td>
			<td width='25%'><strong>{$this->lang->words['stats_td_disk_percent']}</strong></td>
			<td width='25%'>{$stats['dp_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_uploads']}</strong></td>
			<td>{$stats['user_uploads']}</td>
			<td><strong>{$this->lang->words['stats_td_ups_percent']}</strong></td>
			<td>{$stats['up_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_average_all']}</strong></td>
			<td>{$stats['total_avg_size']}</td>
			<td><strong>{$this->lang->words['stats_td_average']}</strong></td>
			<td>{$stats['user_avg_size']}</td>
		</tr>
HTML;

if( $this->settings['gallery_detailed_bandwidth'] )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$stats['bw']['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_transfer_nop']}</strong></td>
			<td>{$stats['user_transfer']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_tpercent']}</strong></td>
			<td>{$stats['bw']['tr_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_views']}</strong></td>
			<td>{$stats['user_viewed']}</td>
			<td><strong>{$this->lang->words['stats_td_views_percent']}</strong></td>
			<td>{$stats['bw']['vi_percent']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;

if( $this->settings['gallery_detailed_bandwidth'] )
{
	$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$stats['bw']['list_title']}</h3>

	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['stats_bandwidth_file']}</th>
			<th>{$this->lang->words['stats_bandwidth_transfer']}</th>
			<th>{$this->lang->words['stats_bandwidth_user_trans']}</th>
			<th>{$this->lang->words['stats_bandwidth_loads']}</th>
			<th>{$this->lang->words['stats_bandwidth_user_views']}</th>
		</tr>
HTML;

foreach( $stats['bw']['rows'] as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>
				<strong>{$r['file_name']}</strong> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=dofilesrch&amp;viewfile={$r['id']}' title='{$this->lang->words['stats_view_file_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['stats_view_file_alt']}' /></a>
			</td>
			<td>{$r['transfer']}%</td>
			<td>{$r['dp_percent']}</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
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
<div class='acp-box'>
	<h3>{$this->lang->words['stats_other_tbl_header']}</h3>

	<table class='ipsTable'>
		<tr>
			<td width='16%'><strong>{$this->lang->words['stats_td_comments']}</strong></td>
			<td width='16%'>{$comments}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_ttl_rating']}</strong></td>
			<td width='16%'>{$rate['total_rates']}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_avg_rating']}</strong></td>
			<td width='16%'>{$rate['avg_rate']}</td>
		</tr>
	</table>
</div><br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_take_mem_action']}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
		<input type='hidden' name='do' value='domemact' />
		<input type='hidden' name='mid' value='{$mid}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_mem_dis_up']}</strong></td>
				<td class='field_field'>{$stats['remove_uploading']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_mem_dis_gal']}</strong></td>
				<td class='field_field'>{$stats['remove_gallery']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['stats_mem_action_submit']}' class='primary button' accesskey='s'>
		</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * File report
 *
 * @param	array		$img		Image
 * @param	array		$file		File data
 * @param	array		$rate		Ratings data
 * @param	array		$bw			Bandwidth data
 * @return	@e string	HTML
 */
public function statFileReport( $img, $file, $rate, $bw ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['stats_file_result_title'], $file['image_file_name'] );

$form = array( 'new_owner'		 => $this->registry->output->formSimpleInput( 'new_owner', $file['members_display_name'], 40 ),
			   'clear_bandwidth' => $this->registry->output->formYesNo( 'clear_bandwidth' ),
			   'clear_rating'	 => $this->registry->output->formYesNo( 'clear_rating' )
			  );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<center>{$img}</center>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_file_overview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_file_gen_overview']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_file_uploadedby']}</strong></td>
			<td width='25%'>{$file['members_display_name']} <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$file['mid']}' title='{$this->lang->words['stats_view_mem_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['stats_view_mem_alt']}' /></a></td>
			<td width='25%'><strong>{$this->lang->words['stats_file_approved']}</strong></td>
			<td width='25%'>{$file['image_approved']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_size']}</strong></td>
			<td>{$file['image_file_size']}</td>
			<td><strong>{$this->lang->words['stats_file_type']}</strong></td>
			<td>{$file['image_file_type']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_maskname']}</strong></td>
			<td>{$file['image_masked_file_name']}</td>
			<td><strong>{$this->lang->words['stats_file_thumb']}</strong></td>
			<td>{$file['image_thumbnail']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_date']}</strong></td>
			<td>{$file['image_date']}</td>
			<td><strong>{$file['local_name']}</strong></td>
			<td>{$file['container']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_comments']}</strong></td>
			<td>{$file['image_comments']}</td>
			<td><strong>{$this->lang->words['stats_file_views']}</strong></td>
			<td>{$file['image_views']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_rates']}</strong></td>
			<td>{$rate['total_rate']}</td>
			<td><strong>{$this->lang->words['stats_file_avg_rates']}</strong></td>
			<td>{$rate['avg_rate']}</td>
		</tr>
HTML;

if( $this->settings['gallery_detailed_bandwidth'] )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$bw['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_bandwidth_loads']}</strong></td>
			<td>{$bw['views']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_transfer']}</strong></td>
			<td>{$bw['transfer']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<form name='DOIT' id='DOIT' action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dofileact' />
	<input type='hidden' name='fid' value='{$file['image_id']}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['gal_takeactiononfile']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_changeowner']}</strong></td>
				<td class='field_field'>{$form['new_owner']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_clearbwlogs']}</strong></td>
				<td class='field_field'>{$form['clear_bandwidth']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_clearratinglogs']}</strong></td>
				<td class='field_field'>{$form['clear_rating']}</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['gal_takeaction']}" class="button primary" type="submit" />
		</div>
	</div>
</form>
<script type="text/javascript" defer="defer">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('new_owner'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


}