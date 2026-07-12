<?php
// admin/armada/edit.php — Redirect ke tambah.php dengan id
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../armada/index.php'); exit; }
header('Location: tambah.php?id=' . $id);
exit;
