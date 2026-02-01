<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // Validasi Metode Pengiriman Data
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh POST"]);
        exit;
    }

    // Ambil Header
    $headers = getallheaders();
    $username = $headers['username'] ?? '';
    $token    = $headers['token'] ?? '';

    // Validasi Header
    if (!$username || !$token) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Username and Token are required"]);
        exit;
    }

    // Validasi Token
    $ValidasiToken = ValidasteCredential($Conn,$username,$token);
    if ($ValidasiToken !== "Valid") {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>$ValidasiToken]);
        exit;
    }

    // Tangkap RAW JSON
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    // Field wajib & opsional
    $required = ["id_rincian","kategori","pemeriksaan","hasil","interpertasi"];

    // Validasi field kosong
    $missing = [];
    foreach ($required as $field) {
        if (empty($data[$field])) $missing[] = $field;
    }

    if ($missing) {
        http_response_code(400);
        echo json_encode([
            "status"=>"error",
            "message"=>"Field wajib belum diisi",
            "missing_fields"=>$missing
        ]);
        exit;
    }

    // Sanitasi input
    foreach ($data as $k => $v) {
        $data[$k] = validateAndSanitizeInput($v);
    }

    // Set default optional
    $data['interpertasi']  = $data['interpertasi']  ?? "";
    $data['keterangan']  = $data['keterangan']  ?? "";
    $font = 12;
    
    // Update Database
    $stmt = $Conn->prepare("
        UPDATE radiologi_rincian SET
            kategori = ?, 
            pemeriksaan = ?, 
            hasil = ?, 
            interpertasi = ?, 
            keterangan = ?
        WHERE id_rincian = ?
    ");

    $stmt->bind_param(
        "sssssi",
        $data['kategori'],
        $data['pemeriksaan'],
        $data['hasil'],
        $data['interpertasi'],
        $data['keterangan'],
        $data['id_rincian']
    );

    // Jika gagal Update
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Gagal update: ".$stmt->error]);
        exit;
    }

    // Response sukses
    echo json_encode([
        "status" => "success",
        "message" => "Update expertise radiologi berhasil disimpan",
        "id_rincian" => $data['id_rincian'],
        "data" => $data
    ]);

?>