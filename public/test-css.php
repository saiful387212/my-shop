<?php
define('ABSPATH', realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div style="background: #2C3E8F; color: white; padding: 20px; font-family: 'Inter', sans-serif;">
        <h1>CSS Test</h1>
        <p>If this is blue with white text, CSS is loading!</p>
        <p>CSS Path: <?php echo SITE_URL; ?>assets/css/register.css</p>
    </div>
</body>
</html>