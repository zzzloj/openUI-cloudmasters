<?php
/**
 * @file		generate.php 	Navigation plugin: Blog
 * $Copyright: (c) 2001 - 2012 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: AndyMillne $ 
 * @since		7/24/2012
 * $LastChangedDate: 2012-07-25 18:49:37 -0400 (Wed, 25 Jul 2012) $
 * @version		v2.6.3
 * $Revision: 11134 $
 */

class navigation_blog
{
        /**
         * Registry Object Shortcuts
         *
         * @var         $registry
         * @var         $DB
         * @var         $settings
         * @var         $request
         * @var         $lang
         * @var         $member
         * @var         $memberData
         * @var         $cache
         * @var         $caches
         */
		protected $registry;
		protected $DB;
		protected $settings;
		protected $request;
		protected $lang;
		protected $member;
		protected $memberData;
		protected $cache;
		protected $caches;

        /**
         * Constructor
         *
         * @return      @e void
         */
        public function __construct()
        {
                $this->registry		=  ipsRegistry::instance();
                $this->DB			=  $this->registry->DB();
                $this->settings		=& $this->registry->fetchSettings();
                $this->request		=& $this->registry->fetchRequest();
                $this->member		=  $this->registry->member();
                $this->memberData	=& $this->registry->member()->fetchMemberData();
                $this->cache		=  $this->registry->cache();
                $this->caches		=& $this->registry->cache()->fetchCaches();
                $this->lang			=  $this->registry->class_localization;
                
                /* Load the lang loquaciously */
                $this->lang->loadLanguageFile( array( 'public_blog' ), 'blog' );
                
                /* Build the blog functions bodaciously */
                $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
                $this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
        }
        
        /**
         * Return the tab title
         *
         * @return      @e string
         */
        public function getTabName()
        {
                return IPSLib::getAppTitle( 'blog' );
        }
        
        /**
         * Returns navigation data
         *
         * @return      @e array
         */
        public function getNavigationData()
        {
                $blocks = array();
                
                $blogStats = $this->cache->getCache('blog_stats');
                
                /* Important Blogs */ 
                if ( $blogStats['stats_num_blogs'] )
                {
                	if ( is_array( $blogStats['pinned'] ) AND count( $blogStats['pinned'] ) )
                	{            		
                		$whereExtra     = '';
                		$importantBlogLinks	= array();
                		               		
                		if ( ! $this->memberData['member_id'] )
                		{
                			$whereExtra = " AND b.blog_allowguests = 1";
                		}
                		
                		$this->DB->build( array( 'select'	=> 'b.*',
                				'from'		=> array( 'blog_blogs' => 'b' ),
                				'where'	=> 'b.blog_id IN (' . implode( ',', array_values( $blogStats['pinned'] ) ) . ') AND b.blog_disabled=0 AND blog_type=\'local\' AND b.blog_view_level=\'public\'' . $whereExtra,
                				'order'    => 'b.blog_last_edate DESC',
                				'limit'    => array( 0, 20 ) ) );
                		
                		$o = $this->DB->execute();
                
                		while( $row = $this->DB->fetch( $o ) )
                		{
                			$importantBlogLinks[] = array( 'important' => false,
                					'depth'		=> 0,
                					'title'		=> $row['blog_name'],
                					'url'		=> $this->registry->output->buildSEOUrl( 'app=blog&amp;module=display&amp;section=blog&amp;blogid=' . $row['blog_id'], 'public', $row['blog_seo_name'], 'showblog' )
                			);                			
                		}
                	}
                }
                
                if ( count( $importantBlogLinks ) )
                {                		
	                $blocks[] = array(
	                		'title' => $this->lang->words['bloglist_start_pinned'],
	                		'links' => $importantBlogLinks
	                );
                }
                
                /* Your Blogs */                
                $myBlogs = $this->registry->getClass('blogFunctions')->fetchMyBlogs( $this->memberData, true );
                             
                if ( count( $myBlogs ) )
                {
                	$myBlogLinks = array();
                	
                	foreach( $myBlogs as $id => $blog )
                	{
                		if( $blog['_type'] != 'editor' AND $blog['_type'] != 'privateclub' )
                		{
                			$myBlogLinks[] = array( 'important' => false,
                									'depth'		=> 0,
                									'title'		=> $blog['blog_name'],
                									'url'		=> $this->registry->output->buildSEOUrl( 'app=blog&amp;module=display&amp;section=blog&amp;blogid=' . $blog['blog_id'], 'public', $blog['blog_seo_name'], 'showblog' )
                					);
                		}
                	}
                		
                
                
	                $blocks[] = array(
	                		'title' => $this->lang->words['your_current_blogs'], /* This is NOT required, but will give your block a 'heading' */
	                		'links' => $myBlogLinks
	                );
                }

                return $blocks;
        }
}