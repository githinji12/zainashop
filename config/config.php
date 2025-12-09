<?php
// Database configuration
define('DB_HOST', 'localhost:3307');
define('DB_NAME', 'zaina_beauty');
define('DB_USER', 'root');
define('DB_PASS', '');

// Shop settings
define('SHOP_NAME', "Zaina's Beauty Shop");
define('CURRENCY', 'KES');

// 🔥 M-Pesa Daraja API (SANDBOX)
define('MPESA_ENV', 'live');
define('MPESA_SECURITY_CREDENTIAL', 'EB5f+z8ho40Y0JWVOT/6hVpBcpFIqkxxf7mhjkH0ik0ZUSPca28ZKp9YvTEkbFyOC5leWVPkJ15HsoXTJ61dmnfbz7zcdP+SyQg7Ba/SlAh7cV8QqaM5uz9/YKIS2wFUi2EGxBVganQz0O7lO64BmrZ68mKHwGgUzO3A46SnnB2aaIufZ0GhXbLlkN/dB6w3s+iQHNDoQrP3AG4MnjkVq8n/p4Dfelq/rnnqYyT7kckr8Zv5iQzyXCroHqsidFeYjU8AcSi+Ai0Tr+MhsiwkYxR0Op4Ue7zkft4+HOAZ1WiKm1dSjjp5F63OtHqy1E7aX20tA1cpPfwstd5saY0J9g==');
define('MPESA_CONSUMER_KEY', 'qM1DabnkaKfjasZGbaqlHss1g27qDEAX054k2wklfU7M5DJO');
define('MPESA_CONSUMER_SECRET', 'q8joU9CU4RGjwKuudGBPRzFPe0Rflu2diAt7LKMB8ULjme7oCLlU3pNnvWBuzBwa');
define('MPESA_SHORTCODE', '400200');
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_CALLBACK_URL', 'https://fb64758c9d4a.ngrok-free.app/zaina-beauty/mpesa/callback.php');
define('MPESA_LOG_PATH', __DIR__ . '/logs/');
?>
