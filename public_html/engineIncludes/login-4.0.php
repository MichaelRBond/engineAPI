<?php
require_once '/home/www.libraries.wvu.edu/phpincludes/engine/engineAPI/4.0/engine.php';
$engine = EngineAPI::singleton();
errorHandle::errorReporting(errorHandle::E_ALL);

recurseInsert("includes/engineHeader.php","php");

login::$loginType = "ldap"; //"mysql"

$localVars  = localvars::getInstance();
$engineVars = enginevars::getInstance();

if($engineVars->get('forceSSLLogin') === TRUE && (!isset($_SERVER['HTTPS']) or is_empty($_SERVER['HTTPS']))){
        $engineVars->set('loginPage',str_replace("http://","https://",$engineVars->get('loginPage')));
        header("Location: ".$engineVars->get('loginPage')."?".$_SERVER['QUERY_STRING']);
        exit;
}


$localVars->set('pageTitle',"Login Page");
$localVars->set("domain","wvu-ad");

$authFail  = FALSE; // Authorization to the current resource .. we may end up not using this
$loginFail = FALSE; // Login Success/Failure

if (!session::get("page") && isset($_GET['HTML']['page'])) {
	$page = $_GET['HTML']['page']; 
	if (isset($_GET['HTML']['qs'])) {
		$qs = urldecode($_GET['HTML']['qs']);
		$qs = preg_replace('/&amp;amp;/','&',$qs);
		$qs = preg_replace('/&amp;/','&',$qs);
	}
	else {
		$qs = "";
	}

	session::set("page",$page);
	session::set("qs",$qs);

}

//Login processing:
if (isset($_POST['HTML']['loginSubmit'])) {
	if (!isset($_POST['HTML']['username']) || !isset($_POST['HTML']['password'])) {
		$authFail  = TRUE;
		$loginFail = TRUE;
	}
	else {
		if (login::login()) {
//            die(__LINE__.' - '.__FILE__);
            if(isset($_GET['HTML']['url'])) {
				header("Location: ".$_GET['HTML']['URL'] ) ;
			}
			else {
				
				if (session::get("page")) {
					$url = sprintf("%s?%s",
						session::get("page"),
						session::get("qs")
						);

					header("Location: ".$url );

					exit;
				}
				else {
					header("Location: ".$engineVars->get('WEBROOT') );
				}

			}
		}
		else {
			$loginFail = TRUE;
		}
		
	}

}

templates::load("library2012.1col");
templates::display('header');
?>

<h1>Login</h1>

<?php
if($loginFail) {
	print "<div style=\"\"><p>Login Failed</p></div>";
}
?>

<?php
if(isset($page)) {
	print "<div style=\"color:red;\"><p>You are either not logged in or do not have access to the requested page.</p></div>";
}
?>

<form name="loginForm" action="<?php print $_SERVER['PHP_SELF']?><?php if(isset($page)){ echo "?page=".$page; if(isset($qs)) { echo "&qs=".(urlencode($qs)); } } ?>" method="post">
	{csrf}
	
	<table>
		<tr>
			<td>
				<label for="username">Username:</label>
			</td>
			<td>
				<input type="text" name="username" id="username" value="" />
			</td>
		</tr>
		<tr>
			<td>	
				<label for="password">Password:</label>
			</td>
			<td>
				<input type="password" name="password" id="password" value="" onkeypress="capsLockCheck(event);"/> <span id="capsLock" style="display:none;">Caps Lock is On</span>
			</td>
		</tr>
	</table>
	
	<br />
	
	<input type="submit" name="loginSubmit" value="Login" />
</form>


<script>
document.loginForm.username.focus();
</script>

<?php
templates::display('footer');
?>
