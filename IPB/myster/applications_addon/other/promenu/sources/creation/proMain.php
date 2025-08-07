<?php
class {class_name} {
	
	public $registry;
	
	public function __construct() {
		$this -> registry = ipsRegistry::instance();
	}

	public function getOutput() {
		if( IPSLib::appIsInstalled('promenu') ){
			if (!$this->registry->isClassLoaded('app_class_promenu')) {
				$classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('promenu') . '/app_class_promenu.php', 'app_class_promenu', 'promenu');
				$this -> registry -> setClass('app_class_promenu', new $classToLoad($this -> registry));
			}
			$cache 	= $this->registry->profunctions->GetCaches('{group}');
			if(!$cache['groups']['promenu_groups_make_super'])
			{
				$cache['menus'] 	= $this->registry->profunctions->ParseMenus( $cache['menus'], 0, $cache['groups']);
			}			
			if( count($cache['menus']) && is_array($cache['menus']) && $cache['groups']['promenu_groups_enabled'] && !in_array($this->registry->output->skin['set_id'],explode(",",$cache['groups']['promenu_groups_hide_skin'] ) ) && !$cache['groups']['promenu_groups_make_super'] ){			
				$data = $this->registry->promenuHooks->menus( array( 'cache' => $cache, 'template' => '{template}', 'ulID' => '{menu_id}_app_menu', 'jsMenuEnabled' => TRUE) );
				$html = '';

				$html .= <<<EOF
				<div id="{menu_id}">
					<div class="main_widths clearfix">
EOF;
				if($cache['groups']['promenu_groups_is_vertical'])
				{
					$html .=<<<EOF
						<ul class="ipsList_vertical" id="{menu_id}_app_menu">
EOF;
				}
				else{
					$html .=<<<EOF
						<ul class="ipsList_inline" id="{menu_id}_app_menu">
EOF;
				}
				$html .= $data['html'];
				
				$html .= <<<EOF
						</ul>
					</div>
				</div>
				{$data['rhtml']}
EOF;
				return $html;
			}
		}
	}
}