<?php
// === Basit ve güvenli JSON kaydetme uç noktası ===
// Aynı klasörde "menu.json" dosyasını oluşturur/günceller.

// ---- Ayarlar ----
$PASSWORD = 'yiğitesmer213';
$MENU_FILE = __DIR__ . '/menu.json';

// ---- CORS (gerekirse alanınıza göre kısıtlayın) ----
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// ---- Sadece POST kabul ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
  exit;
}

// ---- JSON gövdeyi al ----
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['pass']) || !isset($data['menu'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Geçersiz istek']);
  exit;
}

// ---- Şifre kontrol ----
if ($data['pass'] !== $PASSWORD) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Yetkisiz']);
  exit;
}

// ---- Menü doğrulama (çok temel) ----
$menu = $data['menu'];
if (!isset($menu['categories']) || !is_array($menu['categories'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Geçersiz menü formatı']);
  exit;
}

// ---- Güvenli yazım: temp dosyaya yaz, sonra atomik rename ----
$tmp = $MENU_FILE . '.tmp';
$json = json_encode($menu, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
if ($json === false) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'JSON kodlanamadı']);
  exit;
}

if (file_put_contents($tmp, $json, LOCK_EX) === false) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Geçici dosyaya yazılamadı']);
  exit;
}

if (!rename($tmp, $MENU_FILE)) {
  // Windows’ta rename için önce unlink gerekebilir
  @unlink($MENU_FILE);
  if (!rename($tmp, $MENU_FILE)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Dosya güncellenemedi']);
    exit;
  }
}

echo json_encode(['ok'=>true]);
