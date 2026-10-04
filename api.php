<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    out(['ok' => false, 'error' => 'method'], 405);
}

$in = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($in)) {
    out(['ok' => false, 'error' => 'input'], 400);
}

$action = is_string($in['action'] ?? null) ? $in['action'] : '';
$token = is_string($in['token'] ?? null) ? $in['token'] : '';
if (!valid_token($token)) {
    out(['ok' => false, 'error' => 'token'], 404);
}

// When you preview an invitation while logged in to the admin panel, nothing is recorded.
$preview = is_admin();
$now = time();
$mail = null;

$result = store_tx(function (array &$data) use ($action, $token, $in, $now, $preview, &$mail) {
    if (!isset($data['invites'][$token])) {
        return ['ok' => false, 'error' => 'token', 'code' => 404];
    }
    if ($preview) {
        return ['ok' => true, 'preview' => true];
    }
    $inv = &$data['invites'][$token];

    switch ($action) {
        case 'open':
            $inv['opens'] = (int) ($inv['opens'] ?? 0) + 1;
            $inv['first_opened_at'] = $inv['first_opened_at'] ?? $now;
            $inv['last_opened_at'] = $now;
            return ['ok' => true];

        case 'lang':
            $lang = $in['lang'] ?? '';
            if (!in_array($lang, LANGS, true)) {
                return ['ok' => false, 'error' => 'lang', 'code' => 400];
            }
            $inv['lang'] = $lang;
            return ['ok' => true];

        case 'yes':
            $inv['said_yes_at'] = $inv['said_yes_at'] ?? $now;
            $inv['no_tries'] = max(0, min(99, (int) ($in['tries'] ?? 0)));
            return ['ok' => true];

        case 'answer':
            if (!empty($inv['answer'])) {
                return ['ok' => true, 'answer' => $inv['answer']];
            }
            $acts = [];
            foreach ((array) ($in['activities'] ?? []) as $a) {
                if (is_string($a) && in_array($a, ACTIVITIES, true) && !in_array($a, $acts, true)) {
                    $acts[] = $a;
                }
            }
            $date = is_string($in['date'] ?? null) ? $in['date'] : '';
            $time = is_string($in['time'] ?? null) ? $in['time'] : '';
            $d = DateTime::createFromFormat('!Y-m-d', $date);
            $dateOk = $d && $d->format('Y-m-d') === $date && $date >= date('Y-m-d', $now - 86400);
            $timeOk = (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
            if (!$acts || !$dateOk || !$timeOk) {
                return ['ok' => false, 'error' => 'fields', 'code' => 400];
            }
            $inv['said_yes_at'] = $inv['said_yes_at'] ?? $now;
            $inv['answer'] = ['activities' => $acts, 'date' => $date, 'time' => $time];
            $inv['answered_at'] = $now;
            $mail = ['to' => (string) ($data['settings']['email'] ?? ''), 'inv' => $inv];
            return ['ok' => true, 'answer' => $inv['answer']];
    }
    return ['ok' => false, 'error' => 'action', 'code' => 400];
});

if ($mail && $mail['to'] !== '') {
    $inv = $mail['inv'];
    $body = "Հրավերին պատասխանել են։\n\n"
        . 'Անուն՝ ' . $inv['name'] . "\n"
        . 'Ի՞նչ՝ ' . activities_hy($inv['answer']['activities']) . "\n"
        . 'Ե՞րբ՝ ' . date_hy($inv['answer']['date']) . "\n"
        . 'Ժամը՝ ' . $inv['answer']['time'] . "\n"
        . 'Լեզուն՝ ' . (LANG_HY[$inv['lang'] ?? ''] ?? '—') . "\n"
        . '«Ոչ»-ը փորձել է սեղմել՝ ' . (int) ($inv['no_tries'] ?? 0) . " անգամ\n\n"
        . 'Ադմին պանել՝ ' . site_root_url() . "admin/\n";
    $sent = send_mail($mail['to'], 'Նոր պատասխան՝ ' . $inv['name'], $body);
    store_tx(function (array &$data) use ($token, $sent) {
        if (isset($data['invites'][$token])) {
            $data['invites'][$token]['mail_sent'] = $sent;
        }
        return null;
    });
}

$code = (int) ($result['code'] ?? 200);
unset($result['code']);
out($result, $code);
