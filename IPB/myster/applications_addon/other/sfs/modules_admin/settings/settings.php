<?php

if (!defined('IN_ACP')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class admin_sfs_settings_settings extends ipsCommand
{

    public function doExecute(ipsRegistry $registry)
    {
        switch ($this->request['do']) {
            default:
                $this->sfsSettings();
                break;

            case 'dosettings':
                $this->doSettings();
                break;
        }

        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
    
    private function sfsSettings() {
        $sfs = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'sfs_settings'));
        $this->registry->output->html .= "<div class='acp-box'>
         <h3>{$this->lang->words['sfs_sfs']}</h3>";
         
         $this->registry->output->html .= "<form action='".$this->settings['base_url']."module=settings&amp;section=settings&amp;do=dosettings' method='post'>";
         
         $this->registry->output->html .= "<table class='ipsTable single_pad'>";
         
         $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_lookSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_checkType']}</strong></td><td class='field_field'>{$this->registry->output->formDropdown('checkType' , array(0 => array('0' , $this->lang->words['sfs_both']),
          											1 => array('1' , $this->lang->words['sfs_ip']),
          											2 => array('2' , $this->lang->words['sfs_em']),
          											), $sfs['checkType'])}</td></tr>";
                                                    
         $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_ipAtAll']}</strong></td><td class='field_field'>{$this->registry->output->formYesNo('ipAtAll' , $sfs['ipAtAll'])}</td></tr>";
         
         $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_ipNumTimes']}</strong></td><td class='field_field'>{$this->registry->output->formInput('ipNumTimes', $sfs['ipNumTimes'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";
        
        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_ipDaysAgo']}</strong></td><td class='field_field'>{$this->registry->output->formInput('ipDaysAgo', $sfs['ipDaysAgo'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_ipConfidence']}</strong></td><td class='field_field'>{$this->registry->output->formInput('ipConfidence', $sfs['ipConfidence'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emAtAll']}</strong></td><td class='field_field'>{$this->registry->output->formYesNo('emAtAll' , $sfs['emAtAll'])}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emNumTimes']}</strong></td><td class='field_field'>{$this->registry->output->formInput('emNumTimes', $sfs['emNumTimes'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";
        
        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emDaysAgo']}</strong></td><td class='field_field'>{$this->registry->output->formInput('emDaysAgo', $sfs['emDaysAgo'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emConfidence']}</strong></td><td class='field_field'>{$this->registry->output->formInput('emConfidence', $sfs['emConfidence'])}<br /><span class='desctext'>{$this->lang->words['sfs_zeroDis']}</span></td></tr>";
        
        $lec = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite');
        $le = new $lec();
        
        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_errDes']}</strong></td><td class='field_field'>{$le->show('errorMessage', array('isHtml' => TRUE),$sfs['errorMessage'])}<br /><span class='desctext'>{$this->lang->words['sfs_htbbOk']}</span></td></tr>";

        $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_banSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_addBan']}</strong></td><td class='field_field'>{$this->registry->output->formYesNo('addBan', $sfs['addBan'])}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_banDays']}</strong></td><td class='field_field'>{$this->registry->output->formSimpleInput('keepBanDays', $sfs['keepBanDays'])}<br /><span class='desctext'>{$this->lang->words['sfs_banDaysDes']}</span></td></tr>";

        $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_repSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_sfsAPI']}</strong></td><td class='field_field'>{$this->registry->output->formInput('apiKey', $sfs['apiKey'])}<br /><span class='desctext'>{$this->lang->words['sfs_apiWarn']}</span></td></tr>";

        $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_emSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emailTo']}</strong></td><td class='field_field'>{$this->registry->output->formInput('emailTo', $sfs['emailTo'])}<br /><span class='desctext'>{$this->lang->words['sfs_emailWarn']}</span></td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_emailSub']}</strong></td><td class='field_field'>{$this->registry->output->formInput('emailSub', $sfs['emailSub'])}<br /><span class='desctext'>{$this->lang->words['sfs_emailWarn']}</span></td></tr>";

        $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_boardSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_statTextDes']}</strong></td><td class='field_field'>{$this->registry->output->formInput('statText', $sfs['statText'])}</td></tr>";

        $this->registry->output->html .= "<tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_statsDes']}</strong></td><td class='field_field'>{$this->registry->output->formInput('blockCount', $sfs['blockCount'])}</td></tr>";

        $this->registry->output->html .= "<th colspan='2'>{$this->lang->words['sfs_acpStSet']}</th><tr><td class='field_title'><strong
class='title'>{$this->lang->words['sfs_acpStDes']}</strong></td><td class='field_field'>{$this->registry->output->formInput('acpGraph', $sfs['acpGraph'])}<br /><span class='desctext'>{$this->lang->words['sfs_acpStExp']}</span></td></tr>";

        $this->registry->output->html .= "</table>";
        $this->registry->output->html .="<div class='acp-actionbar'><input type='submit' class='button' value='{$this->lang->words['sfs_upSettings']}' /></div>";
        $this->registry->output->html .= "</form>";
        $this->registry->output->html .= "</div>";
    }
    
    private function doSettings() {
        $lec = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite');
        $le = new $lec();
        $forSave = $le->process($this->request['errorMessage']);
        
        $this->DB->update('sfs_settings', array('checkType' => $this->request['checkType'],
                                                'ipAtAll' => $this->request['ipAtAll'],
                                                'ipNumTimes' => intval($this->request['ipNumTimes']),
                                                'ipDaysAgo' => intval($this->request['ipDaysAgo']),
                                                'ipConfidence' => intval($this->request['ipConfidence']),
                                                'emAtAll' => $this->request['emAtAll'],
                                                'emNumTimes' => intval($this->request['emNumTimes']),
                                                'emDaysAgo' => intval($this->request['emDaysAgo']),
                                                'emConfidence' => intval($this->request['emConfidence']),
                                                'errorMessage' => $forSave,
                                                'apiKey' => $this->request['apiKey'],
                                                'addBan' => $this->request['addBan'],
                                                'keepBanDays' => intval($this->request['keepBanDays']),
                                                'emailTo' => $this->request['emailTo'],
                                                'emailSub' => $this->request['emailSub'],
                                                'statText' => $this->request['statText'],
                                                'blockCount' => $this->request['blockCount'],
                                                'acpGraph' => $this->request['acpGraph'],
        ));
        $this->registry->output->redirect($this->settings['base_url']."module=settings&section=settings" , $this->lang->words['sfs_setRed']);
    }
}

?>