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
                    <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
                        <form method="post" action="index.php?page=contract_delete" class="mt-2" 
                              onsubmit="return confirm('Voulez-vous supprimer ce dossier ?\n\nLe dossier sera déplacé dans la corbeille et pourra être restauré.');">
                            <?= csrf_field() ?>
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
                <h5 class="mb-0">Étapes du dossier</h5>
                <small class="text-muted">Les étapes marquées d’un <span class="text-danger">*</span> sont obligatoires.</small>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php $rightSteps = ['CERFA envoye a l OPCO par l etudiant', 'CERFA recu par l ecole']; ?>
                    <?php foreach ($contract['steps'] as $step): ?>
                        <div class="timeline-item <?= $step['state'] === 'done' ? 'done' : '' ?> <?= in_array($step['step_name'], $rightSteps) ? 'timeline-item-right' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap <?= in_array($step['step_name'], $rightSteps) ? 'flex-row-reverse' : '' ?>">
                                <div>
                                    <h6 class="mb-1">
                                        <?= h(step_label($step['step_name'])) ?>
                                        <?php if (is_mandatory_step($step['step_name'])): ?>
                                            <span class="text-danger" title="Étape obligatoire">*</span>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="small text-muted mb-2">État : <?= h(step_state_label($step['state'])) ?><?php if (!empty($step['done_by_name'])): ?> · Par <?= h($step['done_by_name']) ?><?php if (!empty($step['done_at'])): ?> (<?= h(date('d/m/Y H:i', strtotime($step['done_at']))) ?>)<?php endif; ?><?php endif; ?></div>
                                    <?php if (!empty($step['note'])): ?>
                                        <?php 
                                            $displayNote = $step['note'];
                                            if (strpos($step['step_name'], 'Decision OPCO') === 0 && strpos($step['note'], ':') !== false) {
                                                [$choice, $details] = explode(':', $step['note'], 2);
                                                $choiceLabel = trim($choice) === 'demande-documents'
                                                    ? 'Demande de documents supplémentaires ou de modifications'
                                                    : ucfirst(str_replace('-', ' ', trim($choice)));
                                                $displayNote = 'Décision : ' . $choiceLabel . (trim($details) ? ' - ' . trim($details) : '');
                                            } elseif (strpos($step['step_name'], 'Decision OPCO') === 0) {
                                                $choiceLabel = $step['note'] === 'demande-documents'
                                                    ? 'Demande de documents supplémentaires ou de modifications'
                                                    : ucfirst(str_replace('-', ' ', $step['note']));
                                                $displayNote = 'Décision : ' . $choiceLabel;
                                            }
                                        ?>
                                        <div class="small text-muted"><?= h($displayNote) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (can_edit_step($currentUser, $step['step_name'])): ?>
                                    <?php $canComplete = can_complete_step($contract['steps'], (int) $step['step_order']); ?>
                                    <?php $isExclusiveDone = is_step_mutually_exclusive_with_done($contract['steps'], $step['step_name']); ?>
                                    <?php if (!$canComplete && (int) $step['step_order'] > 1): ?>
                                        <div class="alert alert-warning mb-0 py-2 w-100">
                                            <small class="d-flex align-items-center gap-2">
                                                <i class="fas fa-lock"></i>
                                                <?php 
                                                    $prevOrder = (int) $step['step_order'] - 1;
                                                    $prevStep = null;
                                                    foreach ($contract['steps'] as $s) {
                                                        if ((int) $s['step_order'] === $prevOrder) {
                                                            $prevStep = $s;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                Cette étape ne peut être complétée que si <strong><?= h(step_label($prevStep['step_name'] ?? 'l\'étape précédente')) ?></strong> est complétée.
                                            </small>
                                        </div>
                                    <?php elseif ($isExclusiveDone): ?>
                                        <div class="alert alert-info mb-0 py-2 w-100">
                                            <small class="d-flex align-items-center gap-2">
                                                <i class="fas fa-info-circle"></i>
                                                <?php
                                                    $exclusiveSteps = [
                                                        'APT obtenue' => 'APT refusee',
                                                        'APT refusee' => 'APT obtenue',
                                                    ];
                                                    $exclusiveName = $exclusiveSteps[$step['step_name']] ?? '';
                                                ?>
                                                L'étape <strong><?= h(step_label($exclusiveName)) ?></strong> est déjà complétée, cette étape n'est donc pas applicable.
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <form method="post" action="index.php?page=step_update" class="row g-2 align-items-start step-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="contract_id" value="<?= h((string) $contract['id']) ?>">
                                            <input type="hidden" name="step_id" value="<?= h((string) $step['id']) ?>">
                                            <?php if ($currentUser['role'] === 'etudiant'): ?>
                                                <input type="hidden" name="state" value="done">
                                                <div class="col-auto">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm" <?= $step['state'] === 'done' ? 'disabled' : '' ?>>Signaler</button>
                                                </div>
                                            <?php else: ?>
                                                <?php $customChoices = get_step_custom_choices($step['step_name']); ?>
                                                <?php if ($customChoices !== null): ?>
                                                    <?php
                                                        $currentChoice = $step['note'] ?? '';
                                                        $currentDocuments = '';
                                                        if (strpos($currentChoice, ':') !== false) {
                                                            [$currentChoice, $currentDocuments] = explode(':', $currentChoice, 2);
                                                            $currentDocuments = trim($currentDocuments);
                                                        }
                                                    ?>
                                                    <div class="col-md-auto">
                                                        <select class="form-select form-select-sm decision-choice-select" data-step-id="<?= h((string) $step['id']) ?>" name="note">
                                                            <option value="">Choisir une décision</option>
                                                            <option value="valide" <?= $currentChoice === 'valide' ? 'selected' : '' ?>>Valide</option>
                                                            <option value="refuse" <?= $currentChoice === 'refuse' ? 'selected' : '' ?>>Refuse</option>
                                                            <option value="demande-documents" <?= $currentChoice === 'demande-documents' ? 'selected' : '' ?>>Demande de documents supplémentaires ou de modifications</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md doc-note-container" <?= $currentChoice !== 'demande-documents' ? 'style="display:none;"' : '' ?>>
                                                        <input type="text" class="form-control form-control-sm doc-note-field" name="doc-note" placeholder="Préciser les documents supplémentaires ou modifications demandés" value="<?= h($currentDocuments) ?>">
                                                    </div>
                                                    <input type="hidden" name="state" value="done">
                                                    <div class="col-md-auto">
                                                        <button type="submit" class="btn btn-primary btn-sm">Mettre a jour</button>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="col-md-auto">
                                                        <select class="form-select form-select-sm" name="state">
                                                            <option value="pending" <?= $step['state'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                                            <option value="done" <?= $step['state'] === 'done' ? 'selected' : '' ?>>Complétée</option>
                                                            <option value="rejected" <?= $step['state'] === 'rejected' ? 'selected' : '' ?>>Refusée</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md">
                                                        <input type="text" class="form-control form-control-sm" name="note" placeholder="Note optionnelle">
                                                    </div>
                                                    <div class="col-md-auto">
                                                        <button type="submit" class="btn btn-primary btn-sm">Mettre a jour</button>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
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
                    <p class="text-muted mb-0">Aucune action enregistrée.</p>
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
                <p><strong>Étudiant :</strong><br><?= h($contract['student_name']) ?><br><small class="text-muted"><?= h($contract['student_email']) ?></small></p>
                <p><strong>Numéro :</strong><br><?= h($contract['student_number'] ?? '') ?></p>
                <p><strong>Formation :</strong><br><?= h($contract['formation']) ?></p>
                <p><strong>Année universitaire :</strong><br><?= h($contract['academic_year'] ?? '-') ?></p>
                <p><strong>Entreprise :</strong><br><?= h($contract['company_name']) ?></p>
                <p class="mb-0"><strong>Statut :</strong><br><?= h(status_label($contract['status'])) ?></p>
            </div>
        </div>

        <?php if (in_array($currentUser['role'], ['secretaire', 'responsable'], true)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Statut du dossier</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Statut global (mis à jour automatiquement)</label>
                        <div class="p-3 bg-light rounded border">
                            <strong><?= h(status_label($contract['status'])) ?></strong>
                            <small class="text-muted d-block mt-2">
                                Le statut se met à jour automatiquement en fonction de l’avancement des étapes.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Gestion de l'affichage du champ de note pour la décision OPCO
document.querySelectorAll('.decision-choice-select').forEach(select => {
    select.addEventListener('change', function() {
        const container = this.closest('form').querySelector('.doc-note-container');
        if (container) {
            if (this.value === 'demande-documents') {
                container.style.display = 'block';
                container.querySelector('.doc-note-field').required = true;
            } else {
                container.style.display = 'none';
                container.querySelector('.doc-note-field').required = false;
            }
        }
    });
});

// Validation du formulaire pour Décision OPCO
document.querySelectorAll('.step-form').forEach(form => {
    const choiceSelect = form.querySelector('.decision-choice-select');
    if (choiceSelect) {
        form.addEventListener('submit', function(e) {
            const choice = choiceSelect.value;
            const stepTitleText = form.closest('.timeline-item')?.querySelector('h6')?.textContent || '';
            const isDecisionOpco = stepTitleText.includes('Decision OPCO') || stepTitleText.includes('Décision OPCO');
            
            if (choice === 'demande-documents') {
                const docNoteField = form.querySelector('.doc-note-field');
                if (!docNoteField.value.trim()) {
                    e.preventDefault();
                    alert('Veuillez préciser les documents supplémentaires ou modifications demandés');
                    docNoteField.focus();
                    return;
                }
            }
            
            // Si c'est Décision OPCO avec refusée, faire la mise à jour en AJAX
            if (isDecisionOpco && choice === 'refuse') {
                e.preventDefault();
                
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Mettre à jour le badge du statut
                    const badgeElement = document.querySelector('.badge');
                    if (badgeElement) {
                        badgeElement.className = 'badge bg-danger fs-6';
                        badgeElement.textContent = 'Clôturé';
                    }
                    
                    // Afficher un message de succès
                    alert('Décision OPCO enregistrée. Le dossier est maintenant clôturé.');
                    
                    // Recharger la page après 1 seconde
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la mise à jour.');
                });
            }
        });
    }
});
</script>
