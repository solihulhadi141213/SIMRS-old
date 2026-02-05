<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // UTC TIME INIT
    $utc = new DateTime("now", new DateTimeZone("UTC"));

    // HEADER VALIDATION
    $headers = getallheaders();
    $username = $headers['username'] ?? '';
    $token    = $headers['token'] ?? '';

    if ($username === '' || $token === '') {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Username and Token are required"
        ]);
        exit;
    }

    // TOKEN VALIDATION
    $ValidasiToken = ValidasteCredential($Conn, $username, $token);

    if ($ValidasiToken !== "Valid") {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => $ValidasiToken
        ]);
        exit;
    }

    // INPUT VALIDATION (ID)
    $column = $_GET['column'] ?? '';

    if (empty($column)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Parameter Nama Kolom Tidak Boleh Kosong"
        ]);
        exit;
    }

    // ==============================
    // COLUMN WHITELIST (ANTI SQL INJECTION)
    // ==============================
    $allowedColumns = [
        'alat_pemeriksa',
        'asal_kiriman',
        'permintaan_pemeriksaan',
        'jenis_pembayaran',
        'status_pemeriksaan',
        'dokter_pengirim',
        'dokter_penerima',
        'radiografer'
    ];

    if (!in_array($column, $allowedColumns)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Kolom tidak diizinkan"
        ]);
        exit;
    }

    // ==============================
    // HITUNG TOTAL DATA DISTINCT
    // ==============================
    $stmt = $Conn->prepare("SELECT COUNT(DISTINCT $column) AS total FROM radiologi");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $jml_data = $result['total'] ?? 0;
    $stmt->close();

    // ==============================
    // AMBIL DATA GROUP COUNT
    // ==============================
    $data_arry = [];

    $stmt = $Conn->prepare("
        SELECT $column AS param, COUNT(id_rad) AS jumlah 
        FROM radiologi 
        GROUP BY $column 
        ORDER BY $column ASC
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $data_arry[] = [
            "column" => $row['param'],
            "count"  => (int)$row['jumlah']
        ];
    }

    $stmt->close();

    // ==============================
    // RESPONSE SUCCESS
    // ==============================
    http_response_code(200);
    echo json_encode([
        "status"       => "success",
        "message"      => "Request Berhasil Diterima",
        "jumlah_data"  => $jml_data,
        "data"         => $data_arry
    ]);
    exit;
?>