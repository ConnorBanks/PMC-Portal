<?php
/**
 * Single template for post type: report
 * - Shows ACF fields:
 *   client (Post Object / Relationship / legacy Select) → label/title
 *   site   (Post Object / Relationship / legacy Select) → label/title
 *   date   (Date Picker, Y-m-d or Ymd)
 *   po_number (Text)
 *   image_gallery (Gallery)
 *   full_notes (Textarea / WYSIWYG)
 *   videos (Repeater: video_file [+ video_caption])
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
  echo get_template_part('templates/blocks/page-header');
}

$report_id = get_the_ID();
$post_obj  = $report_id ? get_post($report_id) : null;

/** ---------- Helpers ---------- */
/** Turn any ACF post-link value into a human label. */
function _acf_post_link_label($val) {
  if (is_array($val)) {
    if (isset($val['label'])) return (string) $val['label'];
    if (isset($val['value'])) $val = $val['value'];
  }
  if (is_array($val)) {
    $labels = [];
    foreach ($val as $v) { $labels[] = _acf_post_link_label($v); }
    return implode(', ', array_filter($labels));
  }
  if (is_object($val) && isset($val->ID)) return get_the_title((int) $val->ID);
  if (is_numeric($val)) return get_the_title((int) $val);
  if (is_string($val))  return ctype_digit($val) ? get_the_title((int) $val) : $val;
  return '';
}

/** Format ACF date (supports Y-m-d and Ymd) → e.g., 21 Aug 2025 */
function _fmt_acf_date($raw) {
  if (!$raw) return '';
  if (preg_match('/^\d{8}$/', $raw)) { $y=substr($raw,0,4); $m=substr($raw,4,2); $d=substr($raw,6,2); $ts=strtotime("$y-$m-$d"); }
  else { $ts = strtotime($raw); }
  return $ts ? date_i18n('j M Y', $ts) : $raw;
}

/** Get gallery IDs from ACF gallery (handles ID/array return formats) */
function _acf_gallery_ids($val) {
  if (empty($val)) return [];
  $ids = [];
  if (is_array($val)) {
    foreach ($val as $item) {
      if (is_numeric($item)) { $ids[] = (int)$item; }
      elseif (is_array($item) && isset($item['ID'])) { $ids[] = (int)$item['ID']; }
      elseif (is_object($item) && isset($item->ID)) { $ids[] = (int)$item->ID; }
    }
  } elseif (is_numeric($val)) { $ids[] = (int)$val; }
  return array_values(array_unique(array_filter($ids)));
}

/* ---------- Helpers (safe to include once) ---------- */
if (!function_exists('_acf_post_link_current_id')) {
  function _acf_post_link_current_id($val, string $fallback_post_type = ''): int {
    if (is_object($val) && isset($val->ID)) return (int)$val->ID;
    if (is_array($val) && isset($val['ID'])) return (int)$val['ID'];
    if (is_array($val) && isset($val['value'])) {
      $v = $val['value'];
      if (is_numeric($v)) return (int)$v;
      if (is_string($v) && $fallback_post_type) {
        $p = get_page_by_title($v, OBJECT, $fallback_post_type);
        if ($p) return (int)$p->ID;
      }
    }
    if (is_array($val)) {
      foreach ($val as $v) {
        $id = _acf_post_link_current_id($v, $fallback_post_type);
        if ($id) return $id;
      }
    }
    if (is_numeric($val)) return (int)$val;
    if (is_string($val) && $fallback_post_type) {
      $p = get_page_by_title($val, OBJECT, $fallback_post_type);
      if ($p) return (int)$p->ID;
    }
    return 0;
  }
}

/** Site → Client primary contact name */
if (!function_exists('_primary_contact_name_from_site')) {
  function _primary_contact_name_from_site($site_val): string {
    if (!function_exists('get_field')) return '';
    $site_id = _acf_post_link_current_id($site_val, 'site');
    if (!$site_id) return '';
    $client_val = get_field('client', $site_id);
    $client_id  = _acf_post_link_current_id($client_val, 'client');
    if (!$client_id) return '';
    $g = (array) get_field('primary_contact', $client_id);
    if (!empty($g['name'])) return trim((string)$g['name']);
    $first = isset($g['first_name']) ? trim((string)$g['first_name']) : '';
    $last  = isset($g['last_name'])  ? trim((string)$g['last_name'])  : '';
    return trim($first . ' ' . $last);
  }
}

/** Site → Location/Address label */
if (!function_exists('_site_location_from_site')) {
  function _site_location_from_site($site_val): string {
    if (!function_exists('get_field')) return '';
    $site_id = _acf_post_link_current_id($site_val, 'site');
    if (!$site_id) return '';
    $loc = get_field('location', $site_id);
    if (is_string($loc)) return trim($loc);
    if (is_array($loc)) {
      if (!empty($loc['address'])) return trim((string)$loc['address']);
      if (!empty($loc['formatted_address'])) return trim((string)$loc['formatted_address']);
      $parts=[]; foreach (['street','street_name','street_number','city','state','region','county','postcode','postal_code','zip','country'] as $k) {
        if (!empty($loc[$k])) $parts[] = trim((string)$loc[$k]);
      }
      return $parts ? implode(', ', array_unique($parts)) : '';
    }
    return $loc ? (string)$loc : '';
  }
}

