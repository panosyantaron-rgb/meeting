<?php
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' https://fonts.googleapis.com; font-src https://fonts.googleapis.com https://fonts.gstatic.com; img-src \'self\' data:; script-src \'self\'; form-action \'self\'; base-uri \'self\'');
start_session();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

function redirect_self(): void
{
    header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
    exit;
}

function flash(string $msg, bool $error = false): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'error' => $error];
}

$data = store_read();
$hasPassword = !empty($data['settings']['password_hash']);
$loggedIn = $hasPassword && !empty($_SESSION['admin']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $do = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        flash('Էջը հնացել էր, փորձիր նորից։', true);
        redirect_self();
    }

    if ($do === 'setup' && !$hasPassword) {
        $pass = (string) ($_POST['password'] ?? '');
        $email = trim((string) ($_POST['email'] ?? ''));
        if (strlen($pass) < 6) {
            flash('Գաղտնաբառը պետք է լինի առնվազն 6 նիշ։', true);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Մեյլի հասցեն ճիշտ չէ։', true);
        } else {
            $ok = store_tx(function (array &$d) use ($pass, $email) {
                if (!empty($d['settings']['password_hash'])) {
                    return false;
                }
                $d['settings']['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                $d['settings']['email'] = $email;
                return true;
            });
            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
            }
        }
        redirect_self();
    }

    if ($do === 'login' && $hasPassword) {
        $pass = (string) ($_POST['password'] ?? '');
        $now = time();
        $res = store_tx(function (array &$d) use ($pass, $now) {
            $s = &$d['settings'];
            if (($s['lock_until'] ?? 0) > $now) {
                return 'locked';
            }
            if (password_verify($pass, (string) $s['password_hash'])) {
                $s['fails'] = 0;
                return 'ok';
            }
            $s['fails'] = (int) ($s['fails'] ?? 0) + 1;
            if ($s['fails'] >= 8) {
                $s['fails'] = 0;
                $s['lock_until'] = $now + 600;
            }
            return 'bad';
        });
        if ($res === 'ok') {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
        } elseif ($res === 'locked') {
            flash('Շատ սխալ փորձեր։ Փորձիր 10 րոպեից։', true);
        } else {
            usleep(600000);
            flash('Գաղտնաբառը սխալ է։', true);
        }
        redirect_self();
    }

    if (!$loggedIn) {
        redirect_self();
    }

    if ($do === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect_self();
    }

    if ($do === 'create') {
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? '')) ?? '');
        if ($name === '' || mb_strlen($name) > 40) {
            flash('Գրիր անունը (մինչև 40 նիշ)։', true);
        } elseif (preg_match('/[<>"\']/', $name)) {
            flash('Անունը չի կարող պարունակել հատուկ նիշեր (<>"\')', true);
        } else {
            store_tx(function (array &$d) use ($name) {
                do {
                    $token = bin2hex(random_bytes(6));
                } while (isset($d['invites'][$token]));
                $d['invites'][$token] = ['name' => $name, 'created_at' => time(), 'opens' => 0];
                return null;
            });
            flash('Հրավերը ստեղծված է՝ ' . $name . '։ Պատճենիր հղումը և ուղարկիր։');
        }
        redirect_self();
    }

    if ($do === 'reset') {
        $token = (string) ($_POST['token'] ?? '');
        store_tx(function (array &$d) use ($token) {
            if (isset($d['invites'][$token])) {
                $old = $d['invites'][$token];
                $d['invites'][$token] = ['name' => $old['name'], 'created_at' => $old['created_at'] ?? time(), 'opens' => 0];
            }
            return null;
        });
        flash('Հրավերը զրոյացված է. կարելի է նորից բացել և պատասխանել։');
        redirect_self();
    }

    if ($do === 'delete') {
        $token = (string) ($_POST['token'] ?? '');
        store_tx(function (array &$d) use ($token) {
            unset($d['invites'][$token]);
            return null;
        });
        flash('Հրավերը ջնջված է։');
        redirect_self();
    }

    if ($do === 'blocked') {
        $today = date('Y-m-d');
        $dates = [];
        foreach (explode(',', (string) ($_POST['dates'] ?? '')) as $d) {
            $d = trim($d);
            $dt = DateTime::createFromFormat('!Y-m-d', $d);
            if ($dt && $dt->format('Y-m-d') === $d && $d >= $today) {
                $dates[$d] = true;
            }
        }
        $dates = array_slice(array_keys($dates), 0, 400);
        sort($dates);
        store_tx(function (array &$d) use ($dates) {
            $d['settings']['blocked_dates'] = $dates;
            return null;
        });
        flash($dates ? 'Պահպանված է. ոչ հարմար օրեր՝ ' . count($dates) . '։' : 'Պահպանված է. ոչ հարմար օրեր չկան։');
        redirect_self();
    }

    if ($do === 'settings') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Մեյլի հասցեն ճիշտ չէ։', true);
        } elseif ($pass !== '' && strlen($pass) < 6) {
            flash('Գաղտնաբառը պետք է լինի առնվազն 6 նիշ։', true);
        } else {
            store_tx(function (array &$d) use ($email, $pass) {
                $d['settings']['email'] = $email;
                if ($pass !== '') {
                    $d['settings']['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
                }
                return null;
            });
            flash('Կարգավորումները պահպանված են։');
        }
        redirect_self();
    }

    if ($do === 'testmail') {
        $to = (string) ($data['settings']['email'] ?? '');
        $ok = send_mail($to, 'Փորձնական նամակ', "Եթե կարդում ես սա, ուրեմն կայքը կարողանում է նամակ ուղարկել։\n");
        flash($ok ? 'Փորձնական նամակն ուղարկվեց ' . $to . ' հասցեին։ Ստուգիր նաև Spam-ը։' : 'Չստացվեց նամակ ուղարկել։ Ստուգիր cPanel-ի մեյլի կարգավորումները։', !$ok);
        redirect_self();
    }

    redirect_self();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$root = site_root_url(1);
