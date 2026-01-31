<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // ===============================
    // UTC TIME INIT
    // ===============================
    $utc = new DateTime("now", new DateTimeZone("UTC"));

    // ===============================
    // HEADER VALIDATION
    // ===============================
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

    // ===============================
    // TOKEN VALIDATION
    // ===============================
    $ValidasiToken = ValidasteCredential($Conn, $username, $token);

    if ($ValidasiToken !== "Valid") {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => $ValidasiToken
        ]);
        exit;
    }

    // ===============================
    // INPUT VALIDATION (ID)
    // ===============================
    $id = $_GET['id'] ?? null;

    if (!$id || !ctype_digit($id)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "ID Pelayanan Radiologi tidak valid"
        ]);
        exit;
    }

    $id = (int)$id;

    // ===============================
    // FETCH MAIN DATA
    // ===============================
    $Qry = $Conn->prepare("SELECT * FROM radiologi WHERE id_rad = ?");
    $Qry->bind_param("i", $id);
    $Qry->execute();
    $Result = $Qry->get_result();

    if ($Result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Data radiologi tidak ditemukan"
        ]);
        exit;
    }

    $Data = $Result->fetch_assoc();
    $Qry->close();

    // ===============================
    // FETCH DETAIL DATA
    // ===============================
    $Qry2 = $Conn->prepare("SELECT * FROM radiologi_rincian WHERE id_rad = ?");
    $Qry2->bind_param("i", $id);
    $Qry2->execute();
    $Result2 = $Qry2->get_result();

    $data2 = [];
    while ($row = $Result2->fetch_assoc()) {
        $data2[] = $row;
    }

    $Qry2->close();

    // ===============================
    // RESPONSE
    // ===============================
    echo json_encode([
        "status" => "success",
        "metadata" => $Data,
        "detail" => $data2,
    ], JSON_PRETTY_PRINT);

?>