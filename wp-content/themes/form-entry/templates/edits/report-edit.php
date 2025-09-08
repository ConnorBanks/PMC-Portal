

<?php
$report_id = $_GET['reportID'];;
$status    = get_post_status($report_id);
?>

<style>
  .banner-draft{
    margin:16px 0;
    padding:12px 14px;
    border:1px solid #fde68a;
    background:#fffbeb;
    color:#7c2d12;
    border-radius:10px;
    font-size:15px;
  }
</style>

<?php if ($status === 'draft'): ?>
  <div class="banner-draft">
    This report is currently <strong>in draft</strong> and <strong>has not been sent</strong> to the property manager. To send, please save and use the 'resend email' function.
  </div>
<?php endif; ?>
<?php
/**
 * Template Name: Edit Report (ACF) – Save + Resend
 */
defined('ABSPATH') || exit;

/* ACF field NAMES on Report */
$acf_client       = 'client';
$acf_site         = 'site';
$acf_date         = 'date';
$acf_po_number    = 'po_number';
$acf_gallery      = 'image_gallery';
$acf_full_notes   = 'full_notes';

/* OPTIONAL (recommended): ACF FIELD KEYS */
$acf_key_client   = ''; // e.g. 'field_xxxxxx_client'
$acf_key_site     = ''; // e.g. 'field_yyyyyy_site'

$acf_videos         = 'videos';        // repeater field name
$acf_video_file     = 'video_file';    // file subfield name
$acf_video_caption  = 'video_caption'; // optional; '' if you don’t have one

/* ---------- Helpers ---------- */
function _report_view_pretty_url($post): string {
  if (!$post instanceof WP_Post) return home_url('/');
  $base = trailingslashit(home_url('/report/'));
  return user_trailingslashit($base . rawurlencode($post->post_name) . '/');
}
function _report_safe_redirect(string $url, int $status = 303) {
  while (ob_get_level() > 0) { ob_end_clean(); }
  nocache_headers();
  $safe = wp_validate_redirect($url, home_url('/'));
  wp_safe_redirect($safe, $status);
  echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . esc_attr($safe) . '"><script>location.replace(' . json_encode($safe) . ');</script>';
  exit;
}
/** Normalize ACF post-link to a single ID if possible */
function _acf_post_link_current_id($val, string $fallback_post_type = ''): int {
  if (is_object($val) && isset($val->ID)) return (int)$val->ID;
  if (is_array($val) && isset($val['ID'])) return (int)$val['ID'];
  if (is_array($val) && isset($val['value'])) {
    $v = $val['value'];
    if (is_numeric($v)) return (int)$v;
    if (is_string($v)) {
      if (ctype_digit($v)) return (int)$v;
      if ($fallback_post_type) {
        $p = get_page_by_title($v, OBJECT, $fallback_post_type);
        if ($p) return (int)$p->ID;
      }
    }
  }
  if (is_array($val)) {
    foreach ($val as $v) {
      $id = _acf_post_link_current_id($v, $fallback_post_type);
      if ($id) return $id;
    }
  }
  if (is_numeric($val)) return (int)$val;
  if (is_string($val)) {
    if (ctype_digit($val)) return (int)$val;
    if ($fallback_post_type) {
      $p = get_page_by_title($val, OBJECT, $fallback_post_type);
      if ($p) return (int)$p->ID;
    }
  }
  return 0;
}
/** Save a post link using correct shape; prefer field KEY when available */
function _save_post_link_field(int $post_id, string $field_name, int $incoming_id, string $field_key = ''): void {
  if (!function_exists('update_field')) return;

  $key        = $field_key;
  $type       = 'post_object';
  $multiple   = false;
  $return_fmt = 'id';

  if (function_exists('get_field_object')) {
    $obj = $key ? get_field_object($key, $post_id) : get_field_object($field_name, $post_id);
    if ($obj) {
      $key        = $obj['key'] ?? ($key ?: $field_name);
      $type       = $obj['type'] ?? $type;
      $multiple   = !empty($obj['multiple']);
      $return_fmt = $obj['return_format'] ?? $return_fmt;
    }
  }

  if ($incoming_id > 0) {
    if ($type === 'post_object') {
      $value_to_save = $multiple ? [ $incoming_id ] : $incoming_id;
    } elseif ($type === 'relationship') {
      $value_to_save = [ $incoming_id ];
    } elseif ($type === 'select') {
      $value_to_save = ($return_fmt === 'label') ? get_the_title($incoming_id) : (string)$incoming_id;
    } else {
      $value_to_save = $incoming_id;
    }
  } else {
    $value_to_save = $multiple ? [] : null;
  }

  $update_id = $key ?: $field_name;
  $ok = update_field($update_id, $value_to_save, $post_id);

  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log(sprintf(
      '[Edit Report] update_field %s => %s (type:%s multiple:%s fmt:%s) value:%s result:%s',
      $field_name, $update_id, $type, $multiple ? 'true' : 'false', $return_fmt, wp_json_encode($value_to_save), $ok ? 'OK' : 'FAIL'
    ));
  }

  if (!$ok && $key) {
    update_post_meta($post_id, '_' . $field_name, $key);
    update_post_meta($post_id, $field_name, $value_to_save);
  }
}
/** Convert stored date to Y-m-d input format */
function _acf_date_to_input($raw): string {
  if (!$raw) return '';
  if (is_string($raw) && preg_match('/^\d{8}$/', $raw)) {
    $y = substr($raw,0,4); $m=substr($raw,4,2); $d=substr($raw,6,2);
    return "$y-$m-$d";
  }
  return is_string($raw) ? $raw : '';
}
/** Convert posted input date to ACF field’s return_format */
function _input_date_for_acf(string $input, $field_obj): string {
  if (!$input) return '';
  $fmt = is_array($field_obj) ? ($field_obj['return_format'] ?? 'Y-m-d') : 'Y-m-d';
  if ($fmt === 'Ymd') return str_replace('-', '', $input);
  return $input;
}

