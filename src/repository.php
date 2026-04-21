<?php

declare(strict_types=1);

function find_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function dashboard_metrics(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn();
    $byStatus = [];
    foreach (contract_statuses() as $status) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM contracts WHERE status = :status');
        $stmt->execute(['status' => $status]);
        $byStatus[$status] = (int) $stmt->fetchColumn();
    }

    $recent = $pdo->query('SELECT c.*, u.full_name AS student_name FROM contracts c JOIN users u ON u.id = c.student_user_id ORDER BY c.updated_at DESC LIMIT 10')->fetchAll();

    return [
        'total' => $total,
        'by_status' => $byStatus,
        'recent_contracts' => $recent,
    ];
}

function student_dashboard_metrics(PDO $pdo, int $studentUserId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM contracts WHERE student_user_id = :student_user_id');
    $stmt->execute(['student_user_id' => $studentUserId]);
    $total = (int) $stmt->fetchColumn();
    
    $byStatus = [];
    foreach (contract_statuses() as $status) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM contracts WHERE student_user_id = :student_user_id AND status = :status');
        $stmt->execute(['student_user_id' => $studentUserId, 'status' => $status]);
        $byStatus[$status] = (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT c.*, u.full_name AS student_name FROM contracts c JOIN users u ON u.id = c.student_user_id WHERE c.student_user_id = :student_user_id ORDER BY c.updated_at DESC LIMIT 10');
    $stmt->execute(['student_user_id' => $studentUserId]);
    $recent = $stmt->fetchAll();

    return [
        'total' => $total,
        'by_status' => $byStatus,
        'recent_contracts' => $recent,
    ];
}

function fetch_available_academic_years(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT DISTINCT academic_year FROM contracts WHERE deleted_at IS NULL AND academic_year IS NOT NULL ORDER BY academic_year DESC');
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_filter($years, fn($year) => $year !== null && $year !== '');
}

function fetch_contracts(PDO $pdo, string $query = '', string $status = '', string $academicYear = ''): array
{
    $sql = 'SELECT c.*, u.full_name AS student_name, u.email AS student_email,
                   SUM(CASE WHEN cs.state = "done" THEN 1 ELSE 0 END) AS done_steps,
                   COUNT(cs.id) AS total_steps
            FROM contracts c
            JOIN users u ON u.id = c.student_user_id
            LEFT JOIN contract_steps cs ON cs.contract_id = c.id
            WHERE c.deleted_at IS NULL';
    $params = [];

    if ($query !== '') {
        $sql .= ' AND (c.dossier_number LIKE :term OR c.company_name LIKE :term OR c.academic_year LIKE :term OR u.full_name LIKE :term OR u.email LIKE :term)';
        $params['term'] = '%' . $query . '%';
    }

    if ($status !== '' && in_array($status, contract_statuses(), true)) {
        $sql .= ' AND c.status = :status';
        $params['status'] = $status;
    }

    if ($academicYear !== '') {
        $sql .= ' AND c.academic_year = :academic_year';
        $params['academic_year'] = $academicYear;
    }

    $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll();

    foreach ($contracts as &$contract) {
        $total = max(1, (int) $contract['total_steps']);
        $contract['progress'] = (int) floor(((int) $contract['done_steps'] / $total) * 100);
    }

    return $contracts;
}

function fetch_contract_for_detail(PDO $pdo, int $contractId): ?array
{
    $stmt = $pdo->prepare('SELECT c.*, u.full_name AS student_name, u.email AS student_email, u.student_number
                           FROM contracts c
                           JOIN users u ON u.id = c.student_user_id
                           WHERE c.id = :id');
    $stmt->execute(['id' => $contractId]);
    $contract = $stmt->fetch();
    if (!$contract) {
        return null;
    }

    $contract['steps'] = fetch_contract_steps($pdo, $contractId);
    $contract['history'] = fetch_contract_history($pdo, $contractId);
    $doneCount = 0;
    foreach ($contract['steps'] as $step) {
        if ($step['state'] === 'done') {
            $doneCount++;
        }
    }
    $contract['progress'] = (int) floor(($doneCount / max(1, count($contract['steps']))) * 100);

    return $contract;
}

function fetch_contract_steps(PDO $pdo, int $contractId): array
{
    $stmt = $pdo->prepare('SELECT cs.*, u.full_name AS done_by_name
                           FROM contract_steps cs
                           LEFT JOIN users u ON u.id = cs.done_by_id
                           WHERE cs.contract_id = :contract_id
                           ORDER BY cs.step_order ASC');
    $stmt->execute(['contract_id' => $contractId]);

    return $stmt->fetchAll();
}

function fetch_contract_history(PDO $pdo, int $contractId): array
{
    $stmt = $pdo->prepare('SELECT a.*, u.full_name AS user_name
                           FROM activities a
                           JOIN users u ON u.id = a.user_id
                           WHERE a.contract_id = :contract_id
                           ORDER BY a.created_at DESC');
    $stmt->execute(['contract_id' => $contractId]);

    return $stmt->fetchAll();
}

function fetch_step_by_id(PDO $pdo, int $contractId, int $stepId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM contract_steps WHERE id = :id AND contract_id = :contract_id');
    $stmt->execute(['id' => $stepId, 'contract_id' => $contractId]);
    $step = $stmt->fetch();

    return $step ?: null;
}

function stats_summary(PDO $pdo): array
{
    $metrics = dashboard_metrics($pdo);
    $stepsSummary = [];

    foreach (default_steps() as $index => $stepName) {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(CASE WHEN state = "done" THEN 1 ELSE 0 END) AS done_count
                               FROM contract_steps WHERE step_name = :step_name');
        $stmt->execute(['step_name' => $stepName]);
        $row = $stmt->fetch() ?: ['total' => 0, 'done_count' => 0];
        $stepsSummary[] = [
            'order' => $index,
            'name' => $stepName,
            'total' => (int) $row['total'],
            'done' => (int) ($row['done_count'] ?? 0),
        ];
    }

    $avgDays = (float) $pdo->query("SELECT COALESCE(AVG(DATEDIFF(updated_at, created_at)), 0) FROM contracts WHERE status IN ('VALIDE', 'CLOTURE')")->fetchColumn();

    return [
        'total' => $metrics['total'],
        'by_status' => $metrics['by_status'],
        'recent_contracts' => $metrics['recent_contracts'],
        'steps_summary' => $stepsSummary,
        'avg_days' => round($avgDays, 1),
    ];
}

/**
 * Créer un token de réinitialisation de mot de passe
 */
function create_password_reset_token(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 heure

    $invalidateStmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE user_id = :user_id AND used = 0');
    $invalidateStmt->execute(['user_id' => $userId]);
    
    $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
    $stmt->execute([
        'user_id' => $userId,
        'token' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);
    
    return $token;
}

/**
 * Valider un token de réinitialisation
 */
function validate_reset_token(PDO $pdo, string $token): ?array
{
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare('
        SELECT prt.*, u.id as user_id, u.email, u.full_name 
        FROM password_reset_tokens prt
        JOIN users u ON u.id = prt.user_id
        WHERE (prt.token = :token_hash OR prt.token = :token_plain)
        AND prt.used = 0 
        AND prt.expires_at > NOW()
    ');
    $stmt->execute([
        'token_hash' => $tokenHash,
        'token_plain' => $token,
    ]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Marquer un token comme utilisé
 */
function mark_token_as_used(PDO $pdo, string $token): void
{
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE token = :token_hash OR token = :token_plain');
    $stmt->execute([
        'token_hash' => $tokenHash,
        'token_plain' => $token,
    ]);
}

/**
 * Mettre à jour le mot de passe d'un utilisateur
 */
function update_user_password(PDO $pdo, int $userId, string $newPassword, bool $mustChange = false): void
{
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = :must_change WHERE id = :id');
    $stmt->execute([
        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        'must_change' => $mustChange ? 1 : 0,
        'id' => $userId,
    ]);
}

/**
 * Mettre un contrat à la corbeille (soft delete)
 */
function soft_delete_contract(PDO $pdo, int $contractId): void
{
    $stmt = $pdo->prepare('UPDATE contracts SET deleted_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $contractId]);
}

/**
 * Restaurer un contrat depuis la corbeille
 */
function restore_contract(PDO $pdo, int $contractId): void
{
    $stmt = $pdo->prepare('UPDATE contracts SET deleted_at = NULL WHERE id = :id');
    $stmt->execute(['id' => $contractId]);
}

/**
 * Supprimer définitivement un contrat
 */
function hard_delete_contract(PDO $pdo, int $contractId): void
{
    $stmt = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
    $stmt->execute(['id' => $contractId]);
}

/**
 * Récupérer les contrats dans la corbeille
 */
function fetch_deleted_contracts(PDO $pdo): array
{
    $sql = 'SELECT c.*, u.full_name AS student_name, u.email AS student_email
            FROM contracts c
            JOIN users u ON u.id = c.student_user_id
            WHERE c.deleted_at IS NOT NULL
            ORDER BY c.deleted_at DESC';
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