/**
 * Build a normalized list of video items from ACF Repeater rows.
 * Returns items like: ['id'=>int,'url'=>string,'mime'=>string,'title'=>string,'caption'=>string]
 */
if (!function_exists('_acf_video_items')) {
  function _acf_video_items($rows, string $file_field = 'video_file', string $cap_field = 'video_caption'): array {
    if (empty($rows) || !is_array($rows)) return [];
    $out = [];
    foreach ($rows as $row) {
      if (!is_array($row)) continue;

      $att   = $row[$file_field] ?? null;
      $cap   = $row[$cap_field]  ?? '';
      $id    = 0; $url=''; $mime=''; $title='';

      if (is_numeric($att)) {
        $id = (int)$att;
      } elseif (is_array($att) && isset($att['ID'])) {
        $id = (int)$att['ID'];
      } elseif (is_object($att) && isset($att->ID)) {
        $id = (int)$att->ID;
      } elseif (is_string($att) && filter_var($att, FILTER_VALIDATE_URL)) {
        $url = $att;
        $ft  = wp_check_filetype($url);
        $mime = $ft['type'] ?? '';
        $title = basename(parse_url($url, PHP_URL_PATH));
      }

      if ($id) {
        $url   = wp_get_attachment_url($id) ?: $url;
        $mime  = get_post_mime_type($id) ?: $mime;
        $title = get_the_title($id) ?: $title;
      }

      $caption = '';
      if (is_string($cap)) {
        $caption = trim($cap);
      } elseif (is_array($cap)) {
        // Support for possible structured caption fields
        $caption = trim((string)($cap['text'] ?? $cap['value'] ?? ''));
      }

      if ($url) {
        $out[] = [
          'id'      => $id,
          'url'     => $url,
          'mime'    => $mime,
          'title'   => $title ?: 'Video',
          'caption' => $caption,
        ];
      }
    }
    return $out;
  }
}

/** ---------- Pull ACF fields ---------- */
$client_raw   = function_exists('get_field') ? get_field('client',        $report_id) : '';
$site_raw     = function_exists('get_field') ? get_field('site',          $report_id) : '';
$date_raw     = function_exists('get_field') ? get_field('date',          $report_id) : '';
$po_number    = function_exists('get_field') ? get_field('po_number',     $report_id) : '';
$notes_raw    = function_exists('get_field') ? get_field('full_notes',    $report_id) : '';
$gallery_raw  = function_exists('get_field') ? get_field('image_gallery', $report_id) : '';
$videos_raw   = function_exists('get_field') ? get_field('videos',        $report_id) : []; // repeater rows

/* ---------- Build labels ---------- */
$client_label = _acf_post_link_label($client_raw);
$site_label   = _acf_post_link_label($site_raw);
$date_label   = _fmt_acf_date(is_string($date_raw) ? $date_raw : '');
$gallery_ids  = _acf_gallery_ids($gallery_raw);
$site_primary_contact_label = _primary_contact_name_from_site($site_raw);
$site_location_label        = _site_location_from_site($site_raw);
$video_items = _acf_video_items($videos_raw, 'video_file', 'video_caption');

/** Heading prefers post title */
$heading = get_the_title($report_id);

/** Flags */
$updated = isset($_GET['updated']) ? (int) $_GET['updated'] : 0;

