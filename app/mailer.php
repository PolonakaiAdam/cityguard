<?php
// mailer.php – Email küldés Gmail SMTP-n keresztül (külső könyvtár nélkül)
// Követelmény: PHP openssl kiterjesztés, Gmail App Password

function cg_email_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cg_email_button(string $href, string $label, string $bg = '#1d4ed8'): string {
    $safeHref  = cg_email_escape($href);
    $safeLabel = cg_email_escape($label);

    return "<table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%' style='margin:0 auto;max-width:340px'>
      <tr>
        <td align='center' bgcolor='{$bg}' style='border-radius:14px;background:{$bg};box-shadow:0 12px 24px rgba(8,47,73,0.28)'>
          <a href='{$safeHref}' style='display:block;padding:16px 22px;font-family:Arial,sans-serif;font-size:17px;line-height:24px;font-weight:700;letter-spacing:0.2px;color:#ffffff;text-decoration:none;border-radius:14px'>
            {$safeLabel}
          </a>
        </td>
      </tr>
    </table>";
}

function cg_email_info_rows(array $rows): string {
    $html = "<table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%' style='border-collapse:separate;border-spacing:0 12px'>";

    foreach ($rows as $row) {
        $label = cg_email_escape((string)($row['label'] ?? ''));
        $value = cg_email_escape((string)($row['value'] ?? ''));

        $html .= "<tr>
          <td style='background:#0b1220;border:1px solid #1e293b;border-radius:14px;padding:14px 16px'>
            <div style='font-family:Arial,sans-serif;font-size:12px;line-height:16px;font-weight:700;letter-spacing:0.7px;text-transform:uppercase;color:#7dd3fc;margin-bottom:6px'>{$label}</div>
            <div style='font-family:Arial,sans-serif;font-size:18px;line-height:26px;font-weight:700;color:#e2e8f0;word-break:break-word'>{$value}</div>
          </td>
        </tr>";
    }

    $html .= '</table>';
    return $html;
}

function cg_render_email_layout(array $options): string {
    $preheader  = trim((string)($options['preheader'] ?? 'CityGuard értesítés'));
    $eyebrow    = trim((string)($options['eyebrow'] ?? 'CityGuard értesítés'));
    $title      = trim((string)($options['title'] ?? 'CityGuard'));
    $intro      = trim((string)($options['intro'] ?? ''));
    $bodyHtml   = (string)($options['body_html'] ?? '');
    $footerNote = trim((string)($options['footer_note'] ?? 'Ez egy automatikus CityGuard üzenet.'));

    $safePreheader = cg_email_escape($preheader);
    $safeEyebrow   = cg_email_escape($eyebrow);
    $safeTitle     = cg_email_escape($title);
    $safeIntro     = nl2br(cg_email_escape($intro));
    $safeFooter    = cg_email_escape($footerNote);
    $logoUrl       = cg_email_escape(public_url('assets/icons/favicon-64.png'));
    $logoAlt       = cg_email_escape('CityGuard logo');

    return "<!doctype html>
<html lang='hu'>
  <head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='x-apple-disable-message-reformatting'>
    <title>{$safeTitle}</title>
  </head>
  <body style='margin:0;padding:0;background:#060b16'>
    <div style='display:none;max-height:0;overflow:hidden;opacity:0;color:transparent'>{$safePreheader}</div>
    <table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%' style='width:100%;border-collapse:collapse;background:#060b16'>
      <tr>
        <td align='center' style='padding:24px 12px'>
          <table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%' style='width:100%;max-width:620px;border-collapse:collapse'>
            <tr>
              <td style='padding:0 0 14px 0'>
                <table role='presentation' cellpadding='0' cellspacing='0' border='0' width='100%' style='border-collapse:collapse'>
