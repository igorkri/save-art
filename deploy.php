<?php

declare(strict_types=1);

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

// ─── Options ──────────────────────────────────────────────────────────────────
option('dump', null, InputOption::VALUE_OPTIONAL, 'SQL dump file path (for db:push / db:import)');
option('skip-perms', null, InputOption::VALUE_NONE, 'Skip chown/chmod steps (use when DEP_USER_ROOT is not available)');

// ─── Credentials (deploy.local.php is gitignored) ────────────────────────────
if (! file_exists(__DIR__.'/deploy.local.php')) {
    fwrite(STDERR, "\033[0;31m✗\033[0m  deploy.local.php not found.\n");
    fwrite(STDERR, "   cp deploy.local.php.example deploy.local.php  # fill in values\n");
    exit(1);
}
require __DIR__.'/deploy.local.php';

// ─── Host (used only for `dep ssh` built-in routing) ─────────────────────────
host('production')
    ->set('hostname', DEP_HOST)
    ->set('port', DEP_PORT)
    ->set('remote_user', DEP_USER)
    ->set('deploy_path', DEP_PROJECT_PATH)
    ->set('ssh_multiplexing', false)
    ->set('ssh_extra_args', '-o StrictHostKeyChecking=no -o ConnectTimeout=10');

// ─── SSH / transfer helpers ───────────────────────────────────────────────────

function sshBin(): string
{
    $opts = '-o StrictHostKeyChecking=no -o ConnectTimeout=10 -p '.DEP_PORT;
    if (DEP_SSH_KEY) {
        return 'ssh -i '.escapeshellarg(DEP_SSH_KEY)." $opts";
    }

    return 'sshpass -p '.escapeshellarg(DEP_PASSWORD)." ssh $opts";
}

/** Run command on remote as DEP_USER */
function runAs(string $cmd, array $opts = []): string
{
    return runLocally(
        sshBin().' '.DEP_USER.'@'.DEP_HOST.' '.escapeshellarg($cmd),
        array_merge(['timeout' => 300], $opts)
    );
}

function sshBinRoot(): string
{
    return 'sshpass -p '.escapeshellarg(DEP_PASSWORD_ROOT)
        .' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p '.DEP_PORT;
}

/** Run privileged command on remote via sudo (DEP_USER_ROOT) */
function runPriv(string $cmd, array $opts = []): string
{
    $remoteCmd = 'echo '.escapeshellarg(DEP_PASSWORD_ROOT).' | sudo -S -p \'\' bash -c '.escapeshellarg($cmd);

    return runLocally(
        sshBinRoot().' '.DEP_USER_ROOT.'@'.DEP_HOST.' '.escapeshellarg($remoteCmd),
        array_merge(['timeout' => 120], $opts)
    );
}

/** rsync local → remote */
function rsyncTo(string $src, string $dst, array $exclude = [], bool $delete = false): void
{
    $flags = implode(' ', array_map(fn ($e) => '--exclude='.escapeshellarg($e), $exclude));
    if ($delete) {
        $flags .= ' --delete';
    }
    runLocally(
        'rsync -az --progress -e '.escapeshellarg(sshBin())." $flags --ignore-errors $src ".DEP_USER.'@'.DEP_HOST.":$dst",
        ['timeout' => 600]
    );
}

/** rsync remote → local */
function rsyncFrom(string $src, string $dst, array $exclude = []): void
{
    $flags = implode(' ', array_map(fn ($e) => '--exclude='.escapeshellarg($e), $exclude));
    runLocally(
        'rsync -az --progress -e '.escapeshellarg(sshBin())." $flags ".DEP_USER.'@'.DEP_HOST.":$src $dst",
        ['timeout' => 600]
    );
}

function scpTo(string $local, string $remote): void
{
    $opts = '-P '.DEP_PORT.' -o StrictHostKeyChecking=no';
    if (DEP_SSH_KEY) {
        runLocally('scp -i '.escapeshellarg(DEP_SSH_KEY)." $opts ".escapeshellarg($local).' '.DEP_USER.'@'.DEP_HOST.":$remote");
    } else {
        runLocally('sshpass -p '.escapeshellarg(DEP_PASSWORD)." scp $opts ".escapeshellarg($local).' '.DEP_USER.'@'.DEP_HOST.":$remote");
    }
}

