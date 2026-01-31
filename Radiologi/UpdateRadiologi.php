<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // ======================================================
    // VALIDASI METODE
    // ======================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh PUT"]);
        exit;
    }

    // ======================================================
    // AMBIL HEADER
    // ======================================================
    $headers = getallheaders();
    $username = $headers['username'] ?? '';
    $token    = $headers['token'] ?? '';

    // ======================================================
    // VALIDASI HEADER
    // ======================================================
    if (!$username || !$token) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Username and Token are required"]);
        exit;
    }

    // ======================================================
    // VALIDASI TOKEN
    // ======================================================
    $ValidasiToken = ValidasteCredential($Conn, $username, $token);
    if ($ValidasiToken !== "Valid") {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>$ValidasiToken]);
        exit;
    }

    // ======================================================
    // AMBIL RAW JSON
    // ======================================================
    $raw = file_get_contents("php://input");
    $request = json_decode($raw, true);

    if (!$request) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"JSON tidak valid"]);
        exit;
    }

    // ======================================================
    // VALIDASI ID_RAD
    // ======================================================
    $id_rad = $request['id_rad'] ?? '';

    if (!$id_rad) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"id_rad wajib dikirim"]);
        exit;
    }

    // ======================================================
    // VALIDASI DATA OBJECT
    // ======================================================
    $data = $request['data'] ?? null;

    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Object data wajib dikirim"]);
        exit;
    }

    // ======================================================
    // FIELD WAJIB
    // ======================================================
    $required = [
        "id_pasien","id_kunjungan","nama","waktu","permintaan_pemeriksaan",
        "alat_pemeriksa","status_pemeriksaan","jenis_pembayaran",
        "dokter_pengirim","dokter_penerima","radiografer",
        "kesan","klinis","selesai"
    ];

    // ======================================================
    // VALIDASI FIELD KOSONG
    // ======================================================
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

    // ======================================================
    // SANITASI INPUT
    // ======================================================
    foreach ($data as $k => $v) {
        $data[$k] = validateAndSanitizeInput($v);
    }

    // ======================================================
    // SET DEFAULT OPTIONAL
    // ======================================================
    $data['kv']  = $data['kv']  ?? null;
    $data['ma']  = $data['ma']  ?? null;
    $data['sec'] = $data['sec'] ?? null;
    $data['asal_kiriman'] = $data['asal_kiriman'] ?? null;

    // ======================================================
    // CEK DATA ADA ATAU TIDAK
    // ======================================================
    $cek = $Conn->prepare("SELECT id_rad FROM radiologi WHERE id_rad = ?");
    $cek->bind_param("s", $id_rad);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows == 0) {
        http_response_code(404);
        echo json_encode(["status"=>"error","message"=>"Data radiologi tidak ditemukan"]);
        exit;
    }

    // ======================================================
    // UPDATE DATABASE
    // ======================================================
    $stmt = $Conn->prepare("
        UPDATE radiologi SET
            id_pasien = ?, 
            id_kunjungan = ?, 
            nama = ?, 
            waktu = ?, 
            asal_kiriman = ?, 
            permintaan_pemeriksaan = ?, 
            alat_pemeriksa = ?, 
            status_pemeriksaan = ?, 
            jenis_pembayaran = ?, 
            dokter_pengirim = ?, 
            dokter_penerima = ?, 
            radiografer = ?, 
            kesan = ?, 
            klinis = ?, 
            selesai = ?, 
            kv = ?, 
            ma = ?, 
            sec = ?
        WHERE id_rad = ?
    ");

    $stmt->bind_param(
        "sssssssssssssssssss",
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
        $data['sec'],
        $id_rad
    );

    // ======================================================
    // EKSEKUSI
    // ======================================================
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Gagal update: ".$stmt->error]);
        exit;
    }

    // ======================================================
    // RESPONSE SUKSES
    // ======================================================
    echo json_encode([
        "status" => "success",
        "message" => "Data radiologi berhasil diperbarui",
        "id_rad" => $id_rad,
        "data" => $data
    ]);
?>
