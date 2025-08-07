<?php
/**
 * @file		blog_entries.php 	Task to approve blog entries with a 'future' publish date
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $
 * @since		27th January 2004
 * $LastChangedDate: 2013-02-01 20:54:48 -0500 (Fri, 01 Feb 2013) $
 * @version		v2.6.3
 * $Revision: 11930 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to approve blog entries with a 'future' publish date
 *
 */
class task_item
{
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		$class
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		$task
	 */
	protected $task = array();
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $lang;
	
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
		/* Make registry objects */
		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->lang		= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;
        
		/* Load the Blog functions library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
		$registry->setClass( 'blogFunctions', new $classToLoad( $registry ) );
			
		/* Load up content blocks lib */ 
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$registry->setClass( 'cblocks', new $classToLoad( $registry ) );
    }	
	
	/**
	 * Run this task
	 * 
	 * @return	@e void
	 */
    public function runTask()
	{
		/* INIT */
		$_memberIds = array();
		$_members	= array();
		$_entries	= array();
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_blog' ), 'blog' );

		/* Query the entries */
		$this->DB->build( array( 'select' => 'entry_id, entry_author_id', 'from' => 'blog_entries', 'where' => 'entry_future_date=1 AND entry_date <=' . time() ) );
		$res = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $res ) )
		{
			$this->registry->blogFunctions->changeEntryApproval( array( $r['entry_id'] ), 1, IPSMember::load( $r['entry_author_id'] ) );
		}
		

		/* Log to log table - modify but dont delete */
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_blogentries'], count($_entries) ) );
		
		/* Unlock Task: DO NOT MODIFY! */
		$this->class->unlockTask( $this->task );
	}
}