function scpFrom(string $remote, string $local): void
{
    $opts = '-P '.DEP_PORT.' -o StrictHostKeyChecking=no';
    if (DEP_SSH_KEY) {
        runLocally('scp -i '.escapeshellarg(DEP_SSH_KEY)." $opts ".DEP_USER.'@'.DEP_HOST.":$remote ".escapeshellarg($local));
    } else {
        runLocally('sshpass -p '.escapeshellarg(DEP_PASSWORD)." scp $opts ".DEP_USER.'@'.DEP_HOST.":$remote ".escapeshellarg($local));
    }
}

function ensureDumpDir(): string
{
    $dir = __DIR__.'/dump';
    is_dir($dir) || mkdir($dir, 0755, true);

    return $dir;
}

// Файлы/папки, которые не должны попадать на сервер и не должны затираться там rsync --delete
const SYNC_EXCLUDE = [
    '.git/', '.ddev/', '.env*', '/.env.production',
    '/vendor/', 'node_modules/',
    'bootstrap/cache/',
    'storage/app/', 'storage/logs/', 'storage/debugbar/',
    'storage/framework/cache/', 'storage/framework/sessions/', 'storage/framework/views/',
    'public/build/', 'public/hot',
    'backups/', 'dump/', 'config-files/', 'scripts/',
    'docs/', 'src-figma/',
    '.idea/', '.vscode/', '.junie/', '.ai/', '.claude/',
    '.phpunit.result.cache', 'phpunit.xml',
    'CLAUDE.md', 'AGENTS.md', '*.session.sql',
    'deploy.local.php',
];

// ─── DEPLOY ───────────────────────────────────────────────────────────────────
task('deploy', function (): void {
    $path = DEP_PROJECT_PATH;
    $php = 'php'.DEP_PHP_VERSION;

    if (! input()->getOption('skip-perms')) {
        writeln('<comment>▶ 1/7 Fix permissions</comment>');
        runPriv('chown -R '.DEP_USER.':'.DEP_USER." $path && chmod -R u+rwX $path");
    } else {
        writeln('<comment>▶ 1/7 Fix permissions — skipped (--skip-perms)</comment>');
    }

    writeln('<comment>▶ 2/7 Sync code</comment>');
    rsyncTo(__DIR__.'/', "$path/", SYNC_EXCLUDE, delete: true);

    writeln('<comment>▶ 3/7 .env</comment>');
    $hasEnv = trim(runAs("[ -f $path/.env ] && echo 1 || echo 0")) === '1';
    if ($hasEnv) {
        writeln('  .env already on server, skipping');
    } elseif (file_exists(__DIR__.'/.env.production')) {
        scpTo(__DIR__.'/.env.production', "$path/.env");
        runAs("cd $path && grep -qE '^APP_KEY=$' .env && $php artisan key:generate --force || true");
        $hasEnv = true;
    } else {
        writeln('  <warning>No .env.production — skipping Artisan steps</warning>');
    }

    writeln('<comment>▶ 4/7 Storage dirs</comment>');
    runAs("mkdir -p $path/storage/app/public $path/storage/framework/{cache,sessions,views} $path/storage/logs $path/bootstrap/cache");
    if (! input()->getOption('skip-perms')) {
        runPriv('chown -R '.DEP_USER.":www-data $path/storage $path/bootstrap/cache && chmod -R 775 $path/storage $path/bootstrap/cache");
    }

    writeln('<comment>▶ 5/7 Dependencies</comment>');
    runAs("cd $path && $php /usr/bin/composer install --no-interaction --no-dev --optimize-autoloader", ['timeout' => 300]);
    runAs("cd $path && npm ci --prefer-offline && npm run build", ['timeout' => 300]);
    runAs("cd $path && rm -f public/hot");

    if ($hasEnv) {
        writeln('<comment>▶ 6/7 Artisan</comment>');
        runAs("cd $path && $php artisan migrate --force --no-interaction");
        runAs("cd $path && $php artisan storage:link --force --no-interaction 2>/dev/null || true");
        runAs("cd $path && $php artisan filament:assets --no-interaction 2>/dev/null || true");
        runAs("cd $path && $php artisan config:clear && $php artisan cache:clear && $php artisan view:clear");
        runAs("cd $path && $php artisan config:cache && $php artisan route:cache && $php artisan view:cache");
        runAs("cd $path && $php artisan queue:restart");

        writeln('<comment>▶ 7/7 Scheduler</comment>');
        invoke('scheduler:ensure');
    }

    writeln('<info>✓  Deploy complete → https://'.DEP_SITE_DOMAIN.'</info>');
})->desc('Full deploy: sync code, install deps, migrate, restart queue');

