<?php
session_start();

require_once __DIR__ . "/../../src/config/database.php";
require_once __DIR__ . "/../../src/utils/mailer.php"; 

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

$success_message = '';
$error_message = '';


if (isset($_GET["read"])) {
    $id = (int) $_GET["read"];
    $stmt = $pdo->prepare("UPDATE messages_contact SET lu = 1 WHERE id = ?");
    $stmt->execute([$id]);

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    header("Location: messages.php");
    exit();
}


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM messages_contact WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: messages.php?success=Message supprimé");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $message_id = (int) $_POST['message_id'];
    $reply_message = trim($_POST['reply_message']);
    $reply_subject = trim($_POST['reply_subject']);

    if (empty($reply_message)) {
        $error_message = "Le message de réponse ne peut pas être vide";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM messages_contact WHERE id = ?");
        $stmt->execute([$message_id]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($original) {
            $nom_client = htmlspecialchars($original['prenom'] ?? $original['nom']);
            $contenu_email = "
            <div style='font-family: Arial; max-width: 600px; margin: 0 auto;'>
                <h2 style='color:#d18b8b;'>Sisi's Beauty</h2>
                <p>Bonjour $nom_client,</p>
                <p><strong>Votre message :</strong><br>" . nl2br(htmlspecialchars($original['message'])) . "</p>
                <p><strong>Notre réponse :</strong><br>" . nl2br(htmlspecialchars($reply_message)) . "</p>
                <hr><small>Email automatique</small>
            </div>";

            $mailer = new Mailer();
            $result = $mailer->sendMail($original['email'], $reply_subject ?: "Réponse Sisi's Beauty", $contenu_email);

            if (!empty($result['success'])) {
                $stmt = $pdo->prepare("UPDATE messages_contact SET repondu = 1, date_reponse = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
                $success_message = "Email envoyé à " . $original['email'];
            } else {
                $error_message = $result['error'] ?? "Erreur d'envoi";
            }
        }
    }
    
    if (!empty($success_message)) {
        header("Location: messages.php?success=" . urlencode($success_message));
        exit();
    } elseif (!empty($error_message)) {
        header("Location: messages.php?error=" . urlencode($error_message));
        exit();
    }
}


if (isset($_GET['success'])) $success_message = $_GET['success'];
if (isset($_GET['error'])) $error_message = $_GET['error'];


$messages = $pdo->query("
    SELECT * FROM messages_contact 
    ORDER BY CASE WHEN lu = 0 THEN 0 ELSE 1 END, created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total = count($messages);
$non_lus = count(array_filter($messages, fn($m) => !$m['lu']));
$non_repondus = count(array_filter($messages, fn($m) => empty($m['repondu'])));

$page_title = "Messages clients";
$page_icon = "📬";


ob_start();
?>
<link rel="stylesheet" href="/css/messages.css">
<script src="/js/messages.js"></script>
<div class="messages-container">
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $non_lus ?></div>
            <div class="stat-label">Non lus</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $non_repondus ?></div>
            <div class="stat-label">Non répondus</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $total - $non_repondus ?></div>
            <div class="stat-label">Répondus</div>
        </div>
    </div>

    
    <?php if ($success_message): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error"> <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    
    <div class="filters">
        <button class="filter-btn active" data-filter="all">Tous</button>
        <button class="filter-btn" data-filter="unread">Non lus</button>
        <button class="filter-btn" data-filter="unreplied">Non répondus</button>
        <button class="filter-btn" data-filter="replied">Répondus</button>
    </div>

    
    <div class="messages-list">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>Aucun message</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <div class="message-card <?= !$m['lu'] ? 'unread' : '' ?> <?= empty($m['repondu']) ? 'unreplied' : '' ?>" 
                     data-id="<?= $m['id'] ?>"
                     data-nom="<?= htmlspecialchars($m['nom']) ?>"
                     data-prenom="<?= htmlspecialchars($m['prenom'] ?? '') ?>"
                     data-email="<?= htmlspecialchars($m['email']) ?>"
                     data-telephone="<?= htmlspecialchars($m['telephone'] ?? '') ?>"
                     data-sujet="<?= htmlspecialchars($m['sujet']) ?>"
                     data-message="<?= htmlspecialchars(str_replace('"', '&quot;', $m['message'])) ?>"
                     data-date="<?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>">
                    
                    <div class="card-badges">
                        <?php if (!$m['lu']): ?>
                            <span class="badge badge-unread">Non lu</span>
                        <?php endif; ?>
                        <?php if (empty($m['repondu'])): ?>
                            <span class="badge badge-unreplied">Non répondu</span>
                        <?php else: ?>
                            <span class="badge badge-replied">Répondu</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-header">
                        <div class="sender">
                            <strong><?= htmlspecialchars($m['prenom'] ?? '') ?> <?= htmlspecialchars($m['nom']) ?></strong>
                            <span>&lt;<?= htmlspecialchars($m['email']) ?>&gt;</span>
                        </div>
                        <div class="date"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></div>
                    </div>
                    
                    <div class="subject"><?= htmlspecialchars($m['sujet']) ?></div>
                    <div class="preview"><?= htmlspecialchars(substr($m['message'], 0, 150)) ?>...</div>
                    
                    <div class="card-actions">
                        <button class="btn-view" onclick="viewMessage(<?= $m['id'] ?>)">Voir</button>
                        <button class="btn-reply" onclick="replyMessage(<?= $m['id'] ?>)">Répondre</button>
                        <a href="?delete=<?= $m['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ?')">Supprimer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Détail du message</h2>
            <button class="close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail"><label>De :</label><span id="viewSender"></span></div>
            <div class="detail"><label>Email :</label><span id="viewEmail"></span></div>
            <div class="detail"><label>Téléphone :</label><span id="viewPhone"></span></div>
            <div class="detail"><label>Sujet :</label><span id="viewSubject"></span></div>
            <div class="detail"><label>Date :</label><span id="viewDate"></span></div>
            <div class="detail"><label>Message :</label><div id="viewMessage" class="message-box"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn-reply" onclick="replyFromView()">Répondre</button>
            <button class="btn-secondary" onclick="closeViewModal()">Fermer</button>
        </div>
    </div>
</div>


<div id="replyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Répondre au message</h2>
            <button class="close" onclick="closeReplyModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="message_id" id="replyId">
                <div class="detail"><label>À :</label><span id="replyTo"></span></div>
                <div class="form-group">
                    <label>Objet</label>
                    <input type="text" name="reply_subject" id="replySubject" class="form-control" value="Réponse Sisi's Beauty">
                </div>
                <div class="form-group">
                    <label>Réponse</label>
                    <textarea name="reply_message" id="replyMessage" rows="6" class="form-control" placeholder="Écrivez votre réponse..."></textarea>
                </div>
                <div class="original-msg">
                    <strong>Message original :</strong>
                    <p id="replyOriginal"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-send">Envoyer</button>
                <button type="button" class="btn-secondary" onclick="closeReplyModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include "layout.php";
?>