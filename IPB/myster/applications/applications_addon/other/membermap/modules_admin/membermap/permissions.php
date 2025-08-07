<?php

/**
 * Access Permissions
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

if ( ! defined( 'IN_ACP' ) )
{
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class admin_membermap_membermap_permissions extends ipsCommand
{
    /**
     * Class entry point
     *
     * @access	public
     * @param	object		Registry reference
     * @return	void		[Outputs to screen/redirects]
     */
    public function doExecute( ipsRegistry $registry )
    {
		/* Keep my camel case naming standards happy */
		$this->dB = $this->DB;

        if(isset($this->request['do']) && $this->request['do'] == 'permissions_save')
        {
            $this->_savePermissionMatrix();
        }
        else
        {
            $this->_showPermissionMatrix();
        }

        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }

    /**
     * Loads permission matrix into ACP
     *
     * @return	void		[Outputs to screen/redirects]
     */
    private function _showPermissionMatrix()
    {
        /* Load Skin */
        $this->html               = $this->registry->output->loadTemplate( 'cp_skin_membermap' );
        $this->html->form_code    = 'module=membermap&amp;section=permissions&amp;do=permissions_save';

        $this->dB->build(array('select' => 'p.*',
                                'from'   => array( 'permission_index' => 'p' ),
                                'where'  => "p.perm_type='membermap' AND perm_type_id=1"));
        $this->dB->execute();

        $f = $this->dB->fetch();

        require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
        $permissions = new classPublicPermissions( ipsRegistry::instance() );
        $form['perm_matrix'] = $permissions->adminPermMatrix( 'membermap', $f );

        $this->registry->output->html .= $this->html->membermapPermissions( $form );
    }

    /**
     * Save changed permissions, then show matrix again.
     *
     * @return	void		[Outputs to screen/redirects]
     */
    private function _savePermissionMatrix()
    {
        /* Permissions */
        require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
        $permissions = new classPublicPermissions( ipsRegistry::instance() );
        $permissions->savePermMatrix( $this->request['perms'], 1, 'membermap' );

        $this->registry->output->global_message = 'The permissions have been updated.';

        $this->_showPermissionMatrix();
    }
}