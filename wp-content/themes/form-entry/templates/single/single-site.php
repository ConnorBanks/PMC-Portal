<?php
if ( is_user_logged_in() ) {
	echo get_template_part('templates/blocks/page-header');
}

/**
 * Single template for post type: site
 * Read-only view using ACF fields:
 * - site_name (text)
 * - location (text)
 * - client (Post Object, Return = ID)  -> displays linked Client title
 * - site_notes (textarea)
 */

defined('ABSPATH') || exit;

$site_id  = get_the_ID();
$post_obj = $site_id ? get_post($site_id) : null;

/**
 * Resolve the ACF Client (Post Object) to a nice label/link.
 * Uses the field KEY to ensure we read the ID-based value.
 * Falls back to the legacy text stored under 'client' (field name) if present.
 */
function site_render_client_html(int $site_id): string {
	$field_key = 'field_687ec0391cc00'; // ACF "Client" Post Object field KEY (Return = ID)
	if (!function_exists('get_field')) return '—';

	$raw = get_field($field_key, $site_id);

	// Normalise to one ID (handles object/array/scalar)
	$cid = 0;
	if (is_numeric($raw)) {
		$cid = (int) $raw;
	} elseif (is_object($raw) && isset($raw->ID)) {
		$cid = (int) $raw->ID;
	} elseif (is_array($raw)) {
		if (isset($raw['ID'])) {
			$cid = (int) $raw['ID'];
		} else {
			// If multi accidentally enabled, pick first
			foreach ($raw as $v) {
				if (is_object($v) && isset($v->ID))      { $cid = (int) $v->ID; break; }
				if (is_array($v)  && isset($v['ID']))    { $cid = (int) $v['ID']; break; }
				if (is_numeric($v))                      { $cid = (int) $v;     break; }
			}
		}
	}

	if ($cid > 0) {
		$title = get_the_title($cid) ?: 'Untitled';
		$url   = get_permalink($cid);
		return $url ? '<a href="'.esc_url($url).'">'.esc_html($title).'</a>' : esc_html($title);
	}

	// Legacy fallback: older content may have stored a TEXT label in field name 'client'
	$legacy = get_field('client', $site_id);
	return $legacy ? esc_html((string)$legacy) : '—';
}

/* Pull ACF fields (simple ones by name) */
$site_name  = function_exists('get_field') ? (string) get_field('site_name',  $site_id) : '';
$location   = function_exists('get_field') ? (string) get_field('location',   $site_id) : '';
$site_notes = function_exists('get_field') ? (string) get_field('site_notes', $site_id) : '';

/* Heading prefers ACF site_name, then post title */
$heading = $site_name !== '' ? $site_name : get_the_title($site_id);

/* Flags */
$updated = isset($_GET['updated']) ? (int) $_GET['updated'] : 0;
$deleted = isset($_GET['deleted']) ? (int) $_GET['deleted'] : 0;

/* Edit link target (page using the edit template for Sites) */
$edit_base = home_url('/edit-site/');
$edit_url  = $site_id ? add_query_arg(['siteID' => $site_id], $edit_base) : $edit_base;
?>
<style>
  .vc-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px}
  .vc-title{font-size:24px;font-weight:700;margin:0}
  .vc-sub{color:#6b7280;margin:0 0 16px}
  .vc-grid{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.vc-grid{grid-template-columns:1fr 1fr}}
  .vc-field{background:#fbfcff;border:1px solid #e5eaf0;border-radius:10px;padding:14px 16px}
  .vc-label{display:block;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;margin-bottom:6px}
  .vc-value a{ color:#133E50;}
  .vc-value a:hover{text-decoration:underline; margin-right:5px;}
  .vc-value i{ font-size:12px; }
  .vc-value{font-size:16px;color:#111827;white-space:pre-line}
  .vc-actions{margin-top:22px;display:flex;gap:12px;flex-wrap:wrap}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 20px;border-radius:10px;border:0;cursor:pointer;font-size:16px;text-decoration:none}
  .btn:hover{background:#111827}
  .note{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;margin:16px 0}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
</style>

<?php if ( ! $post_obj ) : ?>
  <div class="alert">Site not found.</div>
<?php else : ?>
  <?php if ($updated): ?><div class="note">Site details saved successfully.</div><?php endif; ?>
  <?php if ($deleted): ?><div class="note">Site was deleted.</div><?php endif; ?>

  <div class="vc-card">

    <p class="vc-sub">Site details</p>

    <div class="vc-grid">
      <div class="vc-field">
        <span class="vc-label">Site Name</span>
        <div class="vc-value"><?php echo $site_name ? esc_html($site_name) : '—'; ?></div>
      </div>

      <div class="vc-field">
        <span class="vc-label">Address</span>
        <div class="vc-value"><?php echo $location ? esc_html($location) : '—'; ?></div>
      </div>

      <div class="vc-field">
        <span class="vc-label">Property Manager</span>
        <div class="vc-value"><?php echo wp_kses_post(site_render_client_html($site_id)); ?> <i class="fas fa-external-link"></i></div>
      </div>

      <div class="vc-field" style="grid-column:1 / -1">
        <span class="vc-label">Description of Works</span>
        <div class="vc-value"><?php echo $site_notes ? esc_html($site_notes) : '—'; ?></div>
      </div>
      <?php 
      if(is_user_logged_in()){ ?>
        <div class="vc-actions">
          <a class="btn" href="<?php echo esc_url($edit_url); ?>">Edit Site</a>
        </div>
      <?php
      } ?>
      
    </div>
  </div>
<?php endif; ?>
