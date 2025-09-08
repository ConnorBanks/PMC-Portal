<?php
use Dompdf\Dompdf;
use Dompdf\Options;

add_action('admin_post_report_pdf',        'bs_report_pdf_handler');
add_action('admin_post_nopriv_report_pdf', 'bs_report_pdf_handler');

function bs_report_pdf_handler() {
    $report_id = isset($_GET['report_id']) ? (int) $_GET['report_id'] : 0;
    if (!$report_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'report_pdf_' . $report_id)) {
        wp_die('Invalid request.');
    }

    $post = get_post($report_id);
    if (!$post || 'report' !== $post->post_type) {
        wp_die('Report not found.');
    }

    // --- Pull content (same as before) ---
    $client_raw   = function_exists('get_field') ? get_field('client',        $report_id) : '';
    $site_raw     = function_exists('get_field') ? get_field('site',          $report_id) : '';
    $date_raw     = function_exists('get_field') ? get_field('date',          $report_id) : '';
    $po_number    = function_exists('get_field') ? get_field('po_number',     $report_id) : '';
    $notes_raw    = function_exists('get_field') ? get_field('full_notes',    $report_id) : '';
    $gallery_raw  = function_exists('get_field') ? get_field('image_gallery', $report_id) : '';
    $videos_raw   = function_exists('get_field') ? get_field('videos', $report_id) : '';
    $video_items  = function_exists('_acf_video_items') ? _acf_video_items($videos_raw, 'video_file') : [];

    // --- Helpers (same logic you already use) ---
    $fmt_date = function($raw) {
        if (!$raw) return '';
        if (preg_match('/^\d{8}$/', $raw)) {
            $ts = strtotime(substr($raw,0,4).'-'.substr($raw,4,2).'-'.substr($raw,6,2));
        } else {
            $ts = strtotime($raw);
        }
        return $ts ? date_i18n('j M Y', $ts) : $raw;
    };
    $label_post = function($val) use (&$label_post) {
        if (is_array($val)) {
            if (isset($val['label'])) return (string)$val['label'];
            if (isset($val['value'])) $val = $val['value'];
        }
        if (is_array($val)) { $out=[]; foreach($val as $v){ $out[] = $label_post($v);} return implode(', ', array_filter($out)); }
        if (is_object($val) && isset($val->ID)) return get_the_title((int)$val->ID);
        if (is_numeric($val)) return get_the_title((int)$val);
        if (is_string($val)) return ctype_digit($val) ? get_the_title((int)$val) : $val;
        return '';
    };
    $gallery_ids = [];
    if (!empty($gallery_raw)) {
        if (is_array($gallery_raw)) {
            foreach ($gallery_raw as $item) {
                if (is_numeric($item)) $gallery_ids[] = (int)$item;
                elseif (is_array($item) && isset($item['ID'])) $gallery_ids[] = (int)$item['ID'];
                elseif (is_object($item) && isset($item->ID)) $gallery_ids[] = (int)$item->ID;
            }
        } elseif (is_numeric($gallery_raw)) {
            $gallery_ids[] = (int)$gallery_raw;
        }
        $gallery_ids = array_values(array_unique(array_filter($gallery_ids)));
    }
    $current_id_from_link = function($val, $fallback_pt = '') use (&$current_id_from_link) {
        if (is_object($val) && isset($val->ID)) return (int)$val->ID;
        if (is_array($val) && isset($val['ID'])) return (int)$val['ID'];
        if (is_array($val) && isset($val['value'])) {
            $v = $val['value'];
            if (is_numeric($v)) return (int)$v;
            if ($fallback_pt && is_string($v)) { $p = get_page_by_title($v, OBJECT, $fallback_pt); if ($p) return (int)$p->ID; }
        }
        if (is_array($val)) {
            foreach ($val as $v) { $id = $current_id_from_link($v, $fallback_pt); if ($id) return $id; }
        }
        if (is_numeric($val)) return (int)$val;
        if ($fallback_pt && is_string($val)) { $p = get_page_by_title($val, OBJECT, $fallback_pt); if ($p) return (int)$p->ID; }
        return 0;
    };
    $primary_contact_from_site = function($site_val) use ($current_id_from_link) {
        if (!function_exists('get_field')) return '';
        $site_id = $current_id_from_link($site_val, 'site');
        if (!$site_id) return '';
        $client_val = get_field('client', $site_id);
        $client_id  = $current_id_from_link($client_val, 'client');
        if (!$client_id) return '';
        $g = (array) get_field('primary_contact', $client_id);
        if (!empty($g['name'])) return trim((string)$g['name']);
        $first = isset($g['first_name']) ? trim((string)$g['first_name']) : '';
        $last  = isset($g['last_name'])  ? trim((string)$g['last_name'])  : '';
        return trim($first . ' ' . $last);
    };
    $site_location_from_site = function($site_val) use ($current_id_from_link) {
        if (!function_exists('get_field')) return '';
        $site_id = $current_id_from_link($site_val, 'site');
        if (!$site_id) return '';
        $loc = get_field('location', $site_id);
        return is_string($loc) ? trim($loc) : ($loc ? (string)$loc : '');
    };

    $client_label  = $label_post($client_raw);
    $site_label    = $label_post($site_raw);
    $date_label    = $fmt_date(is_string($date_raw) ? $date_raw : '');
    $pm_label      = $primary_contact_from_site($site_raw);
    $site_location = $site_location_from_site($site_raw);
    

    // --- NEW: Get logo from ACF Options (field: site_logo) ---
    $logo_url = '';
    if (function_exists('get_field')) {
        $site_logo = get_field('site_logo', 'option'); // 'options' also works in newer ACF, but 'option' is canonical
        if ($site_logo) {
            if (is_array($site_logo)) {
                if (!empty($site_logo['url'])) {
                    $logo_url = $site_logo['url'];
                } elseif (!empty($site_logo['ID'])) {
                    $logo_url = wp_get_attachment_image_url((int)$site_logo['ID'], 'medium');
                }
            } elseif (is_numeric($site_logo)) {
                $logo_url = wp_get_attachment_image_url((int)$site_logo, 'medium');
            } elseif (is_string($site_logo)) {
                $logo_url = $site_logo; // assume direct URL
            }
        }
    }

    // --- Build PDF HTML ---
    ob_start();
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?php echo esc_html(get_the_title($report_id)); ?> – Report</title>
        <style>
            *{ box-sizing:border-box; }
            body{ font-family: Helvetica, Arial, sans-serif; color:#111; font-size:12px; margin:0; padding:24px; }

            /* Full-width header bar in #133E50 */
            .doc-header{
                background:#133E50;
                /* pull to full width, beyond body padding */
                margin:0;
                padding:14px 24px;
            }
            .brand{
                height:50px;
                width:auto;
                display:block;
            }
            .brand-fallback{
                color:#fff;
                font-weight:700;
                font-size:16px;
            }

            h1{ font-size:24px; margin:12px 24px 8px; }
            .meta{ margin:0 24px 16px; color:#555; }

            .grid{ display:block; width:100%; padding:0 24px; }
            .row{ display:flex; gap:12px; margin:0 0 8px; }
            .cell{ flex:1; border:1px solid #e5eaf0; padding:8px 10px; border-radius:6px; }
            .label{ font-size:14px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; margin:0 0 4px; }
            .val{ font-size:18px; }
            .section{ margin:16px 24px 0; page-break-inside:avoid; }
            .notes{ white-space:pre-line; border:1px solid #e5eaf0; padding:10px; border-radius:6px; }
            .gallery{ display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
            .gallery img{ display:block; width:250px; height:auto; object-fit:cover; border:1px solid #e5eaf0; border-radius:6px; }

            .footer{ margin:18px 24px 0; font-size:10px; color:#666; }
            .section .label {
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #6b7280;
                margin: 0 0 4px;
            }
            .notes {
                white-space: pre-line;
                border: 1px solid #e5eaf0;
                padding: 10px;
                border-radius: 6px;
                background: #f9fafb;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="doc-header">
            <?php if ($logo_url): ?>
                <img class="brand" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?> logo">
            <?php else: ?>
                <div class="brand-fallback"><?php echo esc_html( get_bloginfo('name') ); ?></div>
            <?php endif; ?>
        </div>

        <h1>Report #<?php echo esc_html(get_the_title($report_id)); ?></h1>
        <p class="meta">Generated on <?php echo esc_html( date_i18n('j M Y, H:i') ); ?></p>

        <div class="grid">
            <div class="row">
                <div class="cell">
                    <div class="label">Property Manager</div>
                    <div class="val"><?php echo $pm_label ? esc_html($pm_label) : '—'; ?></div>
                </div>
            </div>
            <div class="row">
                <div class="cell">
                    <div class="label">Client</div>
                    <div class="val"><?php echo $client_label ? esc_html($client_label) : '—'; ?></div>
                </div>
            </div>
            <div class="row">
                <div class="cell">
                    <div class="label">Address</div>
                    <div class="val"><?php echo $site_label ? esc_html($site_label . ($site_location ? ', '.$site_location : '')) : '—'; ?></div>
                </div>
            </div>
            <div class="row">
                 <div class="cell">
                    <div class="label">Date</div>
                    <div class="val"><?php echo $date_label ? esc_html($date_label) : '—'; ?></div>
                </div>
            </div>
            <div class="row">
                <div class="cell">
                    <div class="label">PO Number</div>
                    <div class="val"><?php echo $po_number ? esc_html($po_number) : '—'; ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="label">Description of Works</div>
            <div class="notes"><?php echo $notes_raw ? esc_html($notes_raw) : '—'; ?></div>
        </div>

        <?php if (!empty($gallery_ids)): ?>
            <div class="section">
                <div class="label">Gallery of Works</div>
                <div class="gallery">
                    <?php foreach ($gallery_ids as $img_id):
                        $src = wp_get_attachment_image_src($img_id, 'large'); // keeps PDF size reasonable
                        if ($src && !empty($src[0])): ?>
                            <img src="<?php echo esc_url($src[0]); ?>" alt="">
                        <?php endif;
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($videos_raw )): ?>
            <div class="section">
                <div class="label">Videos Available</div>
                <div class="notes">
                    Videos for this report are available to view on the online portal.
                </div>
            </div>
        <?php endif; ?>
        <div class="footer">© <?php echo esc_html( get_bloginfo('name') ); ?></div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // --- Render with Dompdf ---
    if (!class_exists(Dompdf::class)) {
        wp_die('PDF engine not loaded.');
    }

    $options = new Options();
    $options->set('isRemoteEnabled', true); // important for logo & gallery images
    $options->set('defaultFont', 'Helvetica');
    $options->set('dpi', 150); // improves image sharpness a bit

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = sanitize_title(get_the_title($report_id) ?: 'report') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => 0]);
    exit;
}
?>