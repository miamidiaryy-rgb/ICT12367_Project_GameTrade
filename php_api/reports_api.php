<?php
require_once "condb.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $reports = $conn->query(
        "SELECT r.*, rep.fullname AS reporter_name, sel.fullname AS seller_name, sel.username AS seller_username,
                (SELECT e.file_path FROM report_evidence e WHERE e.report_id = r.report_id LIMIT 1) AS evidence_file
         FROM reports r
         INNER JOIN users rep ON r.reporter_id = rep.user_id
         INNER JOIN users sel ON r.seller_id = sel.user_id
         ORDER BY r.report_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $blacklist = $conn->query(
        "SELECT b.*, u.username AS seller_username, u.fullname AS seller_name, a.fullname AS added_by_name
         FROM blacklist b
         INNER JOIN users u ON b.seller_id = u.user_id
         INNER JOIN users a ON b.added_by = a.user_id
         ORDER BY b.blacklist_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $users = $conn->query("SELECT user_id, username, fullname, email, phone, role, status FROM users ORDER BY user_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "reports" => $reports, "blacklist" => $blacklist, "users" => $users]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input['action'] ?? 'create_report';

try {
    if ($action === 'create_report') {
        $stmt = $conn->prepare(
            "INSERT INTO reports (reporter_id, seller_id, item_id, reason_type, detail, status)
             VALUES (:rid, :sid, :iid, :rtype, :detail, 'รอตรวจสอบ')"
        );
        $stmt->execute([
            ':rid' => intval($input['reporter_id']),
            ':sid' => intval($input['seller_id']),
            ':iid' => !empty($input['item_id']) ? intval($input['item_id']) : null,
            ':rtype' => trim($input['reason_type'] ?? ''),
            ':detail' => trim($input['detail'] ?? ''),
        ]);
        $reportId = $conn->lastInsertId();
        if (!empty($input['evidence_file'])) {
            $ev = $conn->prepare("INSERT INTO report_evidence (report_id, file_path, note) VALUES (:rid, :fp, :note)");
            $ev->execute([':rid' => $reportId, ':fp' => trim($input['evidence_file']), ':note' => 'หลักฐานจากผู้ซื้อ']);
        }
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "ส่งรายงานผู้ขายเรียบร้อยแล้ว รอผู้ดูแลระบบตรวจสอบ"]);
    } elseif ($action === 'review_report') {
        $status = $input['status'] ?? 'ปฏิเสธ';
        $reportId = intval($input['report_id']);
        $stmt = $conn->prepare("UPDATE reports SET status = :st WHERE report_id = :id");
        $stmt->execute([':st' => $status, ':id' => $reportId]);

        if ($status === 'อนุมัติ' && !empty($input['seller_id'])) {
            $bl = $conn->prepare(
                "INSERT INTO blacklist (seller_id, reason, added_by)
                 VALUES (:sid, :reason, :aid)
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason)"
            );
            $bl->execute([
                ':sid' => intval($input['seller_id']),
                ':reason' => trim($input['reason'] ?? 'ผ่านการตรวจสอบคำร้องเรียนว่ามีความผิดจริง'),
                ':aid' => intval($input['admin_id'] ?? 1),
            ]);
        }
        echo json_encode(["success" => true, "message" => "อัปเดตผลการตรวจสอบรายงานเรียบร้อยแล้ว"]);
    } elseif ($action === 'remove_blacklist') {
        $stmt = $conn->prepare("DELETE FROM blacklist WHERE seller_id = :sid");
        $stmt->execute([':sid' => intval($input['seller_id'])]);
        echo json_encode(["success" => true, "message" => "ปลดผู้ขายออกจาก Blacklist เรียบร้อยแล้ว"]);
    } elseif ($action === 'add_blacklist') {
        $stmt = $conn->prepare(
            "INSERT INTO blacklist (seller_id, reason, added_by) VALUES (:sid, :reason, :aid)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason)"
        );
        $stmt->execute([
            ':sid' => intval($input['seller_id']),
            ':reason' => trim($input['reason'] ?? ''),
            ':aid' => intval($input['admin_id'] ?? 1),
        ]);
        echo json_encode(["success" => true, "message" => "เพิ่มผู้ขายลงใน Blacklist เรียบร้อยแล้ว"]);
    }
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
