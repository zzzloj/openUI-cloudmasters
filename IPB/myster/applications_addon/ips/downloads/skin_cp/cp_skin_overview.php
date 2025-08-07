<?php
/**
 * @file		cp_skin_overview.php 	Overview skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2011-10-27 22:05:09 -0400 (Thu, 27 Oct 2011) $
 * @version		v2.5.4
 * $Revision: 9691 $
 */

/**
 *
 * @class		cp_skin_overview
 * @brief		Overview skin file
 */
class cp_skin_overview
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
 * Overview screen
 *
 * @param	array		$data		Data to show
 * @param	array		$latest		Latest files
 * @param	array		$pending	Pending files
 * @param	array		$broken		Broken files
 * @return	@e string	HTML
 */
public function overviewSplash( $data, $latest=array(), $pending=array(), $broken=array() ) {

$IPBHTML = "";
//--starthtml--//

$onlineStatus = $this->settings['idm_online'] ? 'accept' : 'delete';

$uploadMaxFilesize	= @ini_get('upload_max_filesize') ? @ini_get('upload_max_filesize') : $this->lang->words['ov_stat_unknown'];
$postMaxSize		= @ini_get('post_max_size') ? @ini_get('post_max_size') : $this->lang->words['ov_stat_unknown'];
$timeLimit			= defined('ORIGINAL_TIME_LIMIT') ? ORIGINAL_TIME_LIMIT : ( @ini_get('max_execution_time') ? @ini_get('max_execution_time') : $this->lang->words['ov_stat_unknown'] );

if( intval($timeLimit) == $timeLimit )
{
	$timeLimit	.= ' ' . $this->lang->words['ov_stat_seconds'];
}

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.downloads.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['d_overview']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_information']}</h3>
					
	<table class='ipsTable'>
		<tr>
			<td width='20%'><strong class='title'>{$this->lang->words['d_sysonline']}</strong></td>
			<td width='20%'><img src='{$this->settings['skin_acp_url']}/images/icons/{$onlineStatus}.png' alt='' /></td>
			<td width='30%'><strong class='title'>{$this->lang->words['d_totalbw']}</strong></td>
			<td width='30%'>{$data['overview']['total_bw']}</td>		
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldisk']}</strong></td>
			<td>{$data['overview']['total_size']}</td>
			<td><strong class='title'>{$this->lang->words['d_currentbw']}</strong></td>
			<td>{$data['overview']['this_bw']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totalfiles']}</strong></td>
			<td>{$data['overview']['total_files']}</td>
			<td><strong class='title'>{$this->lang->words['d_largest']} ({$data['overview']['largest_file_size']})</strong></td>
			<td>{$data['overview']['largest_file_name']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldls']}</strong></td>
			<td>{$data['overview']['total_downloads']}</td>
			<td><strong class='title'>{$this->lang->words['d_mostviewed']} ({$data['overview']['views_file_views']})</strong></td>
			<td>{$data['overview']['views_file_name']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totalviews']}</strong></td>
			<td>{$data['overview']['total_views']}</td>
			<td><strong class='title'>{$this->lang->words['d_mostdl']} ({$data['overview']['dls_file_downloads']})</strong></td>
			<td>{$data['overview']['dls_file_name']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxuploadsize']}</strong></td>
			<td>{$uploadMaxFilesize}</td>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxpostsize']}</strong></td>
			<td>{$postMaxSize}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxtimelimit']}</strong></td>
			<td>{$timeLimit}</td>
			<td></td>
			<td></td>
		</tr>
	</table>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_runreports']}</h3>
	
	<form action='{$this->settings['base_url']}&amp;module=index&amp;section=stats&amp;do=report' method='post' id='runReport'>
		<table class='ipsTable double_pad'>
			<tr>
				<td width='20%' align='right'><strong class='title'>{$this->lang->words['d_memreport']}</strong></td>
				<td width='20%'>{$data['reports']['member']}</td>
				<td width='20%' align='right'><strong class='title'>{$this->lang->words['d_filereport']}</strong></td>
				<td width='40%'>{$data['reports']['file']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_runbutton']}' class='button primary' />
		</div>
	</form>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_last5']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_approved']}</th>
		</tr>
HTML;

foreach( $latest as $row )
{
	$_image = $row['file_open'] ? 'accept' : 'cross';
	
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=downloads&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_image}.png' alt='' /></td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_pendapprove']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_approvequest']}</th>
		</tr>
HTML;

foreach( $pending as $row )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=downloads&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=downloads&amp;module=moderate&amp;section=moderate&amp;do=togglefile&amp;id={$row['file_id']}&amp;secure_key={$this->member->form_hash}'><img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' /></a></td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_reportbroke']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_removequest']}</th>
		</tr>
HTML;

foreach( $broken as $row )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=downloads&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=downloads&amp;module=moderate&amp;section=moderate&amp;do=delete&amp;id={$row['file_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' /></a></td>
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

}