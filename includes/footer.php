<?php
/**
 * Master Footer Component
 * Veterinary Care for Animal Medical Solution
 */
$isDashboard = $isDashboard ?? false;
?>

<?php if (!$isDashboard): ?>
    <!-- Public Footer -->
    <footer class="footer-main">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="navbar-brand-logo text-white mb-3">
                        <span class="brand-icon">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </span>
                        <span>VetCare<span class="text-info">.Solution</span></span>
                    </div>
                    <p class="text-secondary small mb-3">
                        Comprehensive animal healthcare platform bridging animal owners, qualified veterinarians, and administrative care teams. Manage patient profiles, appointments, clinical histories, and vaccinations seamlessly.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-dark border border-secondary text-secondary p-2"><i class="bi bi-shield-check text-success me-1"></i> Core PHP 8+ PDO</span>
                        <span class="badge bg-dark border border-secondary text-secondary p-2"><i class="bi bi-database text-info me-1"></i> MySQL InnoDB</span>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5>Quick Links</h5>
                    <a href="<?= url('index.php') ?>" class="footer-link">Home</a>
                    <a href="<?= url('about.php') ?>" class="footer-link">About Us</a>
                    <a href="<?= url('services.php') ?>" class="footer-link">Our Services</a>
                    <a href="<?= url('veterinarians.php') ?>" class="footer-link">Veterinary Directory</a>
                    <a href="<?= url('contact.php') ?>" class="footer-link">Contact Us</a>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Animal Care</h5>
                    <div class="text-secondary small mb-2"><i class="bi bi-check2 text-primary me-1"></i> Dogs & Cats (Canine & Feline)</div>
                    <div class="text-secondary small mb-2"><i class="bi bi-check2 text-primary me-1"></i> Cattle & Buffaloes (Bovine)</div>
                    <div class="text-secondary small mb-2"><i class="bi bi-check2 text-primary me-1"></i> Horses & Equine Care</div>
                    <div class="text-secondary small mb-2"><i class="bi bi-check2 text-primary me-1"></i> Goats & Sheep (Small Ruminants)</div>
                    <div class="text-secondary small"><i class="bi bi-check2 text-primary me-1"></i> Poultry & Exotic Birds</div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Clinical Support</h5>
                    <p class="text-secondary small mb-2">
                        <i class="bi bi-geo-alt-fill text-primary me-2"></i> <?= e(CLINIC_ADDRESS) ?>
                    </p>
                    <p class="text-secondary small mb-2">
                        <i class="bi bi-telephone-fill text-primary me-2"></i> <?= e(CLINIC_PHONE) ?>
                    </p>
                    <p class="text-secondary small mb-2">
                        <i class="bi bi-envelope-fill text-primary me-2"></i> <?= e(CLINIC_EMAIL) ?>
                    </p>
                    <div class="mt-3 p-2 rounded bg-dark border border-secondary">
                        <span class="text-warning small fw-bold"><i class="bi bi-exclamation-octagon me-1"></i> Emergency Line:</span>
                        <div class="text-white fw-bold"><?= e(EMERGENCY_PHONE) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Mandatory Academic & Educational Disclaimer -->
            <div class="alert alert-secondary bg-dark border border-secondary text-secondary small rounded-3 p-3 mb-3">
                <strong class="text-light"><i class="bi bi-info-circle-fill text-warning me-1"></i> Educational Demonstration Project:</strong>
                This application is developed strictly for educational, college project, and demonstration purposes. It does not replace professional veterinary clinical diagnosis, hands-on physical examinations, or emergency animal hospital intervention. Always consult a licensed veterinarian in-person for urgent pet health concerns.
            </div>
            
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="text-secondary">
                    &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
                </div>
                <div class="d-flex gap-3 text-secondary small">
                    <a href="<?= url('about.php') ?>" class="text-secondary">Privacy & Terms</a>
                    <span>&bull;</span>
                    <a href="<?= url('auth/login.php') ?>" class="text-secondary">Portal Sign In</a>
                    <span>&bull;</span>
                    <a href="<?= url('scripts/create_admin.php') ?>" class="text-secondary">Admin Setup</a>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script src="<?= url('assets/js/main.js') ?>"></script>
<script src="<?= url('assets/js/validation.js') ?>"></script>

</body>
</html>
