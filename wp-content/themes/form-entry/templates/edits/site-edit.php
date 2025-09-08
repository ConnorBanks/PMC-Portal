<?php
/**
 * Template Name: Edit Site (ACF)
 */
defined('ABSPATH') || exit;

/* ACF field names + KEY */
$acf_site_name   = 'site_name';
$acf_location    = 'location';
$acf_site_notes  = 'site_notes';
$acf_key_client  = 'field_687ec0391cc00'; // Post Object field KEY (Return = ID)

/* Use a safe POST var name to avoid collisions (was "client") */
$client_post_var = 'site_client_id';

/* ---------- Helpers ---------- */
function _site_safe_redirect(string $url, int $status = 303) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    nocache_headers();
    wp_safe_redirect($url, $status);
    echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . esc_attr($url) . '"><script>location.replace(' . json_encode($url) . ');</script>';
    exit;
}
function _norm_ids_from_acf($val): array {
    $ids = [];
    if (is_array($val)) {
        foreach ($val as $v) {
            if (is_object($v) && isset($v->ID))      $ids[] = (int)$v->ID;
            elseif (is_array($v) && isset($v['ID'])) $ids[] = (int)$v['ID'];
            elseif (is_numeric($v))                  $ids[] = (int)$v;
        }
    } else {
        if (is_object($val) && isset($val->ID))      $ids[] = (int)$val->ID;
        elseif (is_array($val) && isset($val['ID'])) $ids[] = (int)$val['ID'];
        elseif (is_numeric($val))                    $ids[] = (int)$val;
    }
    return array_values(array_unique(array_filter($ids)));
}

/* Input + permissions */
$site_id  = isset($_GET['siteID']) ? absint($_GET['siteID']) : 0;
$post_obj = $site_id ? get_post($site_id) : null;
$can_edit = $post_obj ? current_user_can('edit_post', $site_id) : false;

$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

/* Stop canonical redirects on this POST (prevents mystery 404s) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_form_nonce'])) {
    add_filter('redirect_canonical', '__return_false', 99);
}

/* Handle POST (save/delete) */
if ($post_obj && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['site_form_nonce']) || !wp_verify_nonce($_POST['site_form_nonce'], 'site_form_save')) {
        _site_safe_redirect(add_query_arg(['error' => 'nonce'], remove_query_arg(['error'])));
    }
    if (!$can_edit) {
        _site_safe_redirect(add_query_arg(['error' => 'cap'], remove_query_arg(['error'])));
    }
    $posted_id = isset($_POST['site_post_id']) ? absint($_POST['site_post_id']) : 0;
    if ($posted_id !== $site_id) {
        _site_safe_redirect(add_query_arg(['error' => 'mismatch'], remove_query_arg(['error'])));
    }

    // Delete?
    if (isset($_POST['delete_site'])) {
        wp_trash_post($site_id);
        $list_url = trailingslashit(home_url('/sites/')); // adjust to your listing page
        _site_safe_redirect(add_query_arg(['deleted' => 1], $list_url));
    }

    // Save updates
    $site_name   = isset($_POST['site_name'])        ? wp_strip_all_tags($_POST['site_name']) : '';
    $location    = isset($_POST['location'])         ? sanitize_text_field($_POST['location']) : '';
    $client_id   = isset($_POST[$client_post_var])   ? absint($_POST[$client_post_var]) : 0; // renamed POST var
    $site_notes  = isset($_POST['site_notes'])       ? wp_kses_post($_POST['site_notes']) : '';

    $errs = [];
    if ($site_name === '') { $errs[] = 'site_name'; }
    if (!empty($errs)) {
        _site_safe_redirect(add_query_arg(['error' => 'invalid'], remove_query_arg(['updated'])));
    }

    // Update post title (may change slug)
    wp_update_post(['ID' => $site_id, 'post_title' => $site_name]);

    // Update ACF fields
    if (!function_exists('update_field')) {
        _site_safe_redirect(add_query_arg(['error' => 'acf_missing'], remove_query_arg(['updated'])));
    }
    update_field($acf_site_name,  $site_name,  $site_id);
    update_field($acf_location,   $location,   $site_id);
    update_field($acf_site_notes, $site_notes, $site_id);

    // Save Client via KEY (single ID). If you want to allow clearing, pass null when 0.
    if ($client_id > 0) {
        $client_ok = (bool) update_field($acf_key_client, $client_id, $site_id);
        // Optional: verify after ACF init and fall back to array shape
        if ($client_ok && function_exists('get_field') && did_action('acf/init')) {
            $saved_ids = _norm_ids_from_acf(get_field($acf_key_client, $site_id));
            if (!in_array($client_id, $saved_ids, true)) {
                $client_ok = (bool) update_field($acf_key_client, [$client_id], $site_id);
            }
        }
    } else {
        update_field($acf_key_client, null, $site_id); // clear when no selection
    }

    // Re-fetch post (title/slug may have changed)
    clean_post_cache($site_id);
    $post_obj = get_post($site_id);

    // Safe redirect to the single Site page
    $pretty         = get_permalink($site_id);
    $resolved_id    = $pretty ? (function_exists('url_to_postid') ? url_to_postid($pretty) : 0) : 0;
    $needs_fallback = !$pretty || ((int)$resolved_id !== (int)$site_id);
    $fallback       = add_query_arg(['post_type' => 'site', 'p' => $site_id, 'updated' => 1], home_url('/'));

    if ($needs_fallback) {
        _site_safe_redirect($fallback);
    } else {
        _site_safe_redirect(add_query_arg(['updated' => 1], $pretty));
    }
}

