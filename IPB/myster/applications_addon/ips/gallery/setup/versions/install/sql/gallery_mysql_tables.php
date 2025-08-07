<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/

$DB  = ipsRegistry::DB();
$PRE = ipsRegistry::dbFunctions()->getPrefix();

if ( ! $DB->checkForTable( 'core_geolocation_cache' ) )
{
	$TABLE[] = "CREATE TABLE core_geolocation_cache (
			geocache_key		VARCHAR(32) NOT NULL,
			geocache_lat		VARCHAR(100) NOT NULL,
			geocache_lon		VARCHAR(100) NOT NULL,
			geocache_raw		TEXT,
			geocache_country	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_district	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_district2	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_locality	VARCHAR(255) NOT NULL DEFAULT '',
			geocache_type		VARCHAR(255) NOT NULL DEFAULT '',
			geocache_engine		VARCHAR(255) NOT NULL DEFAULT '',
			geocache_added		INT(10) NOT NULL DEFAULT '0',
			geocache_short		TEXT,
			PRIMARY KEY	geocache_key (geocache_key),
			KEY geo_lat_lon (geocache_lat, geocache_lon)
		);";
}


$TABLE[] = "CREATE TABLE gallery_albums (
  album_id bigint(20) NOT NULL AUTO_INCREMENT,
  album_category_id int(10) NOT NULL DEFAULT '0',
  album_owner_id int(10) NOT NULL DEFAULT '0',
  album_name varchar(255) DEFAULT NULL,
  album_name_seo varchar(255) DEFAULT NULL,
  album_description text,
  album_type int(11) NOT NULL DEFAULT '0',
  album_count_imgs int(11) NOT NULL DEFAULT '0',
  album_count_comments int(11) NOT NULL DEFAULT '0',
  album_count_imgs_hidden int(11) NOT NULL DEFAULT '0',
  album_count_comments_hidden int(11) NOT NULL DEFAULT '0',
  album_cover_img_id bigint(20) NOT NULL DEFAULT '0',
  album_last_img_id bigint(20) NOT NULL DEFAULT '0',
  album_last_img_date int(11) NOT NULL DEFAULT '0',
  album_sort_options text,
  album_allow_comments tinyint(4) NOT NULL DEFAULT '0',
  album_allow_rating tinyint(4) NOT NULL DEFAULT '0',
  album_rating_aggregate int(11) NOT NULL DEFAULT '0',
  album_rating_count int(11) NOT NULL DEFAULT '0',
  album_rating_total int(11) NOT NULL DEFAULT '0',
  album_after_forum_id int(11) NOT NULL DEFAULT '0',
  album_position int(11) NOT NULL DEFAULT '0',
  album_watermark tinyint(4) NOT NULL DEFAULT '0',
  album_last_x_images TEXT NULL DEFAULT NULL,
  PRIMARY KEY (album_id),
  KEY album_last_img_date (album_last_img_date),
  KEY album_owner_id (album_owner_id,album_last_img_date),
  KEY album_parent_id (album_category_id,album_name_seo)
);";

$TABLE[] = "CREATE TABLE gallery_bandwidth (
  bid bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  member_id mediumint(8) unsigned NOT NULL DEFAULT '0',
  file_name varchar(60) NOT NULL DEFAULT '',
  bdate int(10) unsigned NOT NULL DEFAULT '0',
  bsize int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (bid),
  KEY file_name (file_name),
  KEY member_id (member_id),
  KEY bdate (bdate)
);";