// ─── QUICK DEPLOY ─────────────────────────────────────────────────────────────
task('deploy:quick', function (): void {
    $path = DEP_PROJECT_PATH;
    $php = 'php'.DEP_PHP_VERSION;

    writeln('<comment>▶ 1/2 Sync code</comment>');
    rsyncTo(__DIR__.'/', "$path/", SYNC_EXCLUDE);

    writeln('<comment>▶ 2/2 Clear cache</comment>');
    runAs("cd $path && $php artisan config:clear && $php artisan cache:clear && $php artisan view:clear && $php artisan queue:restart");

    writeln('<info>✓  Quick deploy done → https://'.DEP_SITE_DOMAIN.'</info>');
})->desc('Quick deploy: code sync + cache clear only (no composer/npm/migrate)');

// ─── SCHEDULER ────────────────────────────────────────────────────────────────
task('scheduler:ensure', function (): void {
    $cronLine = '* * * * * cd '.DEP_PROJECT_PATH.' && php'.DEP_PHP_VERSION.' artisan schedule:run >> /dev/null 2>&1';
    $existing = runAs("crontab -l 2>/dev/null | grep 'artisan schedule:run' || true");

    if ($existing && str_contains($existing, DEP_PROJECT_PATH)) {
        writeln('  Scheduler cron already configured');

        return;
    }

    runAs('(crontab -l 2>/dev/null | grep -v \'artisan schedule:run\' || true; echo '.escapeshellarg($cronLine).') | crontab -');
    writeln('<info>✓  Laravel Scheduler added to crontab</info>');
})->desc('Ensure Laravel Scheduler is in crontab');

// ─── DATABASE ─────────────────────────────────────────────────────────────────
task('db:pull', function (): void {
    $filename = ensureDumpDir().'/dump_'.date('Y-m-d_H-i').'.sql.gz';

    writeln('<comment>▶ Export DB on server ('.DEP_DB_NAME.')</comment>');
    runAs(
        'mysqldump -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT
        .' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\''
        .' --single-transaction --quick --routines --triggers '.escapeshellarg(DEP_DB_NAME)
        .' | gzip > /tmp/dep_dump.sql.gz',
        ['timeout' => 600]
    );
    scpFrom('/tmp/dep_dump.sql.gz', $filename);
    runAs('rm -f /tmp/dep_dump.sql.gz');

    writeln('<info>✓  Saved: '.basename($filename).' ('.round(filesize($filename) / 1024).' KB)</info>');
})->desc('Export remote DB → dump/');

task('db:sync', function (): void {
    invoke('db:pull');

    $dumps = glob(ensureDumpDir().'/*.sql.gz') ?: [];
    usort($dumps, fn ($a, $b) => filemtime($b) - filemtime($a));
    $latest = $dumps[0] ?? null;
    if (! $latest) {
        throw new \RuntimeException('No dump files found after pull');
    }

    writeln('<comment>▶ Import to local DDEV</comment>');
    runLocally('gunzip -c '.escapeshellarg($latest).' > /tmp/dep_import.sql');
    runLocally('ddev import-db --file=/tmp/dep_import.sql');
    runLocally('rm -f /tmp/dep_import.sql');
    writeln('<info>✓  Local DDEV DB updated ('.basename($latest).')</info>');
})->desc('Pull DB from server → import to local DDEV');