/**
 * Branded email (sends to CLIENT primary_contact.email_address by default).
 * Pass a blank 'notify_to' to use client email; otherwise it uses the given address.
 */
function _send_report_email(array $args): void {
  $defaults = [
    'new_id'         => 0,
    'client_id'      => 0,
    'site_id'        => 0,
    'date_store'     => '',
    'po_number'      => '',
    'notes'          => '',
    'view_url'       => '',
    'pdf_url'        => '',

    'notify_to'      => '', // leave blank to use client email
    'notify_cc'      => [],
    'subject_prefix' => 'New Report Available',

    // Brand + links
    'logo_url'       => 'https://pmc-portal.blacksheep-creative.co.uk/wp-content/uploads/2025/07/WhatsApp-Image-2025-05-01-at-12.11.53.jpeg',
    'contact_url'    => 'https://pmc-contractinguk.co.uk/contact',
    'portal_url'     => 'https://pmc-contractinguk.co.uk/portal',

    'company_name'   => 'PMC Contracting (UK)',
    'company_url'    => 'https://pmc-contractinguk.co.uk',
    'company_address'=> '53 West Street, Sittingbourne, Kent ME10 1AN',
    'copyright_line' => '© ' . gmdate('Y') . ' PMC Contracting (UK)',

    'social_twitter'   => 'https://twitter.com/yourhandle',
    'social_facebook'  => 'https://facebook.com/yourpage',
    'social_instagram' => 'https://instagram.com/yourhandle',
  ];
  $a = array_merge($defaults, $args);

  // Resolve recipient to client's primary contact email if not explicitly provided
  $client_email = '';
  if (empty($a['notify_to']) && !empty($a['client_id']) && function_exists('get_field')) {
    $group = (array) get_field('primary_contact', (int) $a['client_id']);
    if (!empty($group['email_address'])) {
      $client_email = sanitize_email($group['email_address']);
    }
  }
  $to = $a['notify_to'] ?: $client_email ?: get_option('admin_email');
  if (!is_email($to)) {
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('[Report Email] Aborted: no valid recipient. client_id='.$a['client_id'].' report_id='.$a['new_id']);
    }
    return;
  }

  // Titles / dates
  $site_title   = $a['site_id']   ? get_the_title($a['site_id'])   : '';
  $date_label   = (function($raw){
    if (!$raw) return '';
    if (preg_match('/^\d{8}$/', $raw)) $raw = substr($raw,0,4).'-'.substr($raw,4,2).'-'.substr($raw,6,2);
    $ts = strtotime($raw);
    return $ts ? date_i18n('j M Y', $ts) : $raw;
  })($a['date_store']);

  $subject = trim(sprintf(
    '%s: %s%s%s',
    $a['subject_prefix'],
    $site_title ?: 'Project',
    ($date_label ? ' – '.$date_label : ''),
    ($a['po_number'] ? ' – PO '.$a['po_number'] : '')
  ));

  // Colors
  $brand_teal   = '#0f3a47';
  $text_main    = '#111827';
  $text_muted   = '#6b7280';
  $border       = '#d7dfe7';
  $bg_page      = '#f9f9fb';

  ob_start(); ?>
  <!doctype html><html><body style="margin:0;padding:0;background:<?php echo $bg_page; ?>;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $bg_page; ?>;">
    <tr><td align="center" style="padding:0">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $brand_teal; ?>;">
        <tr><td align="center" style="padding:18px 16px">
          <img src="<?php echo esc_url($a['logo_url']); ?>" alt="PMC" style="display:block;height:32px;width:auto;border:0;">
        </td></tr>
      </table>

      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;background:#fff;margin:25px auto 0;border-collapse:separate;border:1px solid <?php echo $border; ?>;border-radius:14px;overflow:hidden;">
        <tr><td style="padding:28px 22px 10px 22px;text-align:center">
          <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;font-weight:800;font-size:32px;line-height:1.2;color:<?php echo $text_main; ?>;margin:0 0 8px">New Report<br>Available</div>
          <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;color:<?php echo $text_muted; ?>;font-size:16px;line-height:1.6;margin:8px 0 18px">
            We’ve generated a new report for your project<?php echo $site_title ? ' at <strong style="color:'.$text_main.'">'.$site_title.'</strong>' : ''; ?>.
          </div>
        </td></tr>

        <?php if (!empty($a['view_url'])): ?>
        <tr><td align="center" style="padding:2px 22px 22px 22px">
          <a href="<?php echo esc_url($a['view_url']); ?>" style="display:inline-block;background:<?php echo $brand_teal; ?>;color:#fff;text-decoration:none;font-weight:700;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;font-size:16px;line-height:1;padding:16px 28px;border-radius:28px;">View Report</a>
        </td></tr>
        <?php endif; ?>
      </table>

      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $brand_teal; ?>;margin-top:18px">
        <tr><td align="center" style="padding:6px 16px 2px 16px">
          <a href="<?php echo esc_url($a['company_url']); ?>" style="color:#ffffff;text-decoration:none;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif">pmc-contractinguk.co.uk</a>
        </td></tr>
        <tr><td align="center" style="padding:4px 16px 22px 16px;color:#d1e1e7;font-size:12px;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif">
          <?php echo esc_html($a['copyright_line']); ?>
        </td></tr>
      </table>
    </td></tr>
  </table>
  </body></html>
  <?php
  $message = ob_get_clean();

  $headers = ['Content-Type: text/html; charset=UTF-8'];
  $blogname  = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
  $from_email = get_option('admin_email');
  if ($from_email) $headers[] = 'From: '.$blogname.' <'.$from_email.'>';
  if (!empty($a['notify_cc'])) {
    foreach ((array)$a['notify_cc'] as $cc) {
      if (is_email($cc)) $headers[] = 'Cc: '.$cc;
    }
  }

  $sent = wp_mail($to, $subject, $message, $headers);
  if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && !$sent) {
    error_log('[Report Email] Failed to send to '.$to.' (report '.$a['new_id'].')');
  }
}

