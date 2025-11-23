<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/db.php';
$baseUrl = cg_detect_base_url();

$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS email_change_tokens (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    new_email VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY token_hash (token_hash),
    KEY user_id (user_id),
    CONSTRAINT ect_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$token    = trim($_GET['token'] ?? '');
$valid    = false;
$userName = '';
$newEmail = '';
$oldEmail = '';

if ($token) {
    $tokenHash = hash('sha256', $token);
    $s = $db->prepare(
        "SELECT u.name, u.email AS old_email, ect.new_email
         FROM email_change_tokens ect
         JOIN users u ON u.id = ect.user_id
         WHERE ect.token_hash = ? AND ect.expires_at > NOW() LIMIT 1"
    );
    $s->execute([$tokenHash]);
    $row = $s->fetch();
    if ($row) {
        $valid    = true;
        $userName = htmlspecialchars($row['name'],      ENT_QUOTES, 'UTF-8');
        $oldEmail = htmlspecialchars($row['old_email'], ENT_QUOTES, 'UTF-8');
        $newEmail = htmlspecialchars($row['new_email'], ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