task('db:push', function (): void {
    $file = input()->getOption('dump');

    if (! $file) {
        $file = ensureDumpDir().'/dump_local_'.date('Y-m-d_H-i').'.sql.gz';
        writeln('<comment>▶ Dump local DDEV DB → '.basename($file).'</comment>');
        runLocally(
            'ddev exec -- mysqldump -hdb -uroot -proot --single-transaction --quick --routines --triggers db'
            .' 2>/dev/null | gzip > '.escapeshellarg($file)
        );
        writeln('<info>✓  Local dump: '.basename($file).' ('.round(filesize($file) / 1024).' KB)</info>');
    } elseif (! file_exists($file)) {
        throw new \RuntimeException("File not found: $file");
    }

    if (DEP_BACKUP_BEFORE_RESTORE) {
        writeln('<comment>▶ Backup remote DB before overwrite...</comment>');
        invoke('db:pull');
    }

    $remoteTmp = '/tmp/dep_import_'.basename($file);
    writeln('<comment>▶ Upload → server</comment>');
    scpTo($file, $remoteTmp);

    if (str_ends_with($file, '.gz')) {
        runAs('gunzip -c '.$remoteTmp.' | mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT.' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\' '.escapeshellarg(DEP_DB_NAME));
    } else {
        runAs('mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT.' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\' '.escapeshellarg(DEP_DB_NAME).' < '.$remoteTmp);
    }
    runAs("rm -f $remoteTmp");

    writeln('<info>✓  Import complete</info>');
    if (DEP_VERIFY_AFTER_RESTORE) {
        invoke('db:tables');
    }
})->desc('Dump local DDEV DB and import to server (⚠ overwrites remote data)');

task('db:import', function (): void {
    $file = input()->getOption('dump');
    if (! $file || ! file_exists($file)) {
        throw new \RuntimeException('Specify: dep db:import --dump=./dump/file.sql.gz');
    }

    if (DEP_BACKUP_BEFORE_RESTORE) {
        writeln('<comment>▶ Backup remote DB before import...</comment>');
        invoke('db:pull');
    }

    $remoteTmp = '/tmp/dep_import_'.basename($file);
    scpTo($file, $remoteTmp);

    if (str_ends_with($file, '.gz')) {
        runAs('gunzip -c '.$remoteTmp.' | mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT.' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\' '.escapeshellarg(DEP_DB_NAME));
    } else {
        runAs('mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT.' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\' '.escapeshellarg(DEP_DB_NAME).' < '.$remoteTmp);
    }
    runAs("rm -f $remoteTmp");
    writeln('<info>✓  Import complete</info>');
})->desc('Import a specific dump file to server DB');

task('db:list', function (): void {
    $dir = ensureDumpDir();
    $dumps = glob("$dir/*.sql.gz") ?: [];
    if (! $dumps) {
        writeln("<comment>No dumps in $dir/</comment>");

        return;
    }
    usort($dumps, fn ($a, $b) => filemtime($b) - filemtime($a));
    foreach ($dumps as $f) {
        writeln(sprintf('  %s  %6d KB  %s', date('Y-m-d H:i', filemtime($f)), round(filesize($f) / 1024), basename($f)));
    }
})->desc('List local DB dumps');

task('db:tables', function (): void {
    writeln(runAs(
        'mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT
        .' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\''
        .' -e \'SELECT table_name, table_rows, ROUND(data_length/1024/1024,2) AS size_mb'
        .' FROM information_schema.tables WHERE table_schema="'.DEP_DB_NAME.'" ORDER BY data_length DESC;\''
    ));
})->desc('List DB tables with row counts and sizes');

task('db:shell', function (): void {
    $remoteCmd = 'mysql -h'.DEP_DB_HOST.' -P'.DEP_DB_PORT.' -u'.DEP_DB_USER.' -p\''.DEP_DB_PASSWORD.'\' '.escapeshellarg(DEP_DB_NAME);
    passthru(sshBin().' -t '.DEP_USER.'@'.DEP_HOST.' '.escapeshellarg($remoteCmd));
})->desc('Open interactive MySQL shell on server');

