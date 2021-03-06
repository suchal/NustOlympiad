<?php 

namespace Contact;
require_once(__DIR__."/../bootstrap.php");


use App\OlMail;
use Respect\Validation\Validator as v;

function sanitize($data){
    return htmlspecialchars(strip_tags($data));
}

if($_SERVER['REQUEST_METHOD'] == "POST"){
	$errors=[];
	if(!strlen(sanitize($_POST['name'] ?? "")))
		$errors['name'] = "Please enter a name!";
	else
		$name = sanitize($_POST['name']);
	if(!strlen($_POST['email'] ?? ""))
		$errors['email'] = "Please enter an email!";
	else
		$email = sanitize($_POST['email']);
	if(!strlen(sanitize($_POST['message'] ?? "")))
		$errors['message'] = "Please enter a message!";
	else
		$message = sanitize($_POST['message']);
	$emailvalidation = v::NotEmpty()->email();
	if(!$emailvalidation->validate($email??''))
		$errors['email'] = "Please enter a valid email address";
 	$ipaddress = \App\get_client_ip();
 	$captcha = \App\send_post("https://www.google.com/recaptcha/api/siteverify", 
				[
				"secret" 	=> "6Ldgtg0UAAAAAHx4_kcm5G95hD8CCnEd_AcQeY6k",
				"response"	=> sanitize($_POST['captcha']),
				"remoteip"	=> $ipaddress
				]);
 	if(!$captcha->success)
 		$errors['captcha'] = "Captcha is required!";

	if(!count($errors)){
		//first populate the messages table
		$stmt = $mpdo->prepare("insert into messages(name, email, message) values(?,?,?)");
		$stmt->execute([$name, $email, $message]);
		//then send the email to web_it@nustolympiad.com
		$txtmessage = "A new message has arrived from contact us form. \n Name:$name \n Email: $email \n Message: \"$message\"";
		$brmessage = nl2br($txtmessage);
		$htmlmessage = 
<<<htmlmessage
<html>
<body>
<p>
$brmessage
</p>
</body>
</html>
htmlmessage;
		$mail = new OlMail(["name"=>"Web&IT Olympiad", "email"=>"er@nustolympiad.com"],"A new message!", $htmlmessage, $txtmessage);
		$mail->send();
	}

 }
if(isset($errors) && count($errors)){
	$ajax = json_encode($errors);
	echo $ajax;
	exit();
}
else{
echo 1;
exit();

}

// \App\redirect("/");