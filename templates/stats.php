<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1">Statistiques globales</h2>
                    <p class="text-muted mb-0">Vue d'ensemble des dossiers et du workflow.</p>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Durée moyenne</div>
                    <div class="fs-3 fw-bold text-primary"><?= h((string) $stats['avg_days']) ?> j</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats['by_status'] as $code => $count): ?>
        <div class="col-md-4 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <span class="badge bg-<?= h(status_class($code)) ?> mb-2"><?= h(status_label($code)) ?></span>
                    <div class="fs-4 fw-bold"><?= h((string) $count) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="mb-0">Couverture du workflow</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ordre</th>
                    <th>Étape</th>
                    <th>Complétées</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['steps_summary'] as $row): ?>
                    <tr>
                        <td><?= h((string) ($row['order'] + 1)) ?></td>
                        <td><?= h($row['name']) ?></td>
                        <td><?= h((string) $row['done']) ?></td>
                        <td><?= h((string) $row['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
