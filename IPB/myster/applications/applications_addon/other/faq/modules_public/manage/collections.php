<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_faq_manage_collections extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        switch($this->request['do'])
        {
            case 'add':
            case 'edit':
                $this->_edit();
                break;
                
            case 'save':
                $this->_save();
                break;
                
            case 'delete':
                $this->_delete();
                break;
        }
        
        $this->registry->output->addContent($this->output);
        $this->registry->output->sendOutput();
    }
    
    protected function _edit()
    {
        
    }
    
    protected function _save()
    {
        
    }
    
    protected function _delete()
    {
        
    }
}