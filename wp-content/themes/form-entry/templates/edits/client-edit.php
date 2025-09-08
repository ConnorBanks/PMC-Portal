<?php
/**
 * Template Name: Edit Client (ACF – Group)
 */
 
 defined('ABSPATH') || exit;
 
 /* ACF group + subfield names */
 $group_field = 'primary_contact';
 $sub_name    = 'name';
 $sub_phone   = 'telephone_number';
 $sub_email   = 'email_address';
 $sub_address = 'business_address';
 
 /* ---------- Helpers ---------- */
 function _client_view_pretty_url($post): string {
     if (!$post instanceof WP_Post) return home_url('/');
     $slug = $post->post_name; // client's slug
     // Base is the Page with slug 'client' that your view template uses
     $base = trailingslashit(home_url('/client/'));
     // Build /client/{slug}/ and respect trailing slash settings
     $url  = $base . rawurlencode($slug) . '/';
     return user_trailingslashit($url);
 }
 function _client_safe_redirect(string $url, int $status = 303) {
     while (ob_get_level() > 0) { ob_end_clean(); }
     nocache_headers();
     wp_safe_redirect($url, $status);
     // JS + meta fallback in case headers already sent by something else
     echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . esc_attr($url) . '"><script>location.replace(' . json_encode($url) . ');</script>';
     exit;
 }
 
 /* Input + permissions */
 $client_id = isset($_GET['clientID']) ? absint($_GET['clientID']) : 0;
 $post_obj  = $client_id ? get_post($client_id) : null;
 $can_edit  = $post_obj ? current_user_can('edit_post', $client_id) : false;
 
 $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
 
 /* Where to go after save */
 $view_url = $post_obj ? _client_view_pretty_url($post_obj) : trailingslashit(home_url('/client/')); // /client/{slug}/
 
 /* Handle POST (save) */
 if ($post_obj && $_SERVER['REQUEST_METHOD'] === 'POST') {
     if (!isset($_POST['client_form_nonce']) || !wp_verify_nonce($_POST['client_form_nonce'], 'client_form_save')) {
         _client_safe_redirect(add_query_arg(['error' => 'nonce'], remove_query_arg(['error'])));
     }
     if (!$can_edit) {
         _client_safe_redirect(add_query_arg(['error' => 'cap'], remove_query_arg(['error'])));
     }
     $posted_id = isset($_POST['client_post_id']) ? absint($_POST['client_post_id']) : 0;
     if ($posted_id !== $client_id) {
         _client_safe_redirect(add_query_arg(['error' => 'mismatch'], remove_query_arg(['error'])));
     }
 
     // Save or Delete?
     if (isset($_POST['delete_client'])) {
         wp_trash_post($client_id);
         // After delete, send to a list page (adjust to your listing slug)
         $list_url = trailingslashit(home_url('/clients/')); // change if needed
         _client_safe_redirect(add_query_arg(['deleted' => 1], $list_url));
     }
 
     // Save updates
     $client_name     = isset($_POST['client_name'])     ? wp_strip_all_tags($_POST['client_name']) : '';
     $contact_name    = isset($_POST['contact_name'])    ? sanitize_text_field($_POST['contact_name']) : '';
     $contact_email   = isset($_POST['contact_email'])   ? sanitize_email($_POST['contact_email']) : '';
     $contact_phone   = isset($_POST['contact_phone'])   ? sanitize_text_field($_POST['contact_phone']) : '';
     $contact_address = isset($_POST['contact_address']) ? wp_kses_post($_POST['contact_address']) : '';
 
     $errs = [];
     if ($client_name === '')  { $errs[] = 'client_name'; }
     if ($contact_name === '') { $errs[] = 'contact_name'; }
     if ($contact_email && !is_email($contact_email)) { $errs[] = 'contact_email'; }
     if (!empty($errs)) {
         _client_safe_redirect(add_query_arg(['error' => 'invalid'], remove_query_arg(['updated'])));
     }
 
     // Update post title (may change slug)
     wp_update_post(['ID' => $client_id, 'post_title' => $client_name]);
 
     // Re-fetch post to get the potentially new slug
     clean_post_cache($client_id);
     $post_obj = get_post($client_id);
     $view_url = _client_view_pretty_url($post_obj); // rebuild with updated slug
 
     // Update ACF group
     if (!function_exists('update_field')) {
         _client_safe_redirect(add_query_arg(['error' => 'acf_missing'], remove_query_arg(['updated'])));
     }
     update_field($group_field, [
         $sub_name    => $contact_name,
         $sub_phone   => $contact_phone,
         $sub_email   => $contact_email,
         $sub_address => $contact_address,
     ], $client_id);
 
     // Redirect to /client/{slug}/?updated=1
     _client_safe_redirect(add_query_arg(['updated' => 1], $view_url));
 }
 
 /* Prefill values (initial GET) */
 $business_name = $post_obj ? get_the_title($post_obj) : '';
 $contact_name = $contact_email = $contact_phone = $contact_address = '';
 if ($post_obj && function_exists('get_field')) {
     $group = (array) get_field($group_field, $client_id);
     $contact_name    = $group[$sub_name]    ?? '';
     $contact_phone   = $group[$sub_phone]   ?? '';
     $contact_email   = $group[$sub_email]   ?? '';
     $contact_address = $group[$sub_address] ?? '';
 }
