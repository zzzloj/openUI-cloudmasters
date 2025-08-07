<?php

$TABLE[]	= "CREATE TABLE ccs_blocks (
  block_id mediumint(9) NOT NULL auto_increment,
  block_active tinyint(1) NOT NULL default '0',
  block_name varchar(255) NOT NULL,
  block_description text,
  block_key varchar(255) NOT NULL,
  block_template int NOT NULL DEFAULT 0,
  block_type varchar(32) NOT NULL,
  block_config text,
  block_content mediumtext,
  block_cache_ttl VARCHAR(10) NOT NULL default '0',
  block_cache_last int(11) NOT NULL default '0',
  block_cache_output mediumtext,
  block_position mediumint(9) NOT NULL default '0',
  block_category mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (block_id),
  KEY block_cache_ttl (block_cache_ttl),
  KEY block_active (block_active),
  KEY block_key (block_key),
  KEY block_category (block_category),
  KEY block_template (block_template)
);";

$TABLE[]	= "CREATE TABLE ccs_block_wizard (
  wizard_id varchar(32) NOT NULL,
  wizard_step smallint(6) NOT NULL default '0',
  wizard_type varchar(32) default NULL,
  wizard_name VARCHAR( 255 ) NULL DEFAULT NULL,
  wizard_config longtext,
  wizard_started int NOT NULL DEFAULT '0',
  PRIMARY KEY  (wizard_id),
  KEY ( wizard_started )
);";

$TABLE[]	= "CREATE TABLE ccs_containers (
  container_id int(11) NOT NULL auto_increment,
  container_name varchar(255) default NULL,
  container_type varchar(32) NOT NULL,
  container_order mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (container_id),
  KEY container_type( container_type, container_order )
);";

$TABLE[]	= "CREATE TABLE ccs_folders (
  folder_path text,
  last_modified int NOT NULL default '0'
);";

$TABLE[]	= "CREATE TABLE ccs_pages (
  page_id int(11) NOT NULL auto_increment,
  page_name varchar(255) default NULL,
  page_seo_name varchar(255) default NULL,
  page_folder varchar(255) default NULL,
  page_type varchar(32) default NULL,
  page_last_edited int NOT NULL default '0',
  page_template_used int(11) NOT NULL default '0',
  page_content mediumtext,
  page_cache mediumtext,
  page_view_perms text,
  page_cache_ttl VARCHAR(10) NOT NULL DEFAULT '0',
  page_cache_last int NOT NULL default '0',
  page_content_only tinyint(1) NOT NULL default '0',
  page_meta_keywords text,
  page_meta_description text,
  page_content_type varchar(32) NOT NULL default 'page',
  page_template mediumtext,
  page_ipb_wrapper TINYINT( 1 ) NOT NULL DEFAULT '0',
  page_omit_filename TINYINT( 1 ) NOT NULL DEFAULT '0',
  page_title VARCHAR( 255 ) NULL DEFAULT NULL,
  page_quicknav TINYINT NOT NULL DEFAULT '1',
  PRIMARY KEY  (page_id),
  KEY page_seo_name ( page_seo_name (100), page_folder (100) ),
  KEY page_template_used (page_template_used),
  KEY page_folder (page_folder),
  KEY page_content_type (page_content_type)
);";

$TABLE[]	= "CREATE TABLE ccs_page_templates (
  template_id int(11) NOT NULL auto_increment,
  template_name varchar(255) default NULL,
  template_desc text,
  template_key varchar(32) NOT NULL,
  template_content mediumtext NOT NULL,
  template_updated int NOT NULL default '0',
  template_position mediumint(9) NOT NULL default '0',
  template_category mediumint(9) NOT NULL default '0',
  template_database tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (template_id),
  UNIQUE KEY template_key (template_key),
  KEY template_category (template_category),
  KEY template_database (template_database)
);";

$TABLE[]	= "CREATE TABLE ccs_page_wizard (
  wizard_id varchar(32) NOT NULL,
  wizard_step smallint(6) NOT NULL default '0',
  wizard_edit_id int(11) NOT NULL default '0',
  wizard_name varchar(255) default NULL,
  wizard_folder varchar(255) default NULL,
  wizard_type varchar(32) default NULL,
  wizard_template int(11) NOT NULL default '0',
  wizard_content mediumtext,
  wizard_cache_ttl VARCHAR(10) NOT NULL DEFAULT '0',
  wizard_perms text,
  wizard_seo_name varchar(255) default NULL,
  wizard_content_only tinyint(1) NOT NULL default '0',
  wizard_meta_keywords text,
  wizard_meta_description text,
  wizard_started int NOT NULL DEFAULT '0',
  wizard_previous_type VARCHAR( 32 ) NULL,
  wizard_ipb_wrapper TINYINT( 1 ) NOT NULL DEFAULT '0',
  wizard_omit_filename TINYINT( 1 ) NOT NULL DEFAULT '0',
  wizard_page_title VARCHAR( 255 ) NULL DEFAULT NULL,
  wizard_page_quicknav TINYINT NOT NULL DEFAULT '1',
  PRIMARY KEY  (wizard_id),
  KEY ( wizard_started )
);";

