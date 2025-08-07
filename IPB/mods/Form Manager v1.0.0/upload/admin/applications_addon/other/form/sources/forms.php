<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_forms
{
	public $forms_data	  = array();
	public $form_data_id  = array();
	public $memberForms	  = array();	
	public $addForms	  = array();
		
	protected $registry;	
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
		
	public function __construct( ipsRegistry $registry )
	{
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/*-------------------------------------------------------------------------*/
	// Setup forms cache
	/*-------------------------------------------------------------------------*/	
	public function init()
	{
		if( !IPSLib::appIsInstalled('form') )
		{
			return;
		}
       
		if ( !is_array( $this->caches['forms'] ) AND !count( $this->caches['forms'] ) )
		{
			$this->rebuild_forms();
		}			

		# Organize our array
		foreach( $this->caches['forms'] as $parent_id => $f_id )
		{
			foreach( $f_id as $form_id => $data )
			{
				$this->forms_data[ $parent_id ][ $form_id ] = $data;
				$this->subform_data[ $parent_id ][] 	    = $form_id;
				$this->form_data_id[ $form_id ]			    = $data;
                
				if ( $this->registry->permissions->check( 'view', $data ) )
				{
					$this->viewForms[ $form_id ] = $data;
				}
				if ( $this->registry->permissions->check( 'submit', $data ) )
				{
					$this->submitForms[ $form_id ] = $data;
				}                				
			}			
		}
	}
    
  	/*-------------------------------------------------------------------------*/
	// Form Nav
	/*-------------------------------------------------------------------------*/	
	public function formNav( $form_id )
	{ 
		$form_id = intval( $form_id );
		
		if ( is_array( $this->form_data_id[ $form_id ] ) && count( $this->form_data_id[ $form_id ] ) )
		{
			$parents = array_reverse( $this->getParentForms( $form_id ) );
			
			if ( is_array( $parents ) && count( $parents ) )
			{
				foreach ( $parents as $id )
				{
					$this->registry->output->addNavigation( $this->form_data_id[ $id ]['form_name'], "app=form&amp;do=view_form&amp;id={$id}", $this->form_data_id[ $id ][ 'name_seo' ], 'form_view_form' );
				}
			}
			
			$this->registry->output->addNavigation( $this->form_data_id[ $form_id ][ 'form_name' ], "app=form&amp;do=view_form&amp;id={$form_id}", $this->form_data_id[ $form_id ][ 'name_seo' ], 'form_form_view', 'public' );
		}        
    }
	
	public function getChildForms( $formid, $ids=array() )
	{        
 		if ( is_array( $this->forms_data[ $formid ] ) )
		{
			foreach( $this->forms_data[ $formid ] as $id => $data )
			{			 
			    	$ids[] = $data['cid'];
    				
			    	$ids = $this->getChildForms($data['cid'], $ids);
			}
		}       
		
		return $ids;
	}
	
	function getParentForms( $cid, $ids=array() )
	{
		if ( $this->form_data_id[ $cid ]['parent_id'] && $this->form_data_id[ $cid ]['parent_id'] != 0 )
		{
			$ids[] = $this->form_data_id[ $cid ]['parent_id'];
			$ids   = $this->getParentForms( $this->form_data_id[ $cid ]['parent_id'], $ids );
		}
		
		return $ids;
	}		

	/*-------------------------------------------------------------------------*/
	// Rebuild all form cache
	/*-------------------------------------------------------------------------*/	
	public function rebuild_forms()
	{
		$form_cache = array();
		
		$this->DB->build( array( 
								'select'   => 'f.*',
								'from'     => array( 'form_forms' => 'f' ),
								'order'    => 'f.parent_id, f.position',
								'add_join' => array(
													array(
														'select' => 'p.*',
														'from'   => array( 'permission_index' => 'p' ),
														'where'  => "p.perm_type='form' AND p.perm_type_id=f.form_id AND p.app='form'",
														'type'   => 'left',
														)
									)
								)	);
		$outer = $this->DB->execute();
		
		while( $form = $this->DB->fetch( $outer ) )
		{          
			$form['options']        = unserialize( $form['options'] );
            $form['info']           = unserialize( $form['info'] );
            $form['pm_settings']    = unserialize( $form['pm_settings'] );
            $form['email_settings'] = unserialize( $form['email_settings'] );
            $form['topic_settings'] = unserialize( $form['topic_settings'] );
                        
            # Check seo name, may need to update for first time users.
            if( !$form['name_seo'] )
            {
                $form['name_seo'] = IPSText::makeSeoTitle( $form['form_name'] );
                $this->DB->update( 'form_forms', array( 'name_seo' => $form['name_seo'] ), "form_id=".$form['form_id'] );
            }
			
			$form_cache[ $form['parent_id'] ][ $form['form_id'] ] = $form;
		}

		$this->cache->setCache( 'forms', $form_cache, array( 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );		
	}
	
	/*-------------------------------------------------------------------------*/
	// Rebuild Form Info Setup
	/*-------------------------------------------------------------------------*/
	public function rebuild_form_info( $formid="" )
	{
		if( $formid )
		{
			# Rebuild Single Form		
			$this->_rebuild_form_info( $formid );
	 		
 			# If has parent rebuild that as well
	 		if( $this->form_data_id[$formid]['parent_id'] != 0 )
	 		{
	 			$this->rebuild_form_info( $this->form_data_id[$formid]['parent_id'] );
 			}			
 		}
 		else
 		{
			# Form id empty? rebuild all forms info			
	 		foreach( $this->form_data_id as $formid => $form_data_id )
	 		{
		 		$this->_rebuild_form_info( $formid );
			}
		}
			
 		$this->rebuild_forms();
	} 
    
	/*-------------------------------------------------------------------------*/
	// Rebuild Form Info Setup
	/*-------------------------------------------------------------------------*/
	public function _rebuild_form_info( $formid="" )
	{
		# Setup Main Info		
		$stats_array = array( 'date' => 0 );
		
		# STOP!!!!!
		if( $formid == 0 )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Get the children
		//-----------------------------------------

		$children = $this->getChildForms( $formid, $ids=array() );
		$children_string = "";
		
		if( is_array($children) AND count($children) > 0 )
		{
			$children_string = implode( ",", $children );
		}
		
        $final_string = ( $children_string ) ? $formid . "," . $children_string : $formid;    		

		# Form Latest Log		
		$this->DB->build( array(
							'select'	=> 'l.log_id, l.log_form_id, l.log_date, l.member_id, l.member_name' ,
							'from'		=> array( 'form_logs' => 'l' ),
							'where'		=> 'l.log_form_id IN ('. $final_string .')',
							'order'		=> 'log_date DESC',
							'limit'		=> array( 1 ),
							'group'		=> 'l.log_id',
							'add_join'	=> array(
												# Get real member data.
												array(
														'select'	=> 'm.members_display_name, m.members_seo_name, m.member_group_id',
														'where'		=> 'm.member_id=l.member_id',
														'from'		=> array( 'members' => 'm' ),
														'type'		=> 'left'
													)
												)
							)		);
		$this->DB->execute();
		
		while( $stats = $this->DB->fetch() )
		{
			$stat_array['date'] 	            = $stats['log_date'];
			$stat_array['member_id'] 	        = $stats['member_id'];
			$stat_array['member_name'] 	        = $stats['member_name'];                        
			$stat_array['log_id']               = $stats['log_id'];
			$stat_array['members_display_name'] = $stats['members_display_name'];
			$stat_array['members_seo_name']	    = $stats['members_seo_name'];
 			$stat_array['member_group_id']	    = $stats['member_group_id'];           
		}
		
 		$this->DB->update( 'form_forms', array( 'info' => serialize( $stat_array ) ), 'form_id=' . $formid ); 		
 		return TRUE;
 	}      	
	
	/*-------------------------------------------------------------------------*/
	// General Form Dropdown
	/*-------------------------------------------------------------------------*/
	public function formDropdown( $perm='' )
	{
		$jump_array = array();

		if( count( $this->forms_data[0] ) > 0 )
		{
			foreach( $this->forms_data[0] as $id => $form_data_id )
			{				
				if( $perm && !$this->registry->permissions->check( $perm, $this->form_data_id[ $id ] ) )
				{
					//continue;						
				}               			
					
				$jump_array[] = array( $form_data_id['form_id'], $form_data_id['form_name'] );
			
				$depth_guide = "--";
			
				if ( is_array( $this->forms_data[ $form_data_id['form_id'] ] ) )
				{
					foreach( $this->forms_data[ $form_data_id['form_id'] ] as $id => $form_data_id )
					{				
						if( $perm && !$this->registry->permissions->check( $perm, $this->form_data_id[ $form_data_id['form_id'] ] ) )
						{
							continue;
						}
						
						$jump_array[] = array( $form_data_id['form_id'], $depth_guide.$form_data_id['form_name'] );
						$jump_array = $this->_formDropdown( $form_data_id['form_id'], $jump_array, $depth_guide . "--", $perm );
					}
				}
			}
		}
		
		return $jump_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// Internal Form Dropdown
	/*-------------------------------------------------------------------------*/
	private function _formDropdown( $root_id, $jump_array=array(), $depth_guide="", $perm='' )
	{
		if ( is_array( $this->forms_data[ $root_id ] ) )
		{
			foreach( $this->forms_data[ $root_id ] as $id => $form_data_id )
			{				
				if( $perm && !$this->registry->permissions->check( $perm, $this->form_data_id[ $form_data_id['form_id'] ] ) )
				{
					continue;
				}              
								
				$jump_array[] = array( $form_data_id['form_id'], $depth_guide.$form_data_id['form_name'] );
				$jump_array = $this->_formDropdown( $form_data_id['form_id'], $jump_array, $depth_guide . "--", $perm );
			}
		}
		
		return $jump_array;
	}
}