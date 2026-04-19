<?php

declare(strict_types=1);

function create_pdo(array $db): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    header(
        "Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src 'self' https://cdn.jsdelivr.net; font-src 'self' data: https://cdnjs.cloudflare.com; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; upgrade-insecure-requests"
    );
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals(csrf_token(), $token);
}

function password_strength_error(string $password): ?string
{
    if (strlen($password) < 12) {
        return 'Le mot de passe doit contenir au moins 12 caractères.';
    }

    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.';
    }

    return null;
}

function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 254;
}

function rate_limit_by_ip(PDO $pdo, string $action, int $maxAttempts = 10, int $windowSeconds = 3600): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = sha1($action . ':' . $ip);
    $lockFile = sys_get_temp_dir() . '/ratelimit_' . $key;
    
    if (file_exists($lockFile)) {
        $data = json_decode(file_get_contents($lockFile), true);
        if ($data && isset($data['until']) && $data['until'] > time()) {
            return false;
        }
        @unlink($lockFile);
    }
    
    return true;
}

function record_rate_limit_attempt(string $action, int $maxAttempts = 10, int $windowSeconds = 3600): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = sha1($action . ':' . $ip);
    $lockFile = sys_get_temp_dir() . '/ratelimit_' . $key;
    
    $attempts = 1;
    $until = time() + $windowSeconds;
    
    if (file_exists($lockFile)) {
        $data = json_decode(file_get_contents($lockFile), true);
        if ($data && isset($data['attempts'])) {
            $attempts = (int)$data['attempts'] + 1;
            if ($attempts >= $maxAttempts) {
                $until = time() + 1800; // 30 minutes after max attempts
            }
        }
    }
    
    file_put_contents($lockFile, json_encode(['attempts' => $attempts, 'until' => $until]), LOCK_EX);
}

function login_is_locked(): bool
{
    $lockUntil = $_SESSION['login_lock_until'] ?? 0;
    return is_int($lockUntil) && $lockUntil > time();
}

function login_lock_remaining_seconds(): int
{
    $lockUntil = $_SESSION['login_lock_until'] ?? 0;
    if (!is_int($lockUntil) || $lockUntil <= time()) {
        return 0;
    }

    return $lockUntil - time();
}

function register_login_failure(): void
{
    $attempts = (int) ($_SESSION['login_failed_attempts'] ?? 0);
    $attempts++;
    $_SESSION['login_failed_attempts'] = $attempts;

    if ($attempts >= 5) {
        $_SESSION['login_lock_until'] = time() + 300;
        $_SESSION['login_failed_attempts'] = 0;
    }
}

function clear_login_failures(): void
{
    unset($_SESSION['login_failed_attempts'], $_SESSION['login_lock_until']);
}

