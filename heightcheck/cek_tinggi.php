<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Tinggi - HeightCheck</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Tambahan style untuk loading overlay */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 1000;
            text-align: center;
            padding-top: 20%;
        }

        .spinner {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <div id="loadingOverlay">
        <div class="spinner"></div>
        <h2 id="loadingText">Sedang Menghubungi Sensor...</h2>
        <p>Mohon berdiri tegak di bawah sensor</p>
    </div>

    <img src="assets/logo.svg" alt="Logo"
        style="position: fixed; top: 30px; left: 40px; width: 70px; z-index: 9999; cursor: pointer;"
        onclick="location.href='index.php'">

    <div class="container">
        <div class="form-group">
            <h1>Height Check</h1>
            <p class="desc">Isi data diri Anda sebelum mengukur.</p>

            <input type="text" id="nama" placeholder="Nama Lengkap" required>
            <input type="number" id="umur" placeholder="Umur" required>
            <select id="jenis_kelamin" required>
                <option value="">Pilih Gender</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
            </select>
        </div>

        <div style="margin: 40px 0; text-align: center;">
            <img src="assets/Logo-iot-2.svg" style="width: 80px; opacity: 0.5;">
            <p style="margin-top: 10px; color: #666;">Pastikan sensor siap digunakan.</p>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
            <a href="index.php" class="btn btn-black">Kembali</a>

            <button type="button" onclick="mulaiPengukuran()" class="btn"
                style="background-color: #e74c3c; width: 100%;">
                📡 Ukur Sekarang
            </button>
        </div>
    </div>

    <script>
        // IP ESP32 Sensor (Ultrasonik)
        const espSensorIp = "http://10.14.115.128/ukur";

        // IP ESP32 LCD (YANG BARU SAJA ANDA UPLOAD)
        // Ganti IP di bawah ini dengan IP yang muncul di Serial Monitor ESP LCD!
        const espLcdIp = "http://10.14.115.99/trigger-visual"; // <--- GANTI 'xxx'

        // Variabel waktu data terakhir
        let lastDataTime = "";

        // 1. Ambil waktu data terakhir saat load
        fetch('config/api_latest.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    lastDataTime = data.waktu;
                }
            });

        function mulaiPengukuran() {
            const nama = document.getElementById('nama').value;
            const umur = document.getElementById('umur').value;
            const jk = document.getElementById('jenis_kelamin').value;

            if (nama === "" || umur === "" || jk === "") {
                alert("Harap lengkapi data diri!");
                return;
            }

            // Tampilkan Loading di Layar HP/PC
            document.getElementById('loadingOverlay').style.display = "block";

            // --- LOGIKA BARU DI SINI ---

            // A. Kirim perintah ke LCD (Agar Lampu jadi Kuning)
            // Kita gunakan 'no-cors' agar tidak error meskipun beda domain/IP, 
            // dan tidak menunggu response (fire and forget)
            fetch(espLcdIp, { mode: 'no-cors' }).catch(e => console.log("LCD tidak respon"));

            // B. Kirim perintah ke Sensor (Agar mulai mengukur)
            fetch(espSensorIp)
                .then(response => {
                    if (response.ok) {
                        document.getElementById('loadingText').innerText = "Sensor Sedang Mengukur...";
                        cekDataMasuk(); // Mulai memantau database
                    } else {
                        throw new Error("Gagal trigger Sensor");
                    }
                })
                .catch(err => {
                    alert("Gagal konek ke Sensor ESP. Cek IP/WiFi.");
                    document.getElementById('loadingOverlay').style.display = "none";
                });
        }

        // 3. Fungsi Polling (Cek terus menerus apakah ada data baru)
        function cekDataMasuk() {
            const interval = setInterval(() => {
                fetch('config/api_latest.php')
                    .then(res => res.json())
                    .then(data => {
                        // Jika waktu data di database BEDA dengan waktu awal tadi, berarti data baru sudah masuk!
                        if (data.status === 'success' && data.waktu !== lastDataTime) {
                            clearInterval(interval); // Stop checking
                            document.getElementById('loadingText').innerText = "Menyimpan Data Diri...";

                            // 4. Update Nama User ke Data Tersebut
                            updateUserDatabase();
                        }
                    });
            }, 1000); // Cek setiap 1 detik
        }

        // 5. Fungsi Update Nama User (Karena ESP32 default-nya 'User IoT')
        function updateUserDatabase() {
            const nama = document.getElementById('nama').value;
            const umur = document.getElementById('umur').value;
            const jk = document.getElementById('jenis_kelamin').value;

            const formData = new FormData();
            formData.append('nama', nama);
            formData.append('umur', umur);
            formData.append('jenis_kelamin', jk);

            fetch('config/update_user.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        // 6. Pindah ke Halaman Hasil
                        window.location.href = 'hasil.php';
                    } else {
                        alert("Gagal update user: " + result.message);
                        document.getElementById('loadingOverlay').style.display = "none";
                    }
                });
        }
    </script>

</body>

</html>