?>

<style>
  .f{margin:0 0 22px}.lab{display:block;font-weight:600;margin:0 0 8px}
  .req .lab::after{content:" *";color:#e63946}
  .in,.ta{width:100%;border:1px solid #d7dfe7;border-radius:10px;padding:14px 16px;font-size:16px}
  .ta{min-height:160px;resize:vertical}
  .rule{border:0;border-top:1px solid #d7dfe7;margin:28px 0}
  .h1{font-size:24px;margin:0 0 18px;font-weight:700}
  .g2{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.g2{grid-template-columns:1fr 1fr}}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 22px;border-radius:10px;border:0;cursor:pointer;font-size:16px}
  .btn:hover{background:#111827}
  .btn-delete{background:transparent; color:#880808; margin-left:0}.btn-delete:hover{text-decoration:underline; background-color:transparent}
  .actions{display:flex;gap:12px;flex-wrap:wrap}
</style>

<?php if (!$post_obj): ?>
  <div class="alert">Missing or invalid <code>clientID</code>.</div>
<?php else: ?>
  <?php if ($error === 'nonce'): ?><div class="alert">Security check failed.</div><?php endif; ?>
  <?php if ($error === 'cap'): ?><div class="alert">You don’t have permission to edit this client.</div><?php endif; ?>
  <?php if ($error === 'mismatch'): ?><div class="alert">Form ID mismatch.</div><?php endif; ?>
  <?php if ($error === 'invalid'): ?><div class="alert">Please correct required fields and try again.</div><?php endif; ?>
  <?php if ($error === 'acf_missing'): ?><div class="alert">Advanced Custom Fields is required.</div><?php endif; ?>

  <form action="<?php echo esc_url(add_query_arg([])); ?>" method="post" onsubmit="return confirmDelete(event)" novalidate>
    <?php wp_nonce_field('client_form_save','client_form_nonce'); ?>
    <input type="hidden" name="client_post_id" value="<?php echo esc_attr($client_id); ?>" />

    <div class="f req">
      <label class="lab" for="client_name">Client / Business Name</label>
      <input class="in" id="client_name" name="client_name" type="text" required value="<?php echo esc_attr($business_name); ?>" />
    </div>

    <hr class="rule" />
    <div class="h1">Property Manager</div>

    <div class="g2">
      <div class="f req">
        <label class="lab" for="contact_name">Name</label>
        <input class="in" id="contact_name" name="contact_name" type="text" required value="<?php echo esc_attr($contact_name); ?>" />
      </div>
      <div class="f req">
        <label class="lab" for="contact_email">Email</label>
        <input class="in" id="contact_email" name="contact_email" type="email" required value="<?php echo esc_attr($contact_email); ?>" />
      </div>
    </div>

    <div class="f">
      <label class="lab" for="contact_phone">Telephone Number</label>
      <input class="in" id="contact_phone" name="contact_phone" type="tel" inputmode="tel" value="<?php echo esc_attr($contact_phone); ?>" />
    </div>

    <div class="f">
      <label class="lab" for="contact_address">Business Address</label>
      <textarea class="ta" id="contact_address" name="contact_address"><?php echo esc_textarea($contact_address); ?></textarea>
    </div>

    <div class="f actions">
      <button type="submit" name="save_client" class="btn">Save Client</button>
      <button type="submit" name="delete_client" class="btn btn-delete">Delete Client</button>
    </div>
  </form>
<?php endif; ?>

<script>
function confirmDelete(e){
  if(e.submitter && e.submitter.name === 'delete_client'){
    return confirm('Are you sure you want to delete this client? This action cannot be undone.');
  }
  return true;
}
</script>
