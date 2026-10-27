<?php
/**
 * Contact Us Page
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Contact Us & Clinical Support';
$activeNav = 'contact';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-primary-subtle py-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-2">Get in Touch</span>
        <h1 class="fw-bold text-dark">Contact Our Care Team</h1>
        <p class="text-muted lead max-w-700 mx-auto">
            Have questions regarding our veterinary services, herd health inquiries, or system support? We are here to assist you.
        </p>
    </div>
</div>

<div class="container py-5">
    <?php require_once __DIR__ . '/includes/alerts.php'; ?>

    <div class="row g-5">
        <div class="col-lg-5">
            <h4 class="fw-bold mb-4 text-dark">Clinic Location & Info</h4>
            
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-4">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Clinic Headquarters</h6>
                        <p class="text-secondary small mb-0"><?= e(CLINIC_ADDRESS) ?></p>
                        <small class="text-muted">Demonstration Medical Center Facility</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-4">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Phone & Inquiries</h6>
                        <p class="text-secondary small mb-0"><?= e(CLINIC_PHONE) ?></p>
                        <small class="text-muted">Mon – Sat: 8:00 AM – 7:00 PM</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-4">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Email Inquiries</h6>
                        <p class="text-secondary small mb-0"><?= e(CLINIC_EMAIL) ?></p>
                        <small class="text-muted">Inbound messages logged in database</small>
                    </div>
                </div>

                <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3 text-danger">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                        <span class="fw-bold">24/7 Animal Emergency Hotline</span>
                    </div>
                    <div class="fs-5 fw-bold"><?= e(EMERGENCY_PHONE) ?></div>
                    <small class="text-danger-emphasis d-block mt-1">For urgent life-threatening canine, feline, or livestock trauma.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <h4 class="fw-bold mb-2 text-dark">Send Us a Message</h4>
                <p class="text-muted small mb-4">Fill out the form below. Your message will be recorded in our database for administrative review.</p>

                <form action="<?= url('actions/contact_submit.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sender_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" placeholder="e.g. John Doe" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Your Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="e.g. john@example.com" required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>

                        <div class="col-12">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="e.g. Inquiry about herd vaccination or puppy wellness" required>
                            <div class="invalid-feedback">Please enter a subject.</div>
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">Message Details <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Describe your question or message in detail..." required></textarea>
                            <div class="invalid-feedback">Please provide your message.</div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-send-fill me-1"></i> Send Inbound Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
