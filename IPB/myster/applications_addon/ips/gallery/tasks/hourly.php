<?php
/**
 * @file		hourly.php 	Task to perform hourly cleanup
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2012-10-16 19:58:19 -0400 (Tue, 16 Oct 2012) $
 * @version		v5.0.5
 * $Revision: 11470 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		task_item
 * @brief		Removes old bandwidth logs and deletes temporary images that weren't finalized
 */
class task_item
{
	/**
	 * Limit on the number of temporary images to delete for each call
	 *
	 * @var		int
	 */
	protected $loadLimit	= 100;
	
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		object
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		array
	 */
	protected $task		= array();
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $lang;
	/*#@-*/
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @param	object		$class			Task manager class object
	 * @param	array		$task			Array with the task data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		//-----------------------------------------
		// Create shortcuts
		//-----------------------------------------

		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->settings	=& $this->registry->fetchSettings();
		$this->lang		= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;

		//-----------------------------------------
		// Load language file
		//-----------------------------------------

		$this->lang->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );
	}
	
	/**
	 * Run this task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{
		//-----------------------------------------
		// Get bandwidth time cutoff
		//-----------------------------------------

		$bwCutoff	= time() - ( $this->settings['gallery_bandwidth_period'] * 3600 );

		//-----------------------------------------
		// Clear old bandwidth logs
		//-----------------------------------------

		$this->DB->delete( 'gallery_bandwidth', "bdate < " . intval( $bwCutoff ) );
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_bw_trimmed'] );
		
		//-----------------------------------------
		// Fetch old temporary images
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images_uploads',
								 'where'  => 'upload_date < ' . ( time() - 86400 ),
								 'limit'  => array( 0, $this->loadLimit )
						 )		);

		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// Got any results?
		//-----------------------------------------

		if ( $this->DB->getTotalRows( $outer ) )
		{
			$deleteIds	= array();
			
			//-----------------------------------------
			// Make sure we have our gallery object
			//-----------------------------------------

			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
			
			//-----------------------------------------
			// Loop over the rows
			//-----------------------------------------

			while( $row = $this->DB->fetch( $outer ) )
			{
				//-----------------------------------------
				// Remap and delete files
				//-----------------------------------------

				$row	= $this->registry->gallery->helper('upload')->_remapAsImage( $row );
				
				if ( $this->registry->gallery->helper('moderate')->removeImageFiles( $row ) )
				{
					$deleteIds[]	= "'{$row['image_id']}'";
				}
			}

			//-----------------------------------------
			// If we deleted files, delete db records
			//-----------------------------------------

			if ( count($deleteIds) )
			{
				$this->DB->delete( 'gallery_images_uploads', 'upload_key IN (' . implode(',', $deleteIds) . ')' );
				
				$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_tmp_images_gone'], count($deleteIds) ) );
			}
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}