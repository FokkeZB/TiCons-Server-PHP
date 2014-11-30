<?php

$analytics = 'UA-36046051-3';
$messages = array(
	1 => "The uploaded file exceeds the system maximum",
	2 => "The uploaded file exceeds the form maximum",
	3 => "The uploaded file was only partially uploaded",
	4 => "No file was uploaded",
	6 => "Missing a temporary folder",
	7 => "Failed to write file to disk",
	8 => "Something stopped the file upload"
);

set_time_limit( 0 );

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

	try {
		$radius = (int) $_POST['radius'];

		if ( $_FILES['icon']['error'] != 0 && $_FILES['icon']['error'] != 4 ) {
			throw new Exception( $messages[$_FILES['icon']['error']] );
		}

		if ( $_FILES['icon-transparent']['error'] != 0 && $_FILES['icon-transparent']['error'] != 4 ) {
			throw new Exception( $messages[$_FILES['icon-transparent']['error']] );
		}

		if ( $_FILES['splash']['error'] != 0 && $_FILES['splash']['error'] != 4 ) {
			throw new Exception( $messages[$_FILES['splash']['error']] );
		}

		if ( $_FILES['icon']['error'] == 4 && $_FILES['icon-transparent']['error'] == 4 && $_FILES['splash']['error'] == 4 ) {
			throw new Exception( 'At least select one file.' );
		}

		if ( $_POST['language'] && !preg_match( '/^[a-z]{2}$/', $_POST['language'] ) ) {
			throw new Exception( 'Invalid ISO 639-1 language code.' );
		}
		
		if ( $radius < 0 || $radius > 50 ) {
			throw new Exception( 'Border radius should be between 0 and 50.' );
		}

		if ( is_array( $_POST['platforms'] ) == false || count( $_POST['platforms'] ) == 0 ) {
			throw new Exception( 'Select at least one platform.' );
		}

		if ( $_FILES['splash']['error'] == 0 && is_array( $_POST['orientations'] ) == false || count( $_POST['orientations'] ) == 0 ) {
			throw new Exception( 'Select at least one orientation.' );
		}

		if ( $_FILES['icon']['error'] == 0 || $_FILES['icon-transparent']['error'] == 0 || $_FILES['splash']['error'] == 0 ) {
			$uniqid = uniqid();
			$assets_path = $_POST['alloy'] ? '/app/assets' : '/Resources';
			$tmp_path = dirname( __FILE__ ) . '/tmp/' . $uniqid;
			$zip_path = dirname( __FILE__ ) . '/zip/' . $uniqid . '.zip';
			$zip_url = '/zip/' . $uniqid . '.zip';

			$compress = array();

			define( 'ICON_PATH', 0 );
			define( 'ICON_SIZE', 1 );
			define( 'ICON_DPI', 2 );
			define( 'ICON_RADIUS', 3 );

			define( 'SPLASH_PATH', 0 );
			define( 'SPLASH_WIDTH', 1 );
			define( 'SPLASH_HEIGHT', 2 );
			define( 'SPLASH_DPI', 3 );
			define( 'SPLASH_ROTATE', 4 );

			if ( $_FILES['icon']['error'] == 0 ) {

				$sizes = array();

				// iPhone & iPad
				if ( in_array( 'iphone', $_POST['platforms'] ) || in_array( 'ipad', $_POST['platforms'] ) ) {

					// iTunes Connect
					$sizes[] = array( '/iTunesConnect.png', 1024, 72 );

					// iTunes Artwork
					$sizes[] = array( $assets_path . '/iphone/iTunesArtwork', 512, 72 );
					$sizes[] = array( $assets_path . '/iphone/iTunesArtwork@2x', 1024, 72 );

					// Spotlight & Settings
					$sizes[] = array( $assets_path . '/iphone/appicon-Small@2x.png', 58, 72 );

					// Spotlight (iOS7)
					$sizes[] = array( $assets_path . '/iphone/appicon-Small-40.png', 40, 72 );
					$sizes[] = array( $assets_path . '/iphone/appicon-Small-40@2x.png', 80, 72 );
	
					// App (default)
					$sizes[] = array( $assets_path . '/iphone/appicon.png', 57, 72 );

					// iPhone
					if ( in_array( 'iphone', $_POST['platforms'] ) ) {

						// App
						$sizes[] = array( $assets_path . '/iphone/appicon@2x.png', 114, 72 );

						// Spotlight && Settings
						$sizes[] = array( $assets_path . '/iphone/appicon-Small.png', 29, 72 );
						
						// Settings (iPhone 6 Plus)
						$sizes[] = array( $assets_path . '/iphone/appicon-Small@3x.png', 87, 72 );

						// App (iOS7)
						$sizes[] = array( $assets_path . '/iphone/appicon-60.png', 60, 72 );
						$sizes[] = array( $assets_path . '/iphone/appicon-60@2x.png', 120, 72 );
						$sizes[] = array( $assets_path . '/iphone/appicon-60@3x.png', 180, 72 );
					}

					// iPad
					if ( in_array( 'ipad', $_POST['platforms'] ) ) {

						// App
						$sizes[] = array( $assets_path . '/iphone/appicon-72.png', 72, 72 );
						$sizes[] = array( $assets_path . '/iphone/appicon-72@2x.png', 144, 72 );

						// Spotlight && Settings
						$sizes[] = array( $assets_path . '/iphone/appicon-Small-50.png', 50, 72 );
						$sizes[] = array( $assets_path . '/iphone/appicon-Small-50@2x.png', 100, 72 );

						// App (iOS7)
						$sizes[] = array( $assets_path . '/iphone/appicon-76.png', 76, 72 );
						$sizes[] = array( $assets_path . '/iphone/appicon-76@2x.png', 152, 72 );
					}
				}

				foreach ( $sizes as $size ) {
					$file = $tmp_path . $size[ICON_PATH];
					$dir = dirname( $file );

					$compress[] = $file;

					if ( is_dir( $dir ) == false ) {
						mkdir( $dir, 0777, true );
					}

					$image = new Imagick();
					$image->setResolution( $size[ICON_DPI], $size[ICON_DPI] );
					$image->readImage( $_FILES['icon']['tmp_name'] );
					$image->setImageFormat( 'png' );
					$image->cropThumbnailImage( $size[ICON_SIZE], $size[ICON_SIZE] );
					$image->setImageResolution( $size[ICON_DPI], $size[ICON_DPI] );
					$image->setImageUnits( imagick::RESOLUTION_PIXELSPERINCH );
					// $image->setImageAlphaChannel( imagick::ALPHACHANNEL_DEACTIVATE );
					$image->writeImage( $file );
				}
			}

			if ( $_FILES['icon']['error'] == 0 || $_FILES['icon-transparent']['error'] == 0 ) {
				$hasIconTrans = ( $_FILES['icon-transparent']['error'] == 0 );
				$FILE = $hasIconTrans ? $_FILES['icon-transparent'] : $_FILES['icon'];

				$sizes = array();

				// Android
				if ( in_array( 'android', $_POST['platforms'] ) ) {
					$sizes[] = array( '' . $assets_path . '/android/appicon.png', 128, 72, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-ldpi/appicon.png', 36, 120, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-mdpi/appicon.png', 48
					, 160, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-hdpi/appicon.png', 72, 240, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-xhdpi/appicon.png', 96, 320, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-xxhdpi/appicon.png', 144, 480, !$hasIconTrans );
					$sizes[] = array( '/platform/android/res/drawable-xxxhdpi/appicon.png', 192, 640, !$hasIconTrans );
					$sizes[] = array( '/GooglePlay.png', 512, 72, !$hasIconTrans );
				}

				// Mobile Web
				if ( in_array( 'mobileweb', $_POST['platforms'] ) ) {
					$sizes[] = array( $assets_path . '/mobileweb/appicon.png', 128, 72 );
				}

				// Tizen
				if ( in_array( 'tizen', $_POST['platforms'] ) ) {
					$sizes[] = array( $assets_path . '/tizen/appicon.png', 96, 72 );
				}

				// BlackBerry
				if ( in_array( 'blackberry', $_POST['platforms'] ) ) {
					$sizes[] = array( $assets_path . '/blackberry/appicon.png', 114, 72 );
				}

				foreach ( $sizes as $size ) {
					$file = $tmp_path . $size[ICON_PATH];
					$dir = dirname( $file );

					$compress[] = $file;

					if ( is_dir( $dir ) == false ) {
						mkdir( $dir, 0777, true );
					}

					$image = new Imagick();
					$image->setResolution( $size[ICON_DPI], $size[ICON_DPI] );
					$image->readImage( $FILE['tmp_name'] );
					$image->setImageFormat( 'png' );
					$image->cropThumbnailImage( $size[ICON_SIZE], $size[ICON_SIZE] );
					
					if ( $size[ICON_RADIUS] && $radius > 0) {
						$px = round(($size[ICON_SIZE] / 100) * $radius);
						$image->roundCorners($px, $px);
					} else {
						// $image->setImageAlphaChannel( imagick::ALPHACHANNEL_DEACTIVATE );
					}
					
					$image->setImageResolution( $size[ICON_DPI], $size[ICON_DPI] );
					$image->setImageUnits( imagick::RESOLUTION_PIXELSPERINCH );
					$image->writeImage( $file );
				}
			}

			if ( $_FILES['splash']['error'] == 0 ) {
				$ios_path = $_POST['language'] ? '/i18n/' . $_POST['language'] : $assets_path . '/iphone';
				$android_prefix = $_POST['language'] ? $_POST['language'] . '-' : '';

				$sizes = array();

				// iPhone
				if ( in_array( 'iphone', $_POST['platforms'] ) ) {
					$sizes[] = array( $ios_path . '/Default.png', 320, $_POST['apple'] ? 480 : 460, 72 );
					$sizes[] = array( $ios_path . '/Default@2x.png', 640, 960, 72 );
					$sizes[] = array( $ios_path . '/Default-568h@2x.png', 640, 1136, 72 );
					
					// iPhone 6
					$sizes[] = array( $ios_path . '/Default-667h@2x.png', 750, 1334, 72 );
					$sizes[] = array( $ios_path . '/Default-Portrait-736h@3x.png', 1242, 2208, 72 );
					$sizes[] = array( $ios_path . '/Default-Landscape-736h@3x.png', 2208, 1242, 72 );
				}

				// iPad
				if ( in_array( 'ipad', $_POST['platforms'] ) ) {
					$sizes[] = array( $ios_path . '/Default-Landscape.png', 1024, $_POST['apple'] ? 768 : 748, 72 );
					$sizes[] = array( $ios_path . '/Default-Portrait.png', 768, $_POST['apple'] ? 1024 : 1044, 72 );
					$sizes[] = array( $ios_path . '/Default-Landscape@2x.png', 2048, $_POST['apple'] ? 1536 : 1496, 72 );
					$sizes[] = array( $ios_path . '/Default-Portrait@2x.png', 1536, $_POST['apple'] ? 2048 : 2008, 72 );
				}

				// Android
				if ( in_array( 'android', $_POST['platforms'] ) ) {
					$sizes[] = array( '/GooglePlayFeature.png', 1024, 500, 72 );
					$sizes[] = array( $assets_path . '/android/default.png', 320, 480, 72 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-xxxhdpi/default.png', 1920, 1280, 640 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-xxhdpi/default.png', 1600, 960, 480 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-xhdpi/default.png', 960, 640, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-hdpi/default.png', 800, 480, 240 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-mdpi/default.png', 480, 320, 160 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-land-ldpi/default.png', 400, 240, 120 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-xxxhdpi/default.png', 1280, 1920, 640 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-xxhdpi/default.png', 960, 1600, 480 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-xhdpi/default.png', 640, 960, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-hdpi/default.png', 480, 800, 240 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-mdpi/default.png', 320, 480, 160 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'long-port-ldpi/default.png', 240, 400, 120 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xxxhdpi/default.png', 1920, 1280, 640 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xxhdpi/default.png', 1600, 960, 480 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xhdpi/default.png', 960, 640, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-hdpi/default.png', 800, 480, 240 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-mdpi/default.png', 480, 320, 160 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-ldpi/default.png', 320, 240, 120 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xxxhdpi/default.png', 1280, 1920, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xxhdpi/default.png', 960, 1600, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xhdpi/default.png', 640, 960, 320 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-hdpi/default.png', 480, 800, 240 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-mdpi/default.png', 320, 480, 160 );
					$sizes[] = array( $assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-ldpi/default.png', 240, 320, 120 );
				}

				// Mobile Web
				if ( in_array( 'mobileweb', $_POST['platforms'] ) ) {
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default.jpg', 320, 460, 72 );
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default.png', 320, 460, 72 );
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default-Landscape.jpg', 748, 1024, 72, 90 );
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default-Landscape.png', 748, 1024, 72, 90 );
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default-Portrait.jpg', 768, 1004, 72 );
					$sizes[] = array( $assets_path . '/mobileweb/apple_startup_images/Default-Portrait.png', 768, 1004, 72 );
				}

				// BlackBerry
				if ( in_array( 'blackberry', $_POST['platforms'] ) ) {
					// same name, only fix size
					$sizes[] = array( $assets_path . '/blackberry/splash-600x1024.png', 768, 1280, 72 );
					// Q10 / Q5 support
					$sizes[] = array( $assets_path . '/blackberry/splash-720x720.png', 720, 720, 72 );
					// maybe Appc rename it in the futuer
					//$sizes[] = array( $assets_path . '/blackberry/splash-768x1280.png', 768, 1280, 72 );
				}

				$portrait = in_array( 'portrait', $_POST['orientations'] );
				$landscape = in_array( 'landscape', $_POST['orientations'] );

				foreach ( $sizes as $size ) {

					if ( ( !$portrait && $size[SPLASH_WIDTH] < $size[SPLASH_HEIGHT] ) || ( !$landscape && $size[SPLASH_WIDTH] > $size[SPLASH_HEIGHT] ) ) {
						continue;
					}

					$file = $tmp_path . $size[ICON_PATH];
					$dir = dirname( $file );

					if ( is_dir( $dir ) == false ) {
						mkdir( $dir, 0777, true );
					}

					$ext = substr( $size[SPLASH_PATH], strrpos( $size[SPLASH_PATH], '.' ) + 1 );

					$image = new Imagick();
					$image->setResolution( $size[SPLASH_DPI], $size[SPLASH_DPI] );
					$image->readImage( $_FILES['splash']['tmp_name'] );
					$image->stripImage();

					if ( $ext == 'jpg' ) {

						switch ( $_POST['compression'] ) {
						case 'low': $cq = 80; break;
						case 'medium': $cq = 65; break;
						case 'high': $cq = 50; break;
						default: $cq = 100; break;
						}

						$image->setImageFormat( 'jpeg' );
						$image->setImageCompression( Imagick::COMPRESSION_JPEG );
						$image->setImageCompressionQuality( $cq );

					} else {
						$image->setImageFormat( 'png' );

						$compress[] = $file;
					}

					if ( isset( $size[SPLASH_ROTATE] ) ) {
						$image->rotateImage( new ImagickPixel( 'none' ), $size[SPLASH_ROTATE] );
					}

					$image->cropThumbnailImage( $size[SPLASH_WIDTH], $size[SPLASH_HEIGHT] );
					$image->setImageResolution( $size[SPLASH_DPI], $size[SPLASH_DPI] );
					$image->setImageUnits( imagick::RESOLUTION_PIXELSPERINCH );
					// $image->setImageAlphaChannel( imagick::ALPHACHANNEL_DEACTIVATE );
					$image->writeImage( $file );
				}
			}

			if ( $_POST['compression'] && count( $compress ) > 0 ) {

				switch ( $_POST['compression'] ) {
				case 'low': $o = 1; break;
				case 'medium': $o = 2; break;
				case 'high': $o = 3; break;
				}

				shell_exec( 'optipng -v -o ' . $o . ' "' . implode( '" "', $compress ) . '"' );
			}

			exec( '(cd ' . $tmp_path . ' && zip -r -9 ' . $zip_path . ' ./)' );
			exec( 'rm -rf ' . $tmp_path );

			$download = true;
		}

		if ( $_FILES['icon']['tmp_name'] != '' ) {
			@unlink( $_FILES['icon']['tmp_name'] );
		}

		if ( $_FILES['icon-transparent']['tmp_name'] != '' ) {
			@unlink( $_FILES['icon-transparent']['tmp_name'] );
		}

		if ( $_FILES['splash']['tmp_name'] != '' ) {
			@unlink( $_FILES['splash']['tmp_name'] );
		}

		exec( 'find ' . dirname( __FILE__ ) . '/zip/ -type f -name "*.zip" -mindepth 1 -maxdepth 1 -mmin +60 -exec rm {} \;' );

		if ( $download ) {
			header( 'Location: ' . $zip_url );
			exit;
		}

	} catch ( Exception $e ) {
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
	<script src="http://code.jquery.com/jquery.js"></script>
    <script src="jbootstrap/js/bootstrap.min.js"></script>
  </head>

  <body>

  	<? if ($analytics): ?>
	  <script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', '<?= $analytics ?>');
		ga('send', 'pageview');

		$(document).ready(function () {
		  $('#generate').click(function (e) {
			  ga('send', 'event', 'button', 'click', 'generate');
		  });
		});

	  </script>
	<? endif ?>

    <div class="container-narrow">

      <div class="masthead">
        <ul class="nav nav-pills pull-right">
          <li><a href="#about">About</a></li>
          <li><a href="https://www.npmjs.org/package/ticons" target="_blank">CLI & Module</a></li>
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
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://placehold.it/100x100&text=%201024x1024%20" /></div>
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
				<div class="span3"><h4>Transparent icon</h4></div>
				<div class="span4">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://placehold.it/100x100&text=%20%20512x512%20%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="icon-transparent" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span5">For Android, Mobile Web, BlackBerry and Tizen you can select an alternative 512x512 PNG icon. These platforms do not apply any default effects and promote using transparency to create <a href="http://developer.android.com/guide/practices/ui_guidelines/icon_design_launcher.html" target="_blank">unique shapes</a>.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Splash</h4></div>
				<div class="span4">
					<div class="fileupload fileupload-new" data-provides="fileupload">
					  <div class="fileupload-new thumbnail" style="width: 100px; height: 100px;"><img src="http://placehold.it/100x100&text=%202048x2048%20" /></div>
					  <div class="fileupload-preview fileupload-exists thumbnail" style="width: 100px; height: 100px;"></div>
					  <div>
						<span class="btn btn-file"><span class="fileupload-new">Select</span><span class="fileupload-exists">Replace</span><input type="file" name="splash" /></span>
						<a href="#" class="btn fileupload-exists" data-dismiss="fileupload">Remove</a>
					  </div>
					</div>
				</div>
				<div class="span5">Splash screens come in all sorts of sizes and because they're not squarish, we need to crop. For this to work, you should select a 2208x2208 PNG where the logo or other important artwork is placed within the center 1000x1000 pixels or so.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Border radius</h4></div>
				<div class="span4">
					<input type="text" name="radius" class="input-mini" placeholder="e.g.: '10'" />
				</div>
				<div class="span5">Specify a percentage between 0 and 50 for a border radius to apply to the default icon for Android.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Language</h4></div>
				<div class="span4">
					<input type="text" name="language" class="input-mini" placeholder="e.g.: 'nl'" />
				</div>
				<div class="span5">Specify a <a href="http://en.wikipedia.org/wiki/ISO_639-1" target="_blank">ISO 639-1</a> language code to write iOS and Android splash screens to <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-LocalizedSplashScreens" target="_blank">localized paths</a>. You would need to run TiCons for every required language and then merge the resulting asset folders.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Compression</h4></div>
				<div class="span4">
					<select name="compression">
						<option value="">(none)</option>
						<option value="low">Low</option>
						<option selected="selected" value="medium">Medium</option>
						<option value="high">High</option>
					</select>
				</div>
				<div class="span5">Applies an increasing level for <a href="http://optipng.sourceforge.net/">OptiPNG</a> compression resulting in 10% to 30% reduction on all PNG's and set a compression quality percentage ranging from 80% to 50% on JPEG's.</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Platforms</h4></div>
				<div class="span9">
					<label class="checkbox inline" for="iphone">
					  <input type="checkbox" name="platforms[]" value="iphone" checked="checked" id="iphone"> iPhone
					</label>
					<label class="checkbox inline" for="ipad">
					  <input type="checkbox" name="platforms[]" value="ipad" checked="checked" id="ipad"> iPad
					</label>
					<label class="checkbox inline" for="android">
					  <input type="checkbox" name="platforms[]" value="android" checked="checked" id="android"> Android
					</label>
					<label class="checkbox inline" for="mobileweb">
					  <input type="checkbox" name="platforms[]" value="mobileweb" id="mobileweb"> Mobile Web
					</label>
					<label class="checkbox inline" for="blackberry">
					  <input type="checkbox" name="platforms[]" value="blackberry" id="blackberry"> BlackBerry
					</label>
					<label class="checkbox inline" for="tizen">
					  <input type="checkbox" name="platforms[]" value="tizen" id="tizen"> Tizen
					</label>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Orientations</h4></div>
				<div class="span9">
					<label class="checkbox inline" for="portrait">
					  <input type="checkbox" name="orientations[]" value="portrait" checked="checked" id="portrait"> Portrait
					</label>
					<label class="checkbox inline" for="landscape">
					  <input type="checkbox" name="orientations[]" value="landscape" checked="checked" id="landscape"> Landscape
					</label>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Fix</h4></div>
				<div class="span9">
					<label class="checkbox" for="apple">
					  <input type="checkbox" name="apple" value="1" checked="checked" id="apple"> Conforms to <a href="https://developer.apple.com/library/ios/documentation/UserExperience/Conceptual/MobileHIG/IconMatrix.html#//apple_ref/doc/uid/TP40006556-CH27-SW1" target="_blank">Apple's specs for launch images</a> rather than Appcelerator's. This fixes the splash-shift caused by differences in iPad and iPhone 4 portrait dimensions.
					</label>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span3"><h4>Alloy</h4></div>
				<div class="span9">
					<label class="checkbox" for="alloy">
					  <input type="checkbox" name="alloy" value="1" checked="checked" id="alloy"> Writes to <code>app/assets</code> instead of <code>Resources</code>.
					</label>
				</div>
			</div>
			<div class="row-fluid">
				 <div class="offset3 span4">
				 	<input type="submit" class="btn btn-large btn-success" value="Generate" id="generate" />
				 </div>
			</div>
        </form>
      </div>

  	<hr />
      
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<!-- TiCons -->
	<ins class="adsbygoogle"
     		style="display:inline-block;width:728px;height:90px"
     		data-ad-client="ca-pub-2966238263022924"
     		data-ad-slot="1890839799"></ins>
	<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>

	<hr />

	  <a name="about"></a>
      <div class="row-fluid about">

        <div class="span6">

        	<h4>iPhone 6(+)</h4>
        	<p>TiCons has support for iPhone 6(+) <a href="https://developer.apple.com/library/prerelease/ios/documentation/UserExperience/Conceptual/MobileHIG/IconMatrix.html#//apple_ref/doc/uid/TP40006556-CH27-SW1" target="_blank">icons</a> and <a href="https://developer.apple.com/library/prerelease/ios/documentation/UserExperience/Conceptual/MobileHIG/LaunchImages.html#//apple_ref/doc/uid/TP40006556-CH22-SW1" target="_blank">splashes</a>.</p>

        	<h4>App stores</h4>
        	<p>TiCons also generates iTunes Connect and Google Play assets for you.</p>

          <h4>BlackBerry & Tizen</h4>
          <p>TiCons will generate a Tizen icon and both icon and splash for BlackBerry 10.</p>

					<h4>Filters</h4>
					<p>TiCons does not apply any filters. iOS automatically adds rounded corners and a drop shadow. By default, it also adds a reflective shine. You can disable this in your <code><a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-Pre-renderediconsoniOS" target="_blank">tiapp.xml</a></code>.</p>

        </div>

        <div class="span6">

					<h4>Android nine-patch splash</h4>
					<p>To better support the many differend Android display sizes and densities, you could <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-Splashscreens" target="_blank">use a nine-patch image for your splash screen</a>. This is not supported by TiCons right now.</p>

          <h4>Mobile Web</h4>
          <p>There is partial support for <a href="http://docs.appcelerator.com/titanium/latest/#!/guide/Icons_and_Splash_Screens-section-29004897_IconsandSplashScreens-MobileWebgraphicassetrequirementsandoptions" target="_blank">Mobile Web</a>. HTML splash screens are currently not generated.</p>

          <h4>CLI & Module</h4>
          <p>A CLI & CommonJS module is under development and <a href="https://www.npmjs.org/package/ticons" target="_blank">available through NPM</a>.</p>

          <h4>Contribute</h4>
          <p>Feel free to contact me at <a href="mailto:mail@fokkezb.nl">mail@fokkezb.nl</a> or <a href="https://github.com/FokkeZB/TiCons" target="_blank">fork</a> the code and send a pull request.<p>

        </div>
      </div>

      <hr>

      <div class="footer">
        <p>&copy; <a href="http://www.fokkezb.nl" target="_blank">Fokke Zandbergen</a> 2013 - <a href="https://github.com/FokkeZB/TiCons">License</a></p>
      </div>

    </div>

  </body>
</html>
<!-- 201402071956 -->
