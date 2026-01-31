<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // Validasi Metode Pengiriman Data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $required = [
        "id_pasien","id_kunjungan","nama","waktu","permintaan_pemeriksaan",
        "alat_pemeriksa","status_pemeriksaan","jenis_pembayaran",
        "dokter_pengirim","dokter_penerima","radiografer",
        "kesan","klinis","selesai"
    ];

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
    $data['kv']  = $data['kv']  ?? null;
    $data['ma']  = $data['ma']  ?? null;
    $data['sec'] = $data['sec'] ?? null;
    $data['asal_kiriman'] = $data['asal_kiriman'] ?? null;

    // Insert Database
    $stmt = $Conn->prepare("
    INSERT INTO radiologi (
        id_pasien, id_kunjungan, nama, waktu, asal_kiriman,
        permintaan_pemeriksaan, alat_pemeriksa, status_pemeriksaan,
        jenis_pembayaran, dokter_pengirim, dokter_penerima,
        radiografer, kesan, klinis, selesai, kv, ma, sec
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssssssssssssss",
        $data['id_pasien'],
        $data['id_kunjungan'],
        $data['nama'],
        $data['waktu'],
        $data['asal_kiriman'],
        $data['permintaan_pemeriksaan'],
        $data['alat_pemeriksa'],
        $data['status_pemeriksaan'],
        $data['jenis_pembayaran'],
        $data['dokter_pengirim'],
        $data['dokter_penerima'],
        $data['radiografer'],
        $data['kesan'],
        $data['klinis'],
        $data['selesai'],
        $data['kv'],
        $data['ma'],
        $data['sec']
    );

    // Eksekusi
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Gagal simpan: ".$stmt->error]);
        exit;
    }

    // Ambil ID baru
    $id_rad = $stmt->insert_id;

    // Response sukses
    echo json_encode([
        "status" => "success",
        "message" => "Data radiologi berhasil disimpan",
        "id_rad" => $id_rad,
        "data" => $data
    ]);

?>