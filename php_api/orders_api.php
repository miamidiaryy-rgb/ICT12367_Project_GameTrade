<?php
require_once "condb.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $orders = $conn->query(
        "SELECT o.*, i.item_name, b.fullname AS buyer_name, s.fullname AS seller_name, s.username AS seller_username
         FROM orders o
         INNER JOIN items i ON o.item_id = i.item_id
         INNER JOIN users b ON o.buyer_id = b.user_id
         INNER JOIN users s ON o.seller_id = s.user_id
         ORDER BY o.order_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $reviews = $conn->query(
        "SELECT r.*, b.fullname AS buyer_name, s.fullname AS seller_name
         FROM reviews r
         INNER JOIN users b ON r.buyer_id = b.user_id
         INNER JOIN users s ON r.seller_id = s.user_id
         ORDER BY r.review_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "orders" => $orders, "reviews" => $reviews]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input['action'] ?? 'create_order';

try {
    if ($action === 'create_order') {
        $conn->beginTransaction();
        $stmt = $conn->prepare(
            "INSERT INTO orders (item_id, buyer_id, seller_id, price, order_status)
             VALUES (:iid, :bid, :sid, :price, 'สำเร็จ')"
        );
        $stmt->execute([
            ':iid' => intval($input['item_id']),
            ':bid' => intval($input['buyer_id']),
            ':sid' => intval($input['seller_id']),
            ':price' => floatval($input['price']),
        ]);
        $orderId = $conn->lastInsertId();

        $tx = $conn->prepare(
            "INSERT INTO transactions (order_id, buyer_id, seller_id, amount, transaction_status)
             VALUES (:oid, :bid, :sid, :amt, 'สำเร็จ')"
        );
        $tx->execute([
            ':oid' => $orderId,
            ':bid' => intval($input['buyer_id']),
            ':sid' => intval($input['seller_id']),
            ':amt' => floatval($input['price']),
        ]);

        $upd = $conn->prepare("UPDATE items SET status = 'ขายแล้ว' WHERE item_id = :iid");
        $upd->execute([':iid' => intval($input['item_id'])]);
        $conn->commit();

        http_response_code(201);
        echo json_encode(["success" => true, "message" => "สร้างคำสั่งซื้อและบันทึกประวัติธุรกรรมเรียบร้อยแล้ว", "order_id" => $orderId]);
    } elseif ($action === 'add_review') {
        $stmt = $conn->prepare(
            "INSERT INTO reviews (order_id, buyer_id, seller_id, rating, comment)
             VALUES (:oid, :bid, :sid, :rat, :com)"
        );
        $stmt->execute([
            ':oid' => intval($input['order_id']),
            ':bid' => intval($input['buyer_id']),
            ':sid' => intval($input['seller_id']),
            ':rat' => intval($input['rating']),
            ':com' => trim($input['comment'] ?? ''),
        ]);
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "บันทึกรีวิวและคะแนนผู้ขายเรียบร้อยแล้ว"]);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
