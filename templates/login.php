<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-2">Connexion</h2>
                    <p class="text-muted mb-0">Accedez a la plateforme de suivi des contrats.</p>
                </div>

                <form method="post" action="index.php?page=login">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="nom@demo.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" name="password" placeholder="Votre mot de passe" required>
                    </div>
                    <div class="text-end mb-3">
                        <a href="index.php?page=forgot_password" class="text-decoration-none small">
                            Mot de passe oublié ?
                        </a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>

                <div class="alert alert-info mt-4 mb-0">
                    <h6 class="fw-bold">Comptes de demo</h6>
                    <ul class="mb-0 ps-3 small">
                        <li><code>student@demo.com</code> / <code>student123</code></li>
                        <li><code>secretary@demo.com</code> / <code>secretary123</code></li>
                        <li><code>responsable@demo.com</code> / <code>responsable123</code></li>
                        <li><code>director@demo.com</code> / <code>director123</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