$TABLE[]	= "CREATE TABLE ccs_template_blocks (
  tpb_id int(11) NOT NULL auto_increment,
  tpb_name varchar(255) default NULL,
  tpb_params text,
  tpb_content MEDIUMTEXT NULL DEFAULT NULL,
  tpb_human_name VARCHAR(255) NULL DEFAULT NULL,
  tpb_app_type VARCHAR(30) NULL DEFAULT NULL,
  tpb_content_type VARCHAR(30) NULL DEFAULT NULL,
  tpb_image VARCHAR(255) NULL DEFAULT NULL,
  tpb_position int NOT NULL DEFAULT 0,
  tpb_protected TINYINT NOT NULL DEFAULT 0,
  tpb_desc TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (tpb_id),
  KEY tpb_name (tpb_name)
);";

$TABLE[]	= "CREATE TABLE ccs_template_cache (
  cache_id int(11) NOT NULL auto_increment,
  cache_type varchar(16) NOT NULL,
  cache_type_id int(11) NOT NULL default '0',
  cache_content mediumtext,
  PRIMARY KEY  (cache_id),
  UNIQUE KEY cache_type (cache_type,cache_type_id)
);";

$TABLE[]	= "CREATE TABLE ccs_databases (
  database_id mediumint(9) NOT NULL auto_increment,
  database_name varchar(255) NOT NULL,
  database_key varchar(255) NOT NULL,
  database_database varchar(255) NULL,
  database_description text,
  database_field_count mediumint(9) NOT NULL default '0',
  database_record_count mediumint(9) NOT NULL default '0',
  database_template_listing mediumint(9) NOT NULL default '0',
  database_template_display mediumint(9) NOT NULL default '0',
  database_template_categories mediumint(9) NOT NULL default '0',
  database_all_editable tinyint(1) NOT NULL default '0',
  database_revisions tinyint(1) NOT NULL DEFAULT '0',
  database_field_title VARCHAR( 255 ) NULL DEFAULT NULL,
  database_field_sort VARCHAR( 255 ) NULL DEFAULT NULL,
  database_field_direction VARCHAR( 4 ) NOT NULL DEFAULT 'desc',
  database_field_perpage SMALLINT NOT NULL DEFAULT '25',
  database_comment_approve TINYINT(1) NOT NULL DEFAULT '0',
  database_record_approve TINYINT(1) NOT NULL DEFAULT '0',
  database_rss INT NOT NULL DEFAULT '0',
  database_rss_cache MEDIUMTEXT NULL DEFAULT NULL,
  database_rss_cached INT NOT NULL DEFAULT '0',
  database_field_content VARCHAR( 255 ) NULL DEFAULT NULL,
  database_lang_sl VARCHAR( 255 ) NOT NULL DEFAULT '',
  database_lang_pl VARCHAR( 255 ) NOT NULL DEFAULT '',
  database_lang_su VARCHAR( 255 ) NOT NULL DEFAULT '',
  database_lang_pu VARCHAR( 255 ) NOT NULL DEFAULT '',
  database_comment_bump TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_featured_article INT NOT NULL DEFAULT '0',
  database_is_articles TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_forum_record TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_forum_comments TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_forum_delete TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_forum_forum MEDIUMINT NOT NULL DEFAULT '0',
  database_forum_prefix VARCHAR( 255 ) NULL DEFAULT NULL,
  database_forum_suffix VARCHAR( 255 ) NULL DEFAULT NULL,
  database_search TINYINT( 1 ) NOT NULL DEFAULT '0',
  database_tags_enabled TINYINT NOT NULL DEFAULT '0',
  database_tags_noprefixes TINYINT NOT NULL DEFAULT '0',
  database_tags_predefined TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (database_id),
  UNIQUE KEY database_key (database_key),
  KEY database_is_articles (database_is_articles)
);";

