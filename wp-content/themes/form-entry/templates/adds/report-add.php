<?php
/**
 * Template Name: Add Report (ACF) – With Email (PM Recipient)
 */
defined('ABSPATH') || exit;

/* ACF field NAMES on Report */
$acf_client       = 'client';
$acf_site         = 'site';
$acf_date         = 'date';
$acf_po_number    = 'po_number';
$acf_gallery      = 'image_gallery';
$acf_full_notes   = 'full_notes';

/* >>> OPTIONAL (recommended): ACF FIELD KEYS <<< */
$acf_key_client   = ''; // e.g. 'field_abc123client'
$acf_key_site     = ''; // e.g. 'field_def456site'

/* ACF Repeater (videos) */
$acf_videos         = 'videos';        // repeater field name
$acf_video_file     = 'video_file';    // file subfield name
$acf_video_caption  = 'video_caption'; // optional; '' if you don’t have one

/* ---------- Helpers ---------- */
function _report_safe_redirect(string $url, int $status = 303) {
  $safe = wp_validate_redirect($url, home_url('/'));
  while (ob_get_level() > 0) { ob_end_clean(); }
  nocache_headers();
  wp_safe_redirect($safe, $status);
  echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . esc_attr($safe) . '"><script>location.replace(' . json_encode($safe) . ');</script>';
  exit;
}
function _report_destination_after_save(int $post_id, string $this_page_url): string {
  $status = get_post_status($post_id);
  $dest   = ($status === 'publish') ? get_permalink($post_id) : get_preview_post_link($post_id);
  if (!$dest) $dest = home_url('/');
  // avoid loop back to this same page
  $dest_trim = strtok((string)$dest, '?');
  $this_trim = strtok((string)$this_page_url, '?');
  if ($dest_trim && $this_trim && untrailingslashit($dest_trim) === untrailingslashit($this_trim)) {
    $dest = home_url('/');
  }
  return $dest;
}
/** Save a post link into an ACF field using the correct shape. Prefer field KEY if provided. */
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
      '[Add Report] update_field %s => %s (type:%s multiple:%s fmt:%s) value:%s result:%s',
      $field_name, $update_id, $type, $multiple ? 'true' : 'false', $return_fmt, wp_json_encode($value_to_save), $ok ? 'OK' : 'FAIL'
    ));
  }

  if (!$ok && $key) {
    update_post_meta($post_id, '_' . $field_name, $key);
    update_post_meta($post_id, $field_name, $value_to_save);
  }
}

/**
 * Resolve the Property Manager (primary_contact) email.
 * Site → Client (ACF 'client') → group 'primary_contact' → 'email_address' (or 'email')
 * Falls back to admin_email if none is valid.
 */
function _resolve_property_manager_email(int $site_id, int $client_id = 0): string {
  if (!function_exists('get_field')) {
    return get_option('admin_email');
  }

  if ($site_id && !$client_id) {
    $cv  = get_field('client', $site_id);
    $cid = 0;
    if (is_numeric($cv)) $cid = (int)$cv;
    elseif (is_object($cv) && isset($cv->ID)) $cid = (int)$cv->ID;
    elseif (is_array($cv) && isset($cv['ID'])) $cid = (int)$cv['ID'];
    elseif (is_array($cv) && isset($cv['value']) && is_numeric($cv['value'])) $cid = (int)$cv['value'];
    $client_id = $cid ?: $client_id;
  }

  if ($client_id) {
    $pc = (array) get_field('primary_contact', $client_id);
    $email = isset($pc['email_address']) ? $pc['email_address'] : ($pc['email'] ?? '');
    $email = sanitize_email($email);
    if ($email && is_email($email)) return $email;
  }

  // Optional: if primary_contact stored on Site itself
  if ($site_id) {
    $pc = (array) get_field('primary_contact', $site_id);
    $email = isset($pc['email_address']) ? $pc['email_address'] : ($pc['email'] ?? '');
    $email = sanitize_email($email);
    if ($email && is_email($email)) return $email;
  }

  return get_option('admin_email');
}

/* ---- Permissions ---- */
$ptype   = get_post_type_object('report');
$can_add = $ptype && !empty($ptype->cap->create_posts)
  ? current_user_can($ptype->cap->create_posts)
  : current_user_can('publish_posts');

$errs = [];

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