$invites = $data['invites'];
uasort($invites, fn ($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
$heart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z"/></svg>';
?>
<!doctype html>
<html lang="hy">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Հրավերներ · Ադմին</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Armenian:wght@400;500;700;800&amp;family=Noto+Sans:wght@400;500;700;800&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css?v=3">
<link rel="stylesheet" href="../assets/admin.css?v=3">
</head>
<body class="admin">
<main class="wrap">

<?php if ($flash): ?>
  <p class="flash<?= $flash['error'] ? ' flash-error' : '' ?>" role="status"><?= h($flash['msg']) ?></p>
<?php endif; ?>

<?php if (!$hasPassword): ?>
  <section class="panel auth">
    <div class="badge"><?= $heart ?></div>
    <h1 class="h-md">Առաջին կարգավորում</h1>
    <p class="sub">Դիր ադմին պանելի գաղտնաբառը և մեյլը, որին կգան պատասխանները։</p>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="setup">
      <label for="su-pass">Գաղտնաբառ</label>
      <input id="su-pass" name="password" type="password" minlength="6" required autocomplete="new-password">
      <label for="su-email">Մեյլ պատասխանների համար</label>
      <input id="su-email" name="email" type="email" required autocomplete="email">
      <button type="submit" class="btn btn-primary">Պահպանել</button>
    </form>
  </section>

<?php elseif (!$loggedIn): ?>
  <section class="panel auth">
    <div class="badge"><?= $heart ?></div>
    <h1 class="h-md">Մուտք</h1>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="login">
      <label for="li-pass">Գաղտնաբառ</label>
      <input id="li-pass" name="password" type="password" required autofocus autocomplete="current-password">
      <button type="submit" class="btn btn-primary">Մտնել</button>
    </form>
  </section>

<?php else: ?>
  <header class="top">
    <div class="top-title">
      <div class="badge badge-sm"><?= $heart ?></div>
      <h1>Հրավերներ</h1>
    </div>
    <div class="top-actions">
      <a class="btn-sm" href="./">Թարմացնել</a>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="do" value="logout">
        <button type="submit" class="btn-sm">Ելք</button>
      </form>
    </div>
  </header>

  <section class="panel">
    <h2>Նոր հրավեր</h2>
    <form method="post" class="create">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="create">
      <div class="grow">
        <label for="inv-name">Ում համար է հրավերը</label>
        <input id="inv-name" name="name" type="text" maxlength="40" required placeholder="Օրինակ՝ Անի" autocomplete="off">
      </div>
      <button type="submit" class="btn btn-primary">Ստեղծել հրավեր</button>
    </form>
  </section>

  <?php if (!$invites): ?>
    <p class="empty">Դեռ հրավեր չկա։ Գրիր անունը վերևում և ստեղծիր առաջինը։</p>
  <?php endif; ?>

  <?php foreach ($invites as $token => $inv):
      $link = $root . '?i=' . $token;
      $opens = (int) ($inv['opens'] ?? 0);
      $answer = $inv['answer'] ?? null;
      if ($answer) {
          $status = ['Պատասխանել է', 'st-done'];
      } elseif (!empty($inv['said_yes_at'])) {
          $status = ['Ասել է «այո», դեռ չի ավարտել', 'st-open'];
      } elseif ($opens > 0) {
          $status = ['Բացել է, դեռ չի պատասխանել', 'st-open'];
      } else {
          $status = ['Դեռ չի բացել', 'st-new'];
      }
  ?>
  <article class="panel invite">
    <div class="invite-head">
      <div class="invite-name">
        <span class="name"><?= h($inv['name']) ?></span>
        <span class="status <?= $status[1] ?>"><?= h($status[0]) ?></span>
      </div>
      <form method="post" onsubmit="return confirm('Ջնջե՞լ այս հրավերը։');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="do" value="delete">
        <input type="hidden" name="token" value="<?= h((string) $token) ?>">
        <button type="submit" class="icon-btn" aria-label="Ջնջել հրավերը՝ <?= h($inv['name']) ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
        </button>
      </form>
    </div>

    <div class="link-row">
      <input class="link" type="text" readonly value="<?= h($link) ?>" aria-label="Հրավերի հղումը" onfocus="this.select()">
      <button type="button" class="btn-sm" data-copy="<?= h($link) ?>">Պատճենել հղումը</button>
      <a class="btn-sm" href="<?= h($link) ?>&amp;preview=1" target="_blank" rel="noopener">Նախադիտել</a>
      <?php if ($opens > 0 || $answer): ?>
      <form method="post" onsubmit="return confirm('Զրոյացնե՞լ բացումներն ու պատասխանը։');">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="do" value="reset">
        <input type="hidden" name="token" value="<?= h((string) $token) ?>">
        <button type="submit" class="btn-sm">Զրոյացնել</button>
      </form>
      <?php endif; ?>
    </div>

    <dl class="facts">
      <div>
        <dt>Բացել է հղումը</dt>
        <?php if ($opens > 0): ?>
          <dd>Այո · <?= $opens ?> անգամ</dd>
          <dd class="small">Առաջին անգամ՝ <?= h(stamp_hy((int) ($inv['first_opened_at'] ?? 0))) ?></dd>
          <?php if ($opens > 1): ?><dd class="small">Վերջին անգամ՝ <?= h(stamp_hy((int) ($inv['last_opened_at'] ?? 0))) ?></dd><?php endif; ?>
        <?php else: ?>
          <dd>Ոչ</dd>
        <?php endif; ?>
      </div>
      <?php if ($answer): ?>
        <div><dt>Ի՞նչ</dt><dd><?= h(activities_hy((array) $answer['activities'])) ?></dd></div>
        <div><dt>Ե՞րբ</dt><dd><?= h(date_hy((string) $answer['date'])) ?> · <?= h((string) $answer['time']) ?></dd>
          <dd class="small">Պատասխանել է՝ <?= h(stamp_hy((int) ($inv['answered_at'] ?? 0))) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($inv['lang'])): ?>
        <div><dt>Լեզուն</dt><dd><?= h(LANG_HY[$inv['lang']] ?? '') ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($inv['said_yes_at'])): ?>
        <div><dt>«Ոչ»-ը փորձել է սեղմել</dt><dd><?= (int) ($inv['no_tries'] ?? 0) ?> անգամ</dd></div>
      <?php endif; ?>
    </dl>
    <?php if ($answer && isset($inv['mail_sent']) && !$inv['mail_sent']): ?>
      <p class="warn">Այս պատասխանի նամակը չուղարկվեց։ Ստուգիր մեյլի կարգավորումները ներքևում։</p>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>

  <section class="panel busy">
    <h2>Ինձ ոչ հարմար օրեր</h2>
    <p class="hint">Սեղմիր այն օրերի վրա, որոնք քեզ հարմար չեն, և պահպանիր։ Հրավերում այդ օրերը հնարավոր չի լինի ընտրել։</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="blocked">
      <input type="hidden" name="dates" id="busy-dates" value="<?= h(implode(',', blocked_dates($data))) ?>">
      <div class="cal busy-cal">
        <div class="cal-head">
          <span class="cal-month" id="busy-month" aria-live="polite"></span>
          <div class="cal-nav">
            <button type="button" class="icon-btn" id="busy-prev" aria-label="Նախորդ ամիս"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
            <button type="button" class="icon-btn" id="busy-next" aria-label="Հաջորդ ամիս"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
          </div>
        </div>
        <div class="cal-wd" aria-hidden="true"><div>Երկ</div><div>Երք</div><div>Չրք</div><div>Հնգ</div><div>Ուրբ</div><div>Շբթ</div><div>Կիր</div></div>
        <div class="cal-grid" id="busy-grid" data-today="<?= h(date('Y-m-d')) ?>"></div>
      </div>
      <p class="busy-count" id="busy-count"></p>
      <button type="submit" class="btn btn-primary">Պահպանել օրերը</button>
    </form>
  </section>

  <details class="panel settings">
    <summary>Կարգավորումներ</summary>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="settings">
      <label for="st-email">Մեյլ պատասխանների համար</label>
      <input id="st-email" name="email" type="email" required value="<?= h((string) ($data['settings']['email'] ?? '')) ?>">
      <label for="st-pass">Նոր գաղտնաբառ (թող դատարկ, եթե չես փոխում)</label>
      <input id="st-pass" name="password" type="password" minlength="6" autocomplete="new-password">
      <button type="submit" class="btn btn-primary">Պահպանել</button>
    </form>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="do" value="testmail">
      <button type="submit" class="btn btn-ghost">Ուղարկել փորձնական նամակ</button>
    </form>
  </details>

  <script>
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) { return; }
    var text = b.getAttribute('data-copy'), old = b.textContent;
    function done() { b.textContent = 'Պատճենված է'; setTimeout(function () { b.textContent = old; }, 1600); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, fallback);
    } else { fallback(); }
    function fallback() {
      var i = b.parentNode.querySelector('.link');
      i.focus(); i.select();
      try { document.execCommand('copy'); done(); } catch (err) {}
    }
  });
  (function () {
    var grid = document.getElementById('busy-grid');
    var input = document.getElementById('busy-dates');
    var months = ['Հունվար', 'Փետրվար', 'Մարտ', 'Ապրիլ', 'Մայիս', 'Հունիս', 'Հուլիս', 'Օգոստոս', 'Սեպտեմբեր', 'Հոկտեմբեր', 'Նոյեմբեր', 'Դեկտեմբեր'];
    var todayStr = grid.getAttribute('data-today');
    var tp = todayStr.split('-');
    var today = { y: +tp[0], m: +tp[1] - 1 };
    var view = { y: today.y, m: today.m };
    var MAX_AHEAD = 6;
    var set = {};
    input.value.split(',').forEach(function (d) { if (d) { set[d] = true; } });

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function offset() { return (view.y - today.y) * 12 + (view.m - today.m); }
    function sync() {
      var list = Object.keys(set).sort();
      input.value = list.join(',');
      document.getElementById('busy-count').textContent = list.length ? 'Նշված է ' + list.length + ' օր' : 'Դեռ օր չի նշված';
    }
    function render() {
      document.getElementById('busy-month').textContent = months[view.m] + ' ' + view.y;
      grid.innerHTML = '';
      var first = (new Date(view.y, view.m, 1).getDay() + 6) % 7;
      var count = new Date(view.y, view.m + 1, 0).getDate();
      for (var d = 1; d <= count; d++) {
        (function (d) {
          var s = view.y + '-' + pad(view.m + 1) + '-' + pad(d);
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'day' + (set[s] ? ' blocked' : '') + (s === todayStr ? ' today' : '');
          b.textContent = d;
          if (d === 1) { b.style.gridColumnStart = first + 1; }
          if (s < todayStr) { b.disabled = true; }
          b.setAttribute('aria-pressed', set[s] ? 'true' : 'false');
          b.addEventListener('click', function () {
            if (set[s]) { delete set[s]; } else { set[s] = true; }
            sync(); render();
          });
          grid.appendChild(b);
        })(d);
      }
      document.getElementById('busy-prev').disabled = offset() <= 0;
      document.getElementById('busy-next').disabled = offset() >= MAX_AHEAD;
    }
    document.getElementById('busy-prev').addEventListener('click', function () {
      if (offset() <= 0) { return; }
      view.m--; if (view.m < 0) { view.m = 11; view.y--; }
      render();
    });
    document.getElementById('busy-next').addEventListener('click', function () {
      if (offset() >= MAX_AHEAD) { return; }
      view.m++; if (view.m > 11) { view.m = 0; view.y++; }
      render();
    });
    sync(); render();
  })();
  </script>
<?php endif; ?>

</main>
</body>
</html>
