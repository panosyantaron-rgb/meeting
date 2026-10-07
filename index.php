<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; connect-src \'self\'; font-src https://fonts.googleapis.com https://fonts.gstatic.com; style-src \'self\' https://fonts.googleapis.com; img-src \'self\' data:; script-src \'self\'; form-action \'self\'; base-uri \'self\'; frame-ancestors \'none\'');

if (session_status() === PHP_SESSION_NONE) {
    session_name('meet_session');
    session_start();
}
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}

$token = is_string($_GET['i'] ?? null) ? $_GET['i'] : '';
$inv = null;
$data = [];
if (valid_token($token)) {
    $data = store_read();
    $inv = $data['invites'][$token] ?? null;
}

$fonts = 'https://fonts.googleapis.com/css2?family=Noto+Sans+Armenian:wght@400;500;700;800&family=Noto+Sans:wght@400;500;700;800&display=swap';

if (!$inv) {
    http_response_code(404);
    ?>
<!doctype html>
<html lang="hy">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Հրավեր</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= h($fonts) ?>" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=4">
</head>
<body>
<main class="app">
  <section class="screen active center">
    <div class="badge badge-lg"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg></div>
    <h1>Հղումը չի գտնվել</h1>
    <p class="sub">Ссылка не найдена · This link was not found</p>
  </section>
</main>
</body>
</html>
<?php
    exit;
}

$cfg = [
    'token' => $token,
    'name' => (string) $inv['name'],
    'lang' => $inv['lang'] ?? null,
    'answer' => $inv['answer'] ?? null,
    'today' => date('Y-m-d'),
    'blocked' => blocked_dates($data),
    'preview' => isset($_GET['preview']) && is_admin(),
    'csrf' => $_SESSION['_csrf'],
];
$json = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!doctype html>
<html lang="hy">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#FFEEF1">
<title>Հրավեր քեզ համար</title>
<meta property="og:title" content="Հրավեր քեզ համար">
<meta property="og:description" content="Բացիր, ներսում մի հարց կա">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= h($fonts) ?>" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=5">
</head>
<body>
<script type="application/json" id="cfg"><?= $json ?></script>

<svg class="deco deco-1" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>
<svg class="deco deco-2" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>
<svg class="deco deco-3" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>

<?php if ($cfg['preview']): ?>
<p class="preview-note" role="status">Նախադիտում է. ոչինչ չի պահպանվում և նամակ չի ուղարկվում</p>
<?php endif; ?>
<main class="app" id="app">

  <!-- 1. Language -->
  <section class="screen" id="s-lang">
    <div class="badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg></div>
    <h1 class="h-xl">Ընտրիր լեզուն</h1>
    <p class="sub">Выбери язык · Choose a language</p>
    <div class="stack lang-list">
      <button type="button" class="btn btn-primary btn-row" data-lang="hy" lang="hy"><span>Հայերեն</span><svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
      <button type="button" class="btn btn-ghost btn-row" data-lang="ru" lang="ru"><span>Русский</span><svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
      <button type="button" class="btn btn-ghost btn-row" data-lang="en" lang="en"><span>English</span><svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
    </div>
    <div class="dots" aria-hidden="true"><i class="on"></i><i></i><i></i><i></i></div>
  </section>

  <!-- 2. The question -->
  <section class="screen" id="s-ask">
    <div class="ask-top">
      <div class="envelope">
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <rect class="env-body" x="6" y="18" width="52" height="36" rx="6"/>
          <path class="env-flap" d="M7 21l25 19 25-19"/>
          <path class="env-heart" d="M32 30s-9-5.4-9-12a5 5 0 0 1 9-3.2A5 5 0 0 1 41 18c0 6.6-9 12-9 12z"/>
        </svg>
      </div>
      <h1 class="h-lg center-text" id="ask-title"></h1>
    </div>
    <div class="ask-actions">
      <button type="button" class="btn btn-primary btn-yes" id="btn-yes"><span id="yes-label"></span><svg class="heart" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg></button>
      <span class="no-slot" id="no-slot"><button type="button" class="btn btn-ghost btn-no" id="btn-no"></button></span>
    </div>
    <div class="dots" aria-hidden="true"><i></i><i class="on"></i><i></i><i></i></div>
  </section>

  <!-- 3. What to do -->
  <section class="screen" id="s-what">
    <h1 class="h-md" id="what-title"></h1>
    <p class="sub" id="what-sub"></p>
    <div class="acts" id="acts"></div>
    <button type="button" class="btn btn-primary btn-main" id="btn-what-next" disabled></button>
    <div class="dots" aria-hidden="true"><i></i><i></i><i class="on"></i><i></i></div>
  </section>

  <!-- 4. Date and time -->
  <section class="screen" id="s-when">
    <h1 class="h-md" id="when-title"></h1>
    <p class="sub" id="when-sub"></p>
    <div class="cal">
      <div class="cal-head">
        <span class="cal-month" id="cal-month" aria-live="polite"></span>
        <div class="cal-nav">
          <button type="button" class="icon-btn" id="cal-prev"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
          <button type="button" class="icon-btn" id="cal-next"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
        </div>
      </div>
      <div class="cal-wd" id="cal-wd" aria-hidden="true"></div>
      <div class="cal-grid" id="cal-grid"></div>
    </div>
    <h2 class="h-sm" id="time-title"></h2>
    <div class="times" id="times"></div>
    <p class="error" id="send-error" role="alert" hidden></p>
    <button type="button" class="btn btn-primary btn-main" id="btn-send" disabled></button>
    <div class="dots" aria-hidden="true"><i></i><i></i><i></i><i class="on"></i></div>
  </section>

  <!-- 5. Ticket -->
  <section class="screen" id="s-done">
    <h1 class="h-xl center-text" id="done-title"></h1>
    <p class="sub center-text" id="done-sub"></p>
    <div class="ticket">
      <div class="ticket-top">
        <div>
          <span class="ticket-kicker" id="t-kicker"></span>
          <span class="ticket-title" id="t-title"></span>
        </div>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>
      </div>
      <div class="ticket-cut" aria-hidden="true"></div>
      <div class="ticket-body">
        <div class="field"><span class="field-k" id="t-what-k"></span><span class="field-v" id="t-what"></span></div>
        <div class="field-row">
          <div class="field"><span class="field-k" id="t-when-k"></span><span class="field-v" id="t-when"></span></div>
          <div class="field"><span class="field-k" id="t-time-k"></span><span class="field-v" id="t-time"></span></div>
        </div>
      </div>
    </div>
    <p class="sent"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg><span id="sent-label"></span></p>
  </section>

</main>
<script src="assets/app.js?v=5"></script>
</body>
</html>