$TABLE[]	= "CREATE TABLE ccs_database_fields (
  field_id int(11) NOT NULL auto_increment,
  field_database_id mediumint(9) NOT NULL,
  field_name varchar(255) NOT NULL,
  field_description text,
  field_key varchar(255) NOT NULL,
  field_type varchar(255) default NULL,
  field_required tinyint(1) NOT NULL default '0',
  field_user_editable tinyint(1) NOT NULL default '0',
  field_position int(11) NOT NULL default '0',
  field_max_length mediumint(9) NOT NULL default '0',
  field_extra text,
  field_html TINYINT NOT NULL DEFAULT '0',
  field_is_numeric TINYINT(1) NOT NULL DEFAULT '0',
  field_truncate MEDIUMINT NOT NULL DEFAULT '100',
  field_default_value TEXT NULL DEFAULT NULL,
  field_display_listing TINYINT( 1 ) NOT NULL DEFAULT '1',
  field_display_display TINYINT( 1 ) NOT NULL DEFAULT '1',
  field_format_opts TEXT NULL DEFAULT NULL,
  field_validator TEXT NULL DEFAULT NULL,
  field_topic_format TEXT NULL DEFAULT NULL,
  field_filter TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (field_id),
  KEY field_database_id (field_database_id),
  KEY field_key (field_key)
);";

$TABLE[]	= "CREATE TABLE ccs_revisions (
revision_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
revision_type VARCHAR( 32 ) NOT NULL ,
revision_type_id INT NOT NULL ,
revision_content MEDIUMTEXT NULL DEFAULT NULL ,
revision_other MEDIUMTEXT NULL ,
revision_date INT NOT NULL DEFAULT '0',
revision_member INT NOT NULL DEFAULT '0',
KEY ( revision_type , revision_type_id, revision_date ),
KEY ( revision_member )
);";

$TABLE[]	= "CREATE TABLE ccs_database_revisions (
  revision_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  revision_database_id mediumint(9) NOT NULL,
  revision_record_id int(11) NOT NULL,
  revision_data LONGTEXT NULL,
  revision_date varchar(13) NOT NULL DEFAULT '0',
  revision_member_id int(11) NOT NULL DEFAULT '0',
  KEY revision_database_id (revision_database_id,revision_record_id),
  KEY ( revision_member_id )
);";

$TABLE[]	= "CREATE TABLE ccs_attachments_map (
  map_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  map_attach_id INT NOT NULL DEFAULT '0',
  map_database_id MEDIUMINT NOT NULL DEFAULT  '0',
  map_field_id INT NOT NULL DEFAULT '0',
  map_record_id INT NOT NULL DEFAULT '0',
  KEY map_database_id ( map_database_id , map_record_id ),
  KEY ( map_attach_id )
);";

$TABLE[]	= "CREATE TABLE ccs_database_ratings (
  rating_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rating_user_id INT NOT NULL DEFAULT '0',
  rating_database_id MEDIUMINT NOT NULL DEFAULT '0',
  rating_record_id INT NOT NULL DEFAULT '0',
  rating_rating INT NOT NULL DEFAULT '0',
  rating_added INT NOT NULL DEFAULT '0',
  rating_ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0',
  KEY rating_user_id ( rating_user_id , rating_database_id , rating_record_id )
);";

$TABLE[]	= "CREATE TABLE ccs_database_comments (
  comment_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  comment_user INT NOT NULL DEFAULT '0',
  comment_database_id MEDIUMINT NOT NULL DEFAULT '0',
  comment_record_id INT NOT NULL DEFAULT '0',
  comment_date INT NOT NULL DEFAULT '0',
  comment_ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0',
  comment_post TEXT NULL DEFAULT NULL,
  comment_approved TINYINT(1) NOT NULL DEFAULT '0',
  comment_author VARCHAR( 255 ) NULL DEFAULT NULL,
  comment_edit_date INT NOT NULL DEFAULT '0',
  KEY ( comment_user ),
  KEY comment_database_id ( comment_database_id , comment_record_id , comment_date )
);";

