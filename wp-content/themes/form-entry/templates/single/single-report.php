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
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
  echo get_template_part('templates/blocks/page-header');
}

$report_id = get_the_ID();
$post_obj  = $report_id ? get_post($report_id) : null;

/** ---------- Helpers ---------- */
/**
 * Turn any ACF post-link value into a human label.
 * Works for: post_object (id|object|array), relationship (arrays), and legacy select (value|label).
 */
function _acf_post_link_label($val) {
  if (is_array($val)) {
    // ACF "array" format for return_format=array or multi
    if (isset($val['label'])) return (string) $val['label'];
    if (isset($val['value'])) $val = $val['value']; // continue normalization
  }
  if (is_array($val)) {
    $labels = [];
    foreach ($val as $v) { $labels[] = _acf_post_link_label($v); }
    return implode(', ', array_filter($labels));
  }
  if (is_object($val) && isset($val->ID)) return get_the_title((int) $val->ID);
  if (is_numeric($val)) return get_the_title((int) $val);
  if (is_string($val)) {
    // Numeric string? treat as ID; else it’s already a label
    return ctype_digit($val) ? get_the_title((int) $val) : $val;
  }
  return '';
}

/** Format ACF date (supports Y-m-d and Ymd) → e.g., 21 Aug 2025 */
function _fmt_acf_date($raw) {
  if (!$raw) return '';
  // If Ymd (8 digits)
  if (preg_match('/^\d{8}$/', $raw)) {
    $y = substr($raw, 0, 4); $m = substr($raw, 4, 2); $d = substr($raw, 6, 2);
    $ts = strtotime("$y-$m-$d");
  } else {
    $ts = strtotime($raw);
  }
  return $ts ? date_i18n('j M Y', $ts) : $raw;
}

/** Get gallery IDs from ACF gallery (handles ID/array return formats) */
function _acf_gallery_ids($val) {
  if (empty($val)) return [];
  $ids = [];
  if (is_array($val)) {
    foreach ($val as $item) {
      if (is_numeric($item)) {
        $ids[] = (int) $item;
      } elseif (is_array($item) && isset($item['ID'])) {
        $ids[] = (int) $item['ID'];
      } elseif (is_object($item) && isset($item->ID)) {
        $ids[] = (int) $item->ID;
      }
    }
  } elseif (is_numeric($val)) {
    $ids[] = (int) $val;
  }
  return array_values(array_unique(array_filter($ids)));
}

/** ---------- Pull ACF fields ---------- */
$client_raw   = function_exists('get_field') ? get_field('client',        $report_id) : '';
$site_raw     = function_exists('get_field') ? get_field('site',          $report_id) : '';
$date_raw     = function_exists('get_field') ? get_field('date',          $report_id) : '';
$po_number    = function_exists('get_field') ? get_field('po_number',     $report_id) : '';
$notes_raw    = function_exists('get_field') ? get_field('full_notes',    $report_id) : '';
$gallery_raw  = function_exists('get_field') ? get_field('image_gallery', $report_id) : '';

$client_label = _acf_post_link_label($client_raw);
$site_label   = _acf_post_link_label($site_raw);
$date_label   = _fmt_acf_date(is_string($date_raw) ? $date_raw : '');
$gallery_ids  = _acf_gallery_ids($gallery_raw);

/** Heading prefers post title */
$heading = get_the_title($report_id);

/** Flags */
$updated = isset($_GET['updated']) ? (int) $_GET['updated'] : 0;

/** Optional edit link (adjust slug to your edit page template if you have one) */
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
  .vc-actions{margin-top:22px;display:flex;gap:12px;flex-wrap:wrap}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 20px;border-radius:10px;border:0;cursor:pointer;font-size:16px;text-decoration:none}
  .btn:hover{background:#111827}
  .note{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;margin:16px 0}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .gal{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  @media(min-width:680px){.gal{grid-template-columns:repeat(4,1fr)}}
  .gal a{display:block;border-radius:10px;overflow:hidden;border:1px solid #e5eaf0}
  .gal img{display:block;width:100%;height:140px;object-fit:cover}
</style>

<?php if (!$post_obj): ?>
  <div class="alert">Report not found.</div>
<?php else: ?>
  <?php if ($updated): ?><div class="note">Report saved successfully.</div><?php endif; ?>

    <?php
$report_id = get_the_ID();
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
    This report is currently <strong>in draft</strong> and <strong>has not been sent</strong> to the property manager.
  </div>
<?php endif; ?>


  <div class="vc-head">
    <h1 class="vc-title"><?php echo esc_html($heading); ?></h1>
    <div class="vc-actions">
      <a class="btn" href="<?php echo esc_url($edit_url); ?>">Edit Report</a>
    </div>
  </div>

  <p class="vc-sub">Report details</p>

  <div class="vc-grid">
    <div class="vc-field">
      <span class="vc-label">Client</span>
      <div class="vc-value"><?php echo $client_label !== '' ? esc_html($client_label) : '—'; ?></div>
    </div>

    <div class="vc-field">
      <span class="vc-label">Site</span>
      <div class="vc-value"><?php echo $site_label !== '' ? esc_html($site_label) : '—'; ?></div>
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
      <span class="vc-label">Notes</span>
      <div class="vc-value"><?php echo $notes_raw ? esc_html($notes_raw) : '—'; ?></div>
    </div>

    <?php if (!empty($gallery_ids)): ?>
      <div class="vc-field" style="grid-column:1 / -1">
        <span class="vc-label">Images</span>
        <div class="gal">
          <?php foreach ($gallery_ids as $img_id): ?>
            <?php
              $full = wp_get_attachment_image_src($img_id, 'large');
              $thumb_html = wp_get_attachment_image($img_id, 'medium', false, ['alt' => get_the_title($img_id)]);
            ?>
            <?php if ($full): ?>
              <a href="<?php echo esc_url($full[0]); ?>" target="_blank" rel="noopener">
                <?php echo $thumb_html; ?>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
