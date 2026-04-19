<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="fas fa-lock text-primary" style="font-size: 48px;"></i>
                    <h2 class="fw-bold mb-2 mt-3">Mot de passe oublié ?</h2>
                    <p class="text-muted mb-0">Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
                </div>

                <form method="post" action="index.php?page=forgot_password">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label class="form-label">Adresse email</label>
                        <input type="email" class="form-control" name="email" placeholder="votre.email@example.com" required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">Envoyer le lien de réinitialisation</button>
                    
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
