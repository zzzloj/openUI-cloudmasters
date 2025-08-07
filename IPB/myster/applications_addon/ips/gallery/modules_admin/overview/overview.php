<?php
/**
 * @file		overview.php 	Gallery overview methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * $LastChangedDate: 2012-12-19 23:26:26 -0500 (Wed, 19 Dec 2012) $
 * @version		v5.0.5
 * $Revision: 11737 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_overview_overview
 * @brief		Gallery overview methods
 */
class admin_gallery_overview_overview extends ipsCommand 
{
	/**
	 * Skin object shortcut
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		string
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		string
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//---------------------------------------
		// INIT
		//---------------------------------------

		$this->html	= $this->registry->output->loadTemplate( 'cp_skin_gallery_overview' );

		$this->lang->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		$this->html->form_code    = $this->form_code    = 'module=overview&amp;section=overview&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=overview&section=overview&';

		//---------------------------------------
		// What to do
		//---------------------------------------

		switch( $this->request['do'] )
		{
			case 'update_stats':
				$this->registry->gallery->rebuildStatsCache();
				$this->registry->output->setMessage( $this->lang->words['overview_stats_updated'] );
				/* We intentionally do not 'break' here so that indexScreen() is called */
			
			default:
				$this->indexScreen();
				break;
		}
		
		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Displays the gallery overview screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		//---------------------------------------
		// Load Cached Stats Data
		//---------------------------------------

		if( !is_array( $this->caches['gallery_stats'] ) OR !count( $this->caches['gallery_stats'] ) )
		{
			$this->cache->getCache('gallery_stats');
		}
		
		//---------------------------------------
		// Check for common issues
		//---------------------------------------
		
		$warnings = $this->doSystemCheck();
		
		//---------------------------------------
		// Stats
		//---------------------------------------

		$stats = array(
						'images'		=> $this->caches['gallery_stats']['total_images_visible'] + $this->caches['gallery_stats']['total_images_hidden'],
						'diskspace'		=> $this->caches['gallery_stats']['total_diskspace'],
						'comments'		=> $this->caches['gallery_stats']['total_comments_visible'] + $this->caches['gallery_stats']['total_comments_hidden'],
						'albums'		=> $this->caches['gallery_stats']['total_albums'],
						'categories'	=> $this->caches['gallery_stats']['total_categories'],
						'views'			=> $this->caches['gallery_stats']['total_views'],
					);

		//---------------------------------------
		// Output
		//---------------------------------------

		$this->registry->output->html .= $this->html->galleryOverview( $warnings, $stats );
	}
	
	/**
	 * Checks various gallery settings to see if they are correctly configured
	 *
	 * @return	@e array
	 */
	public function doSystemCheck()
	{
		//---------------------------------------
		// Init
		//---------------------------------------

		$errors = array();

		if( $this->settings['gallery_images_path'] == './uploads' )
		{
			$this->settings['gallery_images_path'] = str_replace( './', ROOT_PATH, $this->settings['gallery_images_path'] );
		}
		
		if( !is_dir($this->settings['gallery_images_path']) )
		{
			$errors[] = array( $this->lang->words['overview_errors_1'], 
							   "{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Directory to store images'), 
							   $this->lang->words['overview_dir_store_images'],
							   $this->lang->words['overview_fixes_1'] );
		}
		elseif( !is_writable($this->settings['gallery_images_path']) )
		{
			$errors[] = array( $this->lang->words['overview_errors_2'], 
							   "{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Directory to store images'),
							   $this->lang->words['overview_dir_store_images'],
							   sprintf( $this->lang->words['overview_fixes_2'], IPS_FOLDER_PERMISSION ) );
		}
		
		if( $this->settings['gallery_img_suite'] == 'im' )
		{
			if( ! file_exists( $this->settings['gallery_im_path'] ) )
			{
				$errors[] = array(	$this->lang->words['overview_errors_5'],
  									"{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Full path to Image Magick'),
  									$this->lang->words['overview_im_path'],
									$this->lang->words['overview_fixes_5'] );
			}
		}
		else
		{
			$gd_installed = extension_loaded( 'gd' );
			
			if( !$gd_installed )
			{
				$errors[] = array(	$this->lang->words['overview_errors_6'],
  									"{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Select an image suite to use'),
  									$this->lang->words['overview_select_suite'],
									$this->lang->words['overview_fixes_6'] );
			}
			else
			{
				if( function_exists( "gd_info" ) )
				{
					$info = gd_info();
					
					if( strpos( $info['GD Version'], '2' ) === false )
					{
						$errors[] = array(  $this->lang->words['overview_errors_10'],
					   						"{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Select an image suite to use'),
					   						$this->lang->words['overview_select_suite'],
						   					sprintf( $this->lang->words['overview_fixes_10'], $info['GD Version'] ) );
					}
					
					if( !$info['JPG Support'] AND !$info['JPEG Support'] )
					{
						$errors[] = array( $this->lang->words['overview_errors_7'],
										   "{$this->settings['_base_url']}&amp;app=core&amp;module=diagnostics&amp;section=diagnostics&amp;phpinfo=1",
										   $this->lang->words['overview_phpinfo'],
										   $this->lang->words['overview_fixes_7'] );
					}
					
					if( !$info['GIF Read Support'] OR !$info['GIF Create Support'] )
					{
						$errors[] = array( $this->lang->words['overview_errors_8'],
										   "{$this->settings['_base_url']}&amp;app=core&amp;module=diagnostics&amp;section=diagnostics&amp;phpinfo=1",
										   $this->lang->words['overview_phpinfo'],
										   $this->lang->words['overview_fixes_8'] );
					}
					
					if( !$info['PNG Support'] )
					{
						$errors[] = array( $this->lang->words['overview_errors_9'],
										   "{$this->settings['_base_url']}&amp;app=core&amp;module=diagnostics&amp;section=diagnostics&amp;phpinfo=1",
										   $this->lang->words['overview_phpinfo'],
										   $this->lang->words['overview_fixes_9'] );
					}
				}
			}
		}
		
		if( !empty($this->settings['gallery_watermark_path']) )
		{
			if( !is_file($this->settings['gallery_watermark_path']) )
			{
				$errors[] = array( $this->lang->words['overview_errors_13'],
								   "{$this->settings['_base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;search=".urlencode('Full path to watermark image'),
								   $this->lang->words['overview_watermark'],
								   $this->lang->words['overview_fixes_13'] );
			}
		}
		
		//---------------------------------------
		// Return any errors
		//---------------------------------------

		return $errors;
	}
}