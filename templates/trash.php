<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Corbeille</h2>
        <p class="text-muted mb-0">Dossiers supprimés - <?= count($deletedContracts) ?> dossier(s)</p>
    </div>
    <a href="index.php?page=contracts" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour aux dossiers
    </a>
</div>

<?php if (empty($deletedContracts)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> La corbeille est vide.
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Numéro dossier</th>
                            <th>Étudiant</th>
                            <th>Entreprise</th>
                            <th>Formation</th>
                            <th>Supprimé le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedContracts as $contract): ?>
                            <tr>
                                <td>
                                    <strong><?= h($contract['dossier_number']) ?></strong>
                                </td>
                                <td>
                                    <div><?= h($contract['student_name']) ?></div>
                                    <small class="text-muted"><?= h($contract['student_email']) ?></small>
                                </td>
                                <td><?= h($contract['company_name']) ?></td>
                                <td>
                                    <small><?= h($contract['formation']) ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($contract['deleted_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <form method="post" action="index.php?page=trash" class="d-inline" 
                                          onsubmit="return confirm('Voulez-vous restaurer ce dossier ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Restaurer">
                                            <i class="fas fa-undo"></i> Restaurer
                                        </button>
                                    </form>
                                    <form method="post" action="index.php?page=trash" class="d-inline" 
                                          onsubmit="return confirm('⚠️ ATTENTION : Cette action est irréversible !\n\nVoulez-vous supprimer définitivement ce dossier ?\nToutes les données (étapes, activités, historique) seront perdues.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_permanent">
                                        <input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer définitivement">
                                            <i class="fas fa-trash"></i> Supprimer définitivement
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-warning mt-3">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Attention :</strong> Les dossiers supprimés définitivement ne peuvent pas être récupérés.
    </div>
<?php endif; ?>
