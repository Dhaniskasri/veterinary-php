<?php
/**
 * Flash Message Renderer Component
 * Veterinary Care for Animal Medical Solution
 */

$flashes = get_flashes();
if (!empty($flashes)): ?>
    <div class="flash-messages-container mb-4">
        <?php foreach ($flashes as $flash): 
            $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info', 'primary']) ? $flash['type'] : 'info';
            $icon = match($type) {
                'success' => 'bi-check-circle-fill',
                'danger'  => 'bi-exclamation-triangle-fill',
                'warning' => 'bi-exclamation-circle-fill',
                default   => 'bi-info-circle-fill'
            };
        ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2 rounded-3" role="alert">
                <i class="bi <?= $icon ?> fs-5"></i>
                <div class="flex-grow-1"><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
