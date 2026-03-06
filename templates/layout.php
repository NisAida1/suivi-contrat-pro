<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'Suivi Contrat Pro - PHP') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
    <?php if ($currentUser): ?>
        <nav class="navbar navbar-expand-xl navbar-dark bg-primary shadow-sm app-navbar">
            <div class="container-fluid px-4">
                <a class="navbar-brand fw-bold d-flex flex-column" href="index.php">
                    <span>Suivi Contrat Pro</span>
                    <small class="text-white-50 fw-normal" style="font-size: 0.75rem;"><?= h($currentUser['full_name']) ?></small>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-xl-0 align-items-xl-center">
                        <li class="nav-item"><a class="nav-link" href="index.php">Tableau de bord</a></li>
                        <?php if (in_array($currentUser['role'], ['secretaire', 'responsable', 'directeur'], true)): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=contracts">Contrats</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=trash"><i class="fas fa-trash"></i> Corbeille</a></li>
                        <?php endif; ?>
                        <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=contract_create">Nouveau dossier</a></li>
                        <?php endif; ?>
                        <?php if ($currentUser['role'] === 'directeur'): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=stats">Statistiques</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex align-items-center gap-3 text-white flex-wrap justify-content-xl-end app-userbar">
                        <span class="badge text-bg-light"><?= h(role_label($currentUser['role'])) ?></span>
                        <a class="btn btn-outline-light btn-sm" href="index.php?page=logout">Deconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main class="container py-4">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= h($flash['type']) === 'danger' ? 'danger' : (h($flash['type']) === 'warning' ? 'warning' : (h($flash['type']) === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

        <?php require $view; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
