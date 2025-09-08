<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header');
		
/**
 * Template Name: User Account
 */
defined('ABSPATH') || exit;

/* ---------------- Helpers ---------------- */
function ua_safe_redirect($url, $status = 303){
  while (ob_get_level() > 0) { ob_end_clean(); }
  nocache_headers();
  wp_safe_redirect($url, $status);
  echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . esc_attr($url) . '"><script>location.replace(' . json_encode($url) . ');</script>';
  exit;
}
function ua_current_page_url(): string {
  $url = home_url(add_query_arg([], wp_make_link_relative($_SERVER['REQUEST_URI'] ?? '')));
  // more robust:
  return add_query_arg([], (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}

/* -------------- Gatekeeping -------------- */
if (!is_user_logged_in()) {
  get_header();
  echo '<div class="alert" style="max-width:960px;margin:24px auto;padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;"><p>Please <a href="'.esc_url(wp_login_url( ua_current_page_url() )).'">log in</a> to manage your account.</p></div>';
  get_footer();
  return;
}

$current = wp_get_current_user();
$user_id = (int) $current->ID;
$errors  = [];

/* Where to go after save */
$view_url = add_query_arg(['updated' => 1], remove_query_arg(['updated','error']));

/* -------------- Handle POST -------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['ua_nonce']) || !wp_verify_nonce($_POST['ua_nonce'], 'ua_save')) {
    $errors[] = 'Security check failed.';
  } elseif (!current_user_can('edit_user', $user_id)) {
    $errors[] = 'You do not have permission to edit this profile.';
  } else {
    // Collect fields
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name']  ?? '');
    $pass1      = (string)($_POST['pass1'] ?? '');
    $pass2      = (string)($_POST['pass2'] ?? '');
    $email1     = sanitize_email($_POST['email1'] ?? '');
    $email2     = sanitize_email($_POST['email2'] ?? '');
    $website    = esc_url_raw(trim($_POST['website'] ?? ''));
    $bio        = sanitize_textarea_field($_POST['bio'] ?? '');

    // Validate
    if ($email1 !== '' || $email2 !== '') {
      if ($email1 === '' || $email2 === '' || $email1 !== $email2) { $errors[] = 'Emails do not match.'; }
      if ($email1 && !is_email($email1)) { $errors[] = 'Please enter a valid email address.'; }
      $conflict = email_exists($email1);
      if ($email1 && $conflict && (int)$conflict !== $user_id) { $errors[] = 'That email is already in use.'; }
    }
    if ($pass1 !== '' || $pass2 !== '') {
      if ($pass1 !== $pass2) { $errors[] = 'Passwords do not match.'; }
      if ($pass1 !== '' && strlen($pass1) < 8) { $errors[] = 'Please use a password with at least 8 characters.'; }
    }

    // Upload avatar (optional)
    $avatar_attachment_id = 0;
    if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
      if (!function_exists('media_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
      }
      $file = $_FILES['avatar'];
      // Basic type guard
      if (!empty($file['type']) && strpos($file['type'], 'image/') !== 0) {
        $errors[] = 'Avatar must be an image.';
      } else {
        $attachment_id = media_handle_upload('avatar', 0);
        if (is_wp_error($attachment_id)) {
          $errors[] = 'Avatar upload failed: ' . $attachment_id->get_error_message();
        } else {
          $avatar_attachment_id = (int)$attachment_id;
        }
      }
    }

    // If valid, save
    if (empty($errors)) {
      // Core user fields
      wp_update_user([
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'user_email' => ($email1 !== '' ? $email1 : $current->user_email),
      ]);

      if ($pass1 !== '') {
        wp_update_user(['ID' => $user_id, 'user_pass' => $pass1]);
      }

      if ($avatar_attachment_id) {
        update_user_meta($user_id, 'profile_avatar_id', $avatar_attachment_id);
      }

      ua_safe_redirect($view_url);
    }
  }
}

/* -------------- Prefill values -------------- */
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name  = get_user_meta($user_id, 'last_name',  true);
$email      = $current->user_email;

?>

<style>
  .ua-title{font-size:40px;line-height:1.2;font-weight:800;margin:0 0 28px;color:#111827}
  .row{display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:18px}
  @media(min-width:720px){.row.two{grid-template-columns:1fr 1fr}}
  .lab{display:block;font-weight:600;margin:0 0 6px;color:#374151}
  .in,.ta{width:100%;border:1px solid #CBD5E1;border-radius:10px;padding:12px 14px;font-size:16px;background:#fff}
  .ta{min-height:140px;resize:vertical}
  .hint{font-size:12px;color:#6B7280;margin-top:6px}
  .dz{border:2px dashed #CBD5E1;border-radius:12px;padding:32px;text-align:center;background:#fff}
  .dz.drag{background:#F8FAFC;border-color:#94A3B8}
  .dz p{margin:8px 0;color:#111827}
  .btn{display:inline-block;background:#111827;color:#fff;font-weight:700;padding:12px 20px;border-radius:10px;border:0;cursor:pointer}
  .btn:hover{background:#0b0f17}
  .note{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;color:#065f46;margin:16px 0}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .avatar-preview{display:flex;align-items:center;gap:12px;margin:8px 0}
  .avatar-preview img{width:56px;height:56px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb}
</style>

<div class="ua-wrap">

  <?php if (isset($_GET['updated']) && (int)$_GET['updated'] === 1): ?>
    <div class="note">Profile updated successfully.</div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert">
      <strong>Please fix the following:</strong>
      <ul style="margin:8px 0 0 18px">
        <?php foreach ($errors as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <?php wp_nonce_field('ua_save','ua_nonce'); ?>

    <div class="row two">
      <div>
        <label class="lab" for="first_name">Name</label>
        <input class="in" id="first_name" name="first_name" type="text" value="<?php echo esc_attr($first_name); ?>" placeholder="First">
        <div class="hint">First</div>
      </div>
      <div style="margin-top:26px">
        <input class="in" id="last_name" name="last_name" type="text" value="<?php echo esc_attr($last_name); ?>" placeholder="Last">
        <div class="hint">Last</div>
      </div>
    </div>

    <div class="row two">
      <div>
        <label class="lab" for="pass1">Password</label>
        <input class="in" id="pass1" name="pass1" type="password" value="" placeholder="Enter Password" autocomplete="new-password">
        <div class="hint">Enter Password</div>
      </div>
      <div style="margin-top:26px">
        <input class="in" id="pass2" name="pass2" type="password" value="" placeholder="Confirm Password" autocomplete="new-password">
        <div class="hint">Confirm Password</div>
      </div>
    </div>

    <div class="row two">
      <div>
        <label class="lab" for="email1">Email Address</label>
        <input class="in" id="email1" name="email1" type="email" value="<?php echo esc_attr($email); ?>" placeholder="Enter Email">
        <div class="hint">Enter Email</div>
      </div>
      <div style="margin-top:26px">
        <input class="in" id="email2" name="email2" type="email" value="<?php echo esc_attr($email); ?>" placeholder="Confirm Email">
        <div class="hint">Confirm Email</div>
      </div>
    </div>
    <button class="btn" type="submit">Save Changes</button>
  </form>
</div>

<script>
(function(){
  const dz = document.getElementById('dropzone');
  const input = document.getElementById('avatar');
  const label = document.getElementById('avatarName');
  if(!dz || !input) return;
  const openPicker = () => input.click();
  dz.addEventListener('click', openPicker);
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('drag');
    if(e.dataTransfer.files.length){ input.files = e.dataTransfer.files; label.textContent = e.dataTransfer.files[0].name; }
  });
  input.addEventListener('change', () => { if(input.files.length){ label.textContent = input.files[0].name; } });
})();
</script>

<?php
		}
		else{
			echo get_template_part('templates/partials/login-form');
		} ?>
		
	</main>
<?php get_footer(); ?>
