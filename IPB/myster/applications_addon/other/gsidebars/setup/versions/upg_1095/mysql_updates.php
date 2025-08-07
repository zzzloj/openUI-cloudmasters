<?php

$SQL[] = "ALTER TABLE dp3_gs_adverts  ADD       a_is_nexus      INT(5)                  NOT NULL";
$SQL[] = "ALTER TABLE dp3_gs_adverts  CHANGE    a_is_advanced   a_is_advanced INT(5)    NOT NULL"; 
$SQL[] = "ALTER TABLE dp3_gs_sidebars ADD       s_nexus_adverts varchar(255)            NOT NULL";