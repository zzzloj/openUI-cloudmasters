<?php

$SQL = array();

# Use this as insert file instead.
ipsRegistry::DB()->buildAndFetch(array('select' => '*', 'from' => 'form_forms' ));
if ( !ipsRegistry::DB()->GetTotalRows() )
{
    ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'admin_form' ), 'form' );
    
    # Setup Default Form
    $defaultForm = array(
                  'form_name'     => 'Contact Form',                          
                  'name_seo'      => 'contact-form', 
                  'description'   => 'Contact our forum.',
                  'options'       => serialize( array( 'log_message'  => "New form submission from [b]{form_name}[/b].<br /><br />{field_list}",							
    				                                   'enable_rss'   => 0,
                                                       'confirm_type' => 1,
                                                       'confirm_data' => '',
                                                       'attachments'  => 0,
    			                              )      ),
                  'pm_settings'   => serialize( array( 'enable'        => 1,
                                                       'sender'        => '-1',
                                                       'receiver'      => ipsRegistry::member()->getProperty( 'member_id' ), 
                                                       'receiver_type' => 1,
                                                       'subject'       => "New Form Submission",
                                                       'message'       => "New form submission has been received. View form [url={form_url}]here[/url]: <br /><br />Name: [b]{member_name}[/b]<br />Email: [b]{member_email}[/b]<br />Form: [b]{form_name}[/b]<br /><br />[quote]{field_list}[/quote]"
                                          )      ),
                  'email_settings' => serialize( array( 'enable'        => 1,
                                                        'receiver_type' => 1,
                                                        'sender'        => ipsRegistry::member()->getProperty( 'email' ),
                                                        'receiver'      => ipsRegistry::member()->getProperty( 'email' ),                                                        
                                                        'subject'       => "New Form Submission",
                                                        'message'       => "New form submission has been received. View form [url={form_url}]here[/url]: <br /><br />Name: [b]{member_name}[/b]<br />Email: [b]{member_email}[/b]<br />Form: [b]{form_name}[/b]<br /><br />{field_list}",
                                          ) ),
                  'topic_settings' => serialize( array( 'enable' => 0,
                                                        'author' => ipsRegistry::member()->getProperty( 'member_id' ),                                              
                                                        'title'  => "New Form Submission ({form_name})",
                                                        'post'   => "New form submission has been received. View form [url={form_url}]here[/url]: <br /><br />Name: [b]{member_name}[/b]<br />Email: [b]{member_email}[/b]<br />Form: [b]{form_name}[/b]<br /><br />[quote]{field_list}[/quote]"
                                           )      ),                                                      
                  'position'      => 1,
                 );     
    
    # Insert Form
    ipsRegistry::DB()->insert( 'form_forms', $defaultForm );
    $formID = ipsRegistry::DB()->getInsertID();
    
    # Form added?
    if( $formID )
    {
        # Setup Form Submissions
        $perm_save = array( 'app' => 'form', 'perm_type' => 'form', 'perm_type_id' => $formID, 'perm_view' => '*', 'perm_2' => '*', 'perm_3' => '' );
        ipsRegistry::DB()->insert( 'permission_index', $perm_save );    
        
        # Add fixed contact fields.
        $defaultFields = array( 
                                array('field_form_id'  => $formID,
                                      'field_title'    => 'Your Name',                          
                                      'field_name'     => 'your-name',  
                                      'field_value'    => '{member_name}',                      
                                      'field_type'     => 'input',
                                      'field_required' => 1,                             
                                      'field_extras'   => serialize( array( 'size'  => 30 ) ),
                                     ),
                                array('field_form_id'  => $formID,
                                      'field_title'    => 'Email Address',                          
                                      'field_name'     => 'email-address',
                                      'field_value'    => '{member_email}',                                          
                                      'field_text'     => 'Please enter an address we can reply to if necessary.',                        
                                      'field_type'     => 'input',
                                      'field_required' => 1,                             
                                      'field_extras'   => serialize( array( 'size'  => 45 ) ),
                                     ),
                                array('field_form_id'  => $formID,
                                      'field_title'    => 'Contact Message',                          
                                      'field_name'     => 'contact-message',                   
                                      'field_type'     => 'editor',
                                      'field_required' => 1,                             
                                     ),                                                                                           
                              ); 
                     
        # Add our default fields            
        foreach( $defaultFields as $key => $field_save )
        {
            ipsRegistry::DB()->insert( 'form_fields', $field_save );
        }
    }
}