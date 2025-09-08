<?php
/**
 * Template Name: Add Site (ACF – Safe POST name + No Canonical on Submit)
 */
defined('ABSPATH') || exit;

/** ACF logical names + the KEY for the Post Object field */
$acf_site_name   = 'site_name';
$acf_location    = 'location';
$acf_client_name = 'client';                 // logical meta name (we still save by KEY)
$acf_site_notes  = 'site_notes';
$acf_key_client  = 'field_687ec0391cc00';    // KEY of the "Client" Post Object on the Site field group

/** Use a unique POST name to avoid collisions */
$client_post_var = 'site_client_id';         // <select name="site_client_id">

/** Debug flags */
$SHOW_DEBUG_SCREEN_ON_SUCCESS = false;       // set true to see a panel instead of redirect
$FORCE_PUBLISH                = true;        // publish so single is viewable if routing allows
$PREFER_PERMALINK             = true;        // try pretty permalink, else fallback

/** Helpers */
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

/** Capability */
$ptype   = get_post_type_object('site');
$can_add = $ptype && !empty($ptype->cap->create_posts) ? current_user_can($ptype->cap->create_posts) : current_user_can('publish_posts');

/** ⛔️ Stop canonical redirects on this POST (prevents 404s caused by WP redirecting POSTs) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_form_nonce'])) {
  add_filter('redirect_canonical', '__return_false', 99);
}

$errs = [];
$site_name_v = $location_v = $site_notes_v = '';
$client_selected_id = 0;

/** Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['site_form_nonce']) || !wp_verify_nonce($_POST['site_form_nonce'], 'site_form_save')) {
    $errs[] = 'Security check failed.';
  } elseif (!$can_add) {
    $errs[] = 'You do not have permission to add sites.';
  } else {
    $site_name_v        = isset($_POST['site_name'])        ? wp_strip_all_tags($_POST['site_name']) : '';
    $location_v         = isset($_POST['location'])         ? sanitize_text_field($_POST['location']) : '';
    $client_selected_id = isset($_POST[$client_post_var])   ? absint($_POST[$client_post_var]) : 0;  // <-- renamed POST var
    $site_notes_v       = isset($_POST['site_notes'])       ? wp_kses_post($_POST['site_notes']) : '';

    if ($site_name_v === '') { $errs[] = 'Site name is required.'; }

    if (empty($errs)) {
      $target_status = $FORCE_PUBLISH ? 'publish' : 'draft';
      $new_id = wp_insert_post([
        'post_type'   => 'site',
        'post_status' => $target_status,
        'post_title'  => $site_name_v,
        'post_author' => get_current_user_id(),
      ], true);

      if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        if (is_wp_error($new_id)) error_log('[Add Site] insert error: ' . $new_id->get_error_message());
        else error_log('[Add Site] inserted post_id='.(int)$new_id.' status='.get_post_status($new_id));
      }

      if (is_wp_error($new_id) || !$new_id) {
        $errs[] = 'Insert failed: ' . (is_wp_error($new_id) ? $new_id->get_error_message() : 'unknown');
      } else {
        if (!function_exists('update_field')) {
          $errs[] = 'Advanced Custom Fields is required.';
        } else {
          // Save simple fields
          update_field($acf_site_name,  $site_name_v,  $new_id);
          update_field($acf_location,   $location_v,   $new_id);
          update_field($acf_site_notes, $site_notes_v, $new_id);

          // Save Client by KEY (single ID; your ACF is Post Object, Return=ID, Multiple=off)
          if ($client_selected_id) {
            $client_ok = (bool) update_field($acf_key_client, $client_selected_id, $new_id);

            // Verify after ACF init
            if ($client_ok && function_exists('get_field') && did_action('acf/init')) {
              $saved_ids = _norm_ids_from_acf(get_field($acf_key_client, $new_id));
              if (!in_array($client_selected_id, $saved_ids, true)) {
                // Try array shape fallback
                $client_ok = (bool) update_field($acf_key_client, [$client_selected_id], $new_id);
                if ($client_ok) {
                  $saved_ids = _norm_ids_from_acf(get_field($acf_key_client, $new_id));
                  if (!in_array($client_selected_id, $saved_ids, true)) $client_ok = false;
                }
              }
            }

            if (!$client_ok) {
              // Meta fallback
              update_post_meta($new_id, '_client', $acf_key_client);
              update_post_meta($new_id, 'client',  $client_selected_id);
              if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Add Site] Meta fallback used for client. post='.(int)$new_id.' client='.(int)$client_selected_id);
              }
            }
          }
        }

        if (empty($errs)) {
          // Build redirect target; verify pretty resolves, else fallback
          $pretty         = $PREFER_PERMALINK ? get_permalink($new_id) : '';
          $resolved_id    = $pretty ? (function_exists('url_to_postid') ? url_to_postid($pretty) : 0) : 0;
          $needs_fallback = !$pretty || ((int)$resolved_id !== (int)$new_id);
          $fallback       = add_query_arg(['post_type'=>'site','p'=>$new_id,'updated'=>1], home_url('/'));

          if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf('[Add Site] new_id=%d client=%d permalink=%s resolved_id=%d needs_fallback=%s',
              (int)$new_id, (int)$client_selected_id, $pretty ?: '(empty)', (int)$resolved_id, $needs_fallback ? '1':'0'
            ));
          }

          if ($SHOW_DEBUG_SCREEN_ON_SUCCESS) {
            ?>
            <div class="note" style="padding:16px;border:1px solid #a7f3d0;background:#ecfdf5;border-radius:10px;margin:16px 0">
              <strong>Site created (diagnostic).</strong><br>
              ID: <?php echo (int)$new_id; ?>, Status: <?php echo esc_html(get_post_status($new_id)); ?><br>
              Pretty: <?php echo $pretty ? '<a href="'.esc_url($pretty).'">'.esc_html($pretty).'</a>' : '(empty)'; ?>
              <?php if ($pretty): ?>(resolves to: <?php echo (int)$resolved_id; ?>)<?php endif; ?><br>
              Fallback: <a href="<?php echo esc_url($fallback); ?>"><?php echo esc_html($fallback); ?></a><br>
              Client saved: <?php echo (int)$client_selected_id; ?>
            </div>
            <?php
            return;
          }

          if ($needs_fallback) {
            _site_safe_redirect($fallback);
          } else {
            _site_safe_redirect(add_query_arg(['updated'=>1], $pretty));
          }
        }
      }
    }
  }
}

/** Build Client options (label = primary contact's first name) */
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
    $client_title = get_the_title($cid) ?: 'Untitled';
    $first = '';

    if (function_exists('get_field')) {
      $group = (array) get_field('primary_contact', $cid);

      // Prefer an explicit first_name if it exists
      if (!empty($group['first_name'])) {
        $first = trim((string) $group['first_name']);
      }
      // Otherwise derive from a full "name" field
      elseif (!empty($group['name'])) {
        $full  = trim((string) $group['name']);
        if ($full !== '') {
          $parts = preg_split('/\s+/', $full, -1, PREG_SPLIT_NO_EMPTY);
          $first = $parts[0] ?? '';
        }
      }
    }

    // $label = $first !== '' ? $first : $client_title;

    // // If you'd rather show both for clarity, use:
    $label = $first !== '' ? ($first . ' — ' . $client_title) : $client_title;

    $clients_list[] = [$cid, $label];
  }
}
wp_reset_postdata();

