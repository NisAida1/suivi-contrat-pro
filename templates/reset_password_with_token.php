<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mt-2">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="fas fa-envelope-open text-success" style="font-size: 48px;"></i>
                    <h2 class="fw-bold mb-2 mt-3">Vérifier le code de confirmation</h2>
                    <p class="text-muted mb-0">Un email de confirmation a été envoyé à <strong><?= h($currentUser['email'] ?? '') ?></strong></p>
                </div>

                <form method="post" action="index.php?page=reset_password_with_token">
                    <?= csrf_field() ?>
                    
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Code reçu ?</strong>
                        <p class="mb-0 mt-2">Vérifiez votre boîte email (y compris les spams). Le code a été envoyé à <strong><?= h($currentUser['email'] ?? '') ?></strong> et est valide pendant 1 heure.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Code de confirmation</label>
                        <input type="text" class="form-control form-control-lg text-center" name="token" placeholder="Entrez le code" required autofocus>
                        <small class="text-muted">Le code se trouve dans l'email reçu</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-check me-2"></i> Vérifier et continuer
                    </button>
                    
                    <div class="text-center">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                        </a>
                        <br>
                        <small class="text-muted mt-2 d-block">Vous n'avez pas reçu le code ? 
                            <a href="index.php?page=change_password_request" class="text-decoration-none">Renvoyer le code</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3 border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="card-title fw-bold mb-3"><i class="fas fa-question-circle me-2 text-info"></i> Besoin d'aide ?</h6>
                <ul class="small text-muted mb-0">
                    <li><strong>Code expiré ?</strong> Cliquez sur "Renvoyer le code" pour en recevoir un nouveau</li>
                    <li><strong>Email non reçu ?</strong> Vérifiez votre dossier spam</li>
                    <li><strong>Toujours pas reçu ?</strong> Contactez votre administrateur</li>
                </ul>
            </div>
        </div>
    </div>
</div>
