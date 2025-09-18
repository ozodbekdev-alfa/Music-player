<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');

$musicDir = __DIR__ . '/musiqalar';
$baseUrl  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/musiqalar';
$defaultCover = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/default.jpg';

$allowed = ['mp3','wav','ogg','m4a','flac'];

if (!is_dir($musicDir)) {
  echo json_encode(['status'=>'error','message'=>'musiqalar papkasi topilmadi','data'=>[]], JSON_UNESCAPED_UNICODE);
  exit;
}

$files = scandir($musicDir);
$data = [];

foreach ($files as $f) {
  if ($f === '.' || $f === '..') continue;
  $path = $musicDir . '/' . $f;
  if (!is_file($path)) continue;
  $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) continue;
  $name = pathinfo($f, PATHINFO_FILENAME);
  $artist = 'Noma’lum muallif';
  $title  = $name;
  if (strpos($name, ' - ') !== false) {
    [$a,$t] = explode(' - ', $name, 2);
    $artist = trim($a) ?: $artist;
    $title  = trim($t) ?: $title;
  }
  if ($ext === 'mp3' && is_readable($path) && filesize($path) > 128) {
    $fp = fopen($path, 'rb');
    if ($fp) {
      fseek($fp, -128, SEEK_END);
      $tag = fread($fp, 128);
      fclose($fp);
      if (substr($tag, 0, 3) === 'TAG') {
        $id3_title  = trim(rtrim(substr($tag, 3, 30)));
        $id3_artist = trim(rtrim(substr($tag, 33, 30)));
        if ($id3_title)  $title  = $id3_title;
        if ($id3_artist) $artist = $id3_artist;
      }
    }
  }
  $coverUrl = $defaultCover;
  foreach ($coverExt as $cx) {
    $cpath = $musicDir . '/' . $name . '.' . $cx;
    if (is_file($cpath)) {
      $coverUrl = $baseUrl . '/' . rawurlencode($name . '.' . $cx);
      break;
    }
  }
  $data[] = [
    'src'    => $baseUrl . '/' . rawurlencode($f),
    'title'  => $title ?: $name,
    'artist' => $artist,
    'cover'  => $coverUrl
  ];
}

usort($data, function($a,$b){
  return strnatcasecmp($a['title'], $b['title']);
});

echo json_encode([
  'status' => 'ok',
  'count'  => count($data),
  'data'   => $data
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);