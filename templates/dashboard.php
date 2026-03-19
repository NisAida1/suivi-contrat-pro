<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-0">Bonjour <?= h($currentUser['full_name']) ?></h2>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Total dossiers</div>
                    <div class="display-6 fw-bold text-primary"><?= h((string) $metrics['total']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (in_array($currentUser['role'], ['secretaire', 'responsable', 'directeur'], true)): ?>
<div class="row g-3 mb-4">
    <?php foreach ($metrics['by_status'] as $code => $count): ?>
        <?php 
            $percentage = $metrics['total'] > 0 ? round(($count / $metrics['total']) * 100, 1) : 0;
        ?>
        <div class="col-md-4 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <span class="badge bg-<?= h(status_class($code)) ?> mb-2"><?= h(status_label($code)) ?></span>
                    <div class="fs-4 fw-bold"><?= h((string) $count) ?></div>
                    <div class="small text-muted"><?= h((string) $percentage) ?>%</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= $currentUser['role'] === 'etudiant' ? 'Mes dossiers' : 'Derniers dossiers' ?></h5>
        <div class="d-flex gap-2">
            <?php if (in_array($currentUser['role'], ['secretaire', 'responsable', 'directeur'], true)): ?>
                <a class="btn btn-outline-primary btn-sm" href="index.php?page=contracts">
                    <i class="fas fa-list me-1"></i>Voir tous les dossiers
                </a>
            <?php endif; ?>
            <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
                <a class="btn btn-primary btn-sm" href="index.php?page=contract_create">
                    <i class="fas fa-plus me-1"></i>Creer un dossier
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Dossier</th>
                    <th>Entreprise</th>
                    <th>Etudiant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($dashboardContracts === []): ?>
                    <tr><td colspan="5" class="text-center py-4">Aucun dossier disponible.</td></tr>
                <?php else: ?>
                    <?php foreach ($dashboardContracts as $contract): ?>
                        <tr>
                            <td><?= h($contract['dossier_number']) ?></td>
                            <td><?= h($contract['company_name']) ?></td>
                            <td><?= h($contract['student_name'] ?? '') ?></td>
                            <td><span class="badge bg-<?= h(status_class($contract['status'])) ?>"><?= h(status_label($contract['status'])) ?></span></td>
                            <td><a class="btn btn-outline-primary btn-sm" href="index.php?page=contract_detail&id=<?= h((string) $contract['id']) ?>">Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
