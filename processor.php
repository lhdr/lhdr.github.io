<?php
$database_dir = @$_POST['db_dir'];
$database = $database_dir.'/usersdb.php';
$domain_name = @$_SERVER['SERVER_NAME'];
$signup_success_page = @$_POST['signup_success'];
$signup_error_page = @$_POST['signup_error'];
$login_success_page = @$_POST['login_success'];
$login_error_page = @$_POST['login_error'];
$reset_success_page = @$_POST['reset_success'];
$reset_error_page = @$_POST['reset_error'];
$error_message = "";
$directory = $database_dir;
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}
if (!file_exists($database))
{
   fopen($database, 'w') or die("User database not found2!");
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_name']) && $_POST['form_name'] == 'signupform')
{	
   $newusername = $_POST['username'];
   $newemail = $_POST['email'];
   $newpassword = $_POST['password'];
   $confirmpassword = $_POST['confirmpassword'];
   $newfullname = $_POST['fullname'];
   $extra1 = $_POST['gender'];
   $extra2 = $_POST['phone'];
   $code = 'NA';
   if ($newpassword != $confirmpassword)
   {
      $error_message = 'Password and Confirm Password are not the same!<br>';
   }
   else
   if (!preg_match("/^[A-Za-z0-9-_!@$]{1,50}$/", $newusername))
   {
	  $error_message .= 'Username is not valid, please check and try again!<br>';
   }
   else
   if (!preg_match("/^[A-Za-z0-9-_!@$]{1,50}$/", $newpassword))
   {
      $error_message .= 'Password is not valid, please check and try again!<br>';
   }
   else
   if (!preg_match("/^[A-Za-z0-9-_!@$.' &]{1,50}$/", $newfullname))
   {
      $error_message .= 'Fullname is not valid, please check and try again!<br>';
   }
   else
   if (!preg_match("/^.+@.+\..+$/", $newemail))
   {
      $error_message .= 'Email is not a valid email address. Please check and try again!<br>';
   }
   else
   if (strlen($extra1) == 0)
   {
      $error_message .= 'Jobtitle Field cannot be empty.<br>';
   }
   else
   if (strlen($extra2) == 0)
   {
      $error_message = 'Phone Field cannot be empty.<br>';
   }
   $items = file($database, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach($items as $line)
   {
      list($username, $password, $email, $fullname) = explode('|', trim($line));
      if ($newusername == $username)
      {
         $error_message .= 'Username already used. Please select another username.<br>';
         break;
      }
   }
   if (empty($error_message))
   {
      $file = fopen($database, 'a');
      fwrite($file, $newusername);
      fwrite($file, '|');
      fwrite($file, md5($newpassword));
      fwrite($file, '|');
      fwrite($file, $newemail);
      fwrite($file, '|');
      fwrite($file, $newfullname);
      fwrite($file, '|1|');
      fwrite($file, $code);
      fwrite($file, '|');
      fwrite($file, $extra1);
      fwrite($file, '|');
      fwrite($file, $extra2);
      fwrite($file, "\r\n");
      fclose($file);
	  if (session_id() == "")
      {
         session_start();
      }
	  $_SESSION['username'] = $_POST['username'];
      $_SESSION['fullname'] = $newfullname;
	  $_SESSION['email'] = $_POST['email'];
      $arr =  array("stat" =>"success", "returned" => $signup_success_page);
	  echo json_encode($arr);
      exit;
   }else{
	   $arr = array("stat" => "fail", "returned" => $error_message);
	   echo json_encode($arr);
	   exit;
   }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_name']) && $_POST['form_name'] == 'loginform')
{
   $success_page = $login_success_page;
   $error_page = $login_error_page;
   $database = $database;
   $crypt_pass = md5($_POST['password']);
   $found = false;
   $fullname = '';
   $session_timeout = 600;
   if(filesize($database) > 0)
   {
      $items = file($database, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach($items as $line)
      {
         list($username, $password, $email, $name, $active) = explode('|', trim($line));
         if ($username == $_POST['username'] && $active != "0" && $password == $crypt_pass)
         {
            $found = true;
            $fullname = $name;
         }
      }
   }
   if($found == false)
   {
      $arr = array("stat" => "fail", "returned" => "Account Not Found");
	   echo json_encode($arr);
	   exit;
   }
   else
   {
      if (session_id() == "")
      {
         session_start();
      }
      $_SESSION['username'] = $_POST['username'];
      $_SESSION['fullname'] = $name;
	  $_SESSION['email'] = $email;
      $_SESSION['expires_by'] = time() + $session_timeout;
      $_SESSION['expires_timeout'] = $session_timeout;
      $rememberme = isset($_POST['rememberme']) ? true : false;
      if ($rememberme)
      {
         setcookie('username', $_POST['username'], time() + 3600*24*30);
         setcookie('password', $_POST['password'], time() + 3600*24*30);
      }
	  $arr =  array("stat" =>"success", "returned" => $login_success_page);
	  echo json_encode($arr);
      exit;
   }
$username = isset($_COOKIE['username']) ? $_COOKIE['username'] : '';
$password = isset($_COOKIE['password']) ? $_COOKIE['password'] : '';
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_name']) && $_POST['form_name'] == 'forgotpassword')
{
   $email = isset($_POST['email']) ? addslashes($_POST['email']) : '';
   $found = false;
   $items = array();
   $success_page = $reset_success_page;
   $error_page = $reset_error_page;
   $database = $database;
   if (filesize($database) == 0 || empty($email))
   {
      $arr = array("stat" => "fail", "returned" => "Account Not Found");
	   echo json_encode($arr);
	   exit;
   }
   else
   {
      $items = file($database, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach($items as $line)
      {
         list($username, $password, $emailaddress, $fullname, $active) = explode('|', trim($line));
         if ($email == $emailaddress && $active != "0")
         {
            $found = true;
         }
      }
   }
   if ($found == true)
   {
      $newpassword = '';
      $alphanum = array('a','b','c','d','e','f','g','h','i','j','k','m','n','o','p','q','r','s','t','u','v','x','y','z','A','B','C','D','E','F','G','H','I','J','K','M','N','P','Q','R','S','T','U','V','W','X','Y','Z','2','3','4','5','6','7','8','9');
      $chars = sizeof($alphanum);
      $a = time();
      mt_srand($a);
      for ($i=0; $i < 6; $i++)
      {
         $randnum = intval(mt_rand(0,55));
         $newpassword .= $alphanum[$randnum];
      }
      $crypt_pass = md5($newpassword);
      $file = fopen($database, 'w');
      foreach($items as $line)
      {
         $values = explode('|', trim($line));
         if ($email == $values[2])
         {
            $values[1] = $crypt_pass;
            $line = '';
            for ($i=0; $i < count($values); $i++)
            {
               if ($i != 0)
                  $line .= '|';
               $line .= $values[$i];
            }
         }
         fwrite($file, $line);
         fwrite($file, "\r\n");
      }
      fclose($file);
      $mailto = $_POST['email'];
      $subject = 'New password';
      $message = 'Your new password for http://www.'.$domain_name.'/ is:';
      $message .= $newpassword;
      $header  = "From: account@".$domain_name.""."\r\n";
      $header .= "Reply-To: .account@".$domain_name.""."\r\n";
      $header .= "MIME-Version: 1.0"."\r\n";
      $header .= "Content-Type: text/plain; charset=utf-8"."\r\n";
      $header .= "Content-Transfer-Encoding: 8bit"."\r\n";
      $header .= "X-Mailer: PHP v".phpversion();
      mail($mailto, $subject, $message, $header);
	  $arr = array("stat" => "fail", "returned" => "<div class='alert alert-success col-12'>A New password have been sent to your emaill address</div>");
	   echo json_encode($arr);
	   exit;
   }
   else
   {
      $arr = array("stat" => "fail", "returned" => "Email Not Found");
	   echo json_encode($arr);
	   exit;
   }
   exit;
}
?>