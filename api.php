<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db_config.php';

// Helper to get DB Connection with retries
function getDbConnection($host, $dbname, $user, $pass)
{
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $conn = new PDO($dsn, $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

// Attempt to connect
$pdo = getDbConnection(DB_HOST, DB_NAME, DB_USER, DB_PASS);
if (!$pdo)
    $pdo = getDbConnection(DB_HOST, DB_NAME, 'root', 'root');
if (!$pdo)
    $pdo = getDbConnection(DB_HOST, DB_NAME, 'root', 'password');
if (!$pdo)
    $pdo = getDbConnection(DB_HOST, DB_NAME, 'root', '');

if (!$pdo) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Veritabanı bağlantısı kurulamadı."]);
    exit();
}

// Helper: Normalize User
function normalizeUser($user)
{
    if (!$user)
        return null;
    $user['readingList'] = json_decode($user['reading_list'] ?? '[]');
    $user['watchList'] = json_decode($user['watch_list'] ?? '[]');
    // Ensure numeric types
    $user['xp'] = (int) $user['xp'];
    $user['level'] = (int) $user['level'];
    return $user;
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

// --- ROUTING ---
try {
    // 1. LOGIN
    if ($endpoint === 'login' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(["status" => "success", "user" => normalizeUser($user)]);
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Hatalı e-posta veya şifre."]);
        }
        exit();
    }

    // 2. REGISTER
    if ($endpoint === 'register' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Bu e-posta zaten kayıtlı."]);
            exit();
        }

        $id = uniqid();
        // Default level: 1
        $sql = "INSERT INTO users (id, username, email, password, role, avatar, banner, xp, level, created_at) VALUES (?, ?, ?, ?, 'USER', ?, ?, 0, 1, ?)";
        $stmt = $pdo->prepare($sql);
        // Use Robohash for avatar
        $avatar = "https://robohash.org/" . md5($email) . "?set=set4";
        $stmt->execute([$id, $username, $email, $password, $avatar, null, time()]);

        // Return new user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "user" => normalizeUser($stmt->fetch())]);
        exit();
    }

    // 3. GET ALL USERS (Admin/Public lists)
    if ($endpoint === 'users' && $method === 'GET') {
        $stmt = $pdo->query("SELECT id, username, email, role, avatar, banner, xp, level, created_at FROM users ORDER BY xp DESC");
        $users = $stmt->fetchAll();
        echo json_encode(array_map('normalizeUser', $users));
        exit();
    }

    // 4. USER ACTIONS (Update, Delete)
    if ($endpoint === 'user/update' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "ID gerekli"]);
            exit();
        }

        $updates = [];
        $params = [];

        if (isset($data['avatar'])) {
            $updates[] = "avatar = ?";
            $params[] = $data['avatar'];
        }
        if (isset($data['banner'])) {
            $updates[] = "banner = ?";
            $params[] = $data['banner'];
        }
        if (isset($data['bio'])) { /* Bio to be added if needed */
        }

        // Admin features or regular user updates
        if (isset($data['role'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
        }
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
        }

        if (empty($updates)) {
            echo json_encode(["status" => "success"]);
            exit();
        }

        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Fetch updated
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success", "user" => normalizeUser($stmt->fetch())]);
        exit();
    }

    if ($endpoint === 'users' && $method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success"]);
        exit();
    }

    // 5. MEDIA & CHAPTERS
    if ($endpoint === 'media' && $method === 'GET') {
        $sql = "SELECT m.*, 
                (SELECT COUNT(*) FROM chapters WHERE media_id = m.id) as chapter_count 
                FROM media m ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $media = $stmt->fetchAll();

        // Normalize JSON fields
        foreach ($media as &$m) {
            $m['genres'] = json_decode($m['genres'] ?? '[]');

            // Fetch chapters/episodes if needed (simplified: fetching specifically when requested or client filters)
            // For now, let's fetch chapters for Manga
            if ($m['type'] === 'MANGA') {
                $cStmt = $pdo->prepare("SELECT * FROM chapters WHERE media_id = ? ORDER BY number ASC");
                $cStmt->execute([$m['id']]);
                $chapters = $cStmt->fetchAll();
                foreach ($chapters as &$c) {
                    $c['pages'] = json_decode($c['pages'] ?? '[]');
                }
                $m['chapters'] = $chapters;
            } else {
                $eStmt = $pdo->prepare("SELECT * FROM episodes WHERE media_id = ? ORDER BY number ASC");
                $eStmt->execute([$m['id']]);
                $m['episodes'] = $eStmt->fetchAll();
            }
        }
        echo json_encode($media);
        exit();
    }

    if ($endpoint === 'media' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        // Basic fields
        $id = $data['id'];
        $type = $data['type'];
        $title = $data['title'];
        $desc = $data['description'];
        $cover = $data['coverImage'];
        $banner = $data['bannerImage'] ?? null;
        $genres = json_encode($data['genres'] ?? []);
        $addedBy = $data['addedBy'];

        $stmt = $pdo->prepare("INSERT INTO media (id, type, title, description, cover_image, banner_image, genres, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $type, $title, $desc, $cover, $banner, $genres, $addedBy]);

        echo json_encode(["status" => "success"]);
        exit();
    }

    if ($endpoint === 'chapters' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = uniqid();
        $mediaId = $data['mediaId'];
        $number = $data['number'];
        $title = $data['title'];
        $pages = json_encode($data['pages']); // Array of Base64 strings
        $date = date("Y-m-d");

        $stmt = $pdo->prepare("INSERT INTO chapters (id, media_id, number, title, pages, upload_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $mediaId, $number, $title, $pages, $date]);

        echo json_encode(["status" => "success"]);
        exit();
    }

    // 6. STATUS NOTES
    if ($endpoint === 'status_notes' && $method === 'GET') {
        // 24 saat
        $yesterday = time() - (24 * 60 * 60);
        $sql = "SELECT n.id, n.user_id, n.content, n.created_at, u.username, u.avatar 
                FROM status_notes n 
                JOIN users u ON n.user_id = u.id 
                WHERE n.created_at > ? 
                ORDER BY n.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$yesterday]);
        $notes = $stmt->fetchAll();

        // Format for frontend
        $formatted = [];
        foreach ($notes as $n) {
            $formatted[] = [
                'id' => $n['id'],
                'userId' => $n['user_id'],
                'username' => $n['username'],
                'avatar' => $n['avatar'],
                'content' => $n['content'],
                'createdAt' => (int) $n['created_at']
            ];
        }
        echo json_encode($formatted);
        exit();
    }

    if ($endpoint === 'status_notes' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = uniqid();
        $userId = $data['userId'];
        $content = $data['content'];
        $createdAt = time();

        $stmt = $pdo->prepare("INSERT INTO status_notes (id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $userId, $content, $createdAt]);

        echo json_encode(["status" => "success"]);
        exit();
    }

    if ($endpoint === 'status_notes' && $method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM status_notes WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success"]);
        exit();
    }

    // 7. SITE SETTINGS
    if ($endpoint === 'settings' && $method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM site_settings WHERE id = 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            // Default response if empty
            echo json_encode([
                "popupHtml" => "",
                "popupLink" => "",
                "isPopupActive" => false,
                "ads" => ["header" => "", "footer" => "", "sidebar" => ""]
            ]);
        } else {
            echo json_encode([
                "popupHtml" => $settings['popup_html'],
                "popupLink" => $settings['popup_link'],
                "isPopupActive" => (bool) $settings['is_popup_active'],
                "ads" => [
                    "header" => $settings['ad_header'],
                    "footer" => $settings['ad_footer'],
                    "sidebar" => $settings['ad_sidebar']
                ]
            ]);
        }
        exit();
    }

    if ($endpoint === 'settings' && $method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        $popupHtml = $data['popupHtml'] ?? '';
        $popupLink = $data['popupLink'] ?? '';
        $isPopupActive = $data['isPopupActive'] ? 1 : 0;
        $adHeader = $data['ads']['header'] ?? '';
        $adFooter = $data['ads']['footer'] ?? '';
        $adSidebar = $data['ads']['sidebar'] ?? '';

        // Upsert logic (Insert if not exists, otherwise update)
        $sql = "INSERT INTO site_settings (id, popup_html, popup_link, is_popup_active, ad_header, ad_footer, ad_sidebar) 
                VALUES (1, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                popup_html = VALUES(popup_html), popup_link = VALUES(popup_link), is_popup_active = VALUES(is_popup_active),
                ad_header = VALUES(ad_header), ad_footer = VALUES(ad_footer), ad_sidebar = VALUES(ad_sidebar)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$popupHtml, $popupLink, $isPopupActive, $adHeader, $adFooter, $adSidebar]);

        echo json_encode(["status" => "success"]);
        exit();
    }

    // Default 404
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found or method not supported"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>