/* Prefill values (initial GET) */
$site_title   = $post_obj ? get_the_title($post_obj) : '';
$site_name_v  = $location_v = $site_notes_v = '';
$client_selected_id = 0;

if ($post_obj && function_exists('get_field')) {
    $site_name_v  = (string) get_field($acf_site_name,  $site_id);
    $location_v   = (string) get_field($acf_location,   $site_id);
    $site_notes_v = (string) get_field($acf_site_notes, $site_id);

    // Client Post Object (Return = ID). Normalise in case config ever changes.
    $cval = get_field($acf_key_client, $site_id);
    $ids  = _norm_ids_from_acf($cval);
    $client_selected_id = $ids[0] ?? 0;
}

/* Build Clients list (value=ID, label=title) */
$clients_list = [];
$clients_q = new WP_Query([
    'post_type'      => 'client',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'no_found_rows'  => true,
    'fields'         => 'ids',
]);
if ($clients_q->have_posts()) {
    foreach ($clients_q->posts as $cid) {
        $clients_list[] = [$cid, get_the_title($cid) ?: 'Untitled'];
    }
}
// Resolve the ACF Client (Post Object) to a nice label/link
function site_render_client_html(int $site_id): string {
    $field_key = 'field_687ec0391cc00'; // Client Post Object (Return = ID)
    if (!function_exists('get_field')) return '—';

    $raw = get_field($field_key, $site_id);

    // normalise to an array of IDs
    $ids = [];
    if (is_object($raw) && isset($raw->ID))           $ids = [(int)$raw->ID];
    elseif (is_array($raw) && isset($raw['ID']))      $ids = [(int)$raw['ID']];
    elseif (is_array($raw)) {
        foreach ($raw as $v) {
            if (is_object($v) && isset($v->ID))       $ids[] = (int)$v->ID;
            elseif (is_array($v) && isset($v['ID']))  $ids[] = (int)$v['ID'];
            elseif (is_numeric($v))                   $ids[] = (int)$v;
        }
    } elseif (is_numeric($raw))                       $ids = [(int)$raw];

    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) {
        // Fallback for older content when the field stored a TEXT label
        $legacy = get_field('client', $site_id);
        return $legacy ? esc_html((string)$legacy) : '—';
    }

    $cid   = $ids[0];
    $title = get_the_title($cid) ?: 'Untitled';
    $url   = get_permalink($cid);

    return $url ? '<a href="'.esc_url($url).'">'.esc_html($title).'</a>' : esc_html($title);
}

