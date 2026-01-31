<?php
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    header("Content-Type: application/json");

    // ======================================================
    // VALIDASI METODE
    // ======================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh DELETE"]);
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
    // AMBIL ID DARI QUERY STRING
    // ======================================================
    $id_rad = $_GET['id'] ?? '';

    if (!$id_rad) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Parameter id wajib dikirim"]);
        exit;
    }

    // ======================================================
    // SANITASI ID
    // ======================================================
    $id_rad = validateAndSanitizeInput($id_rad);

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
    // DELETE DATA
    // ======================================================
    $stmt = $Conn->prepare("DELETE FROM radiologi WHERE id_rad = ?");
    $stmt->bind_param("s", $id_rad);

    // ======================================================
    // EKSEKUSI
    // ======================================================
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","message"=>"Gagal hapus: ".$stmt->error]);
        exit;
    }

    // ======================================================
    // RESPONSE SUKSES
    // ======================================================
    echo json_encode([
        "status" => "success",
        "message" => "Data radiologi berhasil dihapus",
        "id_rad" => $id_rad
    ]);

?>
