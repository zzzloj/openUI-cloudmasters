<?php

class task_item {

    public function __construct( ipsRegistry $registry, $class, $task ) {
	   /* Make registry objects */
	   $this->registry   = $registry;
	   $this->DB         = $this->registry->DB();
	   $this->cache      = $this->registry->cache();
	   $this->class      = $class;
	   $this->task       = $task;
    }
	
	public function runTask() {
        $sfs = $this->DB->buildAndFetch(array('select' => 'keepBanDays, acpGraph', 'from' => 'sfs_settings'));
        if ($sfs['keepBanDays']) {
            $xx = $sfs['keepBanDays'] * 86400;
            $dt = time() - $xx;
            $this->DB->delete('banfilters', 'ban_date < '.$dt.' AND sfs = 1');
            
            if ($this->DB->getAffectedRows() > 0) {
                $cache = array();
              
                $this->DB->build( array( 'select' => 'ban_content', 'from' => 'banfilters', 'where' => "ban_type='ip'" ) );
                $this->DB->execute();
              
                while ($r = $this->DB->fetch()) {
                    $cache[] = $r['ban_content'];
                }
        
                $this->cache->setCache( 'banfilters', $cache, array( 'array' => 1 ) );
                
                $this->class->appendTaskLog($this->task, 'Stop Forum Spam Bans Pruned');
            }
        }
        
        if ($sfs['acpGraph']) {
            $co = date(Y) - $sfs['acpGraph'];
            $this->DB->delete('sfs_tracking', 'year < '.$co);
            
            if ($this->DB->getAffectedRows() > 0) {
                $this->class->appendTaskLog($this->task, 'Stop Forum Spam ACP Graphs Pruned');
            }
        }
        $this->class->unlockTask($this->task);
	}
}
?>