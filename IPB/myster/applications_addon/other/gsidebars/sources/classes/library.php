<?php

//-----------------------------------------------
// (DP33) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// Application Library
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 04 / 02 / 2011
// Updated on: 23 / 12 / 2011
//-----------------------------------------------
// Copyright (C) 2011 DawPi
// All Rights Reserved
//-----------------------------------------------  

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gsLibrary
{
	protected $registry;
	
	public $sidebarsCache;
	
	public $adsCache;
	
	public function __construct( ipsRegistry $registry )
	{		
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Load caches */
		
		self::_loadCaches();
	
		/* Load advanced adverts library */
		
		if( $this->caches['app_cache']['advadverts'] && IPSLib::appIsInstalled( 'advadverts' ) )
		{	
			$classToLoad 	= IPSLib::loadLibrary( IPSLib::getAppDir( 'advadverts' ) . '/sources/classes/library.php', 'AdvAdvertsLibrary' );
			$this->adv 		= new $classToLoad( $this->registry );	
		}
    
        /* Load nexus lib */
        
        if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
        {
    	   $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php', 'gsLibraryNexus', 'gsidebars' );
    	   $this->registry->setClass( 'gsLibraryNexus', new $classToLoad( $this->registry ) ); 
        }       			
	}
	
	
	private function _loadCaches()
	{
		if( ! isset( $this->sidebarsCache ) )
		{
			$this->sidebarsCache = $this->cache->getCache( 'gs_sidebars' );
		}
		
		if( ! isset( $this->sidebarsCache2 ) )
		{
			if( is_array( $this->sidebarsCache ) )
			{
				foreach( $this->sidebarsCache as $sCache )
				{
					$this->sidebarsCache2[ $sCache['s_type'] ] = $sCache['s_id'];
				}
			}
		}		
		
		if( ! isset( $this->advertsCache ) )
		{
			$this->advertsCache = $this->cache->getCache( 'gs_adverts' );
		}					
	}

	
	public function checkAccess()
	{
		/* Mod is enabled? */

		if( ! $this->settings['dp3_gsidebars_enable'] )
		{
			return false;	
		}
	    
		/* Get the skins where mod will be disabled */
		
		$disabledSkins		= self::cleanArray( explode(',', $this->settings['dp3_gs_disabled_skins'] ) );		

		/* Check disabled skin for logged member */

		if( count( $disabledSkins ) )
		{
			/* Are we using default skin? */
			
			if( ! $this->memberData['skin'] )
			{
				/* Do we have skin cache set? */
				
				if ( ! is_array( $this->caches['skinsets'] ) OR ! count( $this->caches['skinsets'] ) )
				{
					$this->cache->rebuildCache( 'skinsets', 'global' );
				}
				
				/* Get default ID */

				foreach( $this->caches['skinsets'] as $skin )
				{
					/* Default and html format? */
					
					if( $skin['set_is_default'] && $skin['set_output_format'] == 'html' )
					{
						$this->memberData['skin'] = $skin['set_id'];
						
						break;
					}
				}
			}
			
			if( in_array( $this->caches['skinsets'][ $this->memberData['skin'] ]['set_key'], $disabledSkins ) )
			{
				return false;
			}
			elseif( in_array( $this->registry->output->skin['set_key'], $disabledSkins ) )
			{
				return false;
			}
		}
				
		/* Still here? */
		
		return true;
	}

	
	public function updateSidebarsCache()
	{
		/* INIT */
		
		$cache 	= array();
		
		/* Get content from DB */
						 
		$this->DB->build( array(
							'select'	=> '*',
							'from'		=> 'dp3_gs_sidebars',																													
		)	);							 
									 
		/* Execute */
		
		$query = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $query ) )
		{			
			while ( $row = $this->DB->fetch( $query ) )
			{
				$cache[ $row['s_id'] ] 		= $row;
			}
		}
		
		/* Update cache */

		$this->cache->setCache( 'gs_sidebars', $cache, array( 'array' => 1, 'donow' => 0, 'deletefirst' => 1 ) );	
	}


	public function updateAdvertsCache()
	{
		/* INIT */
		
		$cache = array();
		
		/* Get content from DB */
						 
		$this->DB->build( array(
							'select'	=> '*',
							'from'		=> 'dp3_gs_adverts',
							'order'		=> 'a_pinned DESC, a_position ASC'																													
		)	);							 
									 
		/* Execute */
		
		$query = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $query ) )
		{			
			while ( $row = $this->DB->fetch( $query ) )
			{
				$cache[ $row['a_id'] ] = $row;
			}
		}
		
		/* Update cache */

		$this->cache->setCache( 'gs_adverts', $cache, array( 'array' => 1, 'donow' => 0, 'deletefirst' => 1 ) );		
	}
	
	
	public function cleanArray( $arr = array() )
	{
		$arrOk = array();
		
		if( is_array( $arr ) )
		{
			foreach( $arr as $k => $v )
			{
				if( $v )
				{
					$arrOk[] = $v;
				}
			}
		}
		
		return $arrOk;
	}

	
	public function showSidebar( $html = '' )
	{
		/* INIT */

		$adverts 			= array();
		
		$globalAdvertsHtml	= ''; 

		/* Check access */
		
		if( ! self::checkAccess() )
		{
			return $html;
		}
		
		/* Are we on posting new topic/post page? */
				
		if( ( $this->request['app'] == 'forums' ) && in_array( $this->request['do'], array( 'new_post', 'reply_post', 'new_post_do', 'reply_post_do' ) ) )
		{
			return $html;
		}
        
        /* Are we on the 'search' page? */
        
        if( ( $this->request['app'] == 'core' ) && ( $this->request['module'] == 'search' ) ) 
        {
			return $html;
        }
        
        /* Count adverts */
        
        $advCount = count( $this->advertsCache );	
		        
		/* Do we have both caches? */
		 
		if( ! count( $this->sidebarsCache ) || ! $advCount )
		{
            if( ! $advCount && $this->sidebarsCache2['standard'] && self::_isMainBoardPage( true ) && $this->settings['dp3_gsidebars_disable_default_sidebar'] )
            {              
                return $html;
            }
		}
			
		/* Board index work */
		
		if( self::_isMainBoardPage() )
		{
			/* Get section ID */
	
			$sid 	= $this->sidebarsCache2[ 'index' ];
			
			/* Add type */
			
			$type	= 'index';
		}
		
		/* Forum view work */
		
		elseif( self::_isForumViewPage() )
		{
			/* Get section ID */
	
			$sid 	= $this->sidebarsCache2[ 'forum' ];	
			
			/* Add type */
			
			$type	= 'forum';						
		}
		
		/* Topic view work */
		
		elseif( self::_isTopicViewPage() )
		{
			/* Get section ID */

			$sid 	= $this->sidebarsCache2[ 'topic' ];	
			
			/* Add type */
			
			$type	= 'topic';				
		}
		else
		{		
			/* Get section adverts */
			
		    $section = $this->request['app'];
            
            /* Do we have the section? */
            
            if( ! $section && ( IPS_DEFAULT_PUBLIC_APP != 'forums' ) )
            {
                $section = IPS_DEFAULT_PUBLIC_APP;   
            }
	
			/* Get section ID */
	
			$sid 	= $this->sidebarsCache2[ $section ];
			
			/* Add type */
			
			$type	= $section;				
		}

		/* Sidebar is enabled and SID is exists && we do not have global sidebar */

		if( ( ! $this->sidebarsCache[ $sid ]['s_enabled'] || ! $sid ) && ( ! $this->sidebarsCache2[ 'all' ] && ! $this->sidebarsCache2[ 'standard' ] ) )
		{
			return $html;
		}
         
		/* Get all adverts */

		$checked = true;
	  
		if( $this->sidebarsCache[ $sid ]['s_enabled'] )
		{
			/* Do we have forum sidebar type? Check the blocked forums */
			
			if( $this->sidebarsCache[ $sid ]['s_type'] == 'forum' )
			{
				if( $this->sidebarsCache[ $sid ]['s_custom'] && in_array( $this->request['f'], explode( ',', $this->sidebarsCache[ $sid ]['s_custom'] ) ) )
				{
					$checked = false;
				}  		
			}
			
			/* Member group isn't allowed to see this sidebar? */

			if( $checked && ( ! $this->sidebarsCache[ $sid ]['s_groups'] || ! in_array( $this->memberData['member_group_id'], explode( ',', $this->sidebarsCache[ $sid ]['s_groups'] ) ) ) )
			{			
				$adverts = self::_getAdvertsBaseOnSidebarID( $sid );	
			}			
		}

		/* If we do not have section ads try get global ads */

		if( $this->settings['dp3_gsidebars_display_global'] || ! count( $adverts ) )
		{
			/* Internal init */
			
			$isWeHaveGlobal = 1;
			
			/* Check global disabled pages */

			$disabledOn = explode(',', $this->sidebarsCache[ $this->sidebarsCache2[ 'all' ] ]['s_custom'] );
			$disabledOn = self::cleanArray( $disabledOn );
			
			/* Do we have something to disable? */
			
			if( is_array( $disabledOn ) && count( $disabledOn ) )
			{
				if( in_array( $type, $disabledOn ) )
				{

					$isWeHaveGlobal = 0;
				}	
			}
			
			/* Member group isn't allowed to see this sidebar? */

			if(  $this->sidebarsCache[ $this->sidebarsCache2[ 'all' ] ]['s_groups'] && in_array( $this->memberData['member_group_id'], explode( ',', $this->sidebarsCache[ $this->sidebarsCache2[ 'all' ] ]['s_groups'] ) ) )
			{			
				$isWeHaveGlobal = 0;	
			}			
			
			/* Do we have global sidebar? */

			if( $isWeHaveGlobal )
			{
				/* Default sidebar isn't disabled and we're on main board index page? */
				
				if( $this->settings['dp3_gsidebars_disable_default_sidebar'] && ! self::_isMainBoardPage( true ) )
				{			
					/* Get 'all' section HTML */
			
					$globalAdvertsHtml = self::getSectionHtml( 'all' );
	
					/* Do we have it? */
						
					$isWeHaveGlobal		= IPSText::mbstrlen( $globalAdvertsHtml ) ? 1 : 0; 
				}
				elseif( $this->settings['dp3_gsidebars_disable_default_sidebar'] || ( self::_isMainBoardPage( true ) ) )
				{                                  
					/* Get 'all' section HTML */
		
					 $globalAdvertsHtml = ( (  self::_isMainBoardPage( $this->settings['dp3_gsidebars_disable_default_sidebar'] ) ) ? self::getSectionHtml( 'all' ) : '' ) . ( ( ! $this->settings['dp3_gsidebars_disable_default_sidebar'] ) ? ( ( $this->settings['dp3_gs_side'] == 'left' ) ? ( self::_getStandardSidebarHTML() . self::getSectionHtml( 'index' ) ) : '' ) : '' ); 
					 					 
					/* Do we have it? */
	
					$isWeHaveGlobal		= IPSText::mbstrlen( $globalAdvertsHtml ) ? 1 : 0; 
					
					/* Sidebar widder than 270? */
					
					if( in_array( $this->settings['dp3_gs_sidebar_width'], array( 'big', 'huge' ) ) || ( 270 < $this->settings['dp3_gsidebars_sidebar_custom_width'] ) )
					{
						$isWeHaveGlobal 	= 1;
						$globalAdvertsHtml = $this->settings['dp3_gsidebars_disable_default_sidebar'] ? self::getSectionHtml( 'all' ) : self::_getStandardSidebarHTML();	
					} 				
				}			
				elseif( ! self::_isMainBoardPage( true ) )
				{
					/* Get 'all' section HTML */
			        
					#if( $this->settings['dp3_gsidebars_disable_default_sidebar'] )
			        #{
						$globalAdvertsHtml = self::getSectionHtml( 'all' );
					#}
	              
					/* Do we have it? */
						
					$isWeHaveGlobal		= IPSText::mbstrlen( $globalAdvertsHtml ) ? 1 : 0; 				
				}
			}	
		}

		/* Play with standard sidebar */
		
		$standardId = $this->sidebarsCache2['standard'];

		if( count( $this->sidebarsCache[ $standardId ] ) )
		{
			/* Get it */
			
			$standard = $this->sidebarsCache[ $standardId ];
			
			/* Enabled? */

			if( $standard['s_enabled'] && ! self::_isMainBoardPage() )
			{
				/* Allowed app? */

				if( in_array( $this->request['app'], explode( ',', $standard['s_custom'] ) ) || in_array( $type, explode( ',', $standard['s_custom'] ) ) )
				{				
					/* Member group isn't allowed to see this sidebar? */

					if( ! $standard['s_groups'] || ! in_array( $this->memberData['member_group_id'], explode( ',', $standard['s_groups'] ) ) )
					{			
						/* Replace global sidebar by standard */					  

						if( $this->settings['dp3_gsidebars_disable_default_sidebar'] )
						{
							$globalAdvertsHtml .= self::_getStandardSidebarHTML();
						}
						else
						{
							$globalAdvertsHtml = self::_getStandardSidebarHTML();
						}											
					}
				}
			}
		}        

		/* Count customized adverts */
		
		$cntAdverts = count( $adverts );
				
		/* Do we have any adverts? */

		if( ! $cntAdverts && ! $isWeHaveGlobal && ! $globalAdvertsHtml )
		{
			return $html;
		}	
			
		/* Prepare raw adverts? */
			
		if( ! $this->sidebarsCache[ $sid ]['s_wrapper_separate'] )
		{
			$_adverts = array();

			foreach( $adverts as $aId => $aAdvert )
			{
				$_adverts[] = $aAdvert['advert'];	
			}
            
            if( ! $this->sidebarsCache[ $sid ]['s_wrapper'] )
            {
				$adverts = implode( $this->settings['dp3_gsidebars_sep'], $_adverts );  
			}
			else
			{
				$adverts = $_adverts;
			}
		}	
	
		/* Build adverts */
		
		if( $cntAdverts )
		{
			/* Set title */
		
			$title = $this->sidebarsCache[ $sid ]['s_name'];
		}
			
		/* Get sidebar content */

		$sidebarHTML = ( ( $isWeHaveGlobal || $globalAdvertsHtml ) ? $globalAdvertsHtml . $this->settings['dp3_gsidebars_sep'] : '' ) . ( $cntAdverts ? ( ($this->sidebarsCache[ $sid ]['s_wrapper'] || $this->sidebarsCache[ $sid ]['s_wrapper_separate'] ) ? $this->registry->output->getTemplate('gsidebars')->showAdverts( $adverts, $title, $this->sidebarsCache[ $sid ]['s_wrapper_separate'] ) : $adverts ) : '' );
		
		/* Ok, time for IP.Content blocks! */

		$sidebarHTML = self::parseCCSBlocks( $sidebarHTML );
		
		/* Set sidebar value */
		
		if( $this->settings['dp3_gs_side'] == 'right' )
		{
			$position 	= 'right';
			$plus		= 10;
		}
		else
		{
			$position 	= 'left';
		}
		
		switch( $this->settings['dp3_gs_sidebar_width'] )
		{
			case 'tiny':
				$width = 40;
				
				if( $this->settings['dp3_gs_side'] == 'right' )
				{
					$plus = 10;
				}
				else
				{
				    $plus = 0;
				}					
			break;
			
			case 'small':
				$width = 140;
				
				if( $this->settings['dp3_gs_side'] == 'right' )
				{
					$plus = 10;
				}				
			break;
			
			case 'big':
				$width = 320;
				
				if( $this->settings['dp3_gs_side'] == 'right' )
				{
					$plus = 10;
				}				
			break;
			
			case 'huge':
				$width = 370;
				
				if( $this->settings['dp3_gs_side'] == 'right' )
				{
					$plus = 10;
				}
			break;
			
			case 'large':
			default:
				$width = 270;
			break;				
		}
		
		/* Custom width? */
		
		if( $this->settings['dp3_gsidebars_sidebar_custom_width'] )
		{
			$customWidth 	= $width = $this->settings['dp3_gsidebars_sidebar_custom_width'];
			$plus			= -5;
		}
		
		/* Still here? Send the HTML */
		
		return $this->registry->output->getTemplate('gsidebars')->showHTML( $html, $sidebarHTML, ( $width + $plus ), $position, $customWidth );
	} 


	public function parseCCSBlocks( $html = '' )
	{
		if( $this->caches['app_cache']['ccs'] && $this->caches['app_cache']['ccs']['app_enabled'] )
		{
			if( ! $this->registry->isClassLoaded( 'ccsFunctions' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
				$this->registry->setClass( 'ccsFunctions', new $classToLoad( $this->registry ) );
			}		
            
            if ( !( $this->registry->isClassLoaded('pageBuilder') ) )
            {
                require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
                $this->registry->setClass('pageBuilder', new pageBuilder( $this->registry ) );
            }

		    /* Get all blocks from content */
	       
			preg_match_all( "#\{parse block=\"(.+?)\"\}#", $html, $matches );
	
			if( count( $matches ) )
			{
			
				foreach( $matches[1] as $index => $key )
				{
					$html = str_replace( $matches[0][ $index ], $this->registry->getClass('pageBuilder')->getBlock( $key ), $html );
				}
			}
		}
		
		return $html;	
	}
	
	 	
	private function _getStandardSidebarHTML()
	{
		/* Exists? */

		if( $this->sidebarBlocks && isset( $this->sidebarBlocks ) ) 
		{
			return $this->sidebarBlocks;
		}

        /* Bug with IP.Calendar */
        
        if( $this->caches['app_cache']['calendar'] && IPSLib::appIsInstalled( 'calendar' ) )
        {
            $this->cache->rebuildCache( 'calendar_events', 'calendar' );
        }

        /* Load lang */

        $this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_boards' ), 'forums' );

        if ( ! isset( $this->registry->getClass('output')->compiled_templates['skin_boards'] ) && ! self::_isMainBoardPage() )
        {
    	   	$this->registry->getClass('output')->compiled_templates['skin_boards'] = NULL;
        }

        /* Get them */

        $result = $this->registry->getClass('output')->templateHooks( "<!--hook.foreach.skin_boards.boardIndexTemplate.side_blocks.outer.pre-->" );

        /* Remove not important code */

        $result = str_replace( '<!--hook.foreach.skin_boards.boardIndexTemplate.side_blocks.outer.pre-->', '', $result );
        
        $this->sidebarBlocks = $this->registry->output->getTemplate('gsidebars')->standardSidebar( $result );
	
		/* Return */

		return $this->sidebarBlocks;
	}


	public function getSectionHtml( $section = '', $check = false )
	{
		/* Do we have section? */

		if( ! $section )
		{
			return '';
		}
		
		/* Get section ID */
				
		$sid			= $this->sidebarsCache2[ $section ];

		/* Sidebar is enabled? */
				
		if( $this->sidebarsCache[ $sid ]['s_enabled'] )
		{						
			/* Get all adverts */
		
			$adverts 	= self::_getAdvertsBaseOnSidebarID( $sid );	

			/* Do we have any sidebar content? */
			
			if( ! count( $adverts ) )
			{
				return '';
			}
		
			/* Check direct access? */
			/* Bug fix 1.0.3 after release, reported by na1926 - 28 may 2011 */

			if( $check )
			{
				#Enabled?
				if( ! $this->sidebarsCache[ $sid ]['s_enabled'] )
				{
					return '';	
				}
			
				#Excluded groups?
				if( in_array( $this->memberData['member_group_id'], explode( ',', $this->sidebarsCache[ $sid ]['s_groups'] ) ) )
				{
					return '';
				}
				
				#Disable on pages?
				$disabledOn	= array();
				$disabledOn = explode(',', $this->sidebarsCache[ $this->sidebarsCache2[ $section ] ]['s_custom'] );
				$disabledOn = self::cleanArray( $disabledOn );

				if( count( $disabledOn ) && in_array( 'index', $disabledOn ) && self::_isMainBoardPage( true ) )
				{
					return '';
				}
			}			

			/* Set title */
		
			$title		= $this->sidebarsCache[ $sid ]['s_name'];
			
			/* Prepare raw adverts? */
			
			if( ! $this->sidebarsCache[ $sid ]['s_wrapper_separate'] )
			{
				$_adverts = array();
				
				foreach( $adverts as $aId => $aAdvert )
				{
					$_adverts[] = $aAdvert['advert'];	
				}
				
				$adverts = $_adverts;
			} 			

			/* Parse them */

			$return 	= ( $this->sidebarsCache[ $sid ]['s_wrapper'] || $this->sidebarsCache[ $sid ]['s_wrapper_separate'] ) ? $this->registry->output->getTemplate('gsidebars')->showAdverts( $adverts, $title, $this->sidebarsCache[ $sid ]['s_wrapper_separate'] ) : implode( $this->settings['dp3_gsidebars_sep'], $adverts );
			
            
			/* Ok, time for IP.Content blocks! */

			$return = self::parseCCSBlocks( $return );

			/* Return */
			
			return $return;										
		}	

		/* Still here? */
		
		return '';	
	}


	public function checkSidebar( $type = '', $mainIndex = FALSE )
	{
		/* Do we have type? */
		
		if( ! $type )
		{
			return false;
		}
		
		/* Disable sidebar on main index page when default sidebar isn't disabled */
		
		if( $mainIndex )
		{
			if( ! $this->settings['dp3_gsidebars_disable_default_sidebar'] )
			{
				return false;
			}
		}
		elseif( in_array( $type, array( 'index', 'all' ) ) && ! $this->settings['dp3_gsidebars_disable_default_sidebar'] )
		{
			if ( $this->settings['dp3_gs_side'] == 'left' ) return true;
			
			if( in_array( $this->settings['dp3_gs_sidebar_width'], array( 'big', 'huge' ) ) || ( 270 < $this->settings['dp3_gsidebars_sidebar_custom_width'] ) )
			{
				return true;
			}
			
			return false;
		}
		
		/* Get SID */
		
		$sid =  $this->sidebarsCache2[ $type ];
		
		/* Sidebar exists? */
		
		
		if( ! $sid )
		{
			return false;
		}
	
		/* Sidebar is enabled? */
		
		if( ! $this->sidebarsCache[ $sid ]['s_enabled'] )
		{
			return false;
		}
		
		/* Do we have any advert in this sidebar? */
		
		if( ! $this->sidebarsCache[ $sid ]['s_adverts'] && ! $this->sidebarsCache[ $sid ]['s_adv_adverts'] )
		{
			return false;
		}

		/* Still here? SO all it's okay */
		
		return true;
	}


	private function _getAdvertsBaseOnSidebarID( $sid = 0 )
	{
		/* Do we have SID? */
		
		if( ! $sid )
		{
			return array();
		}
		
		/* INIT */
		
		$adverts = array();
		
		/* Parse all adverts */
		
		if( count( $this->advertsCache ) )
		{		
			foreach( $this->advertsCache as $advert )
			{
				/* Advert is enabled? */
	
				if( ! $advert['a_enabled'] )
				{
					continue;
				}
				
				/* Advert for this section? */
				
				if( $advert['a_sidebar_id'] == $sid )
				{
					/* Do we have pinned ad? */
					
					if( $advert['a_pinned'] )
					{
						$pinnedAd['title'] 	= $advert['a_name'];
						$pinnedAd['advert'] = self::_parseAdvert( $advert['a_content'], $advert['a_raw_mode'], $advert['a_php_mode'] );
						
						continue;							
					}
					
					/* Parse advanced advert - enabled, integrated and is advanced type? */
					
					if( self::advEnabled() && $advert['a_is_advanced'] ) #&& $this->settings['dp30_ada_enable'] )
					{
						/* Get advanced advert */
						
						$adv = self::_parseAdvancedAdvert( $advert['a_is_advanced'] );
						
						/* Have it? */
						
						if( IPStext::mbstrlen( $adv ) )
						{
							/* Store! */
						
							$adverts[ $advert['a_id'] ]['title'] 	= $advert['a_name'];
							$adverts[ $advert['a_id'] ]['advert'] 	= $adv;							
						}
					}
                    elseif( $advert['a_is_nexus'] )
                    {
                        if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
                        {
                            if( $this->registry->gsLibraryNexus->checkAccess() )
                            {
                                $nexusAdvert = '';
                                $nexusAdvert = $this->registry->gsLibraryNexus->getAdvert( $advert['a_is_nexus'], $this->registry->gsLibraryNexus->nexusRawFormat );
                                
                                if( $nexusAdvert )
                                {
    							    $adverts[ $advert['a_id'] ]['title'] 	= $advert['a_name'];
    							    $adverts[ $advert['a_id'] ]['advert'] 	= $nexusAdvert;
                                }	                                
                            }
                        }
                    }
					elseif( $advert['a_duplicate_id'] )
					{
						$adverts[ $advert['a_id'] ]['title'] 	= $advert['a_name'];
						$adverts[ $advert['a_id'] ]['advert'] 	= self::_parseAdvert( $this->advertsCache[ $advert['a_duplicate_id'] ]['a_content'], $advert['a_raw_mode'], $advert['a_php_mode'] );						
					}
					else
					{
						/* Get standard adverts */
						
						$adverts[ $advert['a_id'] ]['title'] 	= $advert['a_name'];
						$adverts[ $advert['a_id'] ]['advert'] 	= self::_parseAdvert( $advert['a_content'], $advert['a_raw_mode'], $advert['a_php_mode'] );
					}
				}
			}
		}
		
		/* Random order? */
		
		if( $this->sidebarsCache[ $sid ]['s_random'] )
		{
			/* Internal init */
 			
			$cnt		= count( $adverts ); // How many we have adverts
			$maxItems	= $this->sidebarsCache[ $sid ]['s_limit_at_once'] ? $this->sidebarsCache[ $sid ]['s_limit_at_once'] : $cnt;
			$outRand	= array(); // Array to unique advert ID's
			$outAdv		= array(); // Array for adverts to asign to $adverts array
			$tmp 		= array();
			$more		= 0;
			
			/* Get ID's */
			
			while( count( $outRand ) < $maxItems )
			{			
				/* Get rand */
				
				$item = mt_rand( 0, $cnt );
				
				/* Fix highest value */
				
				if( $item == $cnt )
				{
					$item -= 1;
				}				
				
				/* Add item to tmp array */
				
				$tmp[] = $item;
								
				/* Unique */
				
				$tmp 		= array_unique( $tmp );
																	
				/* Add tmp array to main array */
				
				$outRand 	= $tmp;			
			}
			
			/* Fix advert keys */
			
			$tmpa 	= array();
			$ii		= 0;
	
			foreach( $adverts as $goodAdvert )
			{
				$tmpa[ $ii ] = $goodAdvert;
				
				$ii++;
			}
	
			$adverts = $tmpa;
			
			unset( $tmpa );

			/* Foreach all ID's and assign adverts */
	
			foreach( $outRand as $k => $aid )
			{				
				/* Add advert to output array */
				
				$outAdv[] = $adverts[ $aid ];		
			}

			/* Assign */
			
			$adverts = $outAdv;
		}
		
		/* Pinned ad stuff */

		if( is_array( $pinnedAd ) && count( $pinnedAd ) )
		{
        	array_pop( $adverts );
			$adverts[ -1 ] = $pinnedAd;
        	ksort( $adverts );
		}	

		/* Return */

		return self::cleanArray( $adverts );	
	}
	
		
	public function _parseAdvancedAdvert( $id = 0 )
	{
		/* Do we have ID? */
		
		if( ! $id )
		{
			return '';
		}
		
		/* Enabled? */

		if( ! self::advEnabled() )
		{
			return '';
		}
		
		/* Get advert info */
		
		$sections = $this->cache->getCache('adv_adverts');
		
		foreach( $sections as $section )
		{
			foreach( $section as $aid => $_advert )
			{
				/* Do we have it? */
				
				if( $aid == $id )
				{
					/* Check advert */
					
					if( $this->adv->checkAdvert( $_advert ) )
					{
						/* Store advert */
						
						$advert = $_advert;
					}
					/* Break it anyway, we found it! */
					
					break 2;
				}
			}
		}
		
		/* Do we have it? */
		
		if( ! $advert['a_id'] )
		{
			return '';
		}
		
		/* Ok, prepare it */
		
		switch( $advert['at_type'] )
		{
			case 'link':
				$content 		= $this->adv->makeAdvertLink( $advert );
			break;
				
			case 'image':					
				$content 		= $this->adv->makeAdvertImage( $advert );
			break;
				
			case 'flash':					
				$advert['acp']	= 0;
				return          $this->adv->makeAdvertFlash( $advert );
			break;
									
			case 'javas':				
				$advert['acp']	= 0;
				return           $this->adv->makeAdvertJava( $advert );
			break;
										
			case 'ptext':				
				$advert['acp'] 	= 0;
				return          $this->adv->makeAdvertText( $advert );
			break;
            
 			case 'php':				
				$advert['acp'] 	= 0;
				return          $this->adv->makeAdvertPHP( $advert );
			break;           									
		}		
		
		/* Still here? */
		
		return self::_parseAdvert( $content );
	}

	private function _isMainBoardPage( $avoid = false )
	{
		if( $this->request['f'] || ( ( $this->request['app'] != 'forums' ) && ! $this->request['f'] && isset( $this->request['app'] ) ) || ( IPS_DEFAULT_PUBLIC_APP != 'forums' && ! $this->request['f'] && ! $this->request['app'] ) )
		{
			return false;
		}
		
		/* Default sidebar isn't disabled? */
		
		if( ! $this->settings['dp3_gsidebars_disable_default_sidebar'] && ! $avoid )
		{
			return false;
		}
		
		/* Still here? */
		
		return true;
	}

	
	private function _isTopicViewPage()
	{		
		if( $this->request['showtopic'] && ( $this->request['app'] == 'forums' ) )
		{
			return true;
		}
		
		return false;		
	}

	
	private function _isForumViewPage()
	{
		if( $this->request['f'] && $this->request['showforum'] && ( $this->request['app'] == 'forums' ) )
		{
			return true;
		}
		
		return false;	
	}

	
	private function _parseAdvert( $txt = '', $raw = FALSE, $php = FALSE )
	{
		/* It's PHP? */
		
		if( $php )
		{
	        return self::parsePHP( $txt );			
		}
		
		/* Raw mode? */
		
		if( $raw )
		{
			/* Fix for .js codes */
			
			$txt = str_replace( '&#092;', '\\', $txt );
			
			/* Return */
			
			return $txt;
		}
		
		/* Set parser */
		
        IPSText::getTextClass('bbcode')->parse_smilies  = TRUE;
        IPSText::getTextClass('bbcode')->parse_bbcode   = TRUE;
        IPSText::getTextClass('bbcode')->parse_html     = FALSE;
		
		/* Return */
		
		return IPSText::getTextClass('bbcode')->preDisplayParse( nl2br( $txt ) );			
	}


    public function parsePHP( $txt = '' )
    {
        $txt = str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $txt );
        if( strpos( $txt, '<<<html' ) )
        {
            $txt = str_replace( '<<<html', '<<<HTML', $txt );
        }

        ob_start();
		eval( $txt );
		$out = ob_get_contents();
		ob_end_clean();
				
        if( ! $out )
        {
            return false;
        }

        return $out;
    }
    
    
    public function advEnabled()
    {
		/* Enabled? */
		
		if( $this->settings['dp3_gsidebars_integrate'] ) #&& $this->settings['dp30_ada_enable'] )
		{
			return true;
		}
		
		/* Still here? */
		
		return false;
	}
	
	public function getAllowedAdverts()
	{
		/* INIT */
		
		$adv = array();
		
		/* Get content from DB */
						 
		$this->DB->build( array(
								'select'	=> 'ad.*',
								'from'		=> array( 'dp3_adv_adverts' => 'ad' ), 																												
		)	);							 
									 
		/* Execute */
		
		$query = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $query ) )
		{			
			while ( $row = $this->DB->fetch( $query ) )
			{
				if( $this->adv->checkAdvert( $row ) )
				{
					$adv[] = array( $row['a_id'], $row['a_name'] );
				}
			}
		}
	
		/* Return them */
		
		return $adv;			
	}
	
	public function getApps()
	{
		/* INIT */
		
		$apps	= array();
		
		/* Get apps */
		
		$_apps	= $this->cache->getCache( 'app_cache' );
		
		if( count( $_apps ) )
		{
			foreach( $_apps as $key => $app )
			{
				/* Dop not add any special types */
				
				if( in_array( $key, array( 'core', 'forums' ) ) )
				{
					continue;
				}
				
				/* App has public title? */
				
				if( $app['app_public_title'] )
				{
					/* App is enabled? */
					
					if( $app['app_enabled'] )
					{
						/* Add it */
					
						$apps[ $key ] = $app['app_public_title'];
					}
				}
			}
		}
		
		/* Return */
		
		return $apps;	
	}
		
		
	/**
	 * Add Copyright Statement
	 *
	 * @access	public
	 * @author	DawPi
	 * @return	string	Processed HTML
	 */
	 
	public function c_acp()
	{
		return "<div id='footer' style='margin: 0px; text-align: center;-moz-border-radius:5px;'>Powered By <a target='_blank' href='http://www.ipslink.pl'>" . $this->caches['app_cache']['gsidebars']['app_title'] . " " . $this->caches['app_cache']['gsidebars']['app_version'] . "</a> &copy; 2011-" . date( 'Y' ) . " <a target='_blank' href='http://www.ipslink.pl/' title='Custom mods, skins, support and more. We develop for you.'>IPSlink.pl</a></div>";
	}
	
	public function small_c_acp()
	{
		return "<div id='footer_utilities'><p id='copyright' class='right'>Powered By <a target='_blank' href='http://www.ipslink.pl'>" . $this->caches['app_cache']['gsidebars']['app_title'] . " " . $this->caches['app_cache']['gsidebars']['app_version'] . "</a> &copy; <a target='_blank' href='http://www.ipslink.pl' title='Custom mods, skins, support and more. We develop for you.'>IPSlink.pl</a></p></div>";
	}		
} // End of class