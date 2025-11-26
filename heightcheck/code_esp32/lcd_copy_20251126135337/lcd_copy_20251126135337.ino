#include <Wire.h> 
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ================= KONFIGURASI =================
const char* ssid = "advan"; 
const char* password = "kontolbanget";

const int ledM = 25;
const int ledK = 26;
const int ledH = 27;

// IP Address Laptop (Server XAMPP)
// Pastikan path-nya benar menuju file yang baru dibuat tadi
String urlDatabase = "http://10.14.115.150/project_iot/Project-IoT---HeightCheck/heightcheck/config/api_latest.php";

// IP Address ESP A (Untuk Trigger Sensor)
String urlSensor   = "http://10.14.115.128/ukur"; 

// ===============================================

const int butt = 4;
LiquidCrystal_I2C lcd(0x27, 16, 2); // Pastikan alamat 0x3F atau 0x27 sesuai yang berhasil tadi

unsigned long lastCheck = 0;
String lastTimeData = ""; // Untuk menyimpan waktu data terakhir agar layar tidak kedip terus

void setup() {
  Serial.begin(115200);
  pinMode(butt, INPUT_PULLUP);
  
  pinMode(ledM, OUTPUT);
  pinMode(ledK, OUTPUT);
  pinMode(ledH, OUTPUT);

  lcd.init();
  lcd.backlight();
  
  lcd.print("Konek WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  lcd.clear();
  lcd.print("Siap Monitor!");
  digitalWrite(ledM, 1);
}

void loop() {
  // 1. FITUR TOMBOL (Trigger Manual)
  if (digitalRead(butt) == LOW) {
    lcd.clear();
    digitalWrite(ledK, 1);
    lcd.print("Mengukur...");
    triggerSensor(); 
    delay(2000); // Debounce
    digitalWrite(ledK, 0);
  }

  // 2. FITUR AUTO-UPDATE (Cek Database tiap 3 detik)
  if (millis() - lastCheck > 3000) {
    cekDataTerbaru();
    lastCheck = millis();
  }
}

// Fungsi Meminta ESP A Mengukur
void triggerSensor() {
  if(WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(urlSensor);
    int httpCode = http.GET(); // Hanya trigger, hasilnya nanti diambil lewat database
    http.end();
    digitalWrite(ledK, 1);
  }
}

// Fungsi Mengambil Data dari Database
void cekDataTerbaru() {
  if(WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(urlDatabase);
    int httpCode = http.GET();

    if(httpCode > 0) {
      String payload = http.getString();
      
      // Parsing Manual Sederhana (Cari kata "tinggi")
      // Format JSON: {"status":"success","tinggi":"165.5","waktu":"..."}
      
      int indexTinggi = payload.indexOf("\"tinggi\":\"");
      int indexWaktu = payload.indexOf("\"waktu\":\"");
      
      if (indexTinggi > 0 && indexWaktu > 0) {
        // Ambil Angka Tinggi
        int startT = indexTinggi + 10; // Panjang string "tinggi":" adalah 10
        int endT = payload.indexOf("\"", startT);
        String tinggiVal = payload.substring(startT, endT);
        
        // Ambil Data Waktu (Untuk cek apakah data baru)
        int startW = indexWaktu + 9;
        int endW = payload.indexOf("\"", startW);
        String waktuVal = payload.substring(startW, endW);

        // Hanya update LCD jika data waktunya beda (Data Baru)
        if (waktuVal != lastTimeData) {
           lcd.clear();
           lcd.setCursor(0, 0);
           lcd.print("Tinggi: " + tinggiVal + " cm");
           lcd.setCursor(0, 1);
           // Tampilkan jam saja (ambil 5 karakter terakhir dari waktu)
           // Format waktu biasanya YYYY-MM-DD HH:MM:SS
           String jam = waktuVal.substring(11, 16); 
           lcd.print("Jam: " + jam);
           digitalWrite(ledH, 1);
           
           lastTimeData = waktuVal; // Simpan waktu agar tidak refresh terus
        }
      }
    }
    http.end();
    digitalWrite(ledH, 0);
  }
}