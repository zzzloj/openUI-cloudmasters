import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

export async function GET(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем основную информацию о пользователе
    const [members] = await connection.execute(`
      SELECT 
        member_id,
        name,
        members_display_name,
        members_seo_name,
        email,
        member_group_id,
        joined,
        last_visit,
        last_activity,
        posts,
        title,
        ip_address,
        warn_level,
        member_banned,
        has_blog,
        has_gallery,
        members_profile_views,
        members_day_posts,
        members_bitoptions,
        member_uploader,
        time_offset,
        language,
        skin,
        dst_in_use,
        coppa_user,
        view_sigs,
        view_img,
        auto_track,
        temp_ban,
        login_anonymous,
        ignored_users,
        mgroup_others,
        org_perm_id,
        member_login_key,
        member_login_key_expire,
        blogs_recache,
        members_auto_dst,
        members_created_remote,
        members_cache,
        members_disable_pm,
        members_l_display_name,
        members_l_username,
        failed_logins,
        failed_login_count,
        members_pass_hash,
        members_pass_salt,
        member_uploader,
        fb_uid,
        fb_emailhash,
        fb_lastsync,
        vk_uid,
        vk_token,
        live_id,
        twitter_id,
        twitter_token,
        twitter_secret,
        notification_cnt,
        tc_lastsync,
        fb_session,
        fb_token,
        ips_mobile_token,
        unacknowledged_warnings,
        ipsconnect_id,
        ipsconnect_revalidate_url,
        gallery_perms,
        activation_code,
        activation_expires,
        is_activated,
        reset_code,
        reset_expires,
        bday_day,
        bday_month,
        bday_year,
        msg_count_new,
        msg_count_total,
        msg_count_reset,
        msg_show_notification,
        misc,
        allow_admin_mails,
        restrict_post,
        mod_posts,
        warn_lastwarn
      FROM members 
      WHERE member_id = ?
    `, [params.id]);

    if (!members || members.length === 0) {
      await connection.end();
      return NextResponse.json(
        { success: false, error: 'Пользователь не найден' },
        { status: 404 }
      );
    }

    const member = members[0];

    // Получаем статистику постов пользователя
    const [postsStats] = await connection.execute(`
      SELECT 
        COUNT(*) as total_posts,
        COUNT(DISTINCT topic_id) as total_topics,
        MAX(created_at) as last_post_date
      FROM forum_posts 
      WHERE author_id = ?
    `, [params.id]);

    // Получаем последние посты пользователя
    const [recentPosts] = await connection.execute(`
      SELECT 
        fp.id,
        fp.content,
        fp.created_at,
        ft.title as topic_title,
        ft.id as topic_id,
        fc.name as forum_name,
        fc.id as forum_id
      FROM forum_posts fp
      JOIN forum_topics ft ON fp.topic_id = ft.id
      JOIN forum_categories fc ON ft.forum_id = fc.id
      WHERE fp.author_id = ?
      ORDER BY fp.created_at DESC
      LIMIT 5
    `, [params.id]);

    // Получаем темы пользователя
    const [userTopics] = await connection.execute(`
      SELECT 
        ft.id,
        ft.title,
        ft.created_at,
        ft.posts_count,
        ft.views_count,
        fc.name as forum_name
      FROM forum_topics ft
      JOIN forum_categories fc ON ft.forum_id = fc.id
      WHERE ft.author_id = ?
      ORDER BY ft.created_at DESC
      LIMIT 10
    `, [params.id]);

    // Получаем информацию о группе пользователя
    const [memberGroups] = await connection.execute(`
      SELECT 
        id,
        name,
        description,
        permissions
      FROM member_groups 
      WHERE id = ?
    `, [member.member_group_id]);

    await connection.end();

    // Формируем ответ
    const profileData = {
      success: true,
      profile: {
        id: member.member_id,
        name: member.name,
        display_name: member.members_display_name,
        seo_name: member.members_seo_name,
        email: member.email,
        member_group_id: member.member_group_id,
        member_group: memberGroups[0] || null,
        joined: member.joined,
        last_visit: member.last_visit,
        last_activity: member.last_activity,
        posts: member.posts,
        title: member.title,
        warn_level: member.warn_level,
        member_banned: member.member_banned === 1,
        has_blog: member.has_blog,
        has_gallery: member.has_gallery,
        profile_views: member.members_profile_views,
        day_posts: member.members_day_posts,
        bitoptions: member.members_bitoptions,
        uploader: member.member_uploader,
        time_offset: member.time_offset,
        language: member.language,
        skin: member.skin,
        dst_in_use: member.dst_in_use === 1,
        coppa_user: member.coppa_user === 1,
        view_sigs: member.view_sigs === 1,
        view_img: member.view_img === 1,
        auto_track: member.auto_track,
        temp_ban: member.temp_ban,
        login_anonymous: member.login_anonymous,
        ignored_users: member.ignored_users,
        mgroup_others: member.mgroup_others,
        org_perm_id: member.org_perm_id,
        member_login_key: member.member_login_key,
        member_login_key_expire: member.member_login_key_expire,
        blogs_recache: member.blogs_recache === 1,
        members_auto_dst: member.members_auto_dst === 1,
        members_created_remote: member.members_created_remote === 1,
        members_cache: member.members_cache,
        members_disable_pm: member.members_disable_pm,
        members_l_display_name: member.members_l_display_name,
        members_l_username: member.members_l_username,
        failed_logins: member.failed_logins,
        failed_login_count: member.failed_login_count,
        members_pass_hash: member.members_pass_hash,
        members_pass_salt: member.members_pass_salt,
        fb_uid: member.fb_uid,
        fb_emailhash: member.fb_emailhash,
        fb_lastsync: member.fb_lastsync,
        vk_uid: member.vk_uid,
        vk_token: member.vk_token,
        live_id: member.live_id,
        twitter_id: member.twitter_id,
        twitter_token: member.twitter_token,
        twitter_secret: member.twitter_secret,
        notification_cnt: member.notification_cnt,
        tc_lastsync: member.tc_lastsync,
        fb_session: member.fb_session,
        fb_token: member.fb_token,
        ips_mobile_token: member.ips_mobile_token,
        unacknowledged_warnings: member.unacknowledged_warnings,
        ipsconnect_id: member.ipsconnect_id,
        ipsconnect_revalidate_url: member.ipsconnect_revalidate_url,
        gallery_perms: member.gallery_perms,
        activation_code: member.activation_code,
        activation_expires: member.activation_expires,
        is_activated: member.is_activated === 1,
        reset_code: member.reset_code,
        reset_expires: member.reset_expires,
        birthday: {
          day: member.bday_day,
          month: member.bday_month,
          year: member.bday_year
        },
        messages: {
          new: member.msg_count_new,
          total: member.msg_count_total,
          reset: member.msg_count_reset,
          show_notification: member.msg_show_notification
        },
        misc: member.misc,
        allow_admin_mails: member.allow_admin_mails === 1,
        restrict_post: member.restrict_post,
        mod_posts: member.mod_posts,
        warn_lastwarn: member.warn_lastwarn,
        ip_address: member.ip_address,
        stats: {
          total_posts: postsStats[0]?.total_posts || 0,
          total_topics: postsStats[0]?.total_topics || 0,
          last_post_date: postsStats[0]?.last_post_date || null
        },
        recent_posts: recentPosts,
        user_topics: userTopics
      }
    };

    return NextResponse.json(profileData);

  } catch (error) {
    console.error('Profile API error:', error);
    return NextResponse.json(
      { success: false, error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
} 