/** Optional edit link */
$edit_base = home_url('/edit-report/');
$edit_url  = add_query_arg(['reportID' => $report_id], $edit_base);
?>
<style>
  .vc-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px}
  .vc-title{font-size:24px;font-weight:700;margin:0}
  .vc-sub{color:#6b7280;margin:0 0 16px}
  .vc-grid{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.vc-grid{grid-template-columns:1fr 1fr}}
  .vc-field{background:#fbfcff;border:1px solid #e5eaf0;border-radius:10px;padding:14px 16px}
  .vc-label{display:block;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;margin-bottom:6px}
  .vc-value{font-size:16px;color:#111827;white-space:pre-line}
  .vc-actions{display:flex;gap:0 12px;flex-wrap:wrap}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 20px;border-radius:10px;border:0;cursor:pointer;font-size:16px;text-decoration:none}
  .btn:hover{background:#111827; color:#fff}
  .note{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;margin:16px 0}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .gal{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  @media(min-width:680px){.gal{grid-template-columns:repeat(4,1fr)}}
  .gal a{display:block;border-radius:10px;overflow:hidden;border:1px solid #e5eaf0}
  .gal img{display:block;width:100%;height:140px;object-fit:cover}

  /* Videos */
  .vids{display:grid;grid-template-columns:1fr;gap:14px}
  @media(min-width:880px){.vids{grid-template-columns:1fr 1fr}}
  .vid-card{border:1px solid #e5eaf0;border-radius:10px;background:#fff;overflow:hidden}
  .vid-wrap{position:relative;background:#000}
  .vid-wrap video{display:block;width:100%;height:auto;max-height:420px}
  .vid-meta{padding:10px 12px}
  .vid-title{font-weight:600;color:#111827;margin:0 0 4px}
  .vid-cap{color:#6b7280;font-size:14px;margin:0}
  .vid-actions{margin-top:8px}
  .vid-actions a{font-size:14px;color:#0f3a47;text-decoration:underline}
  
  /* Draft banner */
  .banner-draft{margin:16px 0;padding:12px 14px;border:1px solid #fde68a;background:#fffbeb;color:#7c2d12;border-radius:10px;font-size:15px}
</style>

<?php if (!$post_obj): ?>
  <div class="alert">Report not found.</div>
<?php else: ?>
  <?php if ($updated): ?><div class="note">Report saved successfully.</div><?php endif; ?>

  <?php $status = get_post_status($report_id); ?>
  <?php if ($status === 'draft'): ?>
    <div class="banner-draft">
      This report is currently <strong>in draft</strong> and <strong>has not been sent</strong> to the property manager.
    </div>
  <?php endif; ?>

  <p class="vc-sub">Report details</p>

  <div class="vc-grid">
    <div class="vc-field">
      <span class="vc-label">Property Manager</span>
      <div class="vc-value"><?php echo $site_primary_contact_label ? esc_html($site_primary_contact_label) : '—'; ?></div>
    </div>

    <div class="vc-field">
      <span class="vc-label">Client</span>
      <div class="vc-value"><?php echo $client_label !== '' ? esc_html($client_label) : '—'; ?></div>
    </div>

    <div class="vc-field">
      <span class="vc-label">Address</span>
      <div class="vc-value">
        <?php echo $site_label !== '' ? esc_html($site_label . ($site_location_label ? ', '.$site_location_label : '')) : '—'; ?>
      </div>
    </div>

    <div class="vc-field">
      <span class="vc-label">Date</span>
      <div class="vc-value"><?php echo $date_label !== '' ? esc_html($date_label) : '—'; ?></div>
    </div>

    <div class="vc-field">
      <span class="vc-label">PO Number</span>
      <div class="vc-value"><?php echo $po_number ? esc_html($po_number) : '—'; ?></div>
    </div>

    <div class="vc-field" style="grid-column:1 / -1">
      <span class="vc-label">Description of Works</span>
      <div class="vc-value"><?php echo $notes_raw ? esc_html($notes_raw) : '—'; ?></div>
    </div>

    <?php if (!empty($gallery_ids)): ?>
      <div class="vc-field" style="grid-column:1 / -1">
        <span class="vc-label">Gallery of Works</span>
        <div class="gal">
          <?php foreach ($gallery_ids as $img_id): ?>
            <?php $full = wp_get_attachment_image_src($img_id, 'full'); ?>
            <?php if ($full): ?>
              <a href="<?php echo esc_url($full[0]); ?>" target="_blank" rel="noopener">
                <?php echo wp_get_attachment_image($img_id, 'medium', false, ['alt' => get_the_title($img_id)]); ?>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($video_items)): ?>
      <div class="vc-field" style="grid-column:1 / -1">
        <span class="vc-label">Videos</span>
        <div class="vids">
          <?php foreach ($video_items as $v): ?>
            <div class="vid-card">
              <div class="vid-wrap">
                <video controls preload="metadata" playsinline>
                  <source src="<?php echo esc_url($v['url']); ?>" <?php echo $v['mime'] ? 'type="'.esc_attr($v['mime']).'"' : ''; ?> />
                  Your browser does not support the video tag.
                </video>
              </div>
              <div class="vid-meta">
                <p class="vid-title"><?php echo esc_html($v['title']); ?></p>
                <?php if (!empty($v['caption'])): ?>
                  <p class="vid-cap"><?php echo esc_html($v['caption']); ?></p>
                <?php endif; ?>
                <div class="vid-actions">
                  <a href="<?php echo esc_url($v['url']); ?>" download>Download video</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="vc-field" style="grid-column:1 / -1">
      <div class="vc-actions">
        <?php if (is_user_logged_in()): ?>
          <a class="btn" href="<?php echo esc_url($edit_url); ?>">Edit Report</a>
        <?php endif; ?>
        <?php
          $pdf_url = wp_nonce_url(
            admin_url('admin-post.php?action=report_pdf&report_id=' . $report_id),
            'report_pdf_' . $report_id
          );
        ?>
        <a class="btn" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener">Download PDF</a>
      </div>
    </div>

  </div>
<?php endif; ?>
