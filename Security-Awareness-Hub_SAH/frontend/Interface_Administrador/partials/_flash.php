<?php
/**
 * Muestra un mensaje de éxito/error leído de la URL
 * (?msg=...&tipo=exito|error), resultado de un
 * redirect-después-de-POST. Sin JavaScript ni sesión de flash.
 */

$msg = $_GET["msg"] ?? "";
$tipo = ($_GET["tipo"] ?? "exito") === "error" ? "error" : "success";

if ($msg !== ""):
?>
    <div class="admin-alert admin-alert-<?= $tipo ?>">
        <?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?>
    </div>
<?php endif; ?>