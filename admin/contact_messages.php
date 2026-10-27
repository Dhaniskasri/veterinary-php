<?php
/**
 * Administrator: Inbound Contact Messages
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_ADMIN);

$pageTitle = 'Inbound Contact Messages';
$isDashboard = true;
$activePage = 'contact_messages';
$user = current_user();

// Handle status toggle / deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();
    $msgId = (int)($_POST['message_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($msgId > 0) {
        if ($action === 'mark_read') {
            $up = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $up->execute([$msgId]);
            set_flash('success', 'Message marked as read.');
        } elseif ($action === 'mark_replied') {
            $up = $pdo->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
            $up->execute([$msgId]);
            set_flash('success', 'Message marked as replied.');
        } elseif ($action === 'delete') {
            $del = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $del->execute([$msgId]);
            set_flash('info', 'Message deleted.');
        }
    }
    redirect('admin/contact_messages.php');
}

$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Inbound Contact Messages</h5>
                    <small class="text-muted">Inquiries received from the public website contact form</small>
                </div>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Status Filter -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted small fw-bold me-2">Filter:</span>
                    <a href="<?= url('admin/contact_messages.php') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        All Messages (<?= count($messages) ?>)
                    </a>
                    <a href="<?= url('admin/contact_messages.php?status=unread') ?>" class="btn btn-sm <?= $statusFilter === 'unread' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Unread
                    </a>
                    <a href="<?= url('admin/contact_messages.php?status=read') ?>" class="btn btn-sm <?= $statusFilter === 'read' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Read
                    </a>
                    <a href="<?= url('admin/contact_messages.php?status=replied') ?>" class="btn btn-sm <?= $statusFilter === 'replied' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
                        Replied
                    </a>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <?php if (empty($messages)): ?>
                    <div class="empty-state-box">
                        <div class="empty-state-icon"><i class="bi bi-envelope-open"></i></div>
                        <h5 class="fw-bold">No Messages Found</h5>
                        <p class="text-muted small">No inquiries match the current filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Sender</th>
                                    <th>Subject & Message</th>
                                    <th>Received On</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $m): ?>
                                    <tr class="<?= $m['status'] === 'unread' ? 'table-warning bg-opacity-25' : '' ?>">
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($m['sender_name']) ?></div>
                                            <a href="mailto:<?= e($m['email']) ?>" class="small text-muted text-decoration-none">
                                                <?= e($m['email']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($m['subject']) ?></div>
                                            <p class="text-secondary small mb-0 mt-1"><?= nl2br(e($m['message'])) ?></p>
                                        </td>
                                        <td><small><?= format_date($m['created_at'], 'M j, Y g:i A') ?></small></td>
                                        <td><?= badge_status($m['status']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($m['status'] === 'unread'): ?>
                                                    <form action="<?= url('admin/contact_messages.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="mark_read">
                                                        <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border" title="Mark Read">
                                                            <i class="bi bi-envelope-open"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($m['status'] !== 'replied'): ?>
                                                    <form action="<?= url('admin/contact_messages.php') ?>" method="POST" class="d-inline m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="mark_replied">
                                                        <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Replied">
                                                            <i class="bi bi-check2-all"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="<?= url('admin/contact_messages.php') ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Delete this message?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
