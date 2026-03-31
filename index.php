<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        set_flash('danger', 'Veuillez renseigner votre email et votre mot de passe.');
        redirect_to('login');
    }

    $user = find_user_by_email($pdo, $email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('danger', 'Identifiants invalides.');
        redirect_to('login');
    }

    $_SESSION['user_id'] = (int) $user['id'];
    
    // Vérifier si l'utilisateur doit changer son mot de passe
    if ((int) $user['must_change_password'] === 1) {
        set_flash('warning', 'Vous devez changer votre mot de passe provisoire.');
        redirect_to('change_password');
    }
    
    set_flash('success', 'Connexion réussie.');
    redirect_to('dashboard');
}

if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    session_start();
    set_flash('info', 'Déconnexion réussie.');
    redirect_to('login');
}

// Changement de mot de passe forcé
if ($page === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = require_login($pdo);
    
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        set_flash('danger', 'Tous les champs sont requis.');
        redirect_to('change_password');
    }
    
    if ($newPassword !== $confirmPassword) {
        set_flash('danger', 'Les mots de passe ne correspondent pas.');
        redirect_to('change_password');
    }
    
    if (strlen($newPassword) < 6) {
        set_flash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');
        redirect_to('change_password');
    }
    
    if (!password_verify($currentPassword, $currentUser['password_hash'])) {
        set_flash('danger', 'Mot de passe actuel incorrect.');
        redirect_to('change_password');
    }
    
    update_user_password($pdo, (int) $currentUser['id'], $newPassword, false);
    set_flash('success', 'Votre mot de passe a été changé avec succès.');
    redirect_to('dashboard');
}

// Mot de passe oublié - afficher le formulaire ou traiter la demande
if ($page === 'forgot_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    
    if ($email === '') {
        set_flash('danger', 'Veuillez entrer votre adresse email.');
        redirect_to('forgot_password');
    }
    
    $user = find_user_by_email($pdo, $email);
    
    // Toujours afficher le même message pour éviter l'énumération des emails
    if ($user) {
        $token = create_password_reset_token($pdo, (int) $user['id']);
        send_password_reset_email($email, $user['full_name'], $token);
    }
    
    set_flash('success', 'Si cette adresse email existe dans notre système, un lien de réinitialisation vous a été envoyé.');
    redirect_to('login');
}

