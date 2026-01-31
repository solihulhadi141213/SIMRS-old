<?php
    // Koneksi dan Function
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    // Set Time Zone
    $utc = new DateTime("now", new DateTimeZone("UTC"));

    // Validasi Metode Pengiriman Data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh POST"]);
        exit;
    }

    // Set Waktu Sekarang
    $creat_at = $utc->format("Y-m-d H:i:s");

    // Tambah 24 jam
    $expired_at = (clone $utc)->modify("+24 hours")->format("Y-m-d H:i:s");

    // Header
    header('Content-Type: application/json');

    // Tangkap Header
    $headers    = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    // Jika Tidak Ada Header Yang Dikirim
    if (!$authHeader) {
        http_response_code(401);
        echo json_encode([
            "status" => 'error',
            "message" => "Authorization header missing"
        ]);
        exit;
    }

    // Format: Basic base64(username:password)
    if (strpos($authHeader, 'Basic ') === 0) {

        $encoded = str_replace('Basic ', '', $authHeader);
        $decoded = base64_decode($encoded);

        if (!$decoded || !str_contains($decoded, ':')) {
            http_response_code(401);
            echo json_encode([
                "status" => 'error',
                "message" => "Invalid Authorization format"
            ]);
            exit;
        }

        list($username, $password) = explode(':', $decoded, 2);

        // Melakukan Validasi Username Dan Password
        $password_catch = GetDetailData($Conn, 'akses', 'username', $username, 'password');

        // Jika Tidak Valid
        if($password_catch!==$password){
             echo json_encode([
                "status" => 'error',
                "message" => "Invalid access permission"
            ]);
            exit;
        }

        // Jika Valid Buat Token Dan Simpan
        $token = generateUUIDv4();

        // ID akses
        $id_akses = GetDetailData($Conn, 'akses', 'username', $username, 'id_akses');

        // Simpan Token ke Database
        $stmt = $Conn->prepare("
            INSERT INTO akses_token 
            (id_akses, username, token, creat_at, expired_at) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issss",
            $id_akses,
            $username,
            $token,
            $creat_at,
            $expired_at
        );

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Failed to save token"
            ]);
            exit;
        }

        echo json_encode([
            "status"     => 'success',
            "username"   => $username,
            "id_akses"   => $id_akses,
            "token"      => $token,
            "creat_at"   => $creat_at,
            "expired_at" => $expired_at,
        ]);
        exit;
    }

    http_response_code(401);
    echo json_encode([
        "status" => 'error',
        "message" => "Unsupported Authorization type"
    ]);
?>