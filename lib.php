<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Yerevan');

const DATA_DIR = __DIR__ . '/data';
const STORE_FILE = DATA_DIR . '/store.php';
// The store is a .php file that exits immediately, so it can never be read over HTTP.
const STORE_HEAD = "<?php http_response_code(404); exit; ?>\n";
const ACTIVITIES = ['dinner', 'cinema', 'coffee', 'walk', 'concert', 'surprise'];
const LANGS = ['hy', 'ru', 'en'];

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function store_decode(string $raw): array
{
    if (strncmp($raw, '<?php', 5) === 0) {
        $nl = strpos($raw, "\n");
        $raw = $nl === false ? '' : substr($raw, $nl + 1);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }
    $data += ['settings' => [], 'invites' => []];
    return $data;
}

function store_handle()
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0755, true);
    }
    $fp = @fopen(STORE_FILE, 'c+');
    if (!$fp) {
        http_response_code(500);
        exit('Cannot write to the data folder. Make the "data" folder writable (755).');
    }
    return $fp;
}

function store_read(): array
{
    if (!is_file(STORE_FILE)) {
        return store_decode('');
    }
    $fp = store_handle();
    flock($fp, LOCK_SH);
    $raw = (string) stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return store_decode($raw);
}

/** Runs $fn on the data under an exclusive lock and saves the result. */
function store_tx(callable $fn)
{
    $fp = store_handle();
    flock($fp, LOCK_EX);
    $data = store_decode((string) stream_get_contents($fp));
    $result = $fn($data);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, STORE_HEAD . $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

function valid_token(string $t): bool
{
    return (bool) preg_match('/^[a-f0-9]{10,32}$/', $t);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function clean_host(): string
{
    $host = preg_replace('/[^a-z0-9.\-:]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $host !== '' ? $host : 'localhost';
}

/** URL of the folder the site lives in, with a trailing slash. $depth = how deep the current script is. */
function site_root_url(int $depth = 0): string
{
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    for ($i = 0; $i < $depth; $i++) {
        $dir = str_replace('\\', '/', dirname($dir));
    }
    $dir = rtrim($dir, '/');
    return (is_https() ? 'https' : 'http') . '://' . clean_host() . $dir . '/';
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('meet_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => is_https(),
    ]);
    session_start();
}

function is_admin(): bool
{
    if (empty($_COOKIE['meet_admin'])) {
        return false;
    }
    start_session();
    return !empty($_SESSION['admin']);
}

function send_mail(string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $host = preg_replace('/:\d+$/', '', clean_host());
    $from = 'noreply@' . preg_replace('/^www\./', '', $host);
    $headers = [
        'From: =?UTF-8?B?' . base64_encode('Հրավեր') . '?= <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

const ACTIVITY_HY = [
    'dinner' => 'Ընթրիք', 'cinema' => 'Կինո', 'coffee' => 'Սուրճ',
    'walk' => 'Զբոսանք', 'concert' => 'Համերգ', 'surprise' => 'Անակնկալ',
];
const LANG_HY = ['hy' => 'Հայերեն', 'ru' => 'Ռուսերեն', 'en' => 'Անգլերեն'];
const MONTH_GEN_HY = [1 => 'հունվարի', 'փետրվարի', 'մարտի', 'ապրիլի', 'մայիսի', 'հունիսի', 'հուլիսի', 'օգոստոսի', 'սեպտեմբերի', 'հոկտեմբերի', 'նոյեմբերի', 'դեկտեմբերի'];
const WEEKDAY_HY = ['Կիրակի', 'Երկուշաբթի', 'Երեքշաբթի', 'Չորեքշաբթի', 'Հինգշաբթի', 'Ուրբաթ', 'Շաբաթ'];

function activities_hy(array $keys): string
{
    $out = [];
    foreach ($keys as $k) {
        if (isset(ACTIVITY_HY[$k])) {
            $out[] = ACTIVITY_HY[$k];
        }
    }
    return implode(', ', $out);
}

/** "2026-10-10" -> "Շաբաթ, հոկտեմբերի 10" */
function date_hy(string $ymd): string
{
    $t = strtotime($ymd . ' 12:00:00');
    if ($t === false) {
        return $ymd;
    }
    return WEEKDAY_HY[(int) date('w', $t)] . ', ' . MONTH_GEN_HY[(int) date('n', $t)] . ' ' . (int) date('j', $t);
}

/** Unix time -> "հոկտեմբերի 4, 21:40" */
function stamp_hy(?int $ts): string
{
    if (!$ts) {
        return '';
    }
    return MONTH_GEN_HY[(int) date('n', $ts)] . ' ' . (int) date('j', $ts) . ', ' . date('H:i', $ts);
}