$TABLE[] = "CREATE TABLE gallery_categories (
  category_id int(11) NOT NULL AUTO_INCREMENT,
  category_parent_id int(11) NOT NULL DEFAULT '0',
  category_name varchar(255) DEFAULT NULL,
  category_name_seo varchar(255) DEFAULT NULL,
  category_description text,
  category_count_imgs int(11) NOT NULL DEFAULT '0',
  category_count_comments int(11) NOT NULL DEFAULT '0',
  category_count_imgs_hidden int(11) NOT NULL DEFAULT '0',
  category_count_comments_hidden int(11) NOT NULL DEFAULT '0',
  category_cover_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_date int(11) NOT NULL DEFAULT '0',
  category_type int(11) NOT NULL DEFAULT '0',
  category_sort_options text,
  category_allow_comments tinyint(4) NOT NULL DEFAULT '0',
  category_allow_rating tinyint(4) NOT NULL DEFAULT '0',
  category_approve_img tinyint(4) NOT NULL DEFAULT '0',
  category_approve_com tinyint(4) NOT NULL DEFAULT '0',
  category_rules mediumtext,
  category_rating_aggregate int(11) NOT NULL DEFAULT '0',
  category_rating_count int(11) NOT NULL DEFAULT '0',
  category_rating_total int(11) NOT NULL DEFAULT '0',
  category_after_forum_id int(11) NOT NULL DEFAULT '0',
  category_watermark tinyint(4) NOT NULL DEFAULT '0',
  category_position int(11) NOT NULL DEFAULT '0',
  category_can_tag tinyint(4) NOT NULL DEFAULT '0',
  category_preset_tags text,
  category_public_albums int(11) NOT NULL DEFAULT '0',
  category_nonpublic_albums int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (category_id),
  KEY category_last_img_id (category_last_img_id)
);";

$TABLE[] = "CREATE TABLE gallery_comments (
  comment_id int(10) NOT NULL AUTO_INCREMENT,
  comment_edit_time int(10) NOT NULL DEFAULT '0',
  comment_author_id int(11) NOT NULL DEFAULT '0',
  comment_author_name varchar(255) DEFAULT NULL,
  comment_ip_address varchar(46) DEFAULT NULL,
  comment_post_date int(10) NOT NULL DEFAULT '0',
  comment_text text,
  comment_approved tinyint(4) NOT NULL DEFAULT '0',
  comment_img_id int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (comment_id),
  KEY author_id (comment_author_id),
  KEY post_date (comment_post_date),
  KEY img_id (comment_img_id,comment_post_date),
  KEY comment_ip_address (comment_ip_address)
);";

$TABLE[] = "CREATE TABLE gallery_images (
  image_id bigint(20) NOT NULL AUTO_INCREMENT,
  image_member_id int NOT NULL DEFAULT '0',
  image_category_id int(11) NOT NULL DEFAULT '0',
  image_album_id bigint(20) NOT NULL DEFAULT '0',
  image_caption varchar(255) NOT NULL,
  image_description text,
  image_directory varchar(255) DEFAULT NULL,
  image_masked_file_name varchar(255) DEFAULT NULL,
  image_medium_file_name varchar(255) DEFAULT NULL,
  image_original_file_name varchar(255) DEFAULT NULL,
  image_file_name varchar(255) DEFAULT NULL,
  image_file_size int(10) NOT NULL DEFAULT '0',
  image_file_type varchar(50) DEFAULT NULL,
  image_approved tinyint(1) NOT NULL DEFAULT '0',
  image_thumbnail tinyint(1) NOT NULL DEFAULT '0',
  image_views int(10) NOT NULL DEFAULT '0',
  image_comments int(10) NOT NULL DEFAULT '0',
  image_comments_queued int(10) NOT NULL DEFAULT '0',
  image_date int(10) NOT NULL DEFAULT '0',
  image_ratings_total int(10) NOT NULL DEFAULT '0',
  image_ratings_count int(10) NOT NULL DEFAULT '0',
  image_rating int(10) NOT NULL DEFAULT '0',
  image_pinned tinyint(1) NOT NULL DEFAULT '0',
  image_last_comment int(10) NOT NULL DEFAULT '0',
  image_media tinyint(1) NOT NULL DEFAULT '0',
  image_credit_info text,
  image_copyright varchar(255) DEFAULT NULL,
  image_metadata text,
  image_media_thumb varchar(255) DEFAULT NULL,
  image_caption_seo varchar(255) DEFAULT NULL,
  image_notes text,
  image_privacy int(11) NOT NULL DEFAULT '0',
  image_data text,
  image_parent_permission varchar(255) DEFAULT NULL,
  image_feature_flag tinyint(1) NOT NULL DEFAULT '0',
  image_gps_raw text,
  image_gps_latlon varchar(255) DEFAULT NULL,
  image_gps_show tinyint(1) NOT NULL DEFAULT '0',
  image_gps_lat varchar(255) DEFAULT NULL,
  image_gps_lon varchar(255) DEFAULT NULL,
  image_loc_short text,
  image_media_data text,
  PRIMARY KEY (image_id),
  KEY member_id (image_member_id),
  KEY im_select (image_approved,image_privacy,image_member_id,image_date),
  KEY cmt_lookup (image_privacy,image_member_id,image_parent_permission,image_album_id,image_approved,image_last_comment),
  KEY idate (image_date),
  KEY album_id (image_album_id,image_approved,image_date),
  KEY image_feature_flag (image_feature_flag,image_date),
  KEY gb_select (image_approved,image_parent_permission,image_date),
  KEY lastcomment (image_last_comment,image_date)
);";

