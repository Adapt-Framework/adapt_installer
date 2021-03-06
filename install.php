<?php
/* * * * C o n f i g u r a t i o n * * * * * * * * * * * * * * * */

/* List the bundles you'd like to install - adapt is always installed */
$bundles_to_install = array('adapt_setup');


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


$repository_url = "https://repository.adaptframework.com/v1";


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
					print "<p class=\"error\">Please enable mod_rewrite (you should also enable mod_exipres if you haven't) in your Apache configuration and then press continue.</p>";
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
					
					$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/.htaccess", "w");
					if ($fp !== false){
						fwrite($fp, "<Files ~ \"\\.(xml)$\">\n");
						fwrite($fp, "deny from all\n");
						fwrite($fp, "</Files>\n");
						fwrite($fp, "Options -Indexes\n");
						fwrite($fp, "<IfModule mod_expires.c>\n");
						fwrite($fp, "ExpiresActive		On\n");
						fwrite($fp, "ExpiresDefault		\"access plus 1 seconds\"\n");
						fwrite($fp, "ExpiresByType		image/gif		\"access plus 120 minutes\"\n");
						fwrite($fp, "ExpiresByType		image/jpeg		\"access plus 120 minutes\"\n");
						fwrite($fp, "ExpiresByType		image/png		\"access plus 120 minutes\"\n");
						fwrite($fp, "ExpiresByType		text/css		\"access plus 60 minutes\"\n");
						fwrite($fp, "ExpiresByType		text/javascript	\"access plus 60 minutes\"\n");
						fwrite($fp, "</IfModule>\n");
					}
					fclose($fp);
					
					/* Create the file store */
					mkdir($_SERVER['DOCUMENT_ROOT'] . '/adapt/store');
					
					/* Create a .htaccess file for the private store */
					$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/store/.htaccess", "w");
					if ($fp !== false){
						fwrite($fp, "deny from all\n");
					}
					fclose($fp);
					
					/* Lets download Adapt */
					$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt.bundle", "w");
					if ($fp){
                                            fwrite($fp, file_get_contents($repository_url . "/bundles/bundle/adapt/latest/download"));
                                            fclose($fp);
					}
					
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt.bundle")){
						/* We have the framework so now we need to unbundle it */
						$bfp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt.bundle", "r");
						
						if ($bfp){
							$manifest = fgets($bfp);
							$manifest = json_decode($manifest, true);
							$bundle_name = "adapt";
							$bundle_version = "0.0.0";
							
							if ($manifest && is_array($manifest)){
								foreach($manifest as $file){
									
									if ($file['name'] == "bundle.xml"){
                                                                            /* We need to get the framework version */
                                                                            $matches = array();
                                                                            $xml = fread($bfp, $file['length']);

                                                                            if (preg_match_all("/<version>(\d+(\.\d+)?(\.\d+)?)+<\/version>/", $xml, $matches)){
                                                                                $bundle_version = $matches[1][0];
                                                                            }
									}else{
                                                                            /* Just to seek the file forward */
                                                                            if ($file['length']){
										fread($bfp, $file['length']);
                                                                            }
									}
								}
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt");
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}");
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}/static");
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}/static/js");
								mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}/static/css");
								
								/* Reset the file pointer */
								fseek($bfp, 0);
								
								/* Re-read the manifest */
								$manifest = fgets($bfp);
								$manifest = json_decode($manifest, true);
								
								foreach($manifest as $file){
									$path = $_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}/" . dirname($file['name']);
									$path = trim($path, ".");
									if (!is_dir($path)){
                                                                            make_dir($path);
									}
									$ofp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt/adapt-{$bundle_version}/" . $file['name'], "w");
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
								fwrite($ifp, "define('ADAPT_VERSION', '{$bundle_version}');\n");
								fwrite($ifp, "define('ADAPT_STARTED', true);\n");
								fwrite($ifp, "require(ADAPT_PATH . 'adapt/adapt-' . ADAPT_VERSION . '/boot.php');\n");
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
								fwrite($ifp, "RewriteRule	.*	index.php?url=\$0	[QSA,L]\n\n");
								fwrite($ifp, "<Files ~\"\\.xml$\">\n");
								fwrite($ifp, "Order allow,deny\n");
								fwrite($ifp, "Deny from all\n");
								fwrite($ifp, "</Files>\n");
								fwrite($ifp, "Options -Indexes\n");
								
								fclose($ifp);
							}
						}
						
						unlink($_SERVER['DOCUMENT_ROOT'] . "/adapt/adapt.bundle");
					}
					
					/*
					 * We need to install the bundles listed in $bundles_to_install
					 */
					foreach($bundles_to_install as $bundle){
						/* Because we do not yet know the type we are just going to pop it into the adapt root and move it later */
						$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}.bundle", "w");
						if ($fp){
							fwrite($fp, file_get_contents($repository_url . "/bundles/bundle/{$bundle}/latest/download"));
							fclose($fp);
						}
						
						if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}.bundle")){
							/* We have the bundle so now we need to unbundle it */
							$bfp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}.bundle", "r");
							
							if ($bfp){
								$manifest = fgets($bfp);
								$manifest = json_decode($manifest, true);
								
								if ($manifest && is_array($manifest)){
									mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}");
									mkdir($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}/temp");
									
									foreach($manifest as $file){
										
										$path_parts = explode('/', trim(dirname($file['name']), '/'));
										$new_path = $_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}/temp";
										foreach($path_parts as $p){
											$new_path .= "/{$p}";
											if (!is_dir($new_path)){
												mkdir($new_path);
											}
										}
										
										$path = $_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}/temp/" . dirname($file['name']);
										$path = trim($path, ".");
										
										$ofp = fopen($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}/temp/" . $file['name'], "w");
										if ($ofp){
											fwrite($ofp, fread($bfp, $file['length']));
											fclose($ofp);
										}
										
									}
									
									
								}
								
								fclose($bfp);
								
								/* Lets read the type from the bundles manifest */
								$manifest = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}/temp/bundle.xml");
								if ($manifest){
									$matches = array();
									if (preg_match_all("/<version>(\d+(\.\d+)?(\.\d+)?)+<\/version>/", $manifest, $matches)){
										rename("{$_SERVER['DOCUMENT_ROOT']}/adapt/{$bundle}/temp", "{$_SERVER['DOCUMENT_ROOT']}/adapt/{$bundle}/{$bundle}-{$matches[1][0]}");
									}
								}
							}
						}
						
						unlink($_SERVER['DOCUMENT_ROOT'] . "/adapt/{$bundle}.bundle");
					}
					
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

function make_dir($dir){
    if (!is_string($dir) || !strlen($dir) > 0){
        return false;
    }

    $path = '/';
    $parts = explode('/', $dir);
    foreach($parts as $part){
        $path .= $part . "/";
        if (!is_dir($path)){
            mkdir($path);
        }
    }

    return true;
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