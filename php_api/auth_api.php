<?php
session_start();
require_once "condb.php";

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? 'login');

try {
    if ($action === 'login') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => trim($input['username'] ?? '')]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $pw = trim($input['password'] ?? '');
        if ($user && (password_verify($pw, $user['password']) || $pw === $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            echo json_encode(["success" => true, "message" => "เข้าสู่ระบบสำเร็จ", "user" => $user]);
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"]);
        }
    } elseif ($action === 'register') {
        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, fullname, email, phone, role)
             VALUES (:u, :p, :f, :e, :ph, :r)"
        );
        $role = in_array($input['role'] ?? 'buyer', ['buyer', 'seller']) ? $input['role'] : 'buyer';
        $stmt->execute([
            ':u' => trim($input['username'] ?? ''),
            ':p' => password_hash(trim($input['password'] ?? '123456'), PASSWORD_BCRYPT),
            ':f' => trim($input['fullname'] ?? ''),
            ':e' => trim($input['email'] ?? ''),
            ':ph' => trim($input['phone'] ?? ''),
            ':r' => $role,
        ]);
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "สมัครสมาชิกเรียบร้อยแล้ว"]);
    } elseif ($action === 'update_profile') {
        $stmt = $conn->prepare("UPDATE users SET fullname = :f, email = :e, phone = :ph WHERE user_id = :id");
        $stmt->execute([
            ':f' => trim($input['fullname'] ?? ''),
            ':e' => trim($input['email'] ?? ''),
            ':ph' => trim($input['phone'] ?? ''),
            ':id' => intval($input['user_id'] ?? 0),
        ]);
        echo json_encode(["success" => true, "message" => "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว"]);
    } elseif ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        echo json_encode(["success" => true, "message" => "ออกจากระบบเรียบร้อยแล้ว"]);
    }
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
