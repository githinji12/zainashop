

<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard/');
} else {
    header('Location: /auth/login.php');
}
exit();
?>