wp_reset_postdata();
?>
<style>
  .f{margin:0 0 22px}.lab{display:block;font-weight:600;margin:0 0 8px}
  .req .lab::after{content:" *";color:#e63946}
  .in,.ta,.sel{width:100%;border:1px solid #d7dfe7;border-radius:10px;padding:14px 16px;font-size:16px}
  .ta{min-height:160px;resize:vertical}
  .rule{border:0;border-top:1px solid #d7dfe7;margin:28px 0}
  .h1{font-size:24px;margin:0 0 18px;font-weight:700}
  .g2{display:grid;grid-template-columns:1fr;gap:18px}
  @media(min-width:880px){.g2{grid-template-columns:1fr 1fr}}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 22px;border-radius:10px;border:0;cursor:pointer;font-size:16px}
  .btn:hover{background:#111827}
  .btn-delete{background:transparent;color:#880808;margin-left:0}.btn-delete:hover{text-decoration:underline;background-color:transparent}
  .actions{display:flex;gap:12px;flex-wrap:wrap}
</style>

<?php if (!$post_obj): ?>
  <div class="alert">Missing or invalid <code>siteID</code>.</div>
<?php else: ?>
  <?php if ($error === 'nonce'): ?><div class="alert">Security check failed.</div><?php endif; ?>
  <?php if ($error === 'cap'): ?><div class="alert">You don’t have permission to edit this site.</div><?php endif; ?>
  <?php if ($error === 'mismatch'): ?><div class="alert">Form ID mismatch.</div><?php endif; ?>
  <?php if ($error === 'invalid'): ?><div class="alert">Please correct required fields and try again.</div><?php endif; ?>
  <?php if ($error === 'acf_missing'): ?><div class="alert">Advanced Custom Fields is required.</div><?php endif; ?>

  <form action="<?php echo esc_url(add_query_arg([])); ?>" method="post" onsubmit="return confirmDelete(event)" novalidate>
    <?php wp_nonce_field('site_form_save','site_form_nonce'); ?>
    <input type="hidden" name="site_post_id" value="<?php echo esc_attr($site_id); ?>" />

    <div class="f req">
      <label class="lab" for="site_name">Site Name</label>
      <input class="in" id="site_name" name="site_name" type="text" required value="<?php echo esc_attr($site_name_v !== '' ? $site_name_v : $site_title); ?>" />
    </div>

    <div class="f">
      <label class="lab" for="location">Address</label>
      <input class="in" id="location" name="location" type="text" value="<?php echo esc_attr($location_v); ?>" />
    </div>

   <?php
// Build the selected contact + label for edit state
$selected_contact = '';
$selected_client  = '';
if (!empty($client_selected_id)) {
  $selected_client = get_the_title($client_selected_id) ?: 'Untitled';
  if (function_exists('get_field')) {
    $g = (array) get_field('primary_contact', $client_selected_id);
    if (!empty($g['name'])) {
      $selected_contact = trim((string) $g['name']);
    } elseif (!empty($g['first_name']) || !empty($g['last_name'])) {
      $selected_contact = trim(((string)($g['first_name'] ?? '')).' '.((string)($g['last_name'] ?? '')));
    }
  }
}
$display_current = $selected_contact ? ($selected_contact . ' — ' . $selected_client) : ($selected_client ?: '— Select —');

$combo_id   = esc_attr($client_post_var) . '-combo';
$panel_id   = esc_attr($client_post_var) . '-panel';
$search_id  = esc_attr($client_post_var) . '-search';
$list_id    = esc_attr($client_post_var) . '-list';
?>
<style>
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
  .opt-sub{font-size:13px; color:#6b7280; margin-top:2px}
</style>

<div class="f">
  <label class="lab" id="<?php echo $combo_id; ?>-label" for="<?php echo $combo_id; ?>">Property Manager</label>

  <!-- Hidden field that actually POSTs the client ID (keeps your server-side code unchanged) -->
  <input type="hidden"
         id="<?php echo esc_attr($client_post_var); ?>"
         name="<?php echo esc_attr($client_post_var); ?>"
         value="<?php echo esc_attr($client_selected_id); ?>">

  <!-- Button that opens the dropdown -->
  <button type="button"
          class="combo-btn"
          id="<?php echo $combo_id; ?>"
          aria-haspopup="listbox"
          aria-expanded="false"
          aria-labelledby="<?php echo $combo_id; ?>-label <?php echo $combo_id; ?>">
    <span class="combo-current"><?php echo esc_html($display_current); ?></span>
    <svg class="caret" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </button>

  <!-- Dropdown panel -->
  <div class="combo-panel" id="<?php echo $panel_id; ?>" hidden>
    <input id="<?php echo $search_id; ?>" class="combo-search" type="text" placeholder="Search by contact name…" autocomplete="off">
    <ul id="<?php echo $list_id; ?>" class="combo-list" role="listbox" aria-labelledby="<?php echo $combo_id; ?>-label">
      <?php foreach ($clients_list as [$cid, $ctitle]): ?>
        <?php
          $ctitle = $ctitle ?: 'Untitled';
          $contact = '';
          if (function_exists('get_field')) {
            $grp = (array) get_field('primary_contact', $cid);
            if (!empty($grp['name'])) {
              $contact = trim((string) $grp['name']);
            } elseif (!empty($grp['first_name']) || !empty($grp['last_name'])) {
              $contact = trim(((string)($grp['first_name'] ?? '')).' '.((string)($grp['last_name'] ?? '')));
            }
          }
          $label_main = $contact ?: $ctitle;
          $label_sub  = $contact ? $ctitle : '';
        ?>
        <li class="combo-option"
            role="option"
            tabindex="-1"
            data-id="<?php echo esc_attr($cid); ?>"
            data-contact="<?php echo esc_attr($label_main); ?>"
            data-client="<?php echo esc_attr($ctitle); ?>">
          <div class="opt-main"><?php echo esc_html($label_main); ?></div>
          <?php if ($label_sub): ?><div class="opt-sub"><?php echo esc_html($label_sub); ?></div><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<script>
(function(){
  var btn     = document.getElementById('<?php echo $combo_id; ?>');
  var panel   = document.getElementById('<?php echo $panel_id; ?>');
  var search  = document.getElementById('<?php echo $search_id; ?>');
  var list    = document.getElementById('<?php echo $list_id; ?>');
  var hidden  = document.getElementById('<?php echo esc_js($client_post_var); ?>');
  var current = btn ? btn.querySelector('.combo-current') : null;

  if (!btn || !panel || !search || !list || !hidden || !current) return;

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
  function togglePanel(){
    if (panel.hidden) openPanel(); else closePanel();
  }
  function visibleOptions(){
    return options.filter(function(o){ return o.style.display !== 'none'; });
  }
  function clearActive(){
    options.forEach(function(o){ o.classList.remove('is-active'); o.removeAttribute('aria-selected'); });
  }
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
    var id     = opt.getAttribute('data-id') || '0';
    var name   = opt.getAttribute('data-contact') || '';
    var client = opt.getAttribute('data-client') || '';
    hidden.value = id;
    current.textContent = (name && client) ? (name + ' — ' + client) : (name || client || '— Select —');
    closePanel();
    btn.focus();
  }
  function filter(q){
    var qq = (q || '').trim().toLowerCase();
    var any = false;
    options.forEach(function(o){
      var contact = (o.getAttribute('data-contact') || '').toLowerCase();
      var client  = (o.getAttribute('data-client')  || '').toLowerCase();
      // Search prefers contact name; still matches client title
      var match = contact.indexOf(qq) !== -1 || client.indexOf(qq) !== -1;
      o.style.display = match ? '' : 'none';
      if (match) any = true;
    });
    list.querySelectorAll('.combo-empty').forEach(function(n){ n.remove(); });
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
    if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) {
      closePanel();
    }
  });

  search.addEventListener('input', function(){ filter(this.value); });

  search.addEventListener('keydown', function(e){
    var vis = visibleOptions();
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveByIndex(activeIndex + 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveByIndex(activeIndex - 1);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIndex >= 0) selectOption(visibleOptions()[activeIndex]);
    } else if (e.key === 'Escape') {
      closePanel();
      btn.focus();
    }
  });

  list.addEventListener('click', function(e){
    var li = e.target.closest('.combo-option');
    if (li) selectOption(li);
  });

  // Optional: enforce selection on submit
  var form = btn.closest('form');
  if (form) {
    form.addEventListener('submit', function(e){
      if (!hidden.value || hidden.value === '0') {
        e.preventDefault();
        openPanel();
        search.setCustomValidity('Please choose a client');
        search.reportValidity();
        setTimeout(function(){ search.setCustomValidity(''); }, 1500);
      }
    });
  }
})();
</script>


    <div class="f">
      <label class="lab" for="site_notes">Site Notes</label>
      <textarea class="ta" id="site_notes" name="site_notes"><?php echo esc_textarea($site_notes_v); ?></textarea>
    </div>

    <div class="f actions">
      <button type="submit" name="save_site" class="btn">Save Site</button>
      <button type="submit" name="delete_site" class="btn btn-delete">Delete Site</button>
    </div>
  </form>
<?php endif; ?>

<script>
function confirmDelete(e){
  if(e.submitter && e.submitter.name === 'delete_site'){
    return confirm('Are you sure you want to delete this site? This action cannot be undone.');
  }
  return true;
}
</script>
