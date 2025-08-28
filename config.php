<?php
$host = "localhost";   // default XAMPP
$user = "root";        // default user XAMPP
$pass = "";            // default password (kosong di XAMPP)
$db   = "db_smart";    // sesuai nama database yang tadi kita buat

$koneksi = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>
