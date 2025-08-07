<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 *
 */



class sql_blog_queries extends db_driver_mysql
{
     protected $db  = "";
     protected $tbl = "";

    /*========================================================================*/
    // Set up...
    /*========================================================================*/

    function sql_blog_queries( &$obj )
    {
    	$this->db	= &$obj;
    	$this->tbl	= ips_DBRegistry::getPrefix();
    }

    /*========================================================================*/

    function blog_rebuild_getcounts( $a )
    {
    	return "SELECT SUM(CASE WHEN entry_status='published' THEN 1 ELSE 0 END) as blog_num_entries,
    				   SUM(CASE WHEN entry_status='draft' THEN 1 ELSE 0 END) as blog_num_drafts
     			FROM {$this->tbl}blog_entries WHERE blog_id={$a['blog_id']} {$a['extra']}";
    }

	function random_album_image( $a )
	{
		return "SELECT b.id, b.caption, b.directory, b.masked_file_name, b.file_name, b.file_type, b.thumbnail, b.media
			    FROM {$this->tbl}gallery_albums a, {$this->tbl}gallery_images b
			    WHERE a.id=b.album_id and a.member_id = {$a['member_id']} {$a['extra']}
				ORDER BY RAND()
				LIMIT 0,1";
	}

    function updateblogviews_get( $a )
    {
    	return "SELECT blog_id, entry_id, COUNT(*) as blogviews
    		   FROM {$this->tbl}blog_views
    		   GROUP BY entry_id";
    }

} // end class


?>