/* ---------- Input + permissions ---------- */
$report_id = isset($_GET['reportID']) ? absint($_GET['reportID']) : 0;
$post_obj  = $report_id ? get_post($report_id) : null;
$can_edit  = $post_obj ? current_user_can('edit_post', $report_id) : false;

$error   = isset($_GET['error'])   ? sanitize_text_field($_GET['error'])   : '';
$emailed = isset($_GET['emailed']) ? (int) $_GET['emailed'] : 0;

/* Where to go after save */
$view_url = $post_obj ? _report_view_pretty_url($post_obj) : trailingslashit(home_url('/report/')); // /report/{slug}/

/**
 * Get repeater + subfield KEYS from names so we can add rows reliably.
 * Returns ['parent'=>'field_xxx', 'file'=>'field_yyy', 'caption'=>'field_zzz' (or '')]
 */
function _video_field_keys(int $post_id, string $parent_name, string $file_name, string $caption_name = ''): array {
  $out = ['parent'=>'', 'file'=>'', 'caption'=>''];
  if (!function_exists('get_field_object')) return $out;
  $parent = get_field_object($parent_name, $post_id);
  if (!$parent || empty($parent['key'])) return $out;

  $out['parent'] = $parent['key'];
  foreach ((array)($parent['sub_fields'] ?? []) as $sf) {
    if (!empty($sf['name']) && !empty($sf['key'])) {
      if ($sf['name'] === $file_name)    $out['file'] = $sf['key'];
      if ($caption_name && $sf['name'] === $caption_name) $out['caption'] = $sf['key'];
    }
  }
  return $out;
}

