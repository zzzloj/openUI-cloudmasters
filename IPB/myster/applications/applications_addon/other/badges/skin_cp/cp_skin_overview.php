<?php

/**
 * Product Title:		[HQ] Badges
 * Product Version:		2.0.0
 * Author:				InvisionHQ - G. Venturini
 * Website:				Lamoneta.it
 * Website URL:			http://lamoneta.it/
 * Email:				reficul@lamoneta.it
 */

class cp_skin_overview extends output
{
public $editor;

public function __destruct()
{
}

//===========================================================================
// Overview Index
//===========================================================================
function overviewIndex( $data, $ativo, $plugin, $plugins, $dwnld ) {
$sess = ipsRegistry::$request['adsess'];
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Badges Overview</h2>
</div>
<table class='ipsTable double_pad'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>Core and Plugins</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th style='width: 40%'>Name</th>
						<th style='width: 30%'>Action</th>
						<th style='width: 30%'>Status</th>
					</tr>
					<tr>
						<td width='40%'><strong>Badges Core</strong></td>
						<td width='30%' align='center'></td>
						<td width='30%' align='center'><span class='ipsBadge badge_green'>INSTALLED</span></td>
					</tr>
					<tr>
						<td width='40%'><strong>Plugin: Group Badges</strong></td>
						<td width='30%' align='center'></td>
						<td width='30%' align='center'><span class='ipsBadge badge_green'>INSTALLED</span></td>
					</tr>
HTML;
			if (count($plugins))
				{
					foreach( $plugins as $hqplugins )
						{
							$name = substr($hqplugins[0], 5);
							$plugin_dir = substr($hqplugins[2], 8);
							$status = substr($hqplugins[4], 7);
							$color = substr($hqplugins[5], 6);
							$link = substr($hqplugins[6], 5);
							$link2 = substr($hqplugins[7], 6);
							if ($status == 'available') 
								{
									$pos = in_array($plugin_dir, $plugin);
									if ($pos) {
										$status = 'installed';
										$color = 'green';
									} else {
										$status = '<a href="'.$link.'" target="_blank"><img src="'.$this->settings['skin_app_url'].'/cart.gif"> buy now</a>';
										$color = 'grey';
									}
								}
if ( $name != '' ) {
$IPBHTML .= <<<HTML
							<tr>
								<td width='40%'><strong>{$name}</strong></td>
HTML;
$pos = strpos($link,'http://');
if ( $link != '' ) {
	if ( $pos === false ) {
$IPBHTML .= <<<HTML
								<td width='30%' align='center'><a href="{$this->settings['admin_url']}?adsess={$sess}&app=badges&{$link}" class="mini_button">{$link2}</a></td>
HTML;
	} else {
$IPBHTML .= <<<HTML
								<td width='30%' align='center'><a href="{$link}" class="mini_button" target="_blank">{$link2}</a></td>
HTML;
	}
} else {
	$IPBHTML .= <<<HTML
								<td width='30%' align='center'></td>
HTML;
}
$IPBHTML .= <<<HTML
								<td width='30%' align='center'><span class='ipsBadge badge_{$color}'>{$status}</span></td>
							</tr>
HTML;
}
						}
				}
$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
		<td width='50%' valign='top'>
		 <div class='acp-box'>
			<h3>Badges Stats</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th style='width: 60%'>Name</th>
						<th style='width: 20%'>Status</th>
					</tr>
					<tr>
						<td width='50%'><strong>Active Badges</strong></td>
						<td width='25%' align='center'>{$ativo}</td>
					</tr>
				</table>
		 </div><br />
			<div class='acp-box'>
				<h3>Upgrade History</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th style='width: 60%'>Version</th>
						<th style='width: 40%'>Date</th>
					</tr>
HTML;
		
		if ( count( $data['upgrade'] ) )
		{
			foreach ( $data['upgrade'] as $upgrade )
			{
				$IPBHTML .= <<<HTML
					<tr>
						<td>{$upgrade['upgrade_version_human']} ({$upgrade['upgrade_version_id']})</td>
						<td>{$upgrade['_date']}</td>
					</tr>
HTML;
			}
		}
			
		$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
	</tr>
</table>
HTML;

$IPBHTML .= <<<HTML
<br />
<table class='ipsTable double_pad'>
	<tr>
	    <td width='50%' valign='top'>
	        <div class="acp-box">
			    <h3>Application: {$this->caches['app_cache']['badges']['app_title']} {$this->caches['app_cache']['badges']['app_version']}</h3>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>General Information</th>
					</tr>
					<tr>
						<td><strong>Author</strong></td>
						<td align='center'>InvisionHQ - G. Venturini</td>
					</tr>
					<tr>
						<td width='40%'><strong>Version</strong></td>
						<td width='60%' align='center'><b>{$this->caches['app_cache']['badges']['app_version']}</b> {$dwnld}</td>
					</tr>
					<tr>
						<td><strong>WebSite</strong></td>
						<td align='center'><a href="http://community.invisionpower.com/index.php?app=core&module=search&do=user_activity&search_app=downloads&mid=136690" target="_blank">bbcode.it</a></td>
					</tr>
					<tr>
						<td colspan="2" align="center">
							<form target="_blank" action='https://www.paypal.com/cgi-bin/webscr' method='post'>
							         <input type='hidden' name='cmd' value='_xclick' />
							         <input type='hidden' name='business' value='reficul@lamoneta.it' />
							         <input type='hidden' name='item_name' value='Donate for new features' />
							         <input type='hidden' name='no_note' value='1' />
							         <input type='hidden' name='currency_code' value='USD' />
							         <input type='hidden' name='on0' value='Nome' />
							         <input type='hidden' name='on1' value='Forum' />
							         <input type='hidden' name='os0' value='{$this->memberData['members_display_name']}' />
							         <input type='hidden' name='os1' value='{$this->settings['board_name']}' />
							         <input type='hidden' name='cancel_return' value='http://community.invisionpower.com/index.php?app=core&module=search&do=user_activity&search_app=downloads&mid=136690' />
							         <input type='image' src='https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif' name='submit' alt='Donate to invisionHQ.' style='border:0px; background:transparent' />
							 </form>
						</td>
					<tr>
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