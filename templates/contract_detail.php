<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-1"><?= h($contract['dossier_number']) ?></h2>
                    <p class="text-muted mb-0"><?= h($contract['student_name']) ?> • <?= h($contract['company_name']) ?></p>
                </div>
                <div class="text-end">
                    <span class="badge bg-<?= h(status_class($contract['status'])) ?> fs-6"><?= h(status_label($contract['status'])) ?></span>
                    <div class="mt-2 text-muted">Progression: <strong><?= h((string) $contract['progress']) ?>%</strong></div>
                    <?php if (in_array($currentUser['role'], ['secretaire', 'responsable', 'directeur'], true)): ?>
                        <form method="post" action="index.php?page=contract_delete" class="mt-2" 
                              onsubmit="return confirm('Voulez-vous supprimer ce dossier ?\n\nLe dossier sera déplacé dans la corbeille et pourra être restauré.');">
                            <input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Etapes du dossier</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($contract['steps'] as $step): ?>
                        <div class="timeline-item <?= $step['state'] === 'done' ? 'done' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h6 class="mb-1"><?= h($step['step_name']) ?></h6>
                                    <div class="small text-muted mb-2">Etat: <?= h($step['state']) ?><?php if (!empty($step['done_by_name'])): ?> · Par <?= h($step['done_by_name']) ?><?php endif; ?></div>
                                    <?php if (!empty($step['note'])): ?>
                                        <div class="small text-muted"><?= h($step['note']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (can_edit_step($currentUser, $step['step_name'])): ?>
                                    <form method="post" action="index.php?page=step_update" class="row g-2 align-items-start step-form">
                                        <input type="hidden" name="contract_id" value="<?= h((string) $contract['id']) ?>">
                                        <input type="hidden" name="step_id" value="<?= h((string) $step['id']) ?>">
                                        <?php if ($currentUser['role'] === 'etudiant'): ?>
                                            <input type="hidden" name="state" value="done">
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-outline-primary btn-sm" <?= $step['state'] === 'done' ? 'disabled' : '' ?>>Signaler</button>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-md-auto">
                                                <select class="form-select form-select-sm" name="state">
                                                    <option value="pending" <?= $step['state'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                                    <option value="done" <?= $step['state'] === 'done' ? 'selected' : '' ?>>Completee</option>
                                                    <option value="rejected" <?= $step['state'] === 'rejected' ? 'selected' : '' ?>>Refusee</option>
                                                </select>
                                            </div>
                                            <div class="col-md">
                                                <input type="text" class="form-control form-control-sm" name="note" placeholder="Note optionnelle">
                                            </div>
                                            <div class="col-md-auto">
                                                <button type="submit" class="btn btn-primary btn-sm">Mettre a jour</button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Historique des actions</h5>
                <a class="btn btn-outline-primary btn-sm" href="index.php?page=contract_history&id=<?= h((string) $contract['id']) ?>">Voir tout</a>
            </div>
            <div class="card-body">
                <?php if ($contract['history'] === []): ?>
                    <p class="text-muted mb-0">Aucune action enregistree.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($contract['history'], 0, 5) as $entry): ?>
                            <li class="list-group-item px-0">
                                <strong><?= h($entry['user_name']) ?></strong>
                                <small class="text-muted"> - <?= h((string) $entry['created_at']) ?></small>
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
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations dossier</h5>
            </div>
            <div class="card-body">
                <p><strong>Etudiant:</strong><br><?= h($contract['student_name']) ?><br><small class="text-muted"><?= h($contract['student_email']) ?></small></p>
                <p><strong>Numero:</strong><br><?= h($contract['student_number'] ?? '') ?></p>
                <p><strong>Formation:</strong><br><?= h($contract['formation']) ?></p>
                <p><strong>Entreprise:</strong><br><?= h($contract['company_name']) ?></p>
                <p><strong>OPCO:</strong><br><?= h($contract['opco']) ?></p>
                <p class="mb-0"><strong>Statut:</strong><br><?= h(status_label($contract['status'])) ?></p>
            </div>
        </div>

        <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Modifier le statut</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=status_update">
                        <input type="hidden" name="contract_id" value="<?= h((string) $contract['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label">Statut global</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statuses as $code): ?>
                                    <option value="<?= h($code) ?>" <?= $contract['status'] === $code ? 'selected' : '' ?>><?= h(status_label($code)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