/* ---------- Handle POST (save/delete/resend) ---------- */
if ($post_obj && $_SERVER['REQUEST_METHOD'] === 'POST') {
  add_filter('redirect_canonical', '__return_false', 99);

  if (!isset($_POST['report_form_nonce']) || !wp_verify_nonce($_POST['report_form_nonce'], 'report_form_save')) {
    _report_safe_redirect(add_query_arg(['error'=>'nonce'], remove_query_arg('error')));
  }
  if (!$can_edit) {
    _report_safe_redirect(add_query_arg(['error'=>'cap'], remove_query_arg('error')));
  }
  $posted_id = isset($_POST['report_post_id']) ? absint($_POST['report_post_id']) : 0;
  if ($posted_id !== $report_id) {
    _report_safe_redirect(add_query_arg(['error'=>'mismatch'], remove_query_arg('error')));
  }

  // RESEND EMAIL (no data changes; sends to client's email)
  if (isset($_POST['resend_report_email'])) {
    $client_raw = function_exists('get_field') ? get_field($acf_client, $report_id) : '';
    $site_raw   = function_exists('get_field') ? get_field($acf_site,   $report_id) : '';
    $date_raw   = function_exists('get_field') ? get_field($acf_date,   $report_id) : '';
    $po_v       = function_exists('get_field') ? (string) get_field($acf_po_number,  $report_id) : '';
    $notes_v    = function_exists('get_field') ? (string) get_field($acf_full_notes, $report_id) : '';

    $client_id = _acf_post_link_current_id($client_raw, 'client');
    $site_id   = _acf_post_link_current_id($site_raw,   'site');

    _send_report_email([
      'new_id'        => $report_id,
      'client_id'     => $client_id,
      'site_id'       => $site_id,
      'date_store'    => is_string($date_raw) ? $date_raw : '',
      'po_number'     => $po_v,
      'notes'         => $notes_v,
      'view_url'      => _report_view_pretty_url($post_obj),
      // no notify_to → resolves to client primary_contact email
      'subject_prefix'=> 'Report (Resent)',
    ]);

    _report_safe_redirect(add_query_arg(['emailed'=>1], remove_query_arg('error')));
  }

  // DELETE?
  if (isset($_POST['delete_report'])) {
    wp_trash_post($report_id);
    $list_url = trailingslashit(home_url('/reports/')); // adjust to your listing slug
    _report_safe_redirect(add_query_arg(['deleted'=>1], $list_url));
  }

  // SAVE updates (NO EMAIL HERE)
  $client_id = isset($_POST['report_client_id']) ? absint($_POST['report_client_id']) : 0;
  $site_id   = isset($_POST['report_site_id'])   ? absint($_POST['report_site_id'])   : 0;
  $date_in   = isset($_POST['date'])             ? sanitize_text_field($_POST['date']) : '';
  $po_num    = isset($_POST['po_number'])        ? sanitize_text_field($_POST['po_number']) : '';
  $notes_v   = isset($_POST['full_notes'])       ? wp_kses_post($_POST['full_notes']) : '';

  if (!function_exists('update_field')) {
    _report_safe_redirect(add_query_arg(['error'=>'acf_missing'], remove_query_arg('error')));
  }

  _save_post_link_field($report_id, $acf_client, $client_id, $acf_key_client);
  _save_post_link_field($report_id, $acf_site,   $site_id,   $acf_key_site);

  $date_obj   = function_exists('get_field_object') ? get_field_object($acf_date, $report_id) : null;
  $date_store = _input_date_for_acf($date_in, $date_obj);
  update_field($date_obj['key'] ?? $acf_date, ($date_store ?: null), $report_id);

  update_field($acf_po_number,  ($po_num ?: null), $report_id);
  update_field($acf_full_notes, ($notes_v ?: null), $report_id);

  // NEW uploads appended to gallery
  if (!empty($_FILES['report_images']) && is_array($_FILES['report_images']['name'])) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $current = function_exists('get_field') ? get_field($acf_gallery, $report_id) : [];
    $current_ids = [];
    if (is_array($current)) {
      foreach ($current as $it) {
        if (is_numeric($it)) $current_ids[] = (int)$it;
        elseif (is_array($it) && isset($it['ID'])) $current_ids[] = (int)$it['ID'];
        elseif (is_object($it) && isset($it->ID))  $current_ids[] = (int)$it->ID;
      }
    } elseif (is_numeric($current)) {
      $current_ids[] = (int)$current;
    }

    $f = $_FILES['report_images']; $new_ids = [];
    foreach ($f['name'] as $i => $name) {
      if (empty($name) || ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
      $file_array = [
        'name'     => $name,
        'type'     => $f['type'][$i],
        'tmp_name' => $f['tmp_name'][$i],
        'error'    => $f['error'][$i],
        'size'     => $f['size'][$i],
      ];
      $att_id = media_handle_sideload($file_array, $report_id);
      if (!is_wp_error($att_id)) $new_ids[] = (int)$att_id;
    }
    if ($new_ids) {
      $merged = array_values(array_unique(array_merge($current_ids, $new_ids)));
      update_field($acf_gallery, $merged, $report_id);
    }

    // --- VIDEOS (Repeater) : delete selected rows ---
$keys = _video_field_keys($report_id, $acf_videos, $acf_video_file, $acf_video_caption);
$parent_id   = $keys['parent'] ?: $acf_videos;
$file_key    = $keys['file']   ?: $acf_video_file;
$caption_key = $acf_video_caption && $keys['caption'] ? $keys['caption'] : '';

if (!empty($_POST['remove_video_rows']) && is_array($_POST['remove_video_rows'])) {
  $rows = array_map('absint', $_POST['remove_video_rows']);
  rsort($rows); // delete from highest index first
  foreach ($rows as $idx) {
    // ACF repeater rows are 1-based for delete_row
    delete_row($parent_id, $idx, $report_id);
  }
}

// --- VIDEOS (Repeater) : add one row per newly uploaded video ---
if (!empty($_FILES['report_videos']) && is_array($_FILES['report_videos']['name'])) {
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';

  $F   = $_FILES['report_videos'];
  $CAP = isset($_POST['report_video_captions']) && is_array($_POST['report_video_captions']) ? $_POST['report_video_captions'] : [];

  foreach ($F['name'] as $i => $name) {
    if (empty($name) || ($F['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    $file_array = [
      'name'     => $name,
      'type'     => $F['type'][$i],
      'tmp_name' => $F['tmp_name'][$i],
      'error'    => $F['error'][$i],
      'size'     => $F['size'][$i],
    ];
    $att_id = media_handle_sideload($file_array, $report_id);
    if (!is_wp_error($att_id)) {
      $row = [ $file_key => (int)$att_id ];
      if ($caption_key) {
        $cap = isset($CAP[$i]) ? sanitize_text_field($CAP[$i]) : '';
        if ($cap !== '') $row[$caption_key] = $cap;
      }
      add_row($parent_id, $row, $report_id);
    }
  }
}

  }

  clean_post_cache($report_id);
  $post_obj = get_post($report_id);
  $view_url = _report_view_pretty_url($post_obj);

  _report_safe_redirect(add_query_arg(['updated'=>1], $view_url));
}

/* ---------- Prefill (GET) ---------- */
$client_selected_id = $site_selected_id = 0;
$date_v = $po_v = $notes_v = '';

if ($post_obj && function_exists('get_field')) {
  $client_raw = get_field($acf_client, $report_id);
  $site_raw   = get_field($acf_site,   $report_id);
  $date_raw   = get_field($acf_date,   $report_id);
  $po_v       = (string) get_field($acf_po_number,  $report_id);
  $notes_v    = (string) get_field($acf_full_notes, $report_id);

  $client_selected_id = _acf_post_link_current_id($client_raw, 'client');
  $site_selected_id   = _acf_post_link_current_id($site_raw,   'site');
  $date_v             = _acf_date_to_input($date_raw);
}

/* Options for Site dropdown (Client is auto from Site) */
$sites = [];
$q_sites = new WP_Query([
  'post_type'      => 'site',
  'post_status'    => 'publish',
  'posts_per_page' => -1,
  'orderby'        => 'title',
  'order'          => 'ASC',
  'fields'         => 'ids',
  'no_found_rows'  => true,
]);
foreach ($q_sites->posts as $sid) { $sites[] = [$sid, get_the_title($sid) ?: 'Untitled']; }
wp_reset_postdata();

/** Helpers for client label + site→client mapping */
function _client_contact_label($client_id){
  $contact = '';
  if ($client_id && function_exists('get_field')) {
    $g = (array) get_field('primary_contact', $client_id);
    if (!empty($g['name'])) {
      $contact = trim((string) $g['name']);
    } elseif (!empty($g['first_name']) || !empty($g['last_name'])) {
      $contact = trim(((string)($g['first_name'] ?? '')).' '.((string)($g['last_name'] ?? '')));
    }
  }
  return $contact;
}
function _normalize_client_id_from_site($site_id){
  if (!$site_id || !function_exists('get_field')) return 0;
  $v = get_field('client', $site_id);
  if (is_numeric($v)) return (int)$v;
  if (is_object($v) && isset($v->ID)) return (int)$v->ID;
  if (is_array($v)) {
    if (isset($v['ID']))    return (int)$v['ID'];
    if (isset($v['value'])) return (int)$v['value'];
  }
  if (is_string($v) && $v !== '') {
    $m = get_posts([
      'post_type'   => 'client',
      'post_status' => 'publish',
      'numberposts' => 1,
      's'           => $v,
      'fields'      => 'ids',
    ]);
    if (!empty($m)) return (int)$m[0];
  }
  return 0;
}

/* Build SiteID -> Client info map for auto-fill */
$site_map = []; // [site_id => ['client_id'=>..,'client_title'=>..,'contact'=>..]]
foreach ($sites as [$sid, $stitle]) {
  $cid     = _normalize_client_id_from_site($sid);
  $ctitle  = $cid ? (get_the_title($cid) ?: '') : '';
  $contact = $cid ? _client_contact_label($cid) : '';
  $site_map[(int)$sid] = [
    'client_id'    => (int)$cid,
    'client_title' => $ctitle,
    'contact'      => $contact,
  ];
}

/* Current display labels */
$site_current_title = $site_selected_id ? (get_the_title($site_selected_id) ?: 'Untitled') : '';
$site_display       = $site_current_title ?: '— Select —';

if (!$client_selected_id && $site_selected_id) {
  $client_selected_id = $site_map[$site_selected_id]['client_id'] ?? 0;
}
$client_current_title   = $client_selected_id ? (get_the_title($client_selected_id) ?: '') : '';
$client_current_contact = $client_selected_id ? _client_contact_label($client_selected_id) : '';
$client_display = $client_selected_id
  ? trim(($client_current_contact ? ($client_current_contact.' — ') : '').$client_current_title)
  : '— Auto-filled from Site —';
?>
<style>
  .f{margin:0 0 22px}.lab{display:block;font-weight:600;margin:0 0 8px}
  .req .lab::after{content:" *";color:#e63946}
  .in,.ta,.sel{width:100%;border:1px solid #d7dfe7;border-radius:10px;padding:14px 16px;font-size:16px;background:#fff}
  .ta{min-height:160px;resize:vertical}
  .rule{border:0;border-top:1px solid #d7dfe7;margin:28px 0}
  .h1{font-size:24px;margin:0 0 18px;font-weight:700}
  .g2{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.g2{grid-template-columns:1fr 1fr}}
  .alert{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;margin:16px 0;color:#065f46}
  .alert.-err{border-color:#fecaca;background:#fff1f2;color:#7f1d1d}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 22px;border-radius:10px;border:0;cursor:pointer;font-size:16px}
  .btn:hover{background:#111827}
  .btn-delete{background:transparent;color:#880808;margin-left:0;font-size:12px}.btn-delete:hover{text-decoration:underline;background:transparent}
  .btn-secondary{background:#efefef;color:#000}.btn-secondary:hover{background:#D3B96D}
  .actions{display:flex;gap:12px;flex-wrap:wrap}
  .drop{border:2px dashed #cfd8e3;border-radius:12px;padding:34px 16px;text-align:center;background:#fbfbfd;cursor:pointer}
  .drop .hint{color:#111827;font-weight:700;margin-bottom:6px}
  .drop .sub{color:#6b7280;font-size:14px}

  /* Combobox (site selector) + read-only client chip */
  .combo{position:relative}
  .combo-btn{width:100%;text-align:left;border:1px solid #d7dfe7;border-radius:10px;padding:14px 44px 14px 16px;font-size:16px;background:#fff;cursor:pointer}
  .combo-btn:focus{outline:2px solid #0f3a47;outline-offset:2px}
  .combo-btn .caret{position:absolute;right:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;pointer-events:none}
  .combo-panel{position:absolute;z-index:50;left:0;right:0;margin-top:6px;background:#fff;border:1px solid #d7dfe7;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.08);overflow:hidden}
  .combo-search{width:100%;border:0;border-bottom:1px solid #eef2f7;padding:12px 14px;font-size:15px}
  .combo-list{max-height:260px;overflow:auto;list-style:none;margin:0;padding:6px}
  .combo-option{padding:10px 12px;border-radius:8px;cursor:pointer;font-size:15px}
  .combo-option:hover,.combo-option.is-active{background:#f5f7fb}
  .combo-empty{padding:12px;color:#6b7280;font-size:14px}
  .opt-main{font-weight:600;color:#111827}
  .is-readonly{background:#f9fafb;cursor:default}
  .hint{display:block;margin-top:6px;color:#6b7280;font-size:13px}

  .vr{border:1px solid #d7dfe7;border-radius:12px;padding:12px}
  .vr-row{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:center;margin-bottom:10px}
  .vr-row input[type="file"]{border:1px solid #d7dfe7;border-radius:10px;padding:10px;background:#fff}
  .vr-row .cap{border:1px solid #d7dfe7;border-radius:10px;padding:10px}
  .vr-add{margin-top:10px}
  .vr-btn{display:inline-block;background:#efefef;color:#111827;font-weight:600;padding:10px 14px;border-radius:10px;border:0;cursor:pointer}
  .vr-btn:hover{background:#e5e7eb}
  .vr-remove{background:transparent;color:#880808}
  .vr-remove:hover{text-decoration:underline}
  .vr-list{list-style:none;margin:0;padding:0}
  .vr-item{display:flex;justify-content:space-between;gap:10px;align-items:center;border:1px solid #eef2f7;border-radius:10px;padding:10px;margin-bottom:8px}
  .vr-item small{color:#6b7280}
  .vr-item .title{font-weight:600}
</style>

<?php if (!$post_obj): ?>
  <div class="alert -err">Missing or invalid <code>reportID</code>.</div>
<?php else: ?>
  <?php if ($error === 'nonce'): ?><div class="alert -err">Security check failed.</div><?php endif; ?>
  <?php if ($error === 'cap'): ?><div class="alert -err">You don’t have permission to edit this report.</div><?php endif; ?>
  <?php if ($error === 'mismatch'): ?><div class="alert -err">Form ID mismatch.</div><?php endif; ?>
  <?php if ($error === 'acf_missing'): ?><div class="alert -err">Advanced Custom Fields is required.</div><?php endif; ?>
  <?php if ($emailed): ?><div class="alert">Notification email sent.</div><?php endif; ?>

  <form action="<?php echo esc_url(add_query_arg([])); ?>" method="post" enctype="multipart/form-data" onsubmit="return confirmDeleteReport(event)" novalidate>
    <?php wp_nonce_field('report_form_save','report_form_nonce'); ?>
    <input type="hidden" name="report_post_id" value="<?php echo esc_attr($report_id); ?>" />

    <?php
      $site_selected_id   = (int) $site_selected_id;
      $client_selected_id = (int) $client_selected_id;

      $site_current_title = $site_selected_id ? (get_the_title($site_selected_id) ?: 'Untitled') : '';
      $site_display       = $site_current_title ?: '— Select —';

      if (!$client_selected_id && $site_selected_id) {
        $client_selected_id = $site_map[$site_selected_id]['client_id'] ?? 0;
      }
      $client_current_title   = $client_selected_id ? (get_the_title($client_selected_id) ?: '') : '';
      $client_current_contact = $client_selected_id ? _client_contact_label($client_selected_id) : '';
      $client_display = $client_selected_id
        ? trim(($client_current_contact ? ($client_current_contact.' — ') : '').$client_current_title)
        : '— Auto-filled from Site —';
    ?>

    <div class="g2">
      <!-- SITE: searchable dropdown -->
      <div class="f">
        <label class="lab" id="report_site_combo-label" for="report_site_combo">Site</label>
        <input type="hidden" id="report_site_id" name="report_site_id" value="<?php echo esc_attr($site_selected_id ?: 0); ?>">

        <div class="combo">
          <button type="button" class="combo-btn" id="report_site_combo"
                  aria-haspopup="listbox" aria-expanded="false"
                  aria-labelledby="report_site_combo-label report_site_combo">
            <span class="combo-current"><?php echo esc_html($site_display); ?></span>
            <svg class="caret" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>

          <div class="combo-panel" id="report_site_panel" hidden>
            <input id="report_site_search" class="combo-search" type="text" placeholder="Search sites…" autocomplete="off">
            <ul id="report_site_list" class="combo-list" role="listbox" aria-labelledby="report_site_combo-label">
              <?php foreach ($sites as [$id,$title]): $title = $title ?: 'Untitled'; ?>
                <li class="combo-option" role="option" tabindex="-1"
                    data-id="<?php echo esc_attr($id); ?>"
                    data-search1="<?php echo esc_attr($title); ?>">
                  <div class="opt-main"><?php echo esc_html($title); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- CLIENT: read-only, auto-filled from selected site -->
      <div class="f">
        <label class="lab" for="report_client_display">Property Manager</label>
        <input type="hidden" id="report_client_id" name="report_client_id" value="<?php echo esc_attr($client_selected_id ?: 0); ?>">
        <div class="combo">
          <div class="combo-btn is-readonly" id="report_client_display" aria-disabled="true">
            <span class="combo-current"><?php echo esc_html($client_display); ?></span>
          </div>
          <small class="hint">Auto-filled from the selected site.</small>
        </div>
      </div>
    </div>

    <div class="g2">
      <div class="f">
        <label class="lab" for="date">Date</label>
        <input class="in" id="date" name="date" type="date" value="<?php echo esc_attr($date_v); ?>" />
      </div>
      <div class="f">
        <label class="lab" for="po_number">PO Number</label>
        <input class="in" id="po_number" name="po_number" type="text" value="<?php echo esc_attr($po_v); ?>" />
      </div>
    </div>

    <div class="f">
      <label class="lab">Add Images</label>
      <div class="drop" onclick="document.getElementById('report_images').click()">
        <div class="hint">Drop files here or click to upload</div>
        <div class="sub">Maximum file size: <?php echo size_format(wp_max_upload_size()); ?></div>
      </div>
      <input id="report_images" name="report_images[]" type="file" accept="image/*" multiple style="display:none">
      <p style="color:#6b7280;margin-top:8px">New images will be <strong>added</strong> to the existing gallery.</p>
    </div>

    <div class="f">
  <label class="lab">Videos</label>

  <!-- Existing videos list (EDIT page only) -->
  <?php if (isset($report_id) && $report_id && function_exists('get_field')): ?>
    <?php $existing_videos = (array) get_field($acf_videos, $report_id); ?>
    <?php if (!empty($existing_videos)): ?>
      <ul class="vr-list" aria-label="Existing videos">
        <?php foreach ($existing_videos as $i => $row): 
          // Normalise attachment ID from return format
          $att_id = 0; $cap_txt = '';
          if (!empty($row[$acf_video_file])) {
            $v = $row[$acf_video_file];
            if (is_numeric($v)) $att_id = (int) $v;
            elseif (is_array($v) && isset($v['ID'])) $att_id = (int) $v['ID'];
            elseif (is_object($v) && isset($v->ID)) $att_id = (int) $v->ID;
          }
          if ($acf_video_caption && !empty($row[$acf_video_caption])) {
            $cap = $row[$acf_video_caption];
            $cap_txt = is_array($cap) ? (string)($cap['text'] ?? '') : (string)$cap;
          }
          $file_url = $att_id ? wp_get_attachment_url($att_id) : '';
          $file_name = $att_id ? get_the_title($att_id) : 'Video';
          $row_index = $i + 1; // ACF repeater rows are 1-based when deleting
        ?>
          <li class="vr-item">
            <div>
              <div class="title"><?php echo esc_html($file_name); ?></div>
              <?php if ($file_url): ?>
                <small><a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener">Open</a></small>
              <?php endif; ?>
              <?php if ($cap_txt): ?>
                <div><small><?php echo esc_html($cap_txt); ?></small></div>
              <?php endif; ?>
            </div>
            <label style="white-space:nowrap;">
              <input type="checkbox" name="remove_video_rows[]" value="<?php echo esc_attr($row_index); ?>">
              Remove
            </label>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Add new videos -->
  <div id="video-repeater" class="vr" aria-label="Add videos">
    <div class="vr-row">
      <input type="file" name="report_videos[]" accept="video/*" />
      <input type="text" name="report_video_captions[]" class="cap" placeholder="Optional caption" />
      <button type="button" class="vr-btn vr-remove" onclick="this.closest('.vr-row').remove()">Remove</button>
    </div>
  </div>
  <div class="vr-add">
    <button type="button" class="vr-btn" id="add-video-row">+ Add another video</button>
  </div>
</div>


    <div class="f">
      <label class="lab" for="full_notes">Description of Works</label>
      <textarea class="ta" id="full_notes" name="full_notes"><?php echo esc_textarea($notes_v); ?></textarea>
    </div>

    <div class="f actions">
      <button type="submit" name="save_report" class="btn">Save Report</button>
      <button type="button" class="btn btn-secondary" id="report-cancel-btn">Cancel</button>
      <button type="submit" name="resend_report_email" class="btn btn-secondary">Resend Email</button>
      <button type="submit" name="delete_report" class="btn btn-delete">Delete Report</button>
    </div>
  </form>
<?php endif; ?>

<script>
function confirmDeleteReport(e){
  if(e.submitter && e.submitter.name === 'delete_report'){
    return confirm('Are you sure you want to delete this report? This action cannot be undone.');
  }
  return true;
}
(function(){
  // PHP → JS: SiteID -> { client_id, client_title, contact }
  window.SiteToClientMap = <?php echo wp_json_encode($site_map, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;

  function updateClientFromSite(siteId){
    var info = (window.SiteToClientMap || {})[siteId] || null;
    var hidClient = document.getElementById('report_client_id');
    var btn = document.getElementById('report_client_display');
    var cur = btn ? btn.querySelector('.combo-current') : null;

    var label = '— Auto-filled from Site —';
    var val   = '0';
    if (info && info.client_id) {
      val = String(info.client_id);
      var parts = [];
      if (info.contact) parts.push(info.contact);
      if (info.client_title) parts.push(info.client_title);
      label = parts.length ? (parts.length === 2 ? (parts[0] + ' — ' + parts[1]) : parts[0]) : label;
    }
    if (hidClient) hidClient.value = val;
    if (cur) cur.textContent = label;
  }

  // Site combobox
  (function initSiteCombo(){
    var btn     = document.getElementById('report_site_combo');
    var panel   = document.getElementById('report_site_panel');
    var search  = document.getElementById('report_site_search');
    var list    = document.getElementById('report_site_list');
    var hidSite = document.getElementById('report_site_id');
    var curSite = btn ? btn.querySelector('.combo-current') : null;

    if (!btn || !panel || !search || !list || !hidSite || !curSite) return;

    var options = Array.prototype.slice.call(list.querySelectorAll('.combo-option'));
    var activeIndex = -1;

    function openPanel(){
      panel.hidden = false;
      btn.setAttribute('aria-expanded','true');
      search.value = '';
      filter('');
      setTimeout(function(){ search.focus(); }, 0);
    }
    function closePanel(){
      panel.hidden = true;
      btn.setAttribute('aria-expanded','false');
      activeIndex = -1;
      clearActive();
    }
    function togglePanel(){ panel.hidden ? openPanel() : closePanel(); }
    function clearActive(){ options.forEach(function(o){ o.classList.remove('is-active'); o.removeAttribute('aria-selected'); }); }
    function visibleOptions(){ return options.filter(function(o){ return o.style.display !== 'none'; }); }
    function setActiveByIndex(i){
      var vis = visibleOptions();
      clearActive();
      if (!vis.length) { activeIndex = -1; return; }
      if (i < 0) i = 0;
      if (i >= vis.length) i = vis.length - 1;
      vis[i].classList.add('is-active');
      vis[i].setAttribute('aria-selected','true');
      vis[i].scrollIntoView({block:'nearest'});
      activeIndex = i;
    }
    function selectOption(opt){
      if (!opt) return;
      var id   = opt.getAttribute('data-id') || '0';
      var main = (opt.querySelector('.opt-main') || {}).textContent || '';
      hidSite.value       = id;
      curSite.textContent = main.trim();
      updateClientFromSite(parseInt(id,10) || 0);
      closePanel();
      btn.focus();
    }
    function filter(q){
      var qq = (q || '').trim().toLowerCase();
      var any = false;
      list.querySelectorAll('.combo-empty').forEach(function(n){ n.remove(); });
      options.forEach(function(o){
        var s1 = (o.getAttribute('data-search1') || '').toLowerCase();
        var match = s1.indexOf(qq) !== -1;
        o.style.display = match ? '' : 'none';
        if (match) any = true;
      });
      if (!any) {
        var li = document.createElement('li');
        li.className = 'combo-empty';
        li.textContent = 'No matches';
        list.appendChild(li);
      }
      activeIndex = -1;
      clearActive();
    }

    btn.addEventListener('click', togglePanel);
    document.addEventListener('click', function(e){
      if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) closePanel();
    });
    search.addEventListener('input', function(){ filter(this.value); });
    search.addEventListener('keydown', function(e){
      if (e.key === 'ArrowDown'){ e.preventDefault(); setActiveByIndex(activeIndex + 1); }
      else if (e.key === 'ArrowUp'){ e.preventDefault(); setActiveByIndex(activeIndex - 1); }
      else if (e.key === 'Enter'){ e.preventDefault(); if (activeIndex >= 0) selectOption(visibleOptions()[activeIndex]); }
      else if (e.key === 'Escape'){ closePanel(); btn.focus(); }
    });
    list.addEventListener('click', function(e){
      var li = e.target.closest('.combo-option');
      if (li) selectOption(li);
    });

    // On load, sync client from preselected site
    var initSite = parseInt(hidSite.value || '0', 10) || 0;
    if (initSite) updateClientFromSite(initSite);

    // Enforce required IDs on submit
    var form = btn.closest('form');
    if (form) {
      form.addEventListener('submit', function(e){
        var siteOk = (parseInt(hidSite.value || '0', 10) || 0) > 0;
        var cliOk  = (parseInt((document.getElementById('report_client_id')||{}).value || '0', 10) || 0) > 0;
        if (!siteOk || !cliOk) {
          e.preventDefault();
          openPanel();
          search.setCustomValidity('Please choose a site');
          search.reportValidity();
          setTimeout(function(){ search.setCustomValidity(''); }, 1500);
        }
      });
    }
  })();
})();
</script>

<script>
(function(){
  var form  = document.querySelector('form');
  var dirty = false;

  // Mark form as "dirty" on any change or input
  if (form){
    form.addEventListener('input',  function(){ dirty = true; }, true);
    form.addEventListener('change', function(){ dirty = true; }, true);
  }

  var cancelBtn = document.getElementById('report-cancel-btn');
  if (!cancelBtn) return;

  cancelBtn.addEventListener('click', function(){
    var proceed = true;
    if (dirty){
      proceed = confirm('Discard changes and leave this page? Unsaved changes will be lost.');
    }
    if (proceed){
      // Destination: single report view if available, else the listing page
      var dest = <?php
        $dest = isset($view_url) && $view_url ? $view_url : trailingslashit(home_url('/reports/'));
        echo json_encode($dest);
      ?>;
      window.location.href = dest;
    }
  });
})();
</script>

<script>
(function(){
  var cont = document.getElementById('video-repeater');
  var add  = document.getElementById('add-video-row');
  if (!cont || !add) return;

  add.addEventListener('click', function(){
    var row = document.createElement('div');
    row.className = 'vr-row';
    row.innerHTML =
      '<input type="file" name="report_videos[]" accept="video/*" />' +
      '<input type="text" name="report_video_captions[]" class="cap" placeholder="Optional caption" />' +
      '<button type="button" class="vr-btn vr-remove">Remove</button>';
    row.querySelector('.vr-remove').addEventListener('click', function(){ row.remove(); });
    cont.appendChild(row);
  });
})();
</script>
