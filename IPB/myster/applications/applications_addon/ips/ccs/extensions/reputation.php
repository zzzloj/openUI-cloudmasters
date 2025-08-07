<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Reputation configuration for application
 * Last Updated: $Date: 2011-12-14 23:28:21 -0500 (Wed, 14 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10002 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$rep_author_config = array( 
						'comment_id'	=> array( 'column' => 'comment_user', 'table'  => 'ccs_database_comments' ),
					);

/* Add databases */
$databases		= ipsRegistry::instance()->cache()->getCache('ccs_databases');

foreach( $databases as $database )
{
	$rep_author_config[ 'record_id_' . $database['database_id'] ] = array( 'column' => 'member_id', 'table' => $database['database_database'], 'id_field' => 'primary_id_field' );
}


/*
 * The following config items are for the log viewer in the ACP 
 */
$rep_log_joins = array(
						array(
								'select'	=> "p.*, p.comment_record_id as repContentID",
								'from'		=> array( 'ccs_database_comments' => 'p' ),
								'where'		=> "r.type='comment_id' AND r.type_id=p.comment_id AND r.app='ccs'",
								'type'		=> 'left'
							),
					);

$letters			= range( 'a', 'z' );
$idx				= 0;
$_possibilities		= array( "p.comment_user" );

foreach( $databases as $database )
{
	$letter = implode( '', array_fill( 0, intval( $idx / count( $letters ) ) +1, $letters[ $idx % count( $letters ) ] ) );

	$idx++;
	
	if( $letter == 'p' OR $letter == 'r' )
	{
		$letter	= $letter . 'bz';
	}

	$_possibilities[]	= "{$letter}.primary_id_field";
	$rep_log_joins[]	= array(
								'select'	=> "{$letter}.primary_id_field as id_field_" . $database['database_id'],
								'from'		=> array( $database['database_database'] => $letter ),
								'where'		=> "r.type='record_id_{$database['database_id']}' AND r.type_id={$letter}.primary_id_field AND r.app='ccs'",
								'type'		=> 'left',
							 );
}


$rep_log_where				= "COALESCE(" . implode( ',', $_possibilities ) . ")=%s";

//-----------------------------------------
// Define callbacks...
//-----------------------------------------

$rep_log_link_callback	= "getCcsRepLogLink";
$rep_log_title_callback	= "getCcsRepLogTitle";

//-----------------------------------------
// Callback to get title
//-----------------------------------------

function getCcsRepLogTitle( $r )
{
	//-----------------------------------------
	// Yes this is a query inside a loop...
	// only accessed via rep logs in ACP though
	//-----------------------------------------
	
	$databases		= ipsRegistry::instance()->cache()->getCache('ccs_databases');

	if( $r['comment_id'] )
	{
		ipsRegistry::instance()->class_localization->loadLanguageFile( array( 'admin_lang' ), 'ccs' );
		
		$record		= ipsRegistry::instance()->DB()->buildAndFetch( array( 'select' => $databases[ $r['comment_database_id'] ]['database_field_title'], 'from' => $databases[ $r['comment_database_id'] ]['database_database'], 'where' => 'primary_id_field=' . $r['comment_record_id'] ) );
		
		return sprintf( ipsRegistry::instance()->class_localization->words['replog_comment_prefix'], IPSText::truncate( $record[ $databases[ $r['comment_database_id'] ]['database_field_title'] ], 64 ) );
	}
	
	foreach( $databases as $database )
	{
		if( $r[ 'id_field_' . $database['database_id'] ] )
		{
			$record		= ipsRegistry::instance()->DB()->buildAndFetch( array( 'select' => $database['database_field_title'], 'from' => $database['database_database'], 'where' => 'primary_id_field=' . $r[ 'id_field_' . $database['database_id'] ] ) );
			
			return IPSText::truncate( $record[ $databases[ $r['comment_database_id'] ]['database_field_title'] ], 64 );
		}
	}
}

//-----------------------------------------
// Callback to get link
//-----------------------------------------

function getCcsRepLogLink( $r )
{
	if( $r['comment_id'] )
	{
		return ipsRegistry::$settings['board_url'] . "/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;database={$r['comment_database_id']}&amp;record={$r['comment_record_id']}&amp;comment={$r['comment_id']}";
	}
	
	$databases		= ipsRegistry::instance()->cache()->getCache('ccs_databases');
	
	foreach( $databases as $database )
	{
		if( $r[ 'id_field_' . $database['database_id'] ] )
		{
			return ipsRegistry::$settings['board_url'] . "/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;database={$database['database_id']}&amp;record=" . $r[ 'id_field_' . $database['database_id'] ];
		}
	}
}