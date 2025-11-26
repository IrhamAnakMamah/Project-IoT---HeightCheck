<?php
// File: config/api_latest.php
require_once 'db.php';
header('Content-Type: application/json');

// Ambil 1 data pengukuran paling baru BESERTA NAMA user-nya
// Kita join tabel data_ukur dengan users
$sql = "SELECT u.nama, d.tinggi, d.tanggal_input 
        FROM data_ukur d 
        JOIN users u ON d.id = u.id 
        ORDER BY d.id_data DESC LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => 'success',
        'nama'   => $row['nama'],       // <-- Tambahan: Mengirim Nama
        'tinggi' => $row['tinggi'],
        'waktu'  => $row['tanggal_input']
    ]);
} else {
    echo json_encode(['status' => 'empty']);
}
?>