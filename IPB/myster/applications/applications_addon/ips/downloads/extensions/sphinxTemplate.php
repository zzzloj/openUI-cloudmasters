<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Sphinx template file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$fields	= array();

ipsRegistry::DB()->build( array( 'select' => 'cf_id', 'from' => 'downloads_cfields' ) );
ipsRegistry::DB()->execute();

while( $r = ipsRegistry::DB()->fetch() )
{
	$fields[]	= $r['cf_id'];
}

$_join	= '';

if( count($fields) )
{
	$_join	= ', cc.field_' . implode( ', cc.field_', $fields );
}

$appSphinxTemplate	= <<<EOF

############################ --- DOWNLOADS --- ##############################

source <!--SPHINX_CONF_PREFIX-->downloads_search_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_downloads_counter', (SELECT max(file_id) FROM <!--SPHINX_DB_PREFIX-->downloads_files), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT f.file_id as dont_use_this, f.file_id as search_id, f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc{$_join}, f.* \
					  FROM <!--SPHINX_DB_PREFIX-->downloads_files f \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_ccontent cc ON (cc.file_id=f.file_id)
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= file_id
	sql_attr_uint			= file_cat
	sql_attr_uint			= file_open
	sql_attr_uint			= file_views
	sql_attr_uint			= file_rating
	sql_attr_uint			= file_downloads
	sql_attr_timestamp		= file_updated
	sql_attr_timestamp		= file_submitted
	sql_attr_uint			= file_submitter
	sql_attr_str2ordinal	= fordinal
	sql_attr_float			= file_cost
	sql_attr_multi			= uint tag_id from query; SELECT tag_meta_id, tag_id FROM <!--SPHINX_DB_PREFIX-->core_tags WHERE tag_meta_app='downloads' AND tag_meta_area='files'
	
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->downloads_search_delta : <!--SPHINX_CONF_PREFIX-->downloads_search_main
{
	# Override the base sql_query_pre
	sql_query_pre	= 
	
	# Query posts for the main source
	sql_query		= SELECT f.file_id as dont_use_this, f.file_id as search_id, f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->downloads_files f \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_ccontent cc ON (cc.file_id=f.file_id) \
					  WHERE f.file_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_downloads_counter' )
}

index <!--SPHINX_CONF_PREFIX-->downloads_search_main
{
	source			= <!--SPHINX_CONF_PREFIX-->downloads_search_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->downloads_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index <!--SPHINX_CONF_PREFIX-->downloads_search_delta : <!--SPHINX_CONF_PREFIX-->downloads_search_main
{
   source			= <!--SPHINX_CONF_PREFIX-->downloads_search_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->downloads_search_delta
}

source <!--SPHINX_CONF_PREFIX-->downloads_comments_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_downloads_comments_counter', (SELECT max(comment_id) FROM <!--SPHINX_DB_PREFIX-->downloads_comments), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.comment_mid as comment_member_id, c.comment_date, c.comment_open, c.comment_text, \
	 						 f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->downloads_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_files f ON ( c.comment_fid=f.file_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_ccontent cc ON (cc.file_id=f.file_id) 
	
	# Fields
	sql_attr_uint			= search_id
	sql_attr_uint			= file_id
	sql_attr_uint			= file_cat
	sql_attr_uint			= file_open
	sql_attr_uint			= file_views
	sql_attr_uint			= file_rating
	sql_attr_uint			= file_downloads
	sql_attr_uint			= comment_open
	sql_attr_uint			= comment_member_id
	sql_attr_timestamp		= comment_date
	sql_attr_timestamp		= file_updated
	sql_attr_timestamp		= file_submitted
	sql_attr_uint			= file_submitter
	sql_attr_str2ordinal	= fordinal
	sql_attr_float			= file_cost
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->downloads_comments_delta : <!--SPHINX_CONF_PREFIX-->downloads_comments_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.comment_mid as comment_member_id, c.comment_date, c.comment_open, c.comment_text, \
	 						 f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->downloads_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_files f ON ( c.comment_fid=f.file_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->downloads_ccontent cc ON (cc.file_id=f.file_id) \
					  WHERE c.comment_id <= ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_downloads_comments_counter' )	
}

index <!--SPHINX_CONF_PREFIX-->downloads_comments_main
{
	source			= <!--SPHINX_CONF_PREFIX-->downloads_comments_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->downloads_comments_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = comment_text
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->downloads_comments_delta : <!--SPHINX_CONF_PREFIX-->downloads_comments_main
{
   source			= <!--SPHINX_CONF_PREFIX-->downloads_comments_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->downloads_comments_delta
}

EOF;
