<?php 
if(is_user_logged_in()){ 
	echo get_template_part('templates/blocks/page-header');
} 

/**
 * Template Name: View Client (ACF – Read Only)
 */

defined('ABSPATH') || exit;

$group_field = 'primary_contact';
$sub_name    = 'name';
$sub_phone   = 'telephone_number';
$sub_email   = 'email_address';
$sub_address = 'business_address';

$client_id = get_the_ID();
$post_obj  = $client_id ? get_post($client_id) : null;

$business_name   = $post_obj ? get_the_title($post_obj) : '';
$contact_name = $contact_email = $contact_phone = $contact_address = '';

if ($post_obj && function_exists('get_field')) {
    $group = (array) get_field($group_field, $client_id);
    $contact_name    = $group[$sub_name]    ?? '';
    $contact_phone   = $group[$sub_phone]   ?? '';
    $contact_email   = $group[$sub_email]   ?? '';
    $contact_address = $group[$sub_address] ?? '';
}

$updated = isset($_GET['updated']) ? (int) $_GET['updated'] : 0;
$deleted = isset($_GET['deleted']) ? (int) $_GET['deleted'] : 0;

/* Edit link target */
$edit_base = home_url('/edit-client/'); // <-- slug of the page using the edit template above
$edit_url  = $client_id ? add_query_arg(['clientID' => $client_id], $edit_base) : $edit_base;

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
</style>

    <?php if (!$post_obj): ?>
      <div class="alert">Missing or invalid <code>clientID</code>.</div>
    <?php else: ?>
      <?php if ($updated): ?><div class="note">Client details saved successfully.</div><?php endif; ?>
      <?php if ($deleted): ?><div class="note">Client was deleted.</div><?php endif; ?>

      <div class="vc-card">
        <p class="vc-sub">Property Manager</p>

        <div class="vc-grid">
          <div class="vc-field">
            <span class="vc-label">Full Name</span>
            <div class="vc-value"><?php echo $contact_name ? esc_html($contact_name) : '—'; ?></div>
          </div>
          <div class="vc-field">
            <span class="vc-label">Email Address</span>
            <div class="vc-value">
              <?php if ($contact_email): ?>
                <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
              <?php else: ?>—<?php endif; ?>
            </div>
          </div>
          <div class="vc-field">
            <span class="vc-label">Telephone Number</span>
            <div class="vc-value"><?php echo $contact_phone ? esc_html($contact_phone) : '—'; ?></div>
          </div>
          <div class="vc-field" style="grid-column:1 / -1">
            <span class="vc-label">Business Address</span>
            <div class="vc-value"><?php echo $contact_address ? esc_html($contact_address) : '—'; ?></div>
          </div>
        </div>

        <div class="vc-actions">
          <a class="btn" href="<?php echo esc_url($edit_url); ?>">Edit Client</a>
        </div>
      </div>
    <?php endif; ?>



