<?php
	//including the required files
	require_once 'include/DbOperation.php';

	if(isset($_POST["reset-password"])) {
		//getting post values
        $api = $_GET["api"];
        $newpassword = $_POST["member_password"];
		
		//Creating DbOperation object
        $db = new DbOperation();

		$result = $db->updatePassword($newpassword, $api);

		//If username password is correct
        if($result == PASSWORD_CHANGED){
			$message1 = 'Senha Redefinida com Sucesso!';
			$message2 = 'Volte ao app e tente fazer login';
        }else if($result == PASSWORD_NOT_CHANGED){
			$message1 = 'Algum erro ocorreu';
			$message2 = 'Tente novamente';
		}
	}
?>

<!DOCTYPE html PUBLIC "."-//W3C//DTD XHTML 1.0 Transitional//PT>
<html>

	<head>
		<title>Notepic.</title>
		<meta http-equiv=Content-Type content=text/html; charset=UTF-8>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
		<link href='https://notepic.com.br/img/logo1.png'>
		<link rel="icon" href="https://notepic.com.br/img/logo1.png" type="image/png"/>
		<!-- <link href="style.css" rel="stylesheet" type="text/css" media="screen"> -->
		
		<style>
			@import url(https://fonts.googleapis.com/css?family=Roboto:300);

			.login-page {
			width: 360px;
			padding: 8% 0 0;
			margin: auto;
			}
			@media(max-width: 720px) {
				.login-page {width: 80%;}
				.form {max-width: 80%;}
			}
			.form {
			position: relative;
			z-index: 1;
			background: #FFFFFF;
			max-width: 360px;
			margin: 0 auto 100px;
			padding: 45px;
			text-align: center;
			box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
			}
			.form input {
			font-family: "Roboto", sans-serif;
			outline: 0;
			background: #f2f2f2;
			width: 100%;
			border: 0;
			margin: 0 0 15px;
			padding: 15px;
			box-sizing: border-box;
			font-size: 0.9em;
			}
			.form button {
			font-family: "Roboto", sans-serif;
			outline: 0;
			background: #00BFFF;
			width: 100%;
			border: none;
			padding: 15px;
			color: #FFFFFF;
			font-weight: bold;
			border-radius: 5px;
			font-size: 1em;
			cursor: pointer;
			}

			.form .message {
			margin: 15px 0 0;
			color: #d3d3d3;
			font-size: 0.8em;
			}
			.form .message a {
			color: #00BFFF;
			text-decoration: none;
			}

			body {
			background: #00BFFF; 
			font-family: "Roboto", sans-serif;
			}
			
			.modal {
				display: none; /* Hidden by default */
				position: fixed; /* Stay in place */
				z-index: 1; /* Sit on top */
				padding-top: 265px;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%; /* Full height */
				overflow: auto; /* Enable scroll if needed */
				background-color: rgb(0,0,0); /* Fallback color */
				background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
			}

			/* Modal Content */
			.modal-content {
				background-color: #fefefe;
				margin: auto;
				padding: 20px;
				border: 1px solid #888;
				width: 55%;
			}
			@media screen and (max-width: 720px) {
				.modal-content {width: 45%;}
			}
			@media screen and (max-width: 640px) {
				.modal-content {width: 75%;}
			}

			/* The Close Button */
			.close {
				color: #aaaaaa;
				float: right;
				font-size: 28px;
				font-weight: bold;
			}

			.close:hover,
			.close:focus {
				color: #000;
				text-decoration: none;
				cursor: pointer;
			}

		</style>
	</head>

	</body>
		<div class="login-page">
		<div class="form">
			<form action='' method="post" onSubmit="return validate_password_reset();">
			<input type="password" name="member_password" id="member_password" placeholder="Senha"/>
			<input type="password" name="confirm_password" id="confirm_password" placeholder="Repita a Senha"/>
			<button type="submit" name="reset-password" id="reset-password">Redefinir senha</button>
			<p class="message">Não conseguiu recuperar sua senha? Entre em contato pelo e-mail <a>suporte@notepic.com.br</a></p>    
			</form>
		</div>
		</div>
		<div id="myModal" class="modal">
			<div class="modal-content">
			<span class="close">&times;</span>
				<div id='text1' style="display:none;">
					<p><br><br></p>
						
				</div>
			</div>
		</div>

		<?php if(!empty($message1)) { ?>
		<div id="message" class="modal" style="display:block;">
			<div class="modal-content">
			<span class="close" onclick="phpClose();" >&times;</span>
				<div id='msg' style="display:block;">
					<p>
						<b><?php echo $message1; ?></b><br><?php echo $message2; ?>
					<br></p>
				</div>
				<script>
					function phpClose(){
						msg.style.display = 'none';
						document.getElementById('message').style.display = 'none';
						document.getElementById('msg').style.display = 'none'
					}

				</script>
			</div>
		</div>
		<?php } ?>
		
	</body>

	<script>
		var modal = document.getElementById('myModal');
		var txt1 = document.getElementById('text1');
		var span = document.getElementsByClassName('close')[0];
		
		modal.style.display = 'none';
		txt1.style.display = 'none';

		span.onclick = function() {
			modal.style.display = 'none';
			txt1.style.display = 'none';
		}

		function validate_password_reset() {
			if((document.getElementById("member_password").value == "") && (document.getElementById("confirm_password").value == "")) {
				txt1.innerHTML = "Por favor, colocar um senha nova!"
				modal.style.display = 'block';
				txt1.style.display = 'block';
				return false;
			}
			if(document.getElementById("member_password").value  != document.getElementById("confirm_password").value) {
				txt1.innerHTML = "As duas senhas devem ser iguais!"
				modal.style.display = 'block';
				txt1.style.display = 'block';
				return false;
			}
			return true;
		}

	</script>
</html>
