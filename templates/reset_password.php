<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="fas fa-key text-success" style="font-size: 48px;"></i>
                    <h2 class="fw-bold mb-2 mt-3">Réinitialisation du mot de passe</h2>
                    <p class="text-muted mb-0">Entrez votre nouveau mot de passe.</p>
                </div>

                <form method="post" action="index.php?page=reset_password&token=<?= h($token ?? '') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" name="new_password" minlength="12" required autofocus>
                        <small class="text-muted">Minimum 12 caractères, avec minuscule, majuscule et chiffre</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="12" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">Réinitialiser le mot de passe</button>
                    
                    <div class="text-center">
                        <a href="index.php?page=login" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la connexion
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
