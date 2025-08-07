<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Reputation configuration for application
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$rep_author_config = array( 
						'comment_id' => array( 'column' => 'comment_mid', 'table'  => 'downloads_comments' )
					);
					
/*
 * The following config items are for the log viewer in the ACP 
 */

$rep_log_joins = array(
						array(
								'from'   => array( 'downloads_comments' => 'c' ),
								'where'  => 'r.type="pid" AND r.type_id=c.comment_id AND r.app="downloads"',
								'type'   => 'left'
							),
						array(
								'select' => 'f.file_name as repContentTitle, f.file_id as repContentID',
								'from'   => array( 'downloads_files' => 'f' ),
								'where'  => 'c.comment_fid=f.file_id',
								'type'   => 'left'
							),
					);

$rep_log_where = "c.comment_mid=%s";

$rep_log_link = 'app=downloads&amp;showfile=%d#comment_%d';