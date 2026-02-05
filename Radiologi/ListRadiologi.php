<?php
    // Koneksi dan Function
    include "../_Config/Connection.php";
    include "../_Config/Function.php";

    // Set Time Zone
    $utc = new DateTime("now", new DateTimeZone("UTC"));
    header("Content-Type: application/json");

    // Validasi Metode Pengiriman Data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status"=>"error","message"=>"Metode pengiriman data hanya boleh POST"]);
        exit;
    }

    // ==============================
    // 1. AMBIL HEADER
    // ==============================
    $headers = getallheaders();
    $username = $headers['username'] ?? '';
    $token    = $headers['token'] ?? '';

    if (!$username || !$token) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Username and Token are required"
        ]);
        exit;
    }

    // ==============================
    // 2. VALIDASI TOKEN KE DATABASE
    // ==============================
    $ValidasiToken = ValidasteCredential($Conn,$username,$token);
    if($ValidasiToken!=="Valid"){
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => $ValidasiToken
        ]);
        exit;
    }

    // ==============================
    // 4. AMBIL PARAMETER POST
    // ==============================
    $limit       = isset($_POST['limit']) ? (int) $_POST['limit'] : 10;
    $page        = isset($_POST['page']) ? (int) $_POST['page'] : 1;
    if(empty($_POST['order_by'])){
        $order_by = "DESC";
    }else{
        $order_by = $_POST['order_by'];
    }
    if(empty($_POST['short_by'])){
        $short_by = "id_rad";
    }else{
        $short_by = $_POST['short_by'];
    }
    $keyword_by  = $_POST['keyword_by'] ?? '';
    $keyword     = $_POST['keyword'] ?? '';

    // Validasi limit max 250
    if ($limit < 1) $limit = 10;
    if ($limit > 250) $limit = 250;

    // Validasi order_by
    $order_by = ($order_by === 'DESC') ? 'DESC' : 'ASC';

    // Offset pagination
    $offset = ($page - 1) * $limit;

    // ==============================
    // 5. VALIDASI KOLOM SORTING
    // ==============================
    $allowedColumns = [
        'id_rad','id_pasien','id_kunjungan','nama','waktu',
        'asal_kiriman','permintaan_pemeriksaan','alat_pemeriksa',
        'status_pemeriksaan','jenis_pembayaran','dokter_pengirim',
        'dokter_penerima','radiografer','kesan','klinis','selesai',
        'kv','ma','sec'
    ];

    if (!in_array($short_by, $allowedColumns)) {
        $short_by = 'id_rad';
    }

    // ==============================
    // 6. BUILD FILTER SEARCH
    // ==============================
    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if ($keyword_by && $keyword && in_array($keyword_by, $allowedColumns)) {
        $where .= " AND $keyword_by LIKE ? ";
        $params[] = "%$keyword%";
        $types .= "s";
    }

    // ==============================
    // 7. HITUNG TOTAL DATA
    // ==============================
    $countSql = "SELECT COUNT(*) as total FROM radiologi $where";
    $countStmt = $Conn->prepare($countSql);

    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $total_data = $countResult['total'];

    $total_pages = ceil($total_data / $limit);

    // ==============================
    // 8. QUERY DATA LIST
    // ==============================
    $sql = "
        SELECT * FROM radiologi 
        $where 
        ORDER BY $short_by $order_by 
        LIMIT ? OFFSET ?
    ";

    $stmt = $Conn->prepare($sql);

    if ($params) {
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // ==============================
    // 9. RESPONSE
    // ==============================
    echo json_encode([
        "status" => "success",
        "meta" => [
            "username" => $username,
            "page" => $page,
            "limit" => $limit,
            "total_data" => $total_data,
            "total_pages" => $total_pages,
            "short_by" => $short_by,
            "order_by" => $order_by,
            "keyword_by" => $keyword_by,
            "keyword" => $keyword
        ],
        "data" => $data
    ], JSON_PRETTY_PRINT);
?>