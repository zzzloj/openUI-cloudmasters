<?php

/**
 * Product Title:		[HQ] Badges
 * Product Version:		1.5.0
 * Author:				InvisionHQ - G. Venturini
 * Website:				Lamoneta.it
 * Website URL:			http://lamoneta.it/
 * Email:				reficul@lamoneta.it
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_badges_overview_overview extends ipsCommand
{
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_overview' );

		/* Set up stuff */
		$this->form_code		  = $this->html->form_code	= 'module=overview&amp;section=overview';
		$this->form_code_js		  = $this->html->form_code_js	= 'module=overview&section=overview';
		$this->html->form_code_js = 'module=overview&section=overview';

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{
			case 'settings':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage_settings' );
				$this->_blockSettings();
				break;
							
			case 'overview':
			default:
				$this->home();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}


	/*-------------------------------------------------------------------------*/
	// Home
	/*-------------------------------------------------------------------------*/

	public function home()
	{
		$result = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_applications', 'where' => "app_directory='badges'" ) );
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
		$checker = new $classToLoad();

		/* Timeout to prevent page from taking too long */
		$checker->timeout = 5;
		
		/* Setup url and check */
		$url = "http://ipb.bbcode.it/resource_updates.php?resource=badges&version={$result['app_long_version']}&boardVersion=" . IPB_LONG_VERSION;
		$return = $checker->getFileContents( $url );
		$x = explode( '|', $return );

		if( $x[0] == '0' )
		{
			$dwnld = "<span class='ipsBadge badge_green'>Up to date</span>";
		}
		else
		{
			$dwnld = "";
			$dwnld = "<span class='ipsBadge badge_purple'>Update Available</span>";
			if( ! empty( $x[1] ) )
			{
				$dwnld = "<a href='{$x[1]}' target='_blank'>{$dwnld}</a>";
			}
			elseif( $result['app_website'] )
			{
				$dwnld = "<a href='{$result['app_website']}' target='_blank'>{$dwnld}</a>";
			}
		}
				
		/* Upgrade history */
		$this->DB->build( array( 'select' => 'upgrade_version_id, upgrade_version_human, upgrade_date',
								 'from'   => 'upgrade_history',
								 'where'  => "upgrade_app='badges'",
								 'order'  => 'upgrade_version_id DESC',
								 'limit'  => array( 0, 2 )
		) );
   		
		$this->DB->execute();
		
   		while ( $row = $this->DB->fetch() )
   		{
   			$row['_date'] = $this->registry->getClass('class_localization')->formatTime( $row['upgrade_date'], 'SHORT' );
   			$data['upgrade'][] = $row;
   		}
   		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'HQ_badges'
		) );
   		
		$this->DB->execute();
		
		$ativo	  = 0;
		$plugin = array();
		$hqplugin  = array();
		
   		while ( $row = $this->DB->fetch() )
   		{
			if ( $row['ba_enabled'] )
			{
				$ativo++;
			}
   		}

   		// check released plugins
   		$hqplugin = $this->plugin_info( "http://www.bbcode.it/hq-badges-plugins.txt" );
   		$hqplugin = explode( '|', $hqplugin );
   		$i = 0;
   		foreach ($hqplugin as $rplugin) {
   			$plugins[$i] = explode( ',', $rplugin );
   			$i++;
   		}
   		
   		// check installed plugins
   		$path = IPSLib::getAppDir( 'badges' ) . '/modules_admin/';
		$results = scandir($path);
		$i = 0;
		foreach ($results as $result) {
		    if ($result === '.' or $result === '..') continue;

		    if (is_dir($path . '/' . $result)) {
		        $plugin[$i] = $result;
		        $i++;
		    }
		}
		
		$this->registry->output->html .= $this->html->overviewIndex( $data, $ativo, $plugin, $plugins, $dwnld );
	}

	public function plugin_info($url) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
	    curl_setopt($ch, CURLOPT_URL, $url);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    return $data;
	}

}