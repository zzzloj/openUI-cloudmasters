<?php

class promenu_upgradeCheck
{
	/**
	 * Check we can upgrade
	 *
	 * @return	mixed	Boolean true or error message
	 */
	public function checkForProblems()
	{
		//-----------------------------------------
		// Compatibility check
		//-----------------------------------------
		
		$requiredIpbVersion = 32000; // 3.2.0
		
		$args = func_get_args();
		if ( !empty( $args ) )
		{
			$numbers = IPSSetUp::fetchAppVersionNumbers( 'core' );
		
			/* Are we upgrading core now? */
			if ( isset( $args[0]['core'] ) )
			{
				$ourVersion = $numbers['latest'][0];
			}
			/* No - check installed version */
			else
			{
				$ourVersion = $numbers['current'][0];
			}
			
			if ( $requiredIpbVersion > $ourVersion )
			{
				$allVersions = IPSSetUp::fetchXmlAppVersions( 'core' );
				
				return "This version of ProMenu requires IP.Board {$allVersions[ $requiredIpbVersion ]} or higher.";
			}
		}
		
		$list = array();
		
		/* lets build the paths */
		$sourcesPath = IPSLib::getAppDir('promenu') . '/sources/';
		$classpath = IPSLib::getAppDir('promenu') . '/sources/classes/';
		$overviewPath = IPSLib::getAppDir('promenu') . '/modules_admin/overview/';
		$skincpPath = IPSLib::getAppDir('promenu') . '/skin_cp/';
		$hooksPath = IPSLib::getAppDir('promenu') . '/xml/hooks/';

		/* time to unlink the files and remove the directories */
		if (file_exists($sourcesPath . 'hooks.php')) {
			if(!is_writable($sourcesPath . 'hooks.php')) {
			$list[] = $sourcesPath . 'hooks.php';
			}
		}
		if (file_exists($sourcesPath . 'news_update.php')) {
			if(!is_writable($sourcesPath . 'news_update.php')) {
			$list[] = $sourcesPath . 'news_update.php';
			}
		}
		if (file_exists($classpath . 'class_bugs.php')) {
			if(!is_writable($classpath . 'class_bugs.php')) {
			$list[] = $classpath . 'class_bugs.php';
			}
		}
		if (file_exists($classpath . 'class_functions.php')) {
			if(!is_writable($classpath . 'class_functions.php')) {
			$list[] = $classpath . 'class_functions.php';
			}
		}
		if (file_exists($classpath . 'class_groups.php')) {
			if(!is_writable($classpath . 'class_groups.php')) {
			$list[] = $classpath . 'class_groups.php';
			}
		}
		if (file_exists($classpath . 'class_menus.php')) {
			if(!is_writable($classpath . 'class_menus.php')) {
			$list[] = $classpath . 'class_menus.php';
			}
		}
		if (file_exists($classpath . 'class_perms.php')) {
			if(!is_writable($classpath . 'class_perms.php')) {
			$list[] = $classpath . 'class_perms.php';
			}
		}
		if (file_exists($classpath . 'index.html')) {
			if(!is_writable($classpath . 'index.html')) {
			$list[] = $classpath . 'index.html';
			}
		}
		if (file_exists($overviewPath . 'overview.php')) {
			if(!is_writable($overviewPath . 'overview.php')) {
			$list[] = $overviewPath . 'overview.php';
			}
		}		
		if (file_exists($overviewPath . 'defaultSection.php')) {
			if(!is_writable($overviewPath . 'defaultSection.php')) {
			$list[] = $overviewPath . 'defaultSection.php';
			}
		}
		if (file_exists($overviewPath . 'index.html')) {
			if(!is_writable($overviewPath . 'index.html')) {
			$list[] = $overviewPath . 'index.html';
			}
		}
		if (file_exists($overviewPath . 'xml/menu.xml')) {
			if(!is_writable($overviewPath . 'xml/menu.xml')) {
			$list[] = $overviewPath . 'xml/menu.xml';
			}
		}
		if (file_exists($overviewPath . 'xml/permissions.xml')) {
			if(!is_writable($overviewPath . 'xml/permissions.xml')) {
			$list[] = $overviewPath . 'xml/permissions.xml';
			}
		}
		if (file_exists($overviewPath . 'xml/index.html')) {
			if(!is_writable($overviewPath . 'xml/index.html')) {
			$list[] = $overviewPath . 'xml/index.html';
			}
		}
		if (is_dir($overviewPath . 'xml')) {
			if(!is_writable($overviewPath . 'xml')) {
			$list[] = $overviewPath . 'xml';
			}
		}
		if (is_dir(IPSLib::getAppDir('promenu') . '/modules_admin/overview')) {
			if(!is_writable(IPSLib::getAppDir('promenu') . '/modules_admin/overview')) {
			$list[] = IPSLib::getAppDir('promenu') . '/modules_admin/overview';
			}
		}		
		if (file_exists($skincpPath . 'cp_skin_add_group.php')) {
			if(!is_writable($skincpPath . 'cp_skin_add_group.php')) {
			$list[] = $skincpPath . 'cp_skin_add_group.php';
			}
		}		
		if (file_exists($skincpPath . 'cp_skin_add_menu.php')) {
			if(!is_writable($skincpPath . 'cp_skin_add_menu.php')) {
			$list[] = $skincpPath . 'cp_skin_add_menu.php';
			}
		}	
		if (file_exists($skincpPath . 'cp_skin_edit_group.php')) {
			if(!is_writable($skincpPath . 'cp_skin_edit_group.php')) {
			$list[] = $skincpPath . 'cp_skin_edit_group.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_edit_menu.php')) {
			if(!is_writable($skincpPath . 'cp_skin_edit_menu.php')) {
			$list[] = $skincpPath . 'cp_skin_edit_menu.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_edit_perms.php')) {
			if(!is_writable($skincpPath . 'cp_skin_edit_perms.php')) {
			$list[] = $skincpPath . 'cp_skin_edit_perms.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_edit_visibility.php')) {
			if(!is_writable($skincpPath . 'cp_skin_edit_visibility.php')) {
			$list[] = $skincpPath . 'cp_skin_edit_visibility.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_groups.php')) {
			if(!is_writable($skincpPath . 'cp_skin_groups.php')) {
			$list[] = $skincpPath . 'cp_skin_groups.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_menus.php')) {
			if(!is_writable($skincpPath . 'cp_skin_menus.php')) {
			$list[] = $skincpPath . 'cp_skin_menus.php';
			}
		}
		if (file_exists($skincpPath . 'cp_skin_overview.php')) {
			if(!is_writable($skincpPath . 'cp_skin_overview.php')) {
			$list[] = $skincpPath . 'cp_skin_overview.php';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Bottom.Bar.Display.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Bottom.Bar.Display.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Bottom.Bar.Display.Tool.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Footer.Display.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Footer.Display.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Footer.Display.Tool.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Header.Display.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Header.Display.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Header.Display.Tool.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Javascripts.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Javascripts.xml')) {
			$list[] = $hooksPath . 'Promenu.Javascripts.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Mobile.Primary.Display.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Mobile.Primary.Display.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Mobile.Primary.Display.Tool.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Primary.Display.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Primary.Display.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Primary.Display.Tool.xml';
			}
		}
		if (file_exists($hooksPath . 'Promenu.Removal.Tool.xml')) {
			if(!is_writable($hooksPath . 'Promenu.Removal.Tool.xml')) {
			$list[] = $hooksPath . 'Promenu.Removal.Tool.xml';
			}
		}
		if (file_exists(IPSLib::getAppDir('promenu') . '/modules_admin/menus/groups.php')) {
			if(!is_writable(IPSLib::getAppDir('promenu') . '/modules_admin/menus/groups.php')) {
			$list[] = IPSLib::getAppDir('promenu') . '/modules_admin/menus/groups.php';
			}
		}
		
		if (count($list) && is_array($list)) {
			$list = implode("<br />", $list);
			$thereturn = <<<EOF
			The list of files below will not be removed from this upgrade process and should be removed manually ... 
			<br />
			{$list}	
			<input type='submit' class='button primary' value='Continue' name='continue' onclick='TRUE' />
			<input type='submit' class='button redbutton' value='Cancel' name='cancel'  onclick='FALSE' />
EOF;
			return $thereturn;
		 }
		
		return TRUE;
	}
}