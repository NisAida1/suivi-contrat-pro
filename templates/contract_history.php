<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Historique - <?= h($contract['dossier_number']) ?></h4>
        </div>
        <a class="btn btn-secondary btn-sm" href="index.php?page=contract_detail&id=<?= h((string) $contract['id']) ?>">Retour au dossier</a>
    </div>
    <div class="card-body">
        <?php if ($contract['history'] === []): ?>
            <p class="text-muted mb-0">Aucune action enregistrée.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($contract['history'] as $entry): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <strong><?= h($entry['user_name']) ?></strong>
                            <small class="text-muted"><?= h((string) $entry['created_at']) ?></small>
                        </div>
                        <div><?= h($entry['action']) ?></div>
                        <?php if (!empty($entry['details'])): ?>
                            <small class="text-muted"><?= h($entry['details']) ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
