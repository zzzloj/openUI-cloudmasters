<?php

class cp_skin_severity extends output {

function showSeverity( $skin ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_app_url']}../modules/severity/js/acp.severity.js'></script>
<div class='section_title'>
	<h2>Manage Severity Colors</h2>
	<ul class='context_menu'>
		<li>
			<a id='saveSeverities' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Save Colors</a>
		</li>
		<li class='disabled' style='display:none;'><span id='saveSuccess'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Colors Saved</span></li>
		<li>
			<a id='recacheSeverities' href='javascript:void(0);'><img src='{$this->settings['skin_app_url']}/images/database_refresh.png' alt='' />Recache Colors</a>
		</li>
		<li class='disabled' style='display:none;'><span id='recacheSuccess'><img src='{$this->settings['skin_app_url']}/images/accept.png' alt='' />Colors Recached</span></li>
	</ul>
</div>
<input type='hidden' id='bgcolor' name='bgcolor[]' value='' />
<input type='hidden' id='ftcolor' name='ftcolor[]' value='' />

<div class='acp-box adv_controls'>
	<h3 class='padded'>Severities</h3>
	<ul id='severity' class='alternate_rows'>
		<li class='header'>
			<table style='width:100%;' class='double_pad'>
				<tr>
					<td style='width:20%;'>&nbsp;</td>
					<td style='width:16%; text-align:center;'>1 - Low</td>
					<td style='width:16%; text-align:center;'>2 - Fair</td>
					<td style='width:16%; text-align:center;'>3 - Medium</td>
					<td style='width:16%; text-align:center;'>4 - High</td>
					<td style='width:16%; text-align:center;'>5 - Critical</td>
				</tr>
			</table>
		</li>
EOF;

	foreach( $skin as $k => $v )
	{
		$IPBHTML .= <<<EOF
		<li class='isSkin'>
			<table style='width:100%;' class='double_pad'>
				<tr>
					<td style='width:20%;' id='skin_{$k}' class='skin_id'>{$v['set_name']}<br /><span class='desc'>(<a href='javascript:void(0);' class='_toggle_font'>Text Color</a> | <a href='javascript:void(0);' class='_toggle_bg'>Background Color</a>)</span></td>
					<td style='width:16%; text-align:center;' class='sev_1'>
						<div class='color_wrap'>
							<div class='sev_box' style='background:{$v[1]['background_color']};'>&nbsp;</div>
							<input type='text' name='bgcolor[$k][1]' size='8' class='input_text color_input' maxlength='7' value='{$v[1]['background_color']}' />
						</div>
						<div class='font_wrap' style='display:none;'>
							<div class='sev_box' style='background:{$v[1]['background_color']}; color:{$v[1]['font_color']}'>Aa</div>
							<input type='text' name='ftcolor[$k][1]' size='8' class='input_text font_input' maxlength='7' value='{$v[1]['font_color']}' />
						</div>
					</td>
					<td style='width:16%; text-align:center;' class='sev_2'>
						<div class='color_wrap'>
							<div class='sev_box' style='background:{$v[2]['background_color']};'>&nbsp;</div>
							<input type='text' name='bgcolor[$k][2]' size='8' class='input_text color_input' maxlength='7' value='{$v[2]['background_color']}' />
						</div>
						<div class='font_wrap' style='display:none;'>
							<div class='sev_box' style='background:{$v[2]['background_color']}; color:{$v[2]['font_color']}'>Aa</div>
							<input type='text' name='ftcolor[$k][2]' size='8' class='input_text font_input' maxlength='7' value='{$v[2]['font_color']}' />
						</div>
					</td>
					<td style='width:16%; text-align:center;' class='sev_3'>
						<div class='color_wrap'>
							<div class='sev_box' style='background:{$v[3]['background_color']};'>&nbsp;</div>
							<input type='text' name='bgcolor[$k][3]' size='8' class='input_text color_input' maxlength='7' value='{$v[3]['background_color']}' />
						</div>
						<div class='font_wrap' style='display:none;'>
							<div class='sev_box' style='background:{$v[3]['background_color']}; color:{$v[3]['font_color']}'>Aa</div>
							<input type='text' name='ftcolor[$k][3]' size='8' class='input_text font_input' maxlength='7' value='{$v[3]['font_color']}' />
						</div>
					</td>
					<td style='width:16%; text-align:center;' class='sev_4'>
						<div class='color_wrap'>
							<div class='sev_box' style='background:{$v[4]['background_color']};'>&nbsp;</div>
							<input type='text' name='bgcolor[$k][4]' size='8' class='input_text color_input' maxlength='7' value='{$v[4]['background_color']}' />
						</div>
						<div class='font_wrap' style='display:none;'>
							<div class='sev_box' style='background:{$v[4]['background_color']}; color:{$v[4]['font_color']}'>Aa</div>
							<input type='text' name='ftcolor[$k][4]' size='8' class='input_text font_input' maxlength='7' value='{$v[4]['font_color']}' />
						</div>
					</td>
					<td style='width:16%; text-align:center;' class='sev_5'>
						<div class='color_wrap'>
							<div class='sev_box' style='background:{$v[5]['background_color']};'>&nbsp;</div>
							<input type='text' name='bgcolor[$k][5]' size='8' class='input_text color_input' maxlength='7' value='{$v[5]['background_color']}' />
						</div>
						<div class='font_wrap' style='display:none;'>
							<div class='sev_box' style='background:{$v[5]['background_color']}; color:{$v[5]['font_color']}'>Aa</div>
							<input type='text' name='ftcolor[$k][5]' size='8' class='input_text font_input' maxlength='7' value='{$v[5]['font_color']}' />
						</div>
					</td>
				</tr>			
			</table>
		</li>	
EOF;
	}
	
$IPBHTML .= <<<EOF
	</ul>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// SEVERITIES START JS
//===========================================================================
function severity_start_js( $skin_js ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<input type='hidden' name='initcol' value='' />
<input type='hidden' name='initformval' value='' />
<script type='text/javascript'>
{$skin_js}
function updatecolor( id )
{
	itm = my_getbyid( id );

	if ( itm )
	{
		eval("newcol = document.theAdminForm.f"+id+".value");
		itm.style.backgroundColor = newcol;
	}

}
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// SEVERITIES ROW
//===========================================================================
function severity_row( $skinid, $sev, $cache ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<fieldset>
<legend><strong>Text</strong></legend>
	<input type='text' id='fskin{$skinid}_sev{$sev}_color' name='fskin{$skinid}_sev{$sev}_color' value='{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['color']}' size='7' class='textinput'>&nbsp;
	<input type='text' id='skin{$skinid}_sev{$sev}_color' onclick="updatecolor('skin{$skinid}_sev{$sev}_color')" size='1' style='border:1px solid black;background-color:{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['color']}' readonly='readonly'>&nbsp;
</fieldset>
<fieldset>
<legend><strong>Background</strong></legend>
	<input type='text' id='fskin{$skinid}_sev{$sev}_backgroundcolor' name='fskin{$skinid}_sev{$sev}_backgroundcolor' value='{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['back']}' size='7' class='textinput'>&nbsp;
	<input type='text' id='skin{$skinid}_sev{$sev}_backgroundcolor'  onclick="updatecolor('skin{$skinid}_sev{$sev}_backgroundcolor')" size='1' style='border:1px solid black;background-color:{$cache['tracker_sevs']['skin'.$skinid]['sev'.$sev]['back']}' readonly='readonly'>&nbsp;
</fieldset>
EOF;

//--endhtml--//
return $IPBHTML;
}

}

?>