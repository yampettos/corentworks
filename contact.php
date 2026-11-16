<?php
// Corentworks — formulario de contacto
// Envía las consultas del sitio a la casilla indicada en $to.
header('Content-Type: application/json; charset=utf-8');

$to = 'hello@corentworks.com';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method']);
  exit;
}

// Campo trampa para bots: los humanos no lo ven ni lo completan.
if (!empty($_POST['website'])) {
  echo json_encode(['ok' => true]);
  exit;
}

function clean($v, $max = 2000) {
  $v = trim((string)$v);
  $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v);
  return mb_substr(strip_tags($v), 0, $max);
}

$name     = clean($_POST['name'] ?? '', 120);
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$company  = clean($_POST['company'] ?? '', 160);
$interest = clean($_POST['interest'] ?? '', 120);
$message  = trim(strip_tags((string)($_POST['message'] ?? '')));
$message  = mb_substr($message, 0, 5000);
$lang     = ($_POST['lang'] ?? 'es') === 'en' ? 'en' : 'es';

if ($name === '' || !$email || $message === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'validation']);
  exit;
}

$subject = 'Nueva consulta web — ' . ($company !== '' ? $company : $name);
$body  = "Nombre: $name\n";
$body .= "Email: $email\n";
$body .= "Empresa: $company\n";
$body .= "Interés: $interest\n";
$body .= "Idioma: $lang\n\n";
$body .= "Mensaje:\n$message\n";

$host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'corentworks.com');
$headers  = "From: Corentworks Web <no-reply@$host>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

if ($sent) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'mail']);
}
