<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-1">Gestion des contrats</h2>
                <p class="text-muted mb-0">
                    Consultez et gérez les dossiers.
                    <?php if ($search !== '' || $statusFilter !== ''): ?>
                        <span class="badge bg-info ms-2">
                            Filtres actifs : 
                            <?php if ($search !== ''): ?>Recherche : "<?= h($search) ?>"<?php endif; ?>
                            <?php if ($statusFilter !== ''): ?>Statut : <?= h(status_label($statusFilter)) ?><?php endif; ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
                <a class="btn btn-primary" href="index.php?page=contract_create">Nouveau dossier</a>
            <?php endif; ?>
        </div>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="get" action="index.php" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="contracts">
                    <div class="col-md-5">
                        <label class="form-label">Recherche</label>
                        <input type="text" class="form-control" name="q" value="<?= h($search ?? '') ?>" placeholder="Nom, entreprise, numéro de dossier, année universitaire">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="status">
                            <option value="">Tous</option>
                            <?php foreach ($statuses as $statusCode): ?>
                                <option value="<?= h($statusCode) ?>" <?= ($statusFilter ?? '') === $statusCode ? 'selected' : '' ?>><?= h(status_label($statusCode)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        <a href="index.php?page=contracts" class="btn btn-secondary w-100">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dossier</th>
                            <th>Année univ.</th>
                            <th>Étudiant</th>
                            <th>Entreprise</th>
                            <th>Statut</th>
                            <th>Progression</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contracts === []): ?>
                            <tr><td colspan="7" class="text-center py-4">Aucun contrat trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($contracts as $contract): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= h($contract['dossier_number']) ?></td>
                                    <td><?= h($contract['academic_year'] ?? '-') ?></td>
                                    <td>
                                        <div><?= h($contract['student_name']) ?></div>
                                        <small class="text-muted"><?= h($contract['student_email']) ?></small>
                                    </td>
                                    <td><?= h($contract['company_name']) ?></td>
                                    <td><span class="badge bg-<?= h(status_class($contract['status'])) ?>"><?= h(status_label($contract['status'])) ?></span></td>
                                    <td style="min-width: 140px;">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= h((string) $contract['progress']) ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= h((string) $contract['progress']) ?>%</small>
                                    </td>
                                    <td><a class="btn btn-outline-primary btn-sm" href="index.php?page=contract_detail&id=<?= h((string) $contract['id']) ?>">Voir</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
