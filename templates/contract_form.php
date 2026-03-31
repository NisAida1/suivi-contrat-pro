<?php
$formations = [
    'Cycle Ingenieur Informatique',
    'Cycle Ingenieur Genie Industriel',
    'Cycle Ingenieur Genie Energetique et Environnement',
    'Cycle Ingenieur Agroalimentaire',
];
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="mb-4">
            <h2 class="mb-2">Créer un nouveau dossier</h2>
            <p class="text-muted">Complétez les informations de l'étudiant et de l'entreprise.</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" action="index.php?page=contract_create" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" id="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" id="last_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Numéro étudiant <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="student_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email universitaire <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="student_email" id="student_email" placeholder="ex: prenom.nom@etu.eilco.univ-littoral.fr" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Formation <span class="text-danger">*</span></label>
                        <select class="form-select" name="formation" required>
                            <option value="">Choisir</option>
                            <?php foreach ($formations as $formation): ?>
                                <option value="<?= h($formation) ?>"><?= h($formation) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Année universitaire <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="academic_year" placeholder="ex: 2025-2026" pattern="\d{4}-\d{4}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Entreprise <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company_name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nationalité <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_eu_eea_swiss" id="is_eu_yes" value="1" required>
                                <label class="form-check-label" for="is_eu_yes">
                                    <strong>Étudiant de l'UE, l'EEE ou de la Suisse</strong>
                                    <small class="text-muted d-block">L'étape "Autorisation Provisoire de Travail" (APT) ne sera pas demandée</small>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="radio" name="is_eu_eea_swiss" id="is_eu_no" value="0" required>
                                <label class="form-check-label" for="is_eu_no">
                                    <strong>Étudiant hors UE/EEE/Suisse</strong>
                                    <small class="text-muted d-block">L'étape "Autorisation Provisoire de Travail" (APT) sera requise</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-end mt-3">
                        <a class="btn btn-secondary" href="index.php?page=contracts">Annuler</a>
                        <button type="submit" class="btn btn-primary">Créer le dossier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