// Réinitialisation du mot de passe avec token
if ($page === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string) ($_GET['token'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    
    if ($token === '') {
        set_flash('danger', 'Token invalide.');
        redirect_to('login');
    }
    
    $tokenData = validate_reset_token($pdo, $token);
    
    if (!$tokenData) {
        set_flash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
        redirect_to('forgot_password');
    }
    
    if ($newPassword !== $confirmPassword) {
        set_flash('danger', 'Les mots de passe ne correspondent pas.');
        redirect_to('reset_password', ['token' => $token]);
    }
    
    if (strlen($newPassword) < 6) {
        set_flash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');
        redirect_to('reset_password', ['token' => $token]);
    }
    
    update_user_password($pdo, (int) $tokenData['user_id'], $newPassword, false);
    mark_token_as_used($pdo, $token);
    
    set_flash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
    redirect_to('login');
}

if ($page === 'contract_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_roles($pdo, ['secretaire', 'responsable']);

    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $studentNumber = trim((string) ($_POST['student_number'] ?? ''));
    $studentEmail = trim((string) ($_POST['student_email'] ?? ''));
    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $formation = trim((string) ($_POST['formation'] ?? ''));
    $academicYear = trim((string) ($_POST['academic_year'] ?? ''));
    $isEuEeaSwiss = (int) ($_POST['is_eu_eea_swiss'] ?? 0) === 1;

    if ($firstName === '' || $lastName === '' || $studentNumber === '' || $studentEmail === '' || $companyName === '' || $formation === '' || $academicYear === '' || !isset($_POST['is_eu_eea_swiss'])) {
        set_flash('danger', 'Tous les champs sont obligatoires.');
        redirect_to('contract_create');
    }

    if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
        set_flash('danger', 'Le format de l\'annee universitaire doit etre AAAA-AAAA (ex: 2025-2026).');
        redirect_to('contract_create');
    }

    $pdo->beginTransaction();

    try {
        $student = find_user_by_email($pdo, $studentEmail);
        $generatedPassword = null;

        if (!$student) {
            $generatedPassword = generate_password();
            $insertUser = $pdo->prepare('INSERT INTO users (full_name, email, role, password_hash, student_number, must_change_password, created_at) VALUES (:full_name, :email, :role, :password_hash, :student_number, :must_change_password, NOW())');
            $insertUser->execute([
                'full_name' => $firstName . ' ' . $lastName,
                'email' => $studentEmail,
                'role' => 'etudiant',
                'password_hash' => password_hash($generatedPassword, PASSWORD_DEFAULT),
                'student_number' => $studentNumber,
                'must_change_password' => 1,
            ]);
            $studentId = (int) $pdo->lastInsertId();
        } else {
            $studentId = (int) $student['id'];
            if (empty($student['student_number'])) {
                $updateUser = $pdo->prepare('UPDATE users SET student_number = :student_number WHERE id = :id');
                $updateUser->execute(['student_number' => $studentNumber, 'id' => $studentId]);
            }
        }

        $dossierNumber = generate_dossier_number($pdo, $formation);
        $insertContract = $pdo->prepare('INSERT INTO contracts (dossier_number, student_user_id, company_name, formation, academic_year, is_eu_eea_swiss, status, current_step, created_at, updated_at) VALUES (:dossier_number, :student_user_id, :company_name, :formation, :academic_year, :is_eu_eea_swiss, :status, :current_step, NOW(), NOW())');
        $insertContract->execute([
            'dossier_number' => $dossierNumber,
            'student_user_id' => $studentId,
            'company_name' => $companyName,
            'formation' => $formation,
            'academic_year' => $academicYear,
            'is_eu_eea_swiss' => $isEuEeaSwiss ? 1 : 0,
            'status' => 'EN_COURS',
            'current_step' => default_steps($isEuEeaSwiss)[0],
        ]);
        $contractId = (int) $pdo->lastInsertId();

        $insertStep = $pdo->prepare('INSERT INTO contract_steps (contract_id, step_order, step_name, state) VALUES (:contract_id, :step_order, :step_name, :state)');
        foreach (default_steps($isEuEeaSwiss) as $index => $stepName) {
            $insertStep->execute([
                'contract_id' => $contractId,
                'step_order' => $index,
                'step_name' => $stepName,
                'state' => 'pending',
            ]);
        }

        log_action($pdo, $contractId, (int) $user['id'], 'Création', 'Dossier ' . $dossierNumber . ' créé');
        $pdo->commit();

        $message = 'Dossier ' . $dossierNumber . ' créé avec succès.';
        if ($generatedPassword !== null) {
            // Envoyer l'email à l'étudiant avec ses identifiants
            $emailSent = send_student_welcome_email(
                $studentEmail,
                $firstName . ' ' . $lastName,
                $generatedPassword,
                $dossierNumber,
                $companyName
            );
            
            if ($emailSent) {
                $message .= ' Un email a été envoyé à l\'étudiant avec ses identifiants.';
            } else {
                $message .= ' Mot de passe temporaire étudiant : ' . $generatedPassword . ' (email non envoyé)';
            }
        }
        set_flash('success', $message);
        redirect_to('contract_detail', ['id' => $contractId]);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        set_flash('danger', 'Impossible de créer le dossier : ' . $exception->getMessage());
        redirect_to('contract_create');
    }
}

