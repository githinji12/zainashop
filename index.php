

<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /zaina-beauty/dashboard/');
} else {
    header('Location: /zaina-beauty/auth/login.php');
}
exit();
?>
