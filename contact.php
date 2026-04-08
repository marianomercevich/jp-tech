<?php
// ============================================================
//  JP TECHNOLOGY — contact.php
//  Recibe el formulario y envía el mail al destinatario.
//  CONFIGURACIÓN: cambiá solo las 3 variables de abajo.
// ============================================================

$destinatario = "contacto@jptechnology.com"; // <-- TU EMAIL
$asunto_prefijo = "[JP Technology] Nuevo contacto: ";
$redirect_ok    = "gracias.html";   // página de éxito (podés crearla)
$redirect_error = "error.html";     // página de error (opcional)

// ---- Seguridad: solo aceptar POST -------------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Método no permitido.");
}

// ---- Función de sanitización ------------------------------
function limpiar($dato) {
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

// ---- Recoger y limpiar campos ----------------------------
$nombre   = limpiar($_POST["nombre"]   ?? "");
$apellido = limpiar($_POST["apellido"] ?? "");
$empresa  = limpiar($_POST["empresa"]  ?? "");
$email    = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
$telefono = limpiar($_POST["telefono"] ?? "");
$asunto   = limpiar($_POST["asunto"]   ?? "");
$mensaje  = limpiar($_POST["mensaje"]  ?? "");
$privacy  = isset($_POST["privacy"]) ? "Sí" : "No";

// ---- Validaciones básicas --------------------------------
$errores = [];

if (empty($nombre))   $errores[] = "El nombre es requerido.";
if (empty($apellido)) $errores[] = "El apellido es requerido.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no es válido.";
if (empty($asunto))   $errores[] = "El asunto es requerido.";
if ($privacy !== "Sí") $errores[] = "Debe aceptar la política de privacidad.";

// Anti-spam simple: honeypot (agregar campo oculto "website" en el form si querés)
if (!empty($_POST["website"] ?? "")) {
    // Bot detectado, salir silenciosamente
    header("Location: " . $redirect_ok);
    exit;
}

if (!empty($errores)) {
    // Podés redirigir o mostrar los errores
    http_response_code(400);
    echo "<pre>Errores:\n" . implode("\n", $errores) . "</pre>";
    exit;
}

// ---- Armar el cuerpo del email ---------------------------
$cuerpo_texto = "
========================================
  NUEVO CONTACTO — JP TECHNOLOGY
========================================

Nombre:    {$nombre} {$apellido}
Empresa:   {$empresa}
Email:     {$email}
Teléfono:  {$telefono}
Servicio:  {$asunto}
Privacidad aceptada: {$privacy}

----------------------------------------
MENSAJE:
{$mensaje}
----------------------------------------
Enviado desde el sitio web el " . date("d/m/Y H:i") . "
";

$cuerpo_html = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><style>
  body{font-family:Arial,sans-serif;background:#f4f7fb;padding:20px}
  .card{background:#fff;border-radius:10px;padding:30px;max-width:600px;margin:0 auto;border-top:4px solid #378add}
  h2{color:#185fa5;margin-bottom:20px}
  table{width:100%;border-collapse:collapse}
  td{padding:10px 8px;border-bottom:1px solid #eee;font-size:14px;vertical-align:top}
  td:first-child{color:#888;width:130px;font-weight:bold}
  .mensaje-box{background:#f0f6ff;border-left:4px solid #378add;padding:15px;border-radius:6px;margin-top:20px;font-size:14px;line-height:1.6;color:#333}
  .footer{text-align:center;margin-top:20px;font-size:12px;color:#aaa}
</style></head>
<body>
<div class='card'>
  <h2>📩 Nuevo contacto desde el sitio web</h2>
  <table>
    <tr><td>Nombre</td><td>{$nombre} {$apellido}</td></tr>
    <tr><td>Empresa</td><td>{$empresa}</td></tr>
    <tr><td>Email</td><td><a href='mailto:{$email}'>{$email}</a></td></tr>
    <tr><td>Teléfono</td><td>{$telefono}</td></tr>
    <tr><td>Servicio</td><td><strong>{$asunto}</strong></td></tr>
    <tr><td>Privacidad</td><td>{$privacy}</td></tr>
  </table>
  <div class='mensaje-box'>
    <strong>Mensaje:</strong><br><br>
    " . nl2br($mensaje) . "
  </div>
  <div class='footer'>Enviado el " . date("d/m/Y \a \l\a\s H:i") . " · JP Technology</div>
</div>
</body></html>
";

// ---- Cabeceras del email ---------------------------------
$asunto_email = $asunto_prefijo . $asunto . " — " . $nombre . " " . $apellido;

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: JP Technology Web <noreply@jptechnology.com>\r\n";
$headers .= "Reply-To: {$nombre} {$apellido} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// ---- Enviar email ----------------------------------------
$enviado = mail($destinatario, $asunto_email, $cuerpo_html, $headers);

// ---- Auto-respuesta al cliente ---------------------------
if ($enviado) {
    $respuesta_html = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><style>
      body{font-family:Arial,sans-serif;background:#f4f7fb;padding:20px}
      .card{background:#fff;border-radius:10px;padding:30px;max-width:600px;margin:0 auto;border-top:4px solid #378add;text-align:center}
      h2{color:#185fa5} p{color:#555;font-size:14px;line-height:1.6}
    </style></head><body>
    <div class='card'>
      <h2>¡Gracias, {$nombre}!</h2>
      <p>Recibimos tu consulta sobre <strong>{$asunto}</strong>.<br>
      Te responderemos a <strong>{$email}</strong> en menos de 24hs.</p>
      <p style='color:#aaa;font-size:12px;margin-top:20px'>JP Technology · contacto@jptechnology.com</p>
    </div></body></html>";

    $h_resp  = "MIME-Version: 1.0\r\n";
    $h_resp .= "Content-Type: text/html; charset=UTF-8\r\n";
    $h_resp .= "From: JP Technology <noreply@jptechnology.com>\r\n";

    mail($email, "Recibimos tu consulta — JP Technology", $respuesta_html, $h_resp);
}

// ---- Redirigir ------------------------------------------
if ($enviado) {
    header("Location: " . $redirect_ok);
} else {
    // Si mail() falla (hosting sin sendmail), igual redirigir a OK
    // o cambiar a $redirect_error para debug
    header("Location: " . $redirect_ok);
}
exit;
?>