if ($page === 'step_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_roles($pdo, ['etudiant', 'secretaire', 'responsable']);
    $contractId = (int) ($_POST['contract_id'] ?? 0);
    $stepId = (int) ($_POST['step_id'] ?? 0);
    $newState = trim((string) ($_POST['state'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    $docNote = trim((string) ($_POST['doc-note'] ?? ''));

    $contract = fetch_contract_for_detail($pdo, $contractId);
    $step = fetch_step_by_id($pdo, $contractId, $stepId);

    if (!$contract || !$step || !can_view_contract($user, $contract)) {
        http_response_code(404);
        exit('Dossier ou etape introuvable.');
    }

    if ($user['role'] === 'etudiant') {
        if (!in_array($step['step_name'], student_allowed_steps(), true) || $newState !== 'done') {
            set_flash('danger', 'Action non autorisee pour votre role.');
            redirect_to('contract_detail', ['id' => $contractId]);
        }
    } elseif (!in_array($newState, ['pending', 'done', 'rejected'], true) && $newState !== '') {
        // Pour Decision OPCO (y compris 2e, 3e...), on accepte les choix personnalisés
        $customChoices = get_step_custom_choices($step['step_name']);
        if ($customChoices === null) {
            set_flash('danger', 'État invalide.');
            redirect_to('contract_detail', ['id' => $contractId]);
        }
    }

    // Gérer les choix personnalisés (Decision OPCO, Decision OPCO 2e, ...)
    $customChoices = get_step_custom_choices($step['step_name']);
    if ($customChoices !== null && $note !== '') {
        // Valider le choix
        if (!in_array($note, $customChoices, true)) {
            set_flash('danger', 'Choix invalide.');
            redirect_to('contract_detail', ['id' => $contractId]);
        }
        
        // Si c'est "demande-documents", ajouter les documents demandés à la note
        if ($note === 'demande-documents' && $docNote !== '') {
            $note = 'demande-documents: ' . $docNote;
        }
        
        $newState = 'done';
    }

    $stmt = $pdo->prepare('UPDATE contract_steps SET state = :state, note = :note, done_at = :done_at, done_by_id = :done_by_id WHERE id = :id AND contract_id = :contract_id');
    $doneAt = in_array($newState, ['done', 'rejected'], true) ? date('Y-m-d H:i:s') : null;
    $doneById = in_array($newState, ['done', 'rejected'], true) ? (int) $user['id'] : null;
    $stmt->execute([
        'state' => $newState,
        'note' => $note !== '' ? $note : null,
        'done_at' => $doneAt,
        'done_by_id' => $doneById,
        'id' => $stepId,
        'contract_id' => $contractId,
    ]);

    // Si Decision OPCO (même 2e, 3e...) avec demande-documents, créer une nouvelle étape Decision OPCO
    if (strpos($step['step_name'], 'Decision OPCO') === 0 && (strpos($note, 'demande-documents') === 0)) {
        // Récupérer le step_order de l'étape actuelle
        $orderStmt = $pdo->prepare('SELECT step_order FROM contract_steps WHERE id = :id');
        $orderStmt->execute(['id' => $stepId]);
        $currentOrder = (int) $orderStmt->fetchColumn();
        
        // Compter combien de "Decision OPCO" existent déjà
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM contract_steps WHERE contract_id = :contract_id AND step_name LIKE "Decision OPCO%"');
        $countStmt->execute(['contract_id' => $contractId]);
        $decisionCount = (int) $countStmt->fetchColumn() + 1; // +1 pour la nouvelle
        
        // Créer le label avec numéro ordinal (2e, 3e, 4e, etc.)
        $newStepName = 'Decision OPCO ' . $decisionCount . 'e';
        
        // Incrémenter les step_order des étapes suivantes
        $incrementStmt = $pdo->prepare('UPDATE contract_steps SET step_order = step_order + 1 WHERE contract_id = :contract_id AND step_order > :order');
        $incrementStmt->execute(['contract_id' => $contractId, 'order' => $currentOrder]);
        
        // Créer la nouvelle étape Decision OPCO
        $insertStmt = $pdo->prepare('INSERT INTO contract_steps (contract_id, step_order, step_name, state) VALUES (:contract_id, :step_order, :step_name, :state)');
        $insertStmt->execute([
            'contract_id' => $contractId,
            'step_order' => $currentOrder + 1,
            'step_name' => $newStepName,
            'state' => 'pending',
        ]);
    }

    update_contract_status($pdo, $contractId);
    log_action($pdo, $contractId, (int) $user['id'], 'Mise à jour étape', $step['step_name'] . ': ' . $step['state'] . ' -> ' . $newState);
    set_flash('success', 'Étape mise à jour.');
    redirect_to('contract_detail', ['id' => $contractId]);
}

if ($page === 'status_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_roles($pdo, ['secretaire', 'responsable']);
    $contractId = (int) ($_POST['contract_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    if (!in_array($status, contract_statuses(), true)) {
        set_flash('danger', 'Statut invalide.');
        redirect_to('contract_detail', ['id' => $contractId]);
    }

    $stmt = $pdo->prepare('UPDATE contracts SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $contractId]);
    log_action($pdo, $contractId, (int) $user['id'], 'Changement statut', 'Nouveau statut : ' . $status);
    set_flash('success', 'Statut mis à jour.');
    redirect_to('contract_detail', ['id' => $contractId]);
}

// Supprimer un contrat (mise à la corbeille)
if ($page === 'contract_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_roles($pdo, ['secretaire', 'responsable']);
    $contractId = (int) ($_POST['contract_id'] ?? 0);
    
    $contract = fetch_contract_for_detail($pdo, $contractId);
    if (!$contract) {
        http_response_code(404);
        exit('Dossier introuvable.');
    }
    
    soft_delete_contract($pdo, $contractId);
    log_action($pdo, $contractId, (int) $user['id'], 'Suppression', 'Dossier déplacé vers la corbeille');
    set_flash('success', 'Le dossier a été déplacé vers la corbeille.');
    redirect_to('contracts');
}

// Gestion de la corbeille
if ($page === 'trash' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_roles($pdo, ['secretaire', 'responsable', 'directeur']);
    $action = trim((string) ($_POST['action'] ?? ''));
    $contractId = (int) ($_POST['contract_id'] ?? 0);
    
    if ($action === 'restore') {
        restore_contract($pdo, $contractId);
        log_action($pdo, $contractId, (int) $user['id'], 'Restauration', 'Dossier restauré depuis la corbeille');
        set_flash('success', 'Le dossier a été restauré avec succès.');
    } elseif ($action === 'delete_permanent') {
        hard_delete_contract($pdo, $contractId);
        set_flash('success', 'Le dossier a été supprimé définitivement.');
    }
    
    redirect_to('trash');
}

$title = 'Connexion';
$view = __DIR__ . '/templates/login.php';

switch ($page) {
    case 'login':
        $title = 'Connexion';
        $view = __DIR__ . '/templates/login.php';
        break;

    case 'change_password':
        $currentUser = require_login($pdo);
        $title = 'Changer le mot de passe';
        $view = __DIR__ . '/templates/change_password.php';
        break;

    case 'forgot_password':
        $title = 'Mot de passe oublié';
        $view = __DIR__ . '/templates/forgot_password.php';
        break;

    case 'reset_password':
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            set_flash('danger', 'Token manquant.');
            redirect_to('login');
        }
        $tokenData = validate_reset_token($pdo, $token);
        if (!$tokenData) {
            set_flash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
            redirect_to('forgot_password');
        }
        $title = 'Réinitialiser le mot de passe';
        $view = __DIR__ . '/templates/reset_password.php';
        break;

    case 'dashboard':
    default:
        $currentUser = require_login($pdo);
        if ($currentUser['role'] === 'etudiant') {
            $metrics = student_dashboard_metrics($pdo, (int) $currentUser['id']);
            $dashboardContracts = $metrics['recent_contracts'];
        } else {
            $metrics = dashboard_metrics($pdo);
            $dashboardContracts = $metrics['recent_contracts'];
        }
        $title = 'Tableau de bord';
        $view = __DIR__ . '/templates/dashboard.php';
        break;

    case 'contracts':
        $currentUser = require_roles($pdo, ['secretaire', 'responsable', 'directeur']);
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $contracts = fetch_contracts($pdo, $search, $statusFilter);
        $statuses = contract_statuses();
        $title = 'Contrats';
        $view = __DIR__ . '/templates/contracts.php';
        break;

    case 'trash':
        $currentUser = require_roles($pdo, ['secretaire', 'responsable', 'directeur']);
        $deletedContracts = fetch_deleted_contracts($pdo);
        $title = 'Corbeille';
        $view = __DIR__ . '/templates/trash.php';
        break;

    case 'contract_create':
        $currentUser = require_roles($pdo, ['secretaire', 'responsable']);
        $title = 'Nouveau dossier';
        $view = __DIR__ . '/templates/contract_form.php';
        break;

    case 'contract_detail':
        $currentUser = require_login($pdo);
        $contractId = (int) ($_GET['id'] ?? 0);
        $contract = fetch_contract_for_detail($pdo, $contractId);
        if (!$contract || !can_view_contract($currentUser, $contract)) {
            http_response_code(404);
            exit('Dossier introuvable.');
        }
        $title = 'Dossier ' . $contract['dossier_number'];
        $view = __DIR__ . '/templates/contract_detail.php';
        break;

    case 'contract_history':
        $currentUser = require_login($pdo);
        $contractId = (int) ($_GET['id'] ?? 0);
        $contract = fetch_contract_for_detail($pdo, $contractId);
        if (!$contract || !can_view_contract($currentUser, $contract)) {
            http_response_code(404);
            exit('Historique introuvable.');
        }
        $title = 'Historique ' . $contract['dossier_number'];
        $view = __DIR__ . '/templates/contract_history.php';
        break;

    case 'stats':
        $currentUser = require_roles($pdo, ['directeur']);
        $stats = stats_summary($pdo);
        $title = 'Statistiques';
        $view = __DIR__ . '/templates/stats.php';
        break;
}

require __DIR__ . '/templates/layout.php';