?>
<style>
  .f{margin:0 0 22px}.lab{display:block;font-weight:600;margin:0 0 8px}
  .req .lab::after{content:" *";color:#e63946}
  .in,.ta,.sel{width:100%;border:1px solid #d7dfe7;border-radius:10px;padding:14px 16px;font-size:16px}
  .ta{min-height:160px;resize:vertical}
  .alert{padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fff1f2;margin:16px 0}
  .btn{display:inline-block;background:#1f2937;color:#fff;font-weight:600;padding:12px 22px;border-radius:10px;border:0;cursor:pointer;font-size:16px}
  .btn:hover{background:#111827}
  .note{padding:12px 14px;border:1px solid #a7f3d0;border-radius:10px;background:#ecfdf5;margin:16px 0}
</style>

<?php if (!empty($errs)): ?>
  <div class="alert">
    <strong>There was a problem:</strong>
    <ul style="margin:8px 0 0 18px">
      <?php foreach ($errs as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form action="<?php echo esc_url(add_query_arg([])); ?>" method="post" novalidate>
  <?php wp_nonce_field('site_form_save','site_form_nonce'); ?>

  <div class="f req">
    <label class="lab" for="site_name">Site Name</label>
    <input class="in" id="site_name" name="site_name" type="text" required value="<?php echo esc_attr($site_name_v); ?>" />
  </div>

  <div class="f">
    <label class="lab" for="location">Address</label>
    <input class="in" id="location" name="location" type="text" value="<?php echo esc_attr($location_v); ?>" />
  </div>

  <?php
// Build selected label (for edit state)
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

  <!-- Hidden field that actually POSTs the ID (keeps your save logic the same) -->
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
          $label_main = $contact ?: $ctitle; // show contact when present
          $label_sub  = $contact ? $ctitle : ''; // show client title as subline
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
        search.setCustomValidity('Please choose a property manager');
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

  <div class="f">
    <button type="submit" class="btn">Create Site</button>
  </div>
</form>
