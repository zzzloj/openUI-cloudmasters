<?php

if ( ! defined( 'IN_IPB' ) )
{
        print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
        exit();
}

class public_mycustompage_modulename_firstname extends ipsCommand {
        public function doExecute( ipsRegistry $registry ) {

                $this->output = "";
        
                $this->registry->output->setTitle( "Расписание" );
                $this->registry->output->addContent( $this->output );
                $this->registry->output->sendOutput();  
  }
}
?>