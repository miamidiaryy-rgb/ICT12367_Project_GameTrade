<?php
require_once "condb.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $games = $conn->query("SELECT * FROM games ORDER BY game_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $conn->query("SELECT * FROM categories ORDER BY category_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT i.*, g.game_name, c.category_name, u.username AS seller_username, u.fullname AS seller_name,
                   EXISTS(SELECT 1 FROM blacklist b WHERE b.seller_id = i.seller_id) AS is_blacklisted,
                   (SELECT b.reason FROM blacklist b WHERE b.seller_id = i.seller_id LIMIT 1) AS blacklist_reason,
                   COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.seller_id = i.seller_id), 0) AS avg_rating,
                   (SELECT COUNT(*) FROM reviews r WHERE r.seller_id = i.seller_id) AS review_count
            FROM items i
            INNER JOIN games g ON i.game_id = g.game_id
            INNER JOIN categories c ON i.category_id = c.category_id
            INNER JOIN users u ON i.seller_id = u.user_id
            ORDER BY i.item_id DESC";
    $items = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "items" => $items, "games" => $games, "categories" => $categories]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input['action'] ?? 'create';

try {
    if ($action === 'create') {
        $stmt = $conn->prepare(
            "INSERT INTO items (seller_id, game_id, category_id, item_name, description, price, image, status)
             VALUES (:sid, :gid, :cid, :name, :desc, :price, :img, 'พร้อมขาย')"
        );
        $stmt->execute([
            ':sid' => intval($input['seller_id'] ?? 2),
            ':gid' => intval($input['game_id'] ?? 1),
            ':cid' => intval($input['category_id'] ?? 1),
            ':name' => trim($input['item_name'] ?? ''),
            ':desc' => trim($input['description'] ?? ''),
            ':price' => floatval($input['price'] ?? 0),
            ':img' => trim($input['image'] ?? 'valorant_skin.jpg'),
        ]);
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "ลงประกาศขายไอเทมเรียบร้อยแล้ว"]);
    } elseif ($action === 'update') {
        $stmt = $conn->prepare(
            "UPDATE items SET game_id=:gid, category_id=:cid, item_name=:name, description=:desc, price=:price, status=:st
             WHERE item_id=:id"
        );
        $stmt->execute([
            ':gid' => intval($input['game_id'] ?? 1),
            ':cid' => intval($input['category_id'] ?? 1),
            ':name' => trim($input['item_name'] ?? ''),
            ':desc' => trim($input['description'] ?? ''),
            ':price' => floatval($input['price'] ?? 0),
            ':st' => $input['status'] ?? 'พร้อมขาย',
            ':id' => intval($input['item_id'] ?? 0),
        ]);
        echo json_encode(["success" => true, "message" => "แก้ไขประกาศขายเรียบร้อยแล้ว"]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM items WHERE item_id = :id");
        $stmt->execute([':id' => intval($input['item_id'] ?? 0)]);
        echo json_encode(["success" => true, "message" => "ลบประกาศขายเรียบร้อยแล้ว"]);
    }
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
