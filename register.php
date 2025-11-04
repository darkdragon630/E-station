
// menangani pesan kesalahan login
$errors = [];
if (isset($_SESSION['Login_Error'])) {
    $errors[] = $_SESSION['Login_Error'];
    unset($_SESSION['Login_Error']);
}
if (isset($_SESSION['Register_Success'])) {
    $errors[] = $_SESSION['Register_Success'];
    unset($_SESSION['Register_Success']);
}
if (isset($_SESSION['Reset_Success'])) {
    $errors[] = $_SESSION['Reset_Success'];
    unset($_SESSION['Reset_Success']);
}
if (isset($_SESSION['Reset_Error'])) {
    $errors[] = $_SESSION['Reset_Error'];
    unset($_SESSION['Reset_Error']);
}
if (!empty($errors)) {
    echo '<script>alert("' . implode('\n', $errors) . '");</script>';
}