function app_base_url(): string
{
    static $baseUrl = null;
    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $config = require __DIR__ . '/../config/app.php';
    $configured = trim((string) ($config['app_url'] ?? ''));
    if ($configured !== '') {
        $baseUrl = rtrim($configured, '/');
        return $baseUrl;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    $baseUrl = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');

    return $baseUrl;
}

function app_url(string $path = ''): string
{
    $base = app_base_url();
    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function redirect_to(string $page, array $params = []): void
{
    $query = http_build_query(array_merge(['page' => $page], $params));
    header('Location: ' . app_url('index.php?' . $query));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function contract_statuses(): array
{
    return ['BROUILLON', 'EN_COURS', 'EN_ATTENTE_OPCO', 'CORRECTION', 'VALIDE', 'CLOTURE'];
}

function status_label(string $status): string
{
    $labels = [
        'BROUILLON' => 'En préparation',
        'EN_COURS' => 'En cours',
        'EN_ATTENTE_OPCO' => 'En attente OPCO',
        'CORRECTION' => 'En correction',
        'VALIDE' => 'Valide',
        'CLOTURE' => 'Clôturé',
    ];

    return $labels[$status] ?? $status;
}

function status_class(string $status): string
{
    switch ($status) {
        case 'VALIDE':
            return 'success';
        case 'CLOTURE':
            return 'danger';
        case 'EN_ATTENTE_OPCO':
        case 'CORRECTION':
            return 'warning';
        case 'EN_COURS':
            return 'info';
        default:
            return 'secondary';
    }
}

function role_label(string $role): string
{
    $labels = [
        'etudiant' => 'Étudiant',
        'secretaire' => 'Secrétaire',
        'responsable' => 'Responsable',
        'directeur' => 'Directeur',
    ];

    return $labels[$role] ?? $role;
}

function step_label(string $stepName): string
{
    if (preg_match('/^Decision OPCO\s+(\d+e)$/', $stepName, $matches) === 1) {
        return 'Décision OPCO ' . $matches[1];
    }

    $labels = [
        'Fiche envoyee a l entreprise par l ecole' => 'Fiche envoyée à l’entreprise par l’école',
        'Fiche completee recue de l entreprise par l ecole' => 'Fiche complétée reçue de l’entreprise par l’école',
        'Mail d acceptation etudiant recu' => 'Mail d’acceptation de l’étudiant reçu',
        'Fiche renvoyee pour correction' => 'Fiche renvoyée pour correction',
        'Fiche corrigee recue' => 'Fiche corrigée reçue',
        'CERFA et convention envoyes a l entreprise' => 'CERFA et convention envoyés à l’entreprise',
        'Dates modifiees et nouvelle version envoyee' => 'Dates modifiées et nouvelle version envoyée',
        'CERFA signe avec l entreprise' => 'CERFA signé avec l’entreprise',
        'Convention signee avec l entreprise' => 'Convention signée avec l’entreprise',
        'Demande APT deposee' => 'Demande APT déposée',
        'APT obtenue' => 'APT obtenue',
        'APT refusee' => 'APT refusée',
        'CERFA recu par l ecole' => 'CERFA reçu par l’école',
        'CERFA envoye a l OPCO par l etudiant' => 'CERFA envoyé à l’OPCO par l’étudiant',
        'CERFA envoye a l OPCO' => 'CERFA envoyé à l’OPCO',
        'Decision OPCO' => 'Décision OPCO',
    ];

    return $labels[$stepName] ?? $stepName;
}

function step_state_label(string $state): string
{
    $labels = [
        'pending' => 'En attente',
        'done' => 'Complétée',
        'rejected' => 'Refusée',
    ];

    return $labels[$state] ?? $state;
}

function formation_prefix(string $formation): string
{
    $map = [
        'INFO' => 'INFO',
        'GI' => 'GI',
        'GEE' => 'GEE',
        'AGRO' => 'AGRO',
        'Cycle Ingenieur Informatique' => 'INFO',
        'Cycle Ingenieur Genie Industriel' => 'GI',
        'Cycle Ingenieur Genie Energetique' => 'GEE',
        'Cycle Ingenieur Genie Energetique et Environnement' => 'GEE',
        'Cycle Ingenieur Agroalimentaire' => 'AGRO',
        'Cycle Ingenieur Environnement' => 'AGRO',
    ];

    return $map[$formation] ?? 'GEN';
}

function default_steps(bool $isEuEeaSwiss = false): array
{
    $steps = [
        'Fiche envoyee a l entreprise par l ecole',
        'Fiche completee recue de l entreprise par l ecole',
        'Mail d acceptation etudiant recu',
        'Fiche renvoyee pour correction',
        'Fiche corrigee recue',
        'CERFA et convention envoyes a l entreprise',
        'Dates modifiees et nouvelle version envoyee',
        'CERFA signe avec l entreprise',
        'Convention signee avec l entreprise',
    ];
    
    // Ajouter les étapes APT uniquement pour les étudiants hors UE/EEE/Suisse
    if (!$isEuEeaSwiss) {
        $steps[] = 'Demande APT deposee';
        $steps[] = 'APT obtenue';
        $steps[] = 'APT refusee';
    }
    
    $steps[] = 'CERFA recu par l ecole';
    $steps[] = 'CERFA envoye a l OPCO par l etudiant';
    $steps[] = 'CERFA envoye a l OPCO';
    $steps[] = 'Decision OPCO';
    
    return $steps;
}

function student_allowed_steps(): array
{
    return [
        'Fiche envoyee a l entreprise par l ecole',
        'CERFA signe avec l entreprise',
        'CERFA envoye a l OPCO par l etudiant',
        'CERFA envoye a l OPCO',
    ];
}

function optional_steps(): array
{
    return [
        'CERFA envoye a l OPCO par l etudiant',
    ];
}

function is_mandatory_step(string $stepName): bool
{
    $optionalSteps = optional_steps();
    return !in_array($stepName, $optionalSteps, true);
}

function can_complete_step(array $allSteps, int $currentStepOrder): bool
{
    // La première étape peut toujours être complétée
    if ($currentStepOrder === 1) {
        return true;
    }
    
    // Vérifier que l'étape précédente est complétée
    foreach ($allSteps as $step) {
        if ((int) $step['step_order'] === $currentStepOrder - 1) {
            // L'étape précédente doit être complétée (état 'done')
            return $step['state'] === 'done';
        }
    }
    
    // Si pas d'étape précédente trouvée, ne pas permettre (sécurité)
    return false;
}

/**
 * Vérifier si une étape est mutuellement exclusive avec une autre
 * (par exemple: APT obtenue et APT refusée)
 */
function is_step_mutually_exclusive_with_done(array $allSteps, string $stepName): bool
{
    // Définir les paires mutuellement exclusives
    $exclusiveSteps = [
        'APT obtenue' => 'APT refusee',
        'APT refusee' => 'APT obtenue',
    ];
    
    if (!isset($exclusiveSteps[$stepName])) {
        return false;
    }
    
    $exclusiveWith = $exclusiveSteps[$stepName];
    
    // Vérifier si l'étape exclusive est déjà complétée
    foreach ($allSteps as $step) {
        if ($step['step_name'] === $exclusiveWith && $step['state'] === 'done') {
            return true; // L'étape exclusive est complétée, donc cette étape ne peut pas l'être
        }
    }
    
    return false;
}

function generate_password(int $length = 14): string
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    $max = strlen($alphabet) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }

    return $password;
}

function slugify_ascii(string $value): string
{
    $value = trim($value);
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = $transliterated !== false ? $transliterated : $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';

    return trim($value, '-');
}

function generate_student_email(string $firstName, string $lastName): string
{
    return sprintf(
        '%s.%s@etu.eilco.univ-littoral.fr',
        slugify_ascii($firstName),
        slugify_ascii($lastName)
    );
}

function current_user(PDO $pdo): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return null;
    }

    return find_user_by_id($pdo, (int) $userId);
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        set_flash('danger', 'Veuillez vous connecter.');
        redirect_to('login');
    }

    return $user;
}

