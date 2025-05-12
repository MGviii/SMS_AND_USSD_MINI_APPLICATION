<?php
include 'menu.php';
include_once 'db.php';

$sessionId   = $_POST['sessionId'];
$phoneNumber = $_POST['phoneNumber'];
$serviceCode = $_POST['serviceCode'];
$text        = $_POST['text'];

$menu = new Menu($text, $sessionId);

$text = $menu->middleware($text);

$conn = DB::connect();
$stmt = $conn->prepare("SELECT * FROM user WHERE phone = ?");
$stmt->execute([$phoneNumber]);
$isRegistered = $stmt->rowCount() > 0;

if ($text == "" && !$isRegistered) {
    $menu->mainMenuUnregistered();

} else if ($text == "" && $isRegistered) {
    $menu->mainMenuRegistered();

} else if (!$isRegistered) {
    $textArray = explode("*", $text);
    switch ($textArray[0]) {
        case 1:
            $menu->menuRegister($textArray);
            break;
        default:
            echo "END Invalid option. Try again.";
    }

} else {
    $textArray = explode("*", $text);
    switch ($textArray[0]) {
        case 1:
            $menu->menuSendMoney($textArray);
            break;
        case 2:
            $menu->menuWithdrawMoney($textArray);
            break;
        case 3:
            $menu->menuCheckBalance($textArray);
            break;
        default:
            echo "END Invalid choice.";
    }
}
?>
