<?php
/**
 * Build the PMC “New Report Available” email HTML.
 * Pass whatever you have; sensible fallbacks are provided.
 */
function pmc_report_email_template_html(array $args = []): string {
  $defaults = [
    // Dynamic content
    'site_title'      => '',      // e.g. get_the_title($site_id)
    'view_url'        => '',      // required for the primary CTA
    'pdf_url'         => '',      // optional – hides button when empty
    'contact_url'     => '',      // optional – text link at the bottom of the card

    // Branding & chrome
    'logo_url'        => 'https://pmc-portal.blacksheep-creative.co.uk/wp-content/uploads/2025/07/WhatsApp-Image-2025-05-01-at-12.11.53.jpeg',
    'company_domain'  => 'pmc-contractinguk.co.uk',
    'company_url'     => 'https://pmc-contractinguk.co.uk',
    'company_address' => '53 West Street, Sittingbourne, Kent ME10 1AN',
    'copyright_line'  => '© ' . gmdate('Y') . ' PMC Contracting (UK)',

    // Social links (icons are optional – supply white glyphs if desired)
    'social_twitter_href'   => '',
    'social_facebook_href'  => '',
    'social_instagram_href' => '',
    'social_twitter_img'    => '',  // e.g. absolute URL to a white twitter icon PNG
    'social_facebook_img'   => '',
    'social_instagram_img'  => '',
  ];
  $a = array_merge($defaults, $args);

  // Colors & layout
  $brand_teal  = '#0f3a47';
  $border_teal = '#0f3a47';
  $text_main   = '#0f313a';
  $text_muted  = '#3d565f';
  $bg_page     = '#f5f7f9';

  ob_start(); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New Report Available</title>
</head>
<body style="margin:0;padding:0;background:<?php echo $bg_page; ?>;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $bg_page; ?>;">
    <tr>
      <td align="center">

        <!-- Top bar with logo -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $brand_teal; ?>;">
          <tr>
            <td align="center" style="padding:16px 12px">
              <?php if (!empty($a['logo_url'])): ?>
                <img src="<?php echo esc_url($a['logo_url']); ?>" alt="PMC Contracting (UK)" style="display:block;height:36px;width:auto;border:0;outline:none;text-decoration:none;">
              <?php endif; ?>
            </td>
          </tr>
        </table>

        <!-- Card -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:446px;background:#ffffff;margin:0 auto;border-left:2px solid <?php echo $brand_teal; ?>;border-right:2px solid <?php echo $brand_teal; ?>;">
          <tr>
            <td style="padding:50px 22px 10px 22px;text-align:center">
              <!-- Heading -->
              <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;font-weight:800;font-size:24px;line-height:1.15;color:#111827;margin:0 0 10px">
                New Report Available
              </div>
              <!-- Intro copy -->
              <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;color:<?php echo $text_muted; ?>;font-size:16px;line-height:1.6;margin:8px 0 22px">
                A new report has been generated for <?php
                  echo $a['site_title'] ? ' <strong style="color:#111827">'.esc_html($a['site_title']).'</strong>' : '';
                ?>. Please follow the link to view it.
              </div>

              <!-- Primary CTA -->
              <?php if (!empty($a['view_url'])): ?>
                <a href="<?php echo esc_url($a['view_url']); ?>"
                   style="display:inline-block;background:<?php echo $brand_teal; ?>;color:#ffffff;text-decoration:none;font-weight:700;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;font-size:16px;line-height:1;padding:16px 28px;border-radius:999px;margin:2px 0 16px 0;">
                  View Report
                </a>
              <?php endif; ?>

              <!-- Secondary CTA -->
              <?php if (!empty($a['pdf_url'])): ?>
                <div style="margin:6px 0 18px 0">
                  <a href="<?php echo esc_url($a['pdf_url']); ?>"
                     style="display:inline-block;background:#ffffff;color:<?php echo $brand_teal; ?>;text-decoration:none;font-weight:700;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;font-size:16px;line-height:1;padding:14px 26px;border-radius:999px;border:2px solid <?php echo $border_teal; ?>;">
                    Download PDF
                  </a>
                </div>
              <?php endif; ?>

              <?php if (!empty($a['contact_url'])): ?>
                <div style="margin:20px 0 50px">
                  <a href="<?php echo esc_url($a['contact_url']); ?>"
                     style="color:<?php echo $brand_teal; ?>;text-decoration:underline;font-weight:700;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;">
                    Contact PMC
                  </a>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        </table>

        <!-- Footer -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $brand_teal; ?>;">
          <tr>
            <td align="center" style="padding:18px 12px 10px 12px">

              <!-- Domain -->
              <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;margin:10px 0 6px">
                <a href="<?php echo esc_url($a['company_url']); ?>" style="color:#ffffff;text-decoration:none">
                  <?php echo esc_html($a['company_domain']); ?>
                </a>
              </div>

              <!-- Address -->
              <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;color:#d1e1e7;font-size:12px;margin:2px 0">
                <?php echo esc_html($a['company_address']); ?>
              </div>

              <!-- Copyright -->
              <div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial,sans-serif;color:#d1e1e7;font-size:12px;margin:2px 0 18px">
                <?php echo esc_html($a['copyright_line']); ?>
              </div>

            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>
</html>
<?php
  return ob_get_clean();
}
