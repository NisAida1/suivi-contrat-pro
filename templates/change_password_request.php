<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mt-2">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-5">
                    <i class="fas fa-lock text-primary" style="font-size: 48px;"></i>
                    <h2 class="fw-bold mb-2 mt-3">Changer votre mot de passe</h2>
                    <p class="text-muted mb-0">Choisissez la méthode qui vous convient le mieux</p>
                </div>

                <div class="row g-4">
                    <!-- Option 1: Avec l'ancien mot de passe -->
                    <div class="col-md-6">
                        <div class="card border-0 h-100 shadow-sm hover-card" style="cursor: pointer; transition: all 0.3s;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-key text-info" style="font-size: 40px;"></i>
                                </div>
                                <h5 class="card-title fw-bold mb-3">Avec votre mot de passe actuel</h5>
                                <p class="card-text text-muted small mb-4">Vous vous souvenez de votre mot de passe ? Utilisez cette méthode rapide et directe.</p>
                                <ul class="small text-muted text-start mb-4">
                                    <li><i class="fas fa-check text-success me-2"></i> Accès immédiat</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Aucun email requis</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Changement instantané</li>
                                </ul>
                                <a href="index.php?page=change_password" class="btn btn-info w-100">
                                    <i class="fas fa-arrow-right me-2"></i> Continuer
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Avec code par email -->
                    <div class="col-md-6">
                        <div class="card border-0 h-100 shadow-sm hover-card" style="cursor: pointer; transition: all 0.3s;">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-envelope text-success" style="font-size: 40px;"></i>
                                </div>
                                <h5 class="card-title fw-bold mb-3">Avec un code par email</h5>
                                <p class="card-text text-muted small mb-4">Vous avez oublié votre mot de passe ? Recevez un code par email pour le réinitialiser.</p>
                                <ul class="small text-muted text-start mb-4">
                                    <li><i class="fas fa-check text-success me-2"></i> Pas besoin d'ancien mot de passe</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Code sécurisé par email</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Valable 1 heure</li>
                                </ul>
                                <form method="post" action="index.php?page=change_password_send_code" class="w-100">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-arrow-right me-2"></i> Continuer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>

        <style>
            .hover-card {
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .hover-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            }
        </style>
    </div>
</div>
