<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="fas fa-key text-warning" style="font-size: 48px;"></i>
                    <h2 class="fw-bold mb-2 mt-3">Changement de mot de passe requis</h2>
                    <p class="text-muted mb-0">Pour des raisons de sécurité, vous devez changer votre mot de passe provisoire.</p>
                </div>

                <form method="post" action="index.php?page=change_password">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" name="new_password" minlength="12" required>
                        <small class="text-muted">Minimum 12 caractères, avec minuscule, majuscule et chiffre</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="12" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Changer le mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</div>