$TABLE[] = "CREATE TABLE gallery_images_uploads (
  upload_key varchar(32) NOT NULL,
  upload_session varchar(32) NOT NULL,
  upload_member_id int(10) NOT NULL DEFAULT '0',
  upload_album_id int(10) NOT NULL DEFAULT '0',
  upload_category_id int(11) NOT NULL DEFAULT '0',
  upload_date int(10) NOT NULL DEFAULT '0',
  upload_file_directory varchar(255) DEFAULT NULL,
  upload_file_orig_name varchar(255) DEFAULT NULL,
  upload_file_name varchar(255) DEFAULT NULL,
  upload_file_name_original varchar(255) DEFAULT NULL,
  upload_file_size int(10) NOT NULL DEFAULT '0',
  upload_file_type varchar(50) DEFAULT NULL,
  upload_thumb_name varchar(255) DEFAULT NULL,
  upload_medium_name varchar(255) DEFAULT NULL,
  upload_title text,
  upload_description text,
  upload_copyright text,
  upload_exif text,
  upload_data text,
  upload_feature_flag int(1) NOT NULL DEFAULT '0',
  upload_geodata text,
  upload_media_data text,
  PRIMARY KEY (upload_key),
  KEY upload_member_id (upload_member_id,upload_album_id),
  KEY upload_date (upload_date),
  KEY upload_session (upload_session)
);";

$TABLE[] = "CREATE TABLE gallery_moderators (
  mod_id int(11) NOT NULL AUTO_INCREMENT,
  mod_type varchar(32) NOT NULL DEFAULT 'group',
  mod_type_id int(11) NOT NULL DEFAULT '0',
  mod_type_name varchar(255) DEFAULT NULL,
  mod_categories text,
  mod_can_approve tinyint(4) NOT NULL DEFAULT '0',
  mod_can_edit tinyint(4) NOT NULL DEFAULT '0',
  mod_can_hide tinyint(4) NOT NULL DEFAULT '0',
  mod_can_delete tinyint(4) NOT NULL DEFAULT '0',
  mod_can_approve_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_edit_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_delete_comments tinyint(4) NOT NULL DEFAULT '0',
  mod_can_move tinyint(4) NOT NULL DEFAULT '0',
  mod_set_cover_image tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (mod_id),
  KEY mod_type (mod_type,mod_type_id)
);";

$TABLE[] = "CREATE TABLE gallery_ratings (
  rate_id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  rate_member_id int(11) NOT NULL DEFAULT '0',
  rate_type varchar(32) NOT NULL DEFAULT 'image',
  rate_type_id bigint(20) NOT NULL DEFAULT '0',
  rate_date int(10) NOT NULL DEFAULT '0',
  rate_rate int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (rate_id),
  KEY rating_find_me (rate_member_id,rate_type,rate_type_id)
);";


$TABLE[] = "ALTER TABLE members ADD gallery_perms VARCHAR( 10 ) DEFAULT '1:1:1' NOT NULL;";

$TABLE[] = "ALTER TABLE groups ADD g_max_diskspace INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_max_upload INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_max_transfer INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_max_views INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_create_albums TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_create_albums_private TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_create_albums_fo TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_album_limit INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_img_album_limit INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_edit_own TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_del_own TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_img_local TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_movies TINYINT( 1 ) UNSIGNED default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_movie_size INT( 10 ) default '0' NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD g_gallery_use TINYINT( 1 ) NOT NULL DEFAULT '1';";
$TABLE[] = "ALTER TABLE groups ADD g_delete_own_albums TINYINT( 1 ) NOT NULL DEFAULT '0';";
