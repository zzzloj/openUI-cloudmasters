<?php

/*
+--------------------------------------------------------------------------
|   [HSC] Ford Bible 1.0
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

class admin_faq_config_permissions extends ipsCommand
{
    public $html;
    
    public function doExecute( ipsRegistry $registry )
    {
        $this->html               = $this->registry->output->loadTemplate( 'cp_skin_faq' );
		$this->html->form_code    = 'module=config&amp;section=permissions';
		$this->html->form_code_js = 'module=config&section=permissions';
        
        switch($this->request['do'])
        {
            case 'view':
            default:
                $this->_showPermissions();
                break;
            case 'save':
                $this->_savePermissions();
                break;
        }
        
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
    
    private function _showPermissions()
    {
        $perms = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'permission_index', 'where' => "perm_type='question' and perm_type_id=1"));
                                                
        require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
		$permissions = new classPublicPermissions( ipsRegistry::instance() );
		$matrix_html	= $permissions->adminPermMatrix( 'question', $perms);
        
        $this->registry->output->html .= $this->html->showPermissions($matrix_html);
    }
    
    private function _savePermissions()
    {
        require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
   		$permissions = new classPublicPermissions( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], 1, 'question' );
        
        $this->_showPermissions();
    }
}