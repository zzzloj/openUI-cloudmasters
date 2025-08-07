<?php

if (!defined('IN_ACP')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
    exit();
}

class admin_sfs_stats_statistics extends ipsCommand
{

    public function doExecute(ipsRegistry $registry)
    {
        switch ($this->request['do']) {
            default:
                $this->sfsStats();
                break;
            case 'drawyear':
                $this->drawYear();
                break;
        }

        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
    
    private function sfsStats() {
        require_once(IPS_KERNEL_PATH.'classFileManagement.php');
        $this->sfsChk = new classFileManagement();
        $n = $this->sfsChk->getFileContents("http://www.stopforumspam.com/api?get=rate");
        $this->registry->output->html .= "<div class='acp-box'>";
		$this->registry->output->html .= "<h3>{$this->lang->words['sfs_Q']}</h3>";
        $this->registry->output->html .= "<table class='ipsTable'>";
        $this->registry->output->html .= "<tr><td class='field_field' width='50%'><strong>{$this->lang->words['sfs_numQ']}</strong><br /><span class='desctext'>{$this->lang->words['sfs_QDesc']}</span></td><td class='field_field' width='50%' align='center'>{$n}</td></tr>";
        $this->registry->output->html .= "</table></div><br />";
        
        $this->registry->output->html .= "<div class='acp-box'>
        <h3>{$this->lang->words['sfs_statsTitle']}</h3>";

        $count = $this->DB->build(array('select' => 'year', 'from' => 'sfs_tracking'));
        $this->DB->execute($count);
         
        $this->registry->output->html .= "<table class='ipsTable single_pad'>";
        
        if ($this->DB->GetTotalRows()) {
            $st = isset($this->request['st']) ? intval($this->request['st']) : 0;
            $totBlk = $this->DB->GetTotalRows();
            $pp = 3;
            $this->DB->build(array('select' => 'year', 'from' => 'sfs_tracking', 'order' => 'year DESC', 'limit' => array($st, $pp)));
            $this->DB->execute();
            
            $pages = $this->registry->output->generatePagination(array('totalItems' => $totBlk,
                                                                  'itemsPerPage' => $pp,
                                                                  'currentStartValue' => $st,
                                                                  'baseUrl' => $this->settings['base_url'].'module=stats&section=statistics'
                                                                  ));
            
            while($bd = $this->DB->fetch()) {
                $this->registry->output->html .= "<tr><td class='field_field' align='center'><img src='{$this->settings['base_url']}app=sfs&module=stats&section=statistics&do=drawyear&theyear={$bd['year']}' /></td></tr>";
            }
        }

        $this->registry->output->html .= "</table>";
        $this->registry->output->html .= "</div>";
        $this->registry->output->html .= "{$pages}";
    }
    
    private function drawYear() {
        $gs = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'sfs_tracking', 'where' => "year = {$this->request['theyear']}"));
        require_once( IPS_KERNEL_PATH . '/classGraph.php' );
        $graph = new classGraph();
        $graph->options['title'] = "{$gs['yearTot']} {$this->lang->words['sfs_regBlkIn']} {$gs['year']}";
        $graph->options['width'] = 900;
        $graph->options['height'] = 400;
        $graph->options['showlegend'] = 0;
        $graph->options['showgridlinesx'] = 0;
        $graph->addLabels(array("{$this->lang->words['sfs_Jan']}", "{$this->lang->words['sfs_Feb']}", "{$this->lang->words['sfs_Mar']}", "{$this->lang->words['sfs_Apr']}", "{$this->lang->words['sfs_May']}", "{$this->lang->words['sfs_Jun']}", "{$this->lang->words['sfs_Jul']}", "{$this->lang->words['sfs_Aug']}", "{$this->lang->words['sfs_Sep']}", "{$this->lang->words['sfs_Oct']}", "{$this->lang->words['sfs_Nov']}", "{$this->lang->words['sfs_Dec']}"));
        $graph->addSeries("", array($gs['Jan'], $gs['Feb'], $gs['Mar'], $gs['Apr'], $gs['May'], $gs['Jun'], $gs['Jul'], $gs['Aug'], $gs['Sep'], $gs['Oct'], $gs['Nov'], $gs['Dec']));
        $graph->options['charttype'] = 'Bar';
        $graph->display();
        exit;
    }
}

?>