$TABLE[]	= "CREATE TABLE ccs_database_categories (
  category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  category_database_id MEDIUMINT NOT NULL DEFAULT '0',
  category_name VARCHAR( 255 ) NULL DEFAULT NULL ,
  category_parent_id INT NOT NULL DEFAULT '0',
  category_last_record_id INT NOT NULL DEFAULT '0',
  category_last_record_date INT NOT NULL DEFAULT '0',
  category_last_record_member INT NOT NULL DEFAULT  '0',
  category_last_record_name VARCHAR( 255 ) NULL DEFAULT NULL,
  category_last_record_seo_name VARCHAR( 255 ) NULL DEFAULT NULL,
  category_description TEXT NULL DEFAULT NULL,
  category_position INT NOT NULL DEFAULT '0',
  category_records INT NOT NULL DEFAULT '0',
  category_records_queued INT NOT NULL DEFAULT '0',
  category_record_comments INT NOT NULL DEFAULT '0',
  category_record_comments_queued INT NOT NULL DEFAULT '0',
  category_has_perms TINYINT(1) NOT NULL DEFAULT '0',
  category_show_records TINYINT(1) NOT NULL DEFAULT '1',
  category_rss INT NOT NULL DEFAULT '0',
  category_rss_cache MEDIUMTEXT NULL DEFAULT NULL,
  category_rss_cached INT NOT NULL DEFAULT '0',
  category_rss_exclude TINYINT NOT NULL DEFAULT '0',
  category_furl_name VARCHAR( 255 ) NULL DEFAULT NULL,
  category_meta_keywords TEXT NULL DEFAULT NULL,
  category_meta_description TEXT NULL DEFAULT NULL,
  category_template INT NOT NULL DEFAULT '0',
  category_forum_override TINYINT( 1 ) NOT NULL DEFAULT '0',
  category_forum_record TINYINT( 1 ) NOT NULL DEFAULT '0',
  category_forum_comments TINYINT( 1 ) NOT NULL DEFAULT '0',
  category_forum_delete TINYINT( 1 ) NOT NULL DEFAULT '0',
  category_forum_forum MEDIUMINT NOT NULL DEFAULT '0',
  category_forum_prefix VARCHAR( 255 ) NULL DEFAULT NULL,
  category_forum_suffix VARCHAR( 255 ) NULL DEFAULT NULL,
  category_page_title VARCHAR( 255 ) NULL DEFAULT NULL,
  category_tags_override TINYINT NOT NULL DEFAULT '0',
  category_tags_enabled TINYINT NOT NULL DEFAULT '0',
  category_tags_noprefixes TINYINT NOT NULL DEFAULT '0',
  category_tags_predefined TEXT NULL DEFAULT NULL,
  KEY ( category_database_id ),
  KEY ( category_template )
);";

$TABLE[]	= "CREATE TABLE ccs_database_moderators (
  moderator_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  moderator_database_id INT NOT NULL DEFAULT  '0',
  moderator_type VARCHAR( 16 ) NULL DEFAULT NULL ,
  moderator_type_id INT NOT NULL DEFAULT  '0',
  moderator_delete_record TINYINT( 1 ) NOT NULL DEFAULT  '0',
  moderator_edit_record TINYINT( 1 ) NOT NULL DEFAULT  '0',
  moderator_lock_record TINYINT( 1 ) NOT NULL DEFAULT  '0',
  moderator_unlock_record TINYINT( 1 ) NOT NULL DEFAULT  '0',
  moderator_delete_comment TINYINT( 1 ) NOT NULL DEFAULT  '0',
  moderator_approve_record TINYINT(1) NOT NULL DEFAULT '0',
  moderator_approve_comment TINYINT(1) NOT NULL DEFAULT '0',
  moderator_pin_record TINYINT(1) NOT NULL DEFAULT '0',
  moderator_add_record TINYINT(1) NOT NULL DEFAULT '0',
  moderator_edit_comment TINYINT NOT NULL DEFAULT '0',
  moderator_restore_revision TINYINT NOT NULL DEFAULT '0',
  moderator_extras TINYINT NOT NULL DEFAULT '0',
  KEY ( moderator_database_id )
);";

$TABLE[]	= "CREATE TABLE ccs_database_modqueue (
  mod_id INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  mod_database INT NOT NULL default '0',
  mod_record INT NOT NULL default '0',
  mod_comment INT NOT NULL default '0',
  mod_poster INT NOT NULL default '0',
  KEY mod_database (mod_database,mod_record,mod_comment)
);";

$TABLE[] = "CREATE TABLE ccs_slug_memory (
  memory_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  memory_url VARCHAR( 255 ) NULL DEFAULT NULL ,
  memory_type VARCHAR( 32 ) NULL DEFAULT NULL ,
  memory_type_id INT NOT NULL DEFAULT '0',
  memory_type_id_2 INT NOT NULL DEFAULT '0',
  KEY ( memory_url )
);";

$TABLE[] = "CREATE TABLE ccs_menus (
  menu_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  menu_parent_id INT NOT NULL DEFAULT '0',
  menu_title VARCHAR( 255 ) NULL DEFAULT NULL ,
  menu_url TEXT NULL DEFAULT NULL ,
  menu_submenu INT NOT NULL DEFAULT '0',
  menu_position VARCHAR( 255 ) NOT NULL DEFAULT '0',
  menu_description TEXT NULL DEFAULT NULL ,
  menu_attributes TEXT NULL DEFAULT NULL ,
  menu_permissions TEXT NULL DEFAULT NULL ,
  INDEX ( menu_parent_id, menu_position ),
  INDEX ( menu_position )
);";

$TABLE[] = "ALTER TABLE core_applications ADD app_tab_attributes TEXT NULL DEFAULT NULL;";
$TABLE[] = "ALTER TABLE core_applications ADD app_tab_description TEXT NULL DEFAULT NULL;";