function require_roles(PDO $pdo, array $roles): array
{
    $user = require_login($pdo);
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Acces refuse.');
    }

    return $user;
}

function can_view_contract(array $user, array $contract): bool
{
    if ($user['role'] === 'etudiant') {
        return (int) $contract['student_user_id'] === (int) $user['id'];
    }

    return true;
}

function can_edit_step(array $user, string $stepName): bool
{
    if (in_array($user['role'], ['secretaire', 'responsable'], true)) {
        return true;
    }

    return $user['role'] === 'etudiant' && in_array($stepName, student_allowed_steps(), true);
}

function generate_dossier_number(PDO $pdo, string $formation): string
{
    $prefix = formation_prefix($formation);
    $year = (int) date('Y');
    $pattern = sprintf('%s-%d-%%', $prefix, $year);

    $stmt = $pdo->prepare('SELECT dossier_number FROM contracts WHERE dossier_number LIKE :pattern ORDER BY dossier_number DESC LIMIT 1');
    $stmt->execute(['pattern' => $pattern]);
    $last = $stmt->fetchColumn();

    $counter = 1;
    if (is_string($last) && preg_match('/(\d{6})$/', $last, $matches)) {
        $counter = ((int) $matches[1]) + 1;
    }

    return sprintf('%s-%d-%06d', $prefix, $year, $counter);
}
function steps_with_custom_choices(): array
{
    return [
        'Decision OPCO' => ['valide', 'refuse', 'demande-documents'],
    ];
}

function get_step_custom_choices(string $stepName): ?array
{
    foreach (steps_with_custom_choices() as $baseStepName => $choices) {
        if ($stepName === $baseStepName || strpos($stepName, $baseStepName . ' ') === 0) {
            return $choices;
        }
    }

    return null;
}
function update_contract_status(PDO $pdo, int $contractId): void
{
    $stmt = $pdo->prepare('SELECT step_name, state, note FROM contract_steps WHERE contract_id = :contract_id ORDER BY step_order ASC');
    $stmt->execute(['contract_id' => $contractId]);
    $steps = $stmt->fetchAll();

    $done = [];
    $decisionOpcoNote = null;
    
    foreach ($steps as $step) {
        if ($step['state'] === 'done') {
            $done[] = $step['step_name'];
            if (strpos($step['step_name'], 'Decision OPCO') === 0) {
                $decisionOpcoNote = $step['note'] ?? '';
            }
        }
    }

    $status = 'BROUILLON';
    if ($decisionOpcoNote !== null) {
        $decisionKey = strpos($decisionOpcoNote, ':') !== false
            ? trim((string) explode(':', $decisionOpcoNote, 2)[0])
            : trim($decisionOpcoNote);

        if ($decisionKey === 'valide') {
            $status = 'VALIDE';
        } elseif ($decisionKey === 'refuse') {
            $status = 'CLOTURE';
        } elseif ($decisionKey === 'demande-documents') {
            $status = 'EN_ATTENTE_OPCO';
        } else {
            $status = 'EN_ATTENTE_OPCO';
        }
    } elseif (in_array('CERFA envoye a l OPCO', $done, true)) {
        $status = 'EN_ATTENTE_OPCO';
    } elseif (in_array('Fiche renvoyee pour correction', $done, true)) {
        $status = 'CORRECTION';
    } elseif ($done !== []) {
        $status = 'EN_COURS';
    }

    $update = $pdo->prepare('UPDATE contracts SET status = :status, updated_at = NOW() WHERE id = :id');
    $update->execute(['status' => $status, 'id' => $contractId]);
}

function log_action(PDO $pdo, int $contractId, int $userId, string $action, ?string $details = null): void
{
    $stmt = $pdo->prepare('INSERT INTO activities (contract_id, user_id, action, details, created_at) VALUES (:contract_id, :user_id, :action, :details, NOW())');
    $stmt->execute([
        'contract_id' => $contractId,
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
    ]);
}
