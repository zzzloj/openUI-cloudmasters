<?php

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

$SQL[] = "UPDATE reputation_index SET type='image_id' where app='gallery' and type='id';";
$SQL[] = "UPDATE reputation_index SET type='comment_id' where app='gallery' and type='pid';";
$SQL[] = "DELETE FROM reputation_cache where app='gallery';";
