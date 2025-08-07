<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_trackmembers_ajax_setup extends ipsAjaxCommand
{
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{		
		$this->registry->class_localization->loadLanguageFile( array( 'public_trackmembers' ) );
		//-----------------------------------------
		// What now?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'save':
				$this->_saveTrackingSettings();
				break;
				
			case 'showPopup';
			default:
				$this->_showSetupPopup();
				break;
		}
    }
    
    protected function _showSetupPopup()
    {
    	$formData 	= $formElements = array();
    	$member_id 	= (int) $this->request['mid'];

    	$member = IPSMember::load( $member_id, 'core' );
    	
    	$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'trackmembers' ) . '/extensions/coreExtensions.php', 'trackMemberMapping' );
    	$mapping = new $classToLoad;
    	
    	$formElements 	= $mapping->functionRemapToPrettyList();
    	$formData		= $mapping->getDefaultSettings();
    	
    	$curSettings	= IPSMember::getFromMemberCache( $member, 'trackmembers' );

    	if ( is_array( $curSettings ) )
    	{
    		$formData = array_merge( $formData, $curSettings );
    	}

    	$this->returnHtml( $this->registry->output->getTemplate( 'trackmembers' )->showSetupPopup( $member, $formElements, $formData ) );
    }
    
    protected function _saveTrackingSettings()
    {
    	$member_id 	= (int) $this->request['mid'];
    	$toSave		= array();
    	
    	$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'trackmembers' ) . '/extensions/coreExtensions.php', 'trackMemberMapping' );
    	$mapping = new $classToLoad;
    	
    	$defaults = $mapping->getDefaultSettings();
    	
    	foreach( array_keys( $defaults ) as $key )
    	{
    		$toSave['trackmembers'][ $key ] = isset( $this->request[ $key ] ) ? $this->request[ $key ] : 0;
    	}

    	IPSMember::setToMemberCache( $member_id, $toSave );
    	
    	$this->returnString( 'OK' );
    }
}