<?php
class admin_quiz_overview_settings extends ipsCommand
{

        public function doExecute( ipsRegistry $registry )
        {
                //-----------------------------------------
                // Set up some shortcuts for our urls
                //-----------------------------------------
                
                $this->form_code        = 'module=overview&amp;section=settings';
                $this->form_code_js     = 'module=overview&section=settings';
                
                //-------------------------------
                // Grab the settings controller, instantiate and set up shortcuts
                //-------------------------------
                
                $classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
                $settings       = new $classToLoad();
                $settings->makeRegistryShortcuts( $this->registry );
                
                //-------------------------------
                // Load language file that will be needed
                //-------------------------------

                ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );

                //-------------------------------
                // Load the skin file the settings file will need and pass shortcuts
                //-------------------------------

                $settings->html                 = $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );
                $settings->form_code    = $settings->html->form_code    = 'module=settings&amp;section=settings';
                $settings->form_code_js = $settings->html->form_code_js = 'module=settings&section=settings';

                //-------------------------------
                // Here we specify the setting group key
                //-------------------------------
                $this->request['conf_title_keyword'] = 'quiz';

                //-------------------------------
                // Here we specify where to send the admin after submitting the form
                //-------------------------------
                $settings->return_after_save             = $this->settings['base_url'] . $this->form_code;

                //-------------------------------
                // View the settings configuration page
                //-------------------------------

                $settings->_viewSettings();
                
                //-----------------------------------------
                // And finally, output
                //-----------------------------------------
                
                $this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();		
                $this->registry->getClass('output')->sendOutput();
        }

}


?>