<?php
/* * * * C o n f i g u r a t i o n * * * * * * * * * * * * * * * */

/* List the bundles you'd like to install - adapt is always installed */
$bundles_to_install = array('adapt_setup');


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Adapt framework installer</title>
	<style>
		html,body,table{ height: 100%; background-color: #eee; }
		td{
			text-align: center;
			font-family: sans-serif;
			color: #333;
		}
		.pane{
			background-color: white;
			border: 1px solid #ddd;
			border-radius: 7px;
			width: 400px;
			font-family: sans-serif;
			margin: 0 auto;
			padding: 15px;
		}
		.progress-bar{
			margin: 15px auto;
			width: 450px;
			border: 1px solid #aaa;
			padding: 5px;
			border-radius: 4px;
		}
		.progress{
			height: 10px;
			background: #333;
		}
		a.button{
			border: 2px solid #aaa;
			border-radius: 5px;
			color: #aaa;
			text-decoration: none;
			padding: 10px 20px;
			font-weight: bold;
		}
		a.button:hover{
			background: #aaa;
			color: #eee;
		}
		p.controls{
			margin-top: 45px;
		}
	</style>
</head>

<body>
	<table width="100%">
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><div class="panex">
			<h1>Adapt framework</h1>
			<h2>Installation</h2>
			<div class='progress-bar'>
				<div class='progress'></div>
			</div>
			
<?php

if (!preg_match("/Apache/", $_SERVER['SERVER_SOFTWARE'])){
	
	print "<style>.progress{ width: 0; }</style>";
	print "<p class=\"error\">Adapt framework requires Apache Webserver</p>";
	
}else{
	
	if ($_SERVER['DOCUMENT_ROOT'] . '/install.php' != $_SERVER['SCRIPT_FILENAME']){
		
		print "<style>.progress{ width: 10%; }</style>";
		print "<p class=\"error\">Adapt framework must be installed in the document root directory.</p>";
		
	}else{
		
		if (!is_writable($_SERVER['DOCUMENT_ROOT'])){
			
			print "<style>.progress{ width: 20%; }</style>";
			print "<p class=\"error\">The directory <strong>&quot;{$_SERVER['DOCUMENT_ROOT']}&quot;</strong> is not writable.</p>";
			print "<p>Please change the permissions for this directory and then press continue.</p>";
			print "<p class=\"controls\"><a class=\"button\" href=\"/install.php\">Continue</a></p>";
			
		}else{
			/*
			 * Check if .htaccess is enabled
			 */
			$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/htaccess_test", "w");
			if ($fp !== false){
				fwrite($fp, "failed");
			}
			fclose($fp);
			
			$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/htaccess_test_redirect", "w");
			if ($fp !== false){
				fwrite($fp, "passed");
			}
			fclose($fp);
			
			$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/.htaccess", "w");
			if ($fp !== false){
				fwrite($fp, "redirect 301 /htaccess_test /htaccess_test_redirect\n");
			}
			fclose($fp);
			
			$url = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
			$url .= $_SERVER['HTTP_HOST']. '/htaccess_test';
			$output = file_get_contents($url);
			
			switch($output){
			case 'passed':
				
				/*
				 * Lets check if mod_rewrite is enabled
				 */
				$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/rewrite_test", "w");
				if ($fp !== false){
					fwrite($fp, "Enabled");
				}
				fclose($fp);
				
				$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/.htaccess", "w");
				if ($fp !== false){
					fwrite($fp, "RewriteEngine On\n");
					fwrite($fp, "RewriteRule .* rewrite_test [QSA,L]\n");
				}
				fclose($fp);
				
				$url = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
				$url .= $_SERVER['HTTP_HOST']. '/made_up_url';
				$output = file_get_contents($url);
				
				if ($output != "Enabled"){
					print "<style>.progress{ width: 40%; }</style>";
					print "<p class=\"error\">Please enable mode_rewrite in your Apache configuration and then press continue.</p>";
					print "<p class=\"controls\"><a class=\"button\" href=\"/install.php\">Continue</a></p>";
					break;
				}else{
					unlink($_SERVER['DOCUMENT_ROOT'] . "/.htaccess");
					
					/*
					 * The environment is ready is we are going
					 * to begin the installation.
					 */
					
					/* Lets create the directories for the bundles */
					mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt");
					mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks");
					mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/extensions");
					mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/templates");
					mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/applications");
					
					/* Lets download Adapt */
					$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt.bundle", "w");
					if ($fp){
						fwrite($fp, file_get_contents('http://repo.adaptframework.com/adapt/bundles/adapt.bundle'));
						fclose($fp);
					}
					
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt.bundle")){
						/* We have the framework so now we need to unbundle it */
						$bfp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt.bundle", "r");
						
						if ($bfp){
							$manifest = fgets($bfp);
							$manifest = json_decode($manifest, true);
							$bundle_name = "adapt";
							
							if ($manifest && is_array($manifest)){
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt");
								
								foreach($manifest as $file){
									$path = $_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt/" . dirname($file['name']);
									$path = trim($path, ".");
									if (!is_dir($path)){
										mkdir($path);
									}
									$ofp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/frameworks/adapt/" . $file['name'], "w");
									if ($ofp){
										fwrite($ofp, fread($bfp, $file['length']));
										fclose($ofp);
									}
									
								}
								
								
							}
							
							fclose($bfp);
							
							/* We need to write index.php */
							$ifp = fopen($_SERVER['DOCUMENT_ROOT'] . "/index.php", "w");
							if ($ifp){
								fwrite($ifp, "<?php\n");
								fwrite($ifp, "\n");
								fwrite($ifp, "define('TEMP_PATH', sys_get_temp_dir() . '/');\n");
								fwrite($ifp, "define('ADAPT_PATH', \$_SERVER['DOCUMENT_ROOT'] . '/adapt/');\n");
								fwrite($ifp, "define('FRAMEWORK_PATH', ADAPT_PATH . 'frameworks/');\n");
								fwrite($ifp, "define('EXTENSION_PATH', ADAPT_PATH . 'extensions/');\n");
								fwrite($ifp, "define('TEMPLATE_PATH', ADAPT_PATH . 'templates/');\n");
								fwrite($ifp, "define('APPLICATION_PATH', ADAPT_PATH . 'applications/');\n");
								fwrite($ifp, "define('ADAPT_STARTED', true);\n");
								fwrite($ifp, "require(FRAMEWORK_PATH . 'adapt/boot.php');\n");
								fwrite($ifp, "\n");
								fwrite($ifp, "?>\n");
								
								fclose($ifp);
							}
							
							/* And write the .htaccess file */
							$ifp = fopen($_SERVER['DOCUMENT_ROOT'] . "/.htaccess", "w");
							if ($ifp){
								fwrite($ifp, "RewriteEngine	On\n");
								fwrite($ifp, "RewriteRule	^(adapt)($|/) - [L]\n");
								fwrite($ifp, "RewriteCond	%{REQUEST_FILENAME} !index.php\n");
								fwrite($ifp, "RewriteRule	.*	index.php?url=\$0	[QSA,L]\n");
								
								fclose($ifp);
							}
						}
					}
					
					/* Download and install any other bundles required */
					
					/* Redirect to set up */
					print "<script type=\"text/javascript\">window.location = '/';</script>";
				}
				
				unlink($_SERVER['DOCUMENT_ROOT'] . "/rewrite_test");
				
				break;
			case 'failed':
				print "<style>.progress{ width: 30%; }</style>";
				print "<p class=\"error\">Please enable htaccess files for this host in your Apache configuration and then press continue.</p>";
				print "<p class=\"controls\"><a class=\"button\" href=\"/install.php\">Continue</a></p>";
				break;
			default:
				print "<style>.progress{ width: 30%; }</style>";
				print "<p class=\"error\">Unable to determin if htaccess files are enabled.</p>";
				break;
			}
			
			unlink($_SERVER['DOCUMENT_ROOT'] . "/htaccess_test");
			unlink($_SERVER['DOCUMENT_ROOT'] . "/htaccess_test_redirect");
			
			
		}
		
	}
	
	
}

?>
			
			</div></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>
</body>
</html>
<?php



?>
