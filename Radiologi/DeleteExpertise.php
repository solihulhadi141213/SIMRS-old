<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // Validasi Metode Pengiriman Data
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh DELETE"]);
        exit;
    }

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
    $id = $_GET['id'] ?? null;

    if (!$id || !ctype_digit($id)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "ID Pelayanan Radiologi tidak valid"
        ]);
        exit;
    }

    $id_rincian = (int)$id;

    // FETCH MAIN DATA
    $Qry = $Conn->prepare("SELECT * FROM radiologi_rincian WHERE id_rincian = ?");
    $Qry->bind_param("i", $id_rincian);
    $Qry->execute();
    $Result = $Qry->get_result();

    if ($Result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Data Expertise radiologi tidak ditemukan"
        ]);
        exit;
    }

    $Data = $Result->fetch_assoc();
    $Qry->close();

    // DELETE DATA
    $stmt = $Conn->prepare("DELETE FROM radiologi_rincian WHERE id_rincian = ?");
    $stmt->bind_param("s", $id_rincian);

    // EKSEKUSI
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Gagal hapus: ".$stmt->error]);
        exit;
    }

    // RESPONSE SUKSES
    echo json_encode([
        "status" => "success",
        "message" => "Data expertise radiologi berhasil dihapus",
        "id_rincian" => $id_rincian
    ]);

?>