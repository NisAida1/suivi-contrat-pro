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
    ]);
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $page, array $params = []): never
{
    $query = http_build_query(array_merge(['page' => $page], $params));
    header('Location: index.php?' . $query);
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
    return ['BROUILLON', 'EN_COURS', 'EN_ATTENTE_OPCO', 'CORRECTION', 'VALIDE', 'REFUSE'];
}

function status_label(string $status): string
{
    $labels = [
        'BROUILLON' => 'En preparation',
        'EN_COURS' => 'En cours',
        'EN_ATTENTE_OPCO' => 'En attente OPCO',
        'CORRECTION' => 'En correction',
        'VALIDE' => 'Valide',
        'REFUSE' => 'Refuse',
    ];

    return $labels[$status] ?? $status;
}

function status_class(string $status): string
{
    return match ($status) {
        'VALIDE' => 'success',
        'REFUSE' => 'danger',
        'EN_ATTENTE_OPCO', 'CORRECTION' => 'warning',
        'EN_COURS' => 'info',
        default => 'secondary',
    };
}

function role_label(string $role): string
{
    $labels = [
        'etudiant' => 'Etudiant',
        'secretaire' => 'Secretaire',
        'responsable' => 'Responsable',
        'directeur' => 'Directeur',
    ];

    return $labels[$role] ?? $role;
}

function formation_prefix(string $formation): string
{
    $map = [
        'Cycle Ingenieur Informatique' => 'INFO',
        'Cycle Ingenieur Genie Industriel' => 'GI',
        'Cycle Ingenieur Genie Energetique' => 'GE',
        'Cycle Ingenieur Environnement' => 'ENVER',
    ];

    return $map[$formation] ?? 'GEN';
}

function default_steps(bool $isEuEeaSwiss = false): array
{
    $steps = [
        'Dossier ouvert',
        'Fiche envoyee a l entreprise',
        'Fiche completee recue de l entreprise',
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
    
    $steps[] = 'CERFA envoye a l ecole';
    $steps[] = 'CERFA envoye a l OPCO';
    $steps[] = 'OPCO valide';
    $steps[] = 'OPCO refuse';
    
    return $steps;
}

function student_allowed_steps(): array
{
    return [
        'Fiche envoyee a l entreprise',
        'CERFA signe avec l entreprise',
        'CERFA envoye a l ecole',
        'CERFA envoye a l OPCO',
    ];
}

function generate_password(int $length = 10): string
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

function update_contract_status(PDO $pdo, int $contractId): void
{
    $stmt = $pdo->prepare('SELECT step_name, state FROM contract_steps WHERE contract_id = :contract_id');
    $stmt->execute(['contract_id' => $contractId]);
    $steps = $stmt->fetchAll();

    $done = [];
    foreach ($steps as $step) {
        if ($step['state'] === 'done') {
            $done[] = $step['step_name'];
        }
    }

    $status = 'BROUILLON';
    if (in_array('OPCO valide', $done, true)) {
        $status = 'VALIDE';
    } elseif (in_array('OPCO refuse', $done, true)) {
        $status = 'REFUSE';
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
