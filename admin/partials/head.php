<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' — Admin' : 'Admin' ?> | <?= h(SITE_NAME) ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<?php if (!empty($adminExtraHead)) echo $adminExtraHead; ?>
</head>
<body class="admin-body">
