<?php

/**
 * @author Codingjungle & Provisionists
 * @link http://www.codingjungle.com && http://www.provisionists.com
 * @copyright Copyright (c) 2013, Michael S. Edwards and Robert Simons All Rights Reserved
 */
if (!defined('IN_IPB')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_promenu_preview_preview extends ipsCommand {

	public function doExecute(ipsRegistry $registry) {
		switch ($this->request['do']) {
			case 'wrapper' :
				$this->registry->proPlus->wrapper();
				break;
			default :
				$this->preview();
				break;
		}
	}

	public function preview() {
		$groupKey = $this->request['group'];
		$cache = $this->registry->profunctions->GetCaches($groupKey);

		$cache['menus'] = $this->registry->profunctions->ParseMenus($cache['menus'], 0, $cache['groups'], TRUE);

		if (count($cache['menus']) && is_array($cache['menus'])) {

			if ($groupKey === "primary") {
				$idKey = "community_app_menu";
			} else if ($groupKey === "header") {
				$idKey = "header_app_menu";
			} else {
				$idKey = $groupKey;
			}
			$html .= <<<EOF
			<script>projQ = jQuery;</script>
            <script src="{$this->settings['public_dir']}/js/ips.promenu.js"></script>
EOF;

                        
            $html .= <<<EOF
				<style>
					{$this->registry->profunctions->GetCss()}
					#{$idKey} ul { list-style: none;}
					#{$idKey}  li {
						cursor: pointer;
						top: 0;
					}
					.ipsList_inline > li {
						display: inline-block;
						margin: 0 3px;
					}
					body, div, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6, pre, form, fieldset, input, textarea, p, blockquote, th, td {
						margin: 0;
						padding: 0;
					}				
					#{$idKey} a {
						text-decoration: none;
					}
					.ipsList_inline > li {
						display: inline-block;
					}
					#{$idKey}{
						list-style:none;
					}
					#{$idKey} .boxShadow {
						-webkit-box-shadow: rgba(0, 0, 0, 0.58) 0px 12px 25px;
						-moz-box-shadow: rgba(0, 0, 0, 0.58) 0px 12px 25px;
						box-shadow: rgba(0, 0, 0, 0.58) 0px 12px 25px;
					}
					#{$idKey}  {
						font: normal 13px helvetica, arial, sans-serif;
					}
				</style>
EOF;
			if ($groupKey === "primary") {
				$m =$this->registry->promenuHooks->menus(array('preview'=> 1,'cache' => $cache, 'template' => 'proMain', 'ulID' => 'community_app_menu', 'jsMenuEnabled' => TRUE));
				$html .= <<<EOF
				<div id="primary_nav">
					<div class="main_width">
						<ul class="ipsList_inline" id="community_app_menu">	
EOF;
				$html .= $m['html'];
				$html .= <<<EOF
						</ul>
					</div>
				</div>
EOF;
			} else if ($groupKey === "header") {
				$html .= <<<EOF
				<div id="header_menu" style="position:static;">
					<div class="main_width">
EOF;
				if($cache['groups']['promenu_groups_is_vertical'])
				{
					$html .=<<<EOF
						<ul class="ipsList_vertical" id="header_app_menu">
EOF;
				}
				else{
					$html .=<<<EOF
						<ul class="ipsList_inline" id="header_app_menu">
EOF;
				}
				$m = $this->registry->promenuHooks->menus(array('preview'=> 1,'cache' => $cache, 'template' => 'proMain', 'ulID' => 'header_app_menu', 'jsMenuEnabled' => TRUE, 'isPrevew' => 1));
				$html .= $m['html'];

				$html .= <<<EOF
						</ul>
					</div>
				</div>
EOF;
			} else if ($groupKey === "site") {
				$html .= <<<EOF
				<div id="site_menu" class="clearfix" style="position:static;">
					<div class="main_width">
EOF;
				$html .= $this->registry->promenuHooks->menus(array('preview'=> 1,'cache' => $cache, 'template' => 'proOther', 'ulID' => 'site_menu', 'jsMenuEnabled' => FALSE));

				$html .= <<<EOF
					</div>
				</div>
EOF;
			} else if ($groupKey === "footer") {
				$html .= <<<EOF
				<div id="footer_menu" class="clearfix clear">
					<div class="main_width">
EOF;
				$html .= $this->registry->promenuHooks->menus(array('preview'=> 1,'cache' => $cache, 'template' => 'proOther', 'ulID' => 'footer_menu', 'jsMenuEnabled' => FALSE));

				$html .= <<<EOF
					</div>
				</div>
EOF;
			} else {
				$m = $this->registry->promenuHooks->menus(array('preview'=> 1,'cache' => $cache, 'template' => $cache['groups']['promenu_groups_template'], 'ulID' => $id . '_app_menu', 'jsMenuEnabled' => TRUE, 'isPrevew' => 1));
				if (is_file(DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . "/style_css/" . $this->registry->output->skin['_csscacheid'] . "/" . $groupKey . ".css")) {

					$style = $this->registry->profunctions->GetContent(DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . "/style_css/" . $this->registry->output->skin['_csscacheid'] . "/" . $groupKey . ".css");
				} else {
					if ($cache['groups']['promenu_groups_template'] === "proMain") {
						$men = $m['html'];
						$style = $this->registry->profunctions->getHookData('proMainCss.css');
					} else {
						$men = $m;
						$style = $this->registry->profunctions->getHookData('proOtherCss.css');
					}

					$style = str_replace("{menu_id}", $cache['groups']['promenu_groups_name'], $style);

					$style = $this->registry->output->outputFormatClass->parseIPSTags(stripslashes(trim($style)));
				}
				if ($cache['groups']['promenu_groups_template'] === "proMain") {
					$men = $m['html'];
				} else {
						$men = $m;
				}
				$group = $groupKey;
				$id = $group;
				$html .= <<<EOF
				<style type="text/css">
				{$style}
				</style>
				<div id="{$id}" class="clear" style="position:static;">
					<div class="main_width">
EOF;
				if($cache['groups']['promenu_groups_is_vertical'])
				{
					$html .=<<<EOF
						<ul class="ipsList_vertical" id="{$id}_app_menu">
EOF;
				}
				else{
					$html .=<<<EOF
						<ul class="ipsList_inline" id="{$id}_app_menu">
EOF;
				}

				$html .= $men;

				$html .= <<<EOF
						</ul>
					</div>
				</div>
EOF;
			}
		}
		$html .= <<<EOF
			<div id="content"><h1 class="ipsType_pagetitle" style="text-align:center;">{$this->lang->words['promenu_word_preview']}</h1></div>
EOF;

		print $html;
	}

}