// ─── STORAGE ──────────────────────────────────────────────────────────────────
task('storage:pull', function (): void {
    writeln('<comment>▶ Sync remote storage → local</comment>');
    rsyncFrom(DEP_STORAGE_PATH.'/', __DIR__.'/storage/app/public/', ['livewire-tmp/', 'temp/']);
    writeln('<info>✓  Storage synced to local</info>');
})->desc('Sync remote storage/app/public → local');

task('storage:push', function (): void {
    writeln('<comment>▶ Sync local storage → remote</comment>');
    rsyncTo(__DIR__.'/storage/app/public/', DEP_STORAGE_PATH.'/', ['livewire-tmp/', 'temp/']);
    writeln('<info>✓  Storage synced to server</info>');
})->desc('Sync local storage/app/public → remote');

// ─── LOGS ─────────────────────────────────────────────────────────────────────
task('logs:laravel', function (): void {
    passthru(sshBin().' '.DEP_USER.'@'.DEP_HOST." 'tail -f ".DEP_PROJECT_PATH."/storage/logs/laravel.log'");
})->desc('Tail Laravel log');

task('logs:queue', function (): void {
    $p = DEP_PROJECT_PATH;
    passthru(sshBin().' '.DEP_USER.'@'.DEP_HOST." 'tail -f $p/storage/logs/queue.log 2>/dev/null || tail -f $p/storage/logs/laravel.log'");
})->desc('Tail queue worker log');

// ─── RESTART ──────────────────────────────────────────────────────────────────
// ⚠ Сервер общий (Hestia, несколько доменов на одной машине). php{VERSION}-fpm.service
// один на все сайты с этой версией PHP — restart/reload затронет их все, не только этот проект.
task('restart:php', function (): void {
    runPriv('systemctl reload-or-restart php'.DEP_PHP_VERSION.'-fpm');
    writeln('<info>✓  PHP-FPM '.DEP_PHP_VERSION.' reloaded (shared with other sites on this server!)</info>');
})->desc('Reload PHP-FPM (⚠ affects all sites on this PHP version)');

task('restart:nginx', function (): void {
    runPriv('nginx -t && systemctl reload nginx');
    writeln('<info>✓  Nginx reloaded (shared with other sites on this server!)</info>');
})->desc('Reload Nginx (⚠ affects all sites on this server)');

task('restart:queue', function (): void {
    $php = 'php'.DEP_PHP_VERSION;
    runAs('cd '.DEP_PROJECT_PATH." && $php artisan queue:restart");
    writeln('<info>✓  Queue workers signalled to restart</info>');
})->desc('Graceful queue:restart (no supervisor program configured for this project yet)');

// ─── STATUS ───────────────────────────────────────────────────────────────────
task('status', function (): void {
    $path = DEP_PROJECT_PATH;

    writeln("\n<comment>── Disk / Memory ──</comment>");
    writeln(runAs('df -h / && free -h'));

    writeln("\n<comment>── Laravel Scheduler (cron) ──</comment>");
    writeln(runAs("crontab -l 2>/dev/null | grep 'artisan schedule:run' || echo '  (not configured)'"));

    writeln("\n<comment>── PHP version ──</comment>");
    writeln(runAs('php'.DEP_PHP_VERSION.' -v | head -1'));

    writeln("\n<comment>── Last deploy ──</comment>");
    writeln(runAs("cd $path && git log -1 --format='%h %s (%cr)' 2>/dev/null || echo 'no git history'"));

    writeln("\n<comment>── Site check ──</comment>");
    $timing = trim((string) shell_exec("curl -w '%{time_starttransfer}/%{http_code}' -o /dev/null -s 'https://".DEP_SITE_DOMAIN."/' 2>/dev/null"));
    [$ttfb, $code] = explode('/', $timing.'/') + ['?', '?'];
    writeln('  https://'.DEP_SITE_DOMAIN."  TTFB={$ttfb}s  HTTP={$code}");
})->desc('Show server status (disk, scheduler, site check)');

// ─── SSH ──────────────────────────────────────────────────────────────────────
task('ssh', function (): void {
    passthru(sshBin().' '.DEP_USER.'@'.DEP_HOST);
})->desc('Open interactive SSH session');
