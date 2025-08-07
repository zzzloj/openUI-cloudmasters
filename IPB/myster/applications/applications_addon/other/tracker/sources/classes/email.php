<?php

class tracker_core_email extends iptCommand
{
	/**
	* Retrieve an email template
	*
	* @access	private
	* @param	string		Template key
	* @param	string		Language to use
	* @return	void
	*/
	public function getTemplate( $name, $language="" )
	{
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		if( $name == "" )
		{
			$this->emailLogError( "A valid email template ID was not passed to the email library during template parsing" );
			return;
		}

		//-----------------------------------------
		// Default?
		//-----------------------------------------
		if( $this->settings['default_language'] == "" )
		{
			$this->settings['default_language'] = 1;
		}

		if( !$language )
		{
			$language = $this->settings['default_language'];
		}

		//-----------------------------------------
		// Get lang files
		//-----------------------------------------
		$this->registry->class_localization->loadLanguageFile( array( 'public_email_content' ), 'core', $language );
		$this->registry->class_localization->loadLanguageFile( array( 'public_email' ), 'tracker', $language );

		//-----------------------------------------
		// Stored KEY?
		//-----------------------------------------
		if ( !isset($this->lang->words[ $name ]) )
		{
			if ( $language == $this->settings['default_language'] )
			{
				$this->emailLogError( "Could not find an email template with an ID of '{$name}'" );
				return;
			}
			else
			{
				$this->registry->class_localization->loadLanguageFile( array( 'public_email_content' ), 'core', $this->settings['default_language'] );
				$this->registry->class_localization->loadLanguageFile( array( 'public_email' ), 'tracker', $this->settings['default_language'] );
				
				if ( !isset($this->lang->words[ $name ]) )
				{
					$this->emailLogError( "Could not find an email template with an ID of '{$name}'" );
					return;
				}
			}
		}

		//-----------------------------------------
		// Subject?
		//-----------------------------------------
		if ( isset( $this->lang->words[ 'subject__'.$name ] ) )
		{
			IPSText::getTextClass( 'email' )->subject = stripslashes( $this->lang->words[ 'subject__'. $name ] );
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------
		IPSText::getTextClass( 'email' )->template = stripslashes($this->lang->words['email_header']) . stripslashes($this->lang->words[ $name ]) . stripslashes($this->lang->words['email_footer']);
	}

	/**
	* Log a fatal error
	*
	* @access	private
	* @param	string		Message
	* @param	string		Help key (deprecated)
	* @return	bool
	*/
	private function logError( $msg )
	{
		$this->DB->insert(
			'mail_error_logs',
			array(
				'mlog_date'     => time(),
				'mlog_to'       => IPSText::getTextClass( 'email' )->to,
				'mlog_from'     => IPSText::getTextClass( 'email' )->from,
				'mlog_subject'  => IPSText::getTextClass( 'email' )->subject,
				'mlog_content'  => substr( IPSText::getTextClass( 'email' )->message, 0, 200 ),
				'mlog_msg'      => $msg,
				'mlog_code'     => IPSText::getTextClass( 'email' )->emailer->smtp_code,
				'mlog_smtp_msg' => IPSText::getTextClass( 'email' )->emailer->smtp_msg
			)
		);

		return false;
	}
}

?>