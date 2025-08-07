<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 */

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_bbcodeTables
{
	/**
	* Constructor
	*
	* @param	object	$registry		ipsRegistry reference
	* @return	void
	* @access	public
	*/

	public function __construct( ipsRegistry $registry )
	{
        // load library
		require_once( IPSLib::getAppDir( 'bbcodeTables' ) . "/sources/classes/library.php" );
        $registry->setClass('bbcodeTables_library', new bbcodeTables_library($registry));

        // load language variables
        if(IN_ACP)
        {
        $registry->getClass('class_localization')->LoadLanguageFile(array('admin_bbcodeTables'),'bbcodeTables');
        }
        else
        {
        $registry->getClass('class_localization')->LoadLanguageFile(array('public_bbcodeTables'),'bbcodeTables');
        }

	}
}
?>