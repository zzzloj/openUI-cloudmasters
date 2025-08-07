<?php

if (!defined('IN_ACP')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class admin_sfs_members_report extends ipsCommand
{

    public function doExecute(ipsRegistry $registry)
    {
        switch ($this->request['do']) {
            default:
                $this->findMember();
                break;

            case 'report':
                $this->report();
                break;
                
            case 'reportmember':
                $this->reportMember();
                break;
                
            case 'upmemcache':
                $this->updateMemCache();
                break;
        }

        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
    
    private function findMember() {
        $this->registry->output->html .= "<script type='text/javascript'>
        document.observe('dom:loaded', function(){
        if( $('sfsMember') )
            {
                var autoComplete = new ipb.Autocomplete( $('sfsMember'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem}});
            }
         });
         </script>
         
         <div class='acp-box'>
         <h3>{$this->lang->words['sfs_findMem']}</h3>";
         
         $this->registry->output->html .= "<form action='".$this->settings['base_url'].            "module=members&amp;section=report&amp;do=report' method='post'>";
         
         $this->registry->output->html .= "<table class='ipsTable single_pad'>";
         
         $this->registry->output->html .= "<tr><td class='field_field' align='center'><strong
class='title'>{$this->lang->words['sfs_doFind']}</strong></td><td class='field_field'>{$this->registry->output->formInput('sfsMember')}</td></tr>";

        $this->registry->output->html .= "</table>";
        $this->registry->output->html .="<div class='acp-actionbar'><input type='submit' class='button' value='{$this->lang->words['sfs_findMem']}' /></div>";
        $this->registry->output->html .= "</form>";
        $this->registry->output->html .= "</div>";
    }

    private function report() {
        if (isset($this->request['member_id'])) {
            $t = IPSMember::load($this->request['member_id']);
        } else {
            $t = IPSMember::load($this->request['sfsMember'], 'all', 'displayname');
        }

        $this->registry->output->html .= "<div class='acp-box'>";
        $this->registry->output->html .= "<h3>{$this->lang->words['sfs_repSFS']}</h3>";

        $this->registry->output->html .= "<form action='".$this->settings['base_url'].            "module=members&amp;section=report&amp;do=reportmember' method='post'>";

        $this->registry->output->html .= "<table class='ipsTable single_pad'>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_ipAdr']}</strong></td><td class='field_field'>{$this->registry->output->formInput('memIP' , $t['ip_address'])}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emAdr']}</strong></td><td class='field_field'>{$this->registry->output->formInput('memEM' , $t['email'])}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_Evidence']}</strong><span class='desctext'>{$this->lang->words['sfs_evDes']}</span></td><td class='field_field'>{$this->registry->output->formTextarea('sfsEv')}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_remMem']}</strong><span class='desctext'>{$this->lang->words['sfs_remMemDes']}</span></td><td class='field_field'>{$this->registry->output->formCheckbox('delSpammer')}</td></tr>";

        $this->registry->output->html .= "<input type='hidden' value='{$t['members_display_name']}' name='memUN'>";
        
        $this->registry->output->html .= "<input type='hidden' value='{$t['member_id']}' name='memID'>";

        $this->registry->output->html .= "</table>";
        $this->registry->output->html .= "<div class='acp-actionbar'><input type='submit' class='button' value='{$this->lang->words['sfs_ReportMem']}' /></div>";
        $this->registry->output->html .= "</form>";
        $this->registry->output->html .= "</div>";

    }

    private function reportMember() {
        $api = $this->DB->buildAndFetch(array('select' => 'apiKey', 'from' => 'sfs_settings'));
        if ($this->request['memIP'] == '') {
            $this->registry->output->showError($this->lang->words['sfs_noIP']);
        }
        
        if ($this->request['memEM'] == '') {
            $this->registry->output->showError($this->lang->words['sfs_noEm']);
        }
        
        if ($api['apiKey'] == '') {
            $this->registry->output->showError($this->lang->words['sfs_noApi']);
        }
        
        $un = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $this->request['memUN']));
        $em = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $this->request['memEM']));
        $temp = IPSText::br2nl($this->request['sfsEv']);
        $ev = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $temp));
        require_once(IPS_KERNEL_PATH.'classFileManagement.php');
        $this->sfsChk = new classFileManagement();
        $this->sfsChk->getFileContents("http://www.stopforumspam.com/add.php?username=".$un."&ip_addr=".$this->request['memIP']."&email=".$em."&evidence=".$ev."&api_key=".$api['apiKey']);
        
        if ($this->request['delSpammer']) {
            IPSMember::remove($this->request['memID']);
        }
        
        $this->registry->output->redirect($this->settings['base_url']."module=members" , "{$this->lang->words['sfs_memSubRed']}");
    }
    
    private function updateMemCache() {
        $member = IPSMember::load($this->request['who'], 'pfields_content');
        require_once(IPS_KERNEL_PATH.'classFileManagement.php');
        $this->sfsChk = new classFileManagement();
        $nm = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $member['members_display_name']));
        $em = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $member['email']));
        $c = $this->sfsChk->getFileContents("http://www.stopforumspam.com/api?username={$nm}&ip={$member['ip_address']}&email={$em}&f=serial");
        $n = time() + 86400;
        $this->DB->update('pfields_content', array('sfsMemInfo' => $c, 'sfsNextCheck' => $n), "member_id = {$member['member_id']}");
        
        $this->registry->output->redirect($this->settings['_base_url']."app=members&amp;module=members&amp;section=members&do=viewmember&member_id=".$member['member_id'] , "{$this->lang->words['sfs_sfsUpRed']}");
    }

}

?>