<?php

$messages = array( 
	1 => "The uploaded file exceeds the system maximum", 
	2 => "The uploaded file exceeds the form maximum",
	3 => "The uploaded file was only partially uploaded", 
	4 => "No file was uploaded", 
	6 => "Missing a temporary folder",
	7 => "Failed to write file to disk",
	8 => "Something stopped the file upload"
);

set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	try {
	
		if ($_FILES['icon']['error'] != 0 && $_FILES['icon']['error'] != 4) {
			throw new Exception($messages[$_FILES['icon']['error']]);
		}

		if ($_FILES['icon-android']['error'] != 0 && $_FILES['icon-android']['error'] != 4) {
			throw new Exception($messages[$_FILES['icon-android']['error']]);
		}
				
		if ($_FILES['splash']['error'] != 0 && $_FILES['splash']['error'] != 4) {
			throw new Exception($messages[$_FILES['splash']['error']]);
		}
		
		if ($_FILES['icon']['error'] == 4 && $_FILES['icon-android']['error'] == 4 && $_FILES['splash']['error'] == 4) {
			throw new Exception('At least select one file.');
		}
		
		if ($_POST['language'] && !preg_match('/^[a-z]{2}$/', $_POST['language'])) {
			throw new Exception('Invalid ISO 639-1 language code.');
		}
		
		if ($_FILES['icon']['error'] == 0 || $_FILES['splash']['error'] == 0) {
			$uniqid			= uniqid();
			$assets_path	= $_POST['alloy'] ? '/app/assets' : '/Resources';
			$tmp_path 		= dirname(__FILE__) . '/tmp/' . $uniqid;
			$zip_path		= dirname(__FILE__) . '/zip/' . $uniqid . '.zip';
			$zip_url 		= '/zip/' . $uniqid . '.zip';
		
			define('ICON_PATH', 0);
			define('ICON_SIZE', 1);
			define('ICON_DPI', 2);
		
			define('SPLASH_PATH', 0);
			define('SPLASH_WIDTH', 1);
			define('SPLASH_HEIGHT', 2);
			define('SPLASH_DPI', 3);
			define('SPLASH_ROTATE', 4);

			if ($_FILES['icon']['error'] == 0) {
		
				$sizes = array(
			
					// iOS
					array('/project' . $assets . '/iphone/appicon.png', 57, 72),
					array('/project' . $assets . '/iphone/appicon@2x.png', 114, 72),
					array('/project' . $assets . '/iphone/appicon-72.png', 72, 72),
					array('/project' . $assets . '/iphone/appicon-72@2x.png', 144, 72),
					array('/project' . $assets . '/iphone/appicon-Small.png', 29, 72),
					array('/project' . $assets . '/iphone/appicon-Small@2x.png', 58, 72),
					array('/project' . $assets . '/iphone/appicon-Small-50.png', 50, 72),
					array('/project' . $assets . '/iphone/appicon-Small-50@2x.png', 100, 72),
					array('/project' . $assets . '/iphone/iTunesArtwork', 512, 72),
					array('/iTunesConnect/icon.png', 1024, 72),
				);
		
				foreach ($sizes as $size) {
					$dir = dirname($tmp_path . $size[ICON_PATH]);
				
					if (is_dir($dir) == false) {
						mkdir($dir, 0777, true);
					}
				
					$image = new Imagick();
					$image->setResolution($size[ICON_DPI], $size[ICON_DPI]);
					$image->readImage($_FILES['icon']['tmp_name']);
					$image->setImageFormat('png');
					$image->cropThumbnailImage($size[ICON_SIZE], $size[ICON_SIZE]);
					$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
					$image->writeImage($tmp_path . $size[ICON_PATH]);
				}
			}
		
			if ($_FILES['icon']['error'] == 0 || $_FILES['icon-android']['error'] == 0) {
				$FILE = ($_FILES['icon-android']['error'] == 0) ? $_FILES['icon-android'] : $_FILES['icon'];

				$sizes = array(
				
					// Android
					array('/project' . $assets . '/android/appicon.png', 128, 72),
					array('/project/platform/android/res/drawable-ldpi/appicon.png', 36, 120),
					array('/project/platform/android/res/drawable-mdpi/appicon.png', 48, 160),
					array('/project/platform/android/res/drawable-hdpi/appicon.png', 72, 240),
					array('/project/platform/android/res/drawable-xhdpi/appicon.png', 96, 320),
					array('/GooglePlay/icon.png', 512, 72),
					
					// Mobile Web
					array('/project' . $assets . '/mobileweb/appicon.png', 128, 72),
				);
		
				foreach ($sizes as $size) {
					$dir = dirname($tmp_path . $size[ICON_PATH]);
				
					if (is_dir($dir) == false) {
						mkdir($dir, 0777, true);
					}
				
					$image = new Imagick();
					$image->setResolution($size[ICON_DPI], $size[ICON_DPI]);
					$image->readImage($FILE['tmp_name']);
					$image->setImageFormat('png');
					$image->cropThumbnailImage($size[ICON_SIZE], $size[ICON_SIZE]);
					$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
					$image->writeImage($tmp_path . $size[ICON_PATH]);
				}
			}
		
			if ($_FILES['splash']['error'] == 0) {
				$ios_path = $_POST['language'] ? '/project/i18n/' . $_POST['language'] : '/project' . $assets . '/iphone';
				$android_prefix = $_POST['language'] ? $_POST['language'] . '-' : '';

				$sizes = array(
				
					// iOS
					array($ios_path . '/Default.png', 320, 460, 72),
					array($ios_path . '/Default@2x.png', 640, 960, 72),
					array($ios_path . '/Default-568h@2x.png', 640, 1136, 72),
					array($ios_path . '/Default-Landscape.png', 1024, 748, 72),
					array($ios_path . '/Default-Portrait.png', 768, 1044, 72),
					array($ios_path . '/Default-Landscape@2x.png', 2048, 1496, 72),
					array($ios_path . '/Default-Portrait@2x.png', 1536, 2008, 72),
				
					// Android
					array('/project' . $assets . '/android/default.png', 320, 480, 72),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'long-land-hdpi/default.png', 800, 480, 240),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'long-land-ldpi/default.png', 400, 240, 120),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'long-port-hdpi/default.png', 480, 800, 240),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'long-land-ldpi/default.png', 240, 400, 120),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-land-hdpi/default.png', 800, 480, 240),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-land-ldpi/default.png', 320, 240, 120),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-land-mdpi/default.png', 480, 320, 160),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-port-hdpi/default.png', 480, 800, 240),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-port-ldpi/default.png', 240, 320, 120),
					array('/project' . $assets . '/android/images/res-' . $android_prefix . 'notlong-port-mdpi/default.png', 320, 480, 160),
				
					// Mobile Web
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default.jpg', 320, 460, 72),
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default.png', 320, 460, 72),
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default-Landscape.jpg', 748, 1024, 72, 90),
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default-Landscape.png', 748, 1024, 72, 90),
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default-Portrait.jpg', 768, 1004, 72),
					array('/project' . $assets . '/mobileweb/apple_startup_images/Default-Portrait.png', 768, 1004, 72),
				);
		
				foreach ($sizes as $size) {
					$dir = dirname($tmp_path . $size[SPLASH_PATH]);
				
					if (is_dir($dir) == false) {
						mkdir($dir, 0777, true);
					}
				
					$ext = substr($size[SPLASH_PATH], strrpos($size[SPLASH_PATH], '.') + 1);
				
					$image = new Imagick();
					$image->setResolution($size[SPLASH_DPI], $size[SPLASH_DPI]);
					$image->readImage($_FILES['splash']['tmp_name']);
				
					if ($ext == 'jpg') {
						$image->setImageCompression(Imagick::COMPRESSION_JPEG);
						$image->setImageCompressionQuality(100);
						$image->stripImage(); 
						$image->setImageFormat('jpeg');
				
					} else {
						$image->setImageFormat('png');
					}
					
					if (isset($size[SPLASH_ROTATE])) {
						$image->rotateImage(new ImagickPixel('none'), $size[SPLASH_ROTATE]); 
					}
				
					$image->cropThumbnailImage($size[SPLASH_WIDTH], $size[SPLASH_HEIGHT]);
					$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
					$image->writeImage($tmp_path . $size[SPLASH_PATH]);
				}
			}
		
			exec('(cd ' . $tmp_path . ' && zip -r -9 ' . $zip_path . ' ./)');
			exec('rm -rf ' . $tmp_path);
		
			$download = true;
		}
	
		if ($_FILES['icon']['tmp_name'] != '') {
			@unlink($_FILES['icon']['tmp_name']);
		}
	
		if ($_FILES['icon-android']['tmp_name'] != '') {
			@unlink($_FILES['icon-android']['tmp_name']);
		}

		if ($_FILES['splash']['tmp_name'] != '') {
			@unlink($_FILES['splash']['tmp_name']);
		}
		
		shell_exec('find ' . dirname(__FILE__) . '/zip/ -type f -name "*.zip" -mindepth 1 -maxdepth 1 -mmin +60 -exec rm {} \;');
		
		if ($download) {
			header('Location: ' . $zip_url);
			exit;
		}
	
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>TiCons - Generate all icon & splash screens for your Titanium app from just 2 or 3 files!</title>
    <meta name="description" content="Generate all icon & splash screens for your Titanium app from just 2 or 3 files!">
    <meta name="author" content="Fokke Zandbergen">
    <link href="jbootstrap/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
    
      body {
        padding-top: 20px;
        padding-bottom: 40px;
      }

      .container-narrow {
        margin: 0 auto;
        max-width: 700px;
      }
      .container-narrow > hr {
        margin: 30px 0;
      }

      .jumbotron {
        margin: 60px 0;
        text-align: center;
      }
      
      .form .row-fluid {
      	margin: 1em 0;
      }
      
      .form h4 {
      	margin-top: 0;
      }
      
      .about {
        margin: 60px 0;
      }
      
      .about p + h4 {
        margin-top: 28px;
      }
      
    </style>
  </head>

  <body>

    <div class="container-narrow">

      <div class="masthead">
        <ul class="nav nav-pills pull-right">
          <li><a href="#about">About</a></li>
          <li><a href="https://github.com/FokkeZB/TiCons" target="_blank">Fork on GitHub</a></li>
          <li><a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens" target="_blank">Titanium Docs</a></li>
        </ul>
        <h1><span class="muted">T</span>iCons</h1>
      </div>

      <hr>

      <div class="jumbotron">
        <h2>Generate Titanium icon & splash assets</h2>
        <p class="lead">Select 2 or 3 source files and get a ZIP with all you need!</p>
      </div>
      
      <? if ($error): ?>
      	<div class="alert alert-error"><?= $error ?></div>
      <? endif ?>
      
      <div class="form">
        <form action="./" method="post" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= 10 * 1024 * 1204 ?>" /> 
			<div class="row-fluid">
				<div class="span3"><h4>Default icon</h4></div>
				<div class="span4">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://dummyimage.com/100x100/eeeeee/333333.png&text=%201024x1024%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="icon" accept="image/png" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span5">Select a 1024x1024 PNG with <strong>no</strong> rounded corners or transparency. You <strong>can</strong> add a custom reflective shine or other effect, but be sure to <a href="#about">disable the default iOS shine</a> in that case.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Android icon</h4></div>
				<div class="span4">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://dummyimage.com/100x100/eeeeee/333333.png&text=%20%20512x512%20%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="icon-android" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span5">For Android you can select an alternative 512x512 PNG icon. Android does not apply any effects and promotes using transparency for <a href="http://developer.android.com/guide/practices/ui_guidelines/icon_design_launcher.html" target="_blank">unique shapes</a>. When provided, this icon will be used to generate the mobile web icon as well.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Splash</h4></div>
				<div class="span4">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://dummyimage.com/100x100/eeeeee/333333.png&text=%202048x2048%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="splash" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span5">Splash screens come in all sorts of sizes and because they're not squarish, we need to crop. For this to work, you should select a 2048x2048 PNG where the logo or other important artwork is placed within the center 500x500 pixels.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Language</h4></div>
				<div class="span4">
					<input type="text" name="language" class="input-mini" placeholder="e.g.: 'nl'" />
				</div>
				<div class="span5">Specify a <a href="http://en.wikipedia.org/wiki/ISO_639-1" target="_blank">ISO 639-1</a> language code to write iOS and Android splash screens to <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-LocalizedSplashScreens" target="_blank">localized paths</a>. You would need to run TiCons for every required language and then merge the resulting asset folders.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Alloy</h4></div>
				<div class="span9">
					<label class="checkbox" for="alloy">
					  <input type="checkbox" name="alloy" value="1" id="alloy"> Writes to <code>app/assets</code> instead of <code>Resources</code>.
					</label>
				</div>
			</div>
			<div class="row-fluid">
				 <div class="offset3 span4">
				 	<input type="submit" class="btn btn-large btn-success" value="Generate" />
				 </div>
			</div>
        </form>
      </div>
        
      <hr>

	  <a name="about"></a>
      <div class="row-fluid about">
      
        <div class="span6">
        
        	<h4>App stores</h4>
        	<p>TiCons also generates iTunes Connect and Google Play assets for you.</p>
        
			<h4>Filters</h4>
			<p>TiCons does not apply any filters. iOS automatically adds rounded corners and a drop shadow. By default, it also adds a reflective shine. You can disable this in your <code><a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-Pre-renderediconsoniOS" target="_blank">tiapp.xml</a></code>.</p>
			
			<h4>Android nine-patch splash</h4>
			<p>To better support the many differend Android display sizes and densities, you could <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-Splashscreens" target="_blank">use a nine-patch image for your splash screen</a>. This is not supported by TiCons right now.</p>
          
        </div>

        <div class="span6">
        
          <h4>Mobile Web</h4>
          <p>There is partial support for <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-MobileWebgraphicassetrequirementsandoptions" target="_blank">Mobile Web</a>. HTML splash screens are currently not generated.</p>

          <h4>BlackBerry & Tizen</h4>
          <p>There is no support for BlackBerry (10) and Tizen.</p>
          
          <h4>Contribute</h4>
          <p>Feel free to contact me at <a href="mailto:mail@fokkezb.nl">mail@fokkezb.nl</a> or <a href="https://github.com/FokkeZB/TiCons" target="_blank">fork</a> the code and send a pull request.<p>

        </div>
      </div>

      <hr>

      <div class="footer">
        <p>&copy; <a href="http://www.fokkezb.nl" target="_blank">Fokke Zandbergen</a> 2013 - <a href="https://github.com/FokkeZB/TiCons">License</a></p>
      </div>

    </div>
    
	<script src="http://code.jquery.com/jquery.js"></script>
    <script src="jbootstrap/js/bootstrap.min.js"></script>

  </body>
</html>