/* ========== Handle POST (create) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  add_filter('redirect_canonical', '__return_false', 99);

  if (!isset($_POST['report_form_nonce']) || !wp_verify_nonce($_POST['report_form_nonce'], 'report_form_save')) {
    $errs[] = 'Security check failed.';
  } elseif (!$can_add) {
    $errs[] = 'You do not have permission to add reports.';
  } else {
    // Was the "Save as Draft" button clicked?
    $save_as_draft = isset($_POST['save_as_draft']);

    // SAFE POST names
    $client_id  = isset($_POST['report_client_id']) ? absint($_POST['report_client_id']) : 0;
    $site_id    = isset($_POST['report_site_id'])   ? absint($_POST['report_site_id'])   : 0;
    $date_v     = isset($_POST['date'])             ? sanitize_text_field($_POST['date']) : '';
    $po_number  = isset($_POST['po_number'])        ? sanitize_text_field($_POST['po_number']) : '';
    $notes_v    = isset($_POST['full_notes'])       ? wp_kses_post($_POST['full_notes']) : '';

    // Server-side validation: only enforce when NOT saving draft
    if (!$save_as_draft) {
      if ($client_id === 0) $errs[] = 'Please select a client.';
      if ($site_id === 0)   $errs[] = 'Please select a site.';
    }

    // Build title
    $title_bits = [];
    if ($date_v)    $title_bits[] = $date_v;
    if ($po_number) $title_bits[] = $po_number;
    if ($site_id)   $title_bits[] = get_the_title($site_id);
    $post_title = $title_bits ? implode(' – ', array_filter($title_bits)) : 'New Report';

    if (empty($errs)) {
      // Decide final status
      $cap_publish   = $ptype && !empty($ptype->cap->publish_posts) ? $ptype->cap->publish_posts : 'publish_posts';
      $target_status = $save_as_draft ? 'draft' : ( current_user_can($cap_publish) ? 'publish' : 'draft' );

      $new_id = wp_insert_post([
        'post_type'   => 'report',
        'post_status' => $target_status,
        'post_title'  => $post_title,
        'post_author' => get_current_user_id(),
      ], true);

      if (is_wp_error($new_id) || !$new_id) {
        $errs[] = 'Insert failed: ' . (is_wp_error($new_id) ? $new_id->get_error_message() : 'unknown');
      } else {
        if (!function_exists('update_field')) {
          $errs[] = 'Advanced Custom Fields is required.';
        } else {
          // Linked fields (client, site)
          _save_post_link_field($new_id, $acf_client, $client_id, $acf_key_client);
          _save_post_link_field($new_id, $acf_site,   $site_id,   $acf_key_site);

          // Date: respect field return_format
          $date_to_store = $date_v;
          if ($date_v && function_exists('get_field_object')) {
            $date_obj = get_field_object($acf_date, $new_id);
            if ($date_obj && ($date_obj['return_format'] ?? 'Y-m-d') === 'Ymd') {
              $date_to_store = preg_replace('/-/', '', $date_v); // 2025-08-21 → 20250821
            }
          }
          update_field($acf_date, $date_to_store ?: null, $new_id);
          update_field($acf_po_number,  ($po_number ?: null), $new_id);
          update_field($acf_full_notes, ($notes_v ?: null),   $new_id);

          // Gallery uploads
          $gallery_ids = [];
          if (!empty($_FILES['report_images']) && is_array($_FILES['report_images']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $f = $_FILES['report_images'];
            foreach ($f['name'] as $i => $name) {
              if (empty($name) || ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
              $file_array = [
                'name'     => $name,
                'type'     => $f['type'][$i],
                'tmp_name' => $f['tmp_name'][$i],
                'error'    => $f['error'][$i],
                'size'     => $f['size'][$i],
              ];
              $att_id = media_handle_sideload($file_array, $new_id);
              if (!is_wp_error($att_id)) $gallery_ids[] = (int)$att_id;
            }
          }
          if ($gallery_ids) update_field($acf_gallery, $gallery_ids, $new_id);

          // --- VIDEOS (Repeater) : add one row per uploaded video ---
          if (!empty($_FILES['report_videos']) && is_array($_FILES['report_videos']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $keys = _video_field_keys($new_id, $acf_videos, $acf_video_file, $acf_video_caption);
            $parent_id   = $keys['parent'] ?: $acf_videos; // fallback to name if key missing
            $file_key    = $keys['file']   ?: $acf_video_file;
            $caption_key = $acf_video_caption && $keys['caption'] ? $keys['caption'] : '';

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
              $att_id = media_handle_sideload($file_array, $new_id);
              if (!is_wp_error($att_id)) {
                $row = [ $file_key => (int)$att_id ];
                if ($caption_key) {
                  $cap = isset($CAP[$i]) ? sanitize_text_field($CAP[$i]) : '';
                  if ($cap !== '') $row[$caption_key] = $cap;
                }
                add_row($parent_id, $row, $new_id);
              }
            }
          }

          // Build destination (permalink/preview)
          $dest = _report_destination_after_save($new_id, get_permalink());

          // Send email ONLY if final status is publish
          if (get_post_status($new_id) === 'publish') {
            $pm_email = _resolve_property_manager_email((int)$site_id, (int)$client_id);

            // Subject + template args
            $subject = 'New Report Available';
            $view_url = get_permalink($new_id);
            // If you already have a PDF generator endpoint, wire it here:
            $pdf_url = wp_nonce_url(
              admin_url('admin-post.php?action=report_pdf&report_id=' . $new_id),
              'report_pdf_' . $new_id
            );

            // Use your global sender; fall back safely if not present.
            if (function_exists('pmc_send_report_email_template')) {
              pmc_send_report_email_template($pm_email, $subject, [
                'site_title'  => get_the_title($site_id),
                'view_url'    => $view_url,
                'pdf_url'     => $pdf_url,
                'contact_url' => 'https://pmc-contractinguk.co.uk/contact',
              ]);
            } elseif (function_exists('pmc_report_email_template_html')) {
              $headers = ['Content-Type: text/html; charset=UTF-8'];
              $blogname  = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
              $from_email = get_option('admin_email');
              if ($from_email) $headers[] = 'From: '.$blogname.' <'.$from_email.'>';
              $html = pmc_report_email_template_html([
                'site_title'  => get_the_title($site_id),
                'view_url'    => $view_url,
                'pdf_url'     => $pdf_url,
                'contact_url' => 'https://pmc-contractinguk.co.uk/contact',
              ]);
              wp_mail($pm_email, $subject, $html, $headers);
            } else {
              // Ultra-safe fallback
              wp_mail($pm_email, $subject, "A new report is available:\n\n$view_url");
            }
          }

          // Redirect (permalink or preview), never back to this page
          _report_safe_redirect(add_query_arg('updated', '1', $dest));
        }
      }
    }
  }
}

/* ========== Options for selects (for combobox rendering) ========== */
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
?>
<style>
  .f{margin:0 0 22px}.lab{display:block;font-weight:600;margin:0 0 8px}
  .in,.ta,.sel{width:100%;border:1px solid #d7dfe7;border-radius:10px;padding:14px 16px;font-size:16px;background:#fff}
  .ta{min-height:160px;resize:vertical}
  .g2{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.g2{grid-template-columns:1fr 1fr}}
  .btn{display:inline-block;background:#0f3a47;color:#fff;font-weight:700;padding:12px 22px;border-radius:10px;border:0;cursor:pointer;font-size:16px}
  .btn:hover{background:#0b2e38}
  .btn-secondary{background:#efefef;color:#111827}
  .btn-secondary:hover{background:#e5e7eb}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .drop{border:2px dashed #cfd8e3;border-radius:12px;padding:34px 16px;text-align:center;background:#fbfbfd;cursor:pointer}
  .drop .hint{color:#111827;font-weight:700;margin-bottom:6px}
  .drop .sub{color:#6b7280;font-size:14px}

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

  /* Combobox */
  .combo{position:relative}
  .combo-btn{width:100%; text-align:left; border:1px solid #d7dfe7; border-radius:10px; padding:14px 44px 14px 16px; font-size:16px; background:#fff; cursor:pointer}
  .combo-btn:focus{outline:2px solid #0f3a47; outline-offset:2px}
  .combo-btn .caret{position:absolute; right:12px; top:50%; transform:translateY(-50%); width:18px; height:18px; pointer-events:none}
  .combo-panel{position:absolute; z-index:50; left:0; right:0; margin-top:6px; background:#fff; border:1px solid #d7dfe7; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.08); overflow:hidden}
  .combo-search{width:100%; border:0; border-bottom:1px solid #eef2f7; padding:12px 14px; font-size:15px}
  .combo-list{max-height:260px; overflow:auto; list-style:none; margin:0; padding:6px}
  .combo-option{padding:10px 12px; border-radius:8px; cursor:pointer; font-size:15px}
  .combo-option:hover, .combo-option.is-active{background:#f5f7fb}
  .combo-empty{padding:12px; color:#6b7280; font-size:14px}
  .opt-main{font-weight:600; color:#111827}
  .is-readonly{background:#f9fafb; cursor:default}
  .hint{display:block; margin-top:6px; color:#6b7280; font-size:13px}
</style>

<?php if (!empty($errs)): ?>
  <div class="alert">
    <strong>There was a problem:</strong>
    <ul style="margin:8px 0 0 18px"><?php foreach ($errs as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form action="<?php echo esc_url(add_query_arg([])); ?>" method="post" enctype="multipart/form-data" novalidate>
  <?php wp_nonce_field('report_form_save','report_form_nonce'); ?>

  <?php
  // Build Site list (and Site->Client map) used by the combobox
  $site_selected_id   = 0;
  $client_selected_id = 0;

  if (!isset($sites) || !is_array($sites) || !$sites) {
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
    foreach ($q_sites->posts as $sid) {
      $sites[] = [$sid, get_the_title($sid) ?: 'Untitled'];
    }
    wp_reset_postdata();
  }

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

  // SiteID -> Client info map for instant client auto-fill
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
    <!-- SITE: searchable dropdown (site title ONLY) -->
    <div class="f">
      <label class="lab" id="report_site_combo-label" for="report_site_combo">Site</label>

      <!-- Real POST field -->
      <input type="hidden" id="report_site_id" name="report_site_id" value="<?php echo esc_attr($site_selected_id ?: 0); ?>">

      <!-- Trigger -->
      <button type="button" class="combo-btn" id="report_site_combo"
              aria-haspopup="listbox" aria-expanded="false"
              aria-labelledby="report_site_combo-label report_site_combo">
        <span class="combo-current"><?php echo esc_html($site_display); ?></span>
        <svg class="caret" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>

      <!-- Panel -->
      <div class="combo-panel" id="report_site_panel" hidden>
        <input id="report_site_search" class="combo-search" type="text" placeholder="Search sites…" autocomplete="off">
        <ul id="report_site_list" class="combo-list" role="listbox" aria-labelledby="report_site_combo-label">
          <?php foreach ($sites as [$id,$title]): ?>
            <?php $title = $title ?: 'Untitled'; ?>
            <li class="combo-option" role="option" tabindex="-1"
                data-id="<?php echo esc_attr($id); ?>"
                data-search1="<?php echo esc_attr($title); ?>">
              <div class="opt-main"><?php echo esc_html($title); ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- CLIENT: read-only, auto-filled from selected site -->
    <div class="f">
      <label class="lab" for="report_client_display">Property Manager</label>

      <!-- Real POST field -->
      <input type="hidden" id="report_client_id" name="report_client_id" value="<?php echo esc_attr($client_selected_id ?: 0); ?>">

      <!-- Read-only chip -->
      <div class="combo">
        <div class="combo-btn is-readonly" id="report_client_display" aria-disabled="true">
          <span class="combo-current"><?php echo esc_html($client_display); ?></span>
        </div>
        <small class="hint">Auto-filled from the selected site.</small>
      </div>
    </div>
  </div>

  <script>
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

      // On load, sync client from any preselected site (should be 0 on add)
      var initSite = parseInt(hidSite.value || '0', 10) || 0;
      if (initSite) updateClientFromSite(initSite);
    })();
  })();
  </script>

  <div class="g2">
    <div class="f">
      <label class="lab" for="date">Date</label>
      <input class="in" id="date" name="date" type="date" value="">
    </div>
    <div class="f">
      <label class="lab" for="po_number">PO Number</label>
      <input class="in" id="po_number" name="po_number" type="text" value="">
    </div>
  </div>

  <div class="f">
    <label class="lab">File Upload</label>
    <div class="drop" onclick="document.getElementById('report_images').click()">
      <div class="hint">Drop a file here or click to upload</div>
      <div class="sub">Maximum file size: <?php echo size_format(wp_max_upload_size()); ?></div>
    </div>
    <input id="report_images" name="report_images[]" type="file" accept="image/*" multiple style="display:none">
  </div>

  <div class="f">
    <label class="lab">Videos</label>

    <!-- Existing videos list (shown on edit pages; on add page $report_id isn’t set) -->
    <?php if (isset($report_id) && $report_id && function_exists('get_field')): ?>
      <?php $existing_videos = (array) get_field($acf_videos, $report_id); ?>
      <?php if (!empty($existing_videos)): ?>
        <ul class="vr-list" aria-label="Existing videos">
          <?php foreach ($existing_videos as $i => $row):
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
    <textarea class="ta" id="full_notes" name="full_notes"></textarea>
  </div>

  <div class="f">
    <button type="submit" class="btn">Submit</button>
    <button type="submit" name="save_as_draft" value="1" class="btn btn-secondary" formnovalidate>Save as Draft</button>
  </div>
</form>

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
