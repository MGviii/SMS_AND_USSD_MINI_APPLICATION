<?php
include_once('myutils.php');
include_once('sms.php');
include_once('db.php');

class Menu
{
    protected $text;
    protected $sessionId;
    public function mainMenuUnregistered()
    {
        echo "CON Welcome to XYZ MOMO\n1. Register";
    }

    public function menuRegister($textArray)
    {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter your full name";
        } else if ($level == 2) {
            echo "CON Enter your PIN";
        } else if ($level == 3) {
            echo "CON Confirm your PIN";
        } else if ($level == 4) {

            $name = $textArray[1];
            $pin = $textArray[2];
            $confirm_pin = $textArray[3];
            $phone = $_POST['phoneNumber'];

            if ($pin !== $confirm_pin) {
                echo "END PINs do not match. Try again.";
                return;
            }


            $conn = DB::connect();
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);


            $stmt = $conn->prepare("INSERT INTO user (name, pin, phone, balance) VALUES (?, ?, ?, 400)");
            try {
                $stmt->execute([$name, $hashed_pin, $phone]);


                $msg = "Dear $name, you have successfully registered.";
                $sms = new Sms($phone);
                $result = $sms->sendSMS($msg, $phone);

                echo $result['status'] === "success"
                    ? "END You will receive an SMS shortly"
                    : "END Registered. SMS failed.";
            } catch (Exception $e) {
                echo "END Registration failed.";
            }
        }
    }


    public function mainMenuRegistered()
    {
        echo "CON Welcome back to XYZ MOMO.\n1. Send Money\n2. Withdraw Money\n3. Check Balance";
    }


    public function menuSendMoney($textArray)
    {
        $conn = DB::connect();
        $level = count($textArray);
        $senderPhone = $_POST['phoneNumber'];

        if ($level == 1) {
            echo "CON Enter recipient phone number";
        } else if ($level == 2) {
            echo "CON Enter amount";
        } else if ($level == 3) {
            echo "CON Enter your PIN";
        } else if ($level == 4) {
            echo "CON Send {$textArray[2]} RWF to {$textArray[1]}?\n1. Confirm\n2. Cancel";
        } else if ($level == 5) {
            if ($textArray[4] != 1) {
                echo "END Transaction cancelled.";
                return;
            }

            $recipientPhone = $textArray[1];
            $amount = floatval($textArray[2]);
            $pin = $textArray[3];

            $stmt = $conn->prepare("SELECT uid, balance, pin FROM user WHERE phone = ?");
            $stmt->execute([$senderPhone]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sender || !password_verify($pin, $sender['pin'])) {
                echo "END Invalid PIN.";
                return;
            }

            if ($sender['balance'] < $amount) {
                echo "END Insufficient funds.";
                return;
            }

            $stmt = $conn->prepare("SELECT uid FROM user WHERE phone = ?");
            $stmt->execute([$recipientPhone]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recipient) {
                echo "END Recipient not found.";
                return;
            }


            try {
                $conn->beginTransaction();


                $conn->prepare("UPDATE user SET balance = balance - ? WHERE uid = ?")
                    ->execute([$amount, $sender['uid']]);

                $conn->prepare("UPDATE user SET balance = balance + ? WHERE uid = ?")
                    ->execute([$amount, $recipient['uid']]);


                $conn->prepare("INSERT INTO transaction (amount, uid, ruid, ttype) VALUES (?, ?, ?, 'send')")
                    ->execute([$amount, $sender['uid'], $recipient['uid']]);

                $conn->commit();


                $msg = "You sent $amount RWF to $recipientPhone.";
                $sms = new Sms($senderPhone);
                $result = $sms->sendSMS($msg, $senderPhone);

                echo $result['status'] === "success"
                    ? "END Transaction successful. SMS sent."
                    : "END Sent. SMS failed.";
            } catch (Exception $e) {
                $conn->rollBack();
                echo "END Transaction failed.";
            }
        }
    }


    public function menuWithdrawMoney($textArray)
    {
        $conn = DB::connect();
        $level = count($textArray);
        $phone = $_POST['phoneNumber'];

        if ($level == 1) {
            echo "CON Enter amount";
        } else if ($level == 2) {
            echo "CON Enter Agent Code";
        } else if ($level == 3) {
            echo "CON Enter your PIN";
        } else if ($level == 4) {
            echo "CON Withdraw {$textArray[1]} RWF using agent code {$textArray[2]}?\n1. Confirm\n2. Cancel";
        } else if ($level == 5) {
            if ($textArray[4] != 1) {
                echo "END Withdrawal cancelled.";
                return;
            }

            $amount = floatval($textArray[1]);
            $agentCode = $textArray[2];
            $pin = $textArray[3];


            $stmt = $conn->prepare("SELECT uid, balance, pin FROM user WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($pin, $user['pin'])) {
                echo "END Invalid PIN.";
                return;
            }

            if ($user['balance'] < $amount) {
                echo "END Insufficient funds.";
                return;
            }


            $stmt = $conn->prepare("SELECT aid FROM agent WHERE agentNumber = ?");
            $stmt->execute([$agentCode]);
            $agent = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$agent) {
                echo "END Invalid agent code.";
                return;
            }


            try {
                $conn->beginTransaction();


                $conn->prepare("UPDATE user SET balance = balance - ? WHERE uid = ?")
                    ->execute([$amount, $user['uid']]);


                $conn->prepare("INSERT INTO transaction (amount, uid, aid, ttype) VALUES (?, ?, ?, 'withdraw')")
                    ->execute([$amount, $user['uid'], $agent['aid']]);

                $conn->commit();


                $msg = "You withdrew $amount RWF using agent $agentCode.";
                $sms = new Sms($phone);
                $result = $sms->sendSMS($msg, $phone);

                echo $result['status'] === "success"
                    ? "END Withdrawal successful. SMS sent."
                    : "END Withdrawal done. SMS failed.";
            } catch (Exception $e) {
                $conn->rollBack();
                echo "END Withdrawal failed.";
            }
        }
    }


    public function menuCheckBalance($textArray)
    {
        $conn = DB::connect();
        $level = count($textArray);
        $phone = $_POST['phoneNumber'];

        if ($level == 1) {
            echo "CON Enter your PIN";
        } else if ($level == 2) {
            $pin = $textArray[1];

            $stmt = $conn->prepare("SELECT balance, pin FROM user WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($pin, $user['pin'])) {

                $msg = "Your balance is: {$user['balance']} RWF.";
                $sms = new Sms($phone);
                $result = $sms->sendSMS($msg, $phone);

                echo $result['status'] === "success"
                    ? "END Balance sent via SMS."
                    : "END Balance: {$user['balance']} RWF.";
            } else {
                echo "END Incorrect PIN.";
            }
        }
    }


    public function middleware($text)
    {
        return $this->goBack($this->goToMainMenu($text));
    }


    public function goBack($text)
    {
        $ExplodedText = explode("*", $text);
        while (($index = array_search(myutils::$GO_BACK, $ExplodedText)) !== false) {
            array_splice($ExplodedText, $index - 1, 2);
        }
        return join("*", $ExplodedText);
    }


    public function goToMainMenu($text)
    {
        $ExplodedText = explode("*", $text);
        while (($index = array_search(myutils::$GO_TO_MAIN_MENU, $ExplodedText)) !== false) {
            $ExplodedText = array_slice($ExplodedText, $index + 1);
        }
        return join("*", $ExplodedText);
    }
}
