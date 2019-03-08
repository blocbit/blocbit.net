<?php
include_once(dirname(__DIR__).'/assets/vendor/CBOR/CBOREncoder.php');
include_once(dirname(__DIR__).'/assets/vendor/CBOR/Types/CBORByteString.php');
include_once(dirname(__DIR__).'/assets/vendor/CBOR/CBORExceptions.php');
include_once(dirname(__DIR__).'/assets/vendor/webauthn/webauthn.php');

define('USER_DATABASE', dirname(__DIR__).'/.bbu/');
if (! file_exists(USER_DATABASE)) {
  if (! @mkdir(USER_DATABASE)) {
    error_log('Cannot create user database directory - is the html directory writable by the web server? If not: "mkdir .users; chmod 777 .users"');
    die("cannot create .users - see error log");
  } 
}
session_start();

function oops($s){
  http_response_code(400);
  echo "{$s}\n";
  exit;
}

function userpath($username){
  $username = str_replace('.', '%2E', $username);
  return sprintf('%s/%s.json', USER_DATABASE, urlencode($username));
}

function getuser($username){
  $user = @file_get_contents(userpath($username));
  if (empty($user)) { oops('user not found'); }
  $user = json_decode($user);
  if (empty($user)) { oops('user not json decoded'); }
  return $user;
}

/* A post is an ajax request, otherwise display the page */
if (! empty($_POST)) {

  try {
  
    $webauthn = new davidearl\webauthn\WebAuthn($_SERVER['HTTP_HOST']);

    switch(TRUE){

    case isset($_POST['registerusername']):
      /* initiate the registration */
      $username = $_POST['registerusername'];
	  $pin = $_POST['registerpin'];
      $userid = md5(time() . '-'. rand(1,1000000000));
	  $db = userpath($username);

      if (file_exists($db)) {	
		$string = file_get_contents($db);
		$json_a = json_decode($string, true);
			if 
			(empty($json_a['webauthnkeys']))
			{
				unlink($db);	
			}
			else{	
			oops("Bit '{$username}' already exists");
			}
		}
		/* Hash password test*/
		$options = ['cost' => 12,];
		$hash = password_hash($pin, PASSWORD_BCRYPT, $options);
		
		
		
		/* Verify Hash */
		if (!password_verify('12345', $hash)) {
			oops ("Invalid password. Register a Bit ID at https://localhost/bloc.bit");
		}

      /* Create a new user in the database. In principle, you can store more 
         than one key in the user's webauthnkeys,
         but you'd probably do that from a user profile page rather than initial 
         registration. The procedure is the same, just don't cancel existing 
         keys like this.*/
      file_put_contents(userpath($username), json_encode(['name'=> $username,
                                                          'id'=> $userid,
                                                          'webauthnkeys' => $webauthn->cancel()]));
      $_SESSION['username'] = $username;
      $j = ['challenge' => $webauthn->prepare_challenge_for_registration($username, $userid)];
      break;

    case isset($_POST['register']):
      /* complete the registration */
      if (empty($_SESSION['username'])) { oops('username not set'); }
      $user = getuser($_SESSION['username']);

      /* The heart of the matter */
      $user->webauthnkeys = $webauthn->register($_POST['register'], $user->webauthnkeys);

      /* Save the result to enable a challenge to be raised agains this 
         newly created key in order to log in */
      file_put_contents(userpath($user->name), json_encode($user));
      $j = 'ok';
      
      break;

    default:
      http_response_code(400);
      echo "unrecognized POST\n";
      break;
    }    

  } catch(Exception $ex) {
    oops($ex->getMessage());
  }
    
  header('Content-type: application/json');
  echo json_encode($j);
  exit;
}
   
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Bloc.bit - launch</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" >
    <!-- Fonts -->
    <link rel="stylesheet" type="text/css" href="assets/LineIcons.min.css">
    <!-- Slicknav -->
    <link rel="stylesheet" type="text/css" href="assets/css/slicknav.css">
    <!-- Off Canvas Menu -->
    <link rel="stylesheet" type="text/css" href="assets/css/menu_sideslide.css">
    <!-- Color Switcher -->
    <link rel="stylesheet" type="text/css" href="assets/css/vegas.min.css">
    <!-- Animate -->
    <link rel="stylesheet" type="text/css" href="assets/css/animate.css">
    <!-- Main Style -->
    <link rel="stylesheet" type="text/css" href="assets/css/main.css">
    <!-- Responsive Style -->
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
	  
</head>
<body>

      <!-- Header Area wrapper Starts -->
    <header id="header-wrap">
      <div class="menu-wrap">
        <div class="menu navbar">
          <div class="menu-list navbar-collapse">
            <ul class="onepage-nev navbar-nav">
              <li class="nav-item active">
                <a class="nav-link" href="#header-wrap">Home</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#services">Bloc What?</a>
              </li>  
			  <li class="nav-item">
                <a class="nav-link" href="#team">Connect</a>
              </li>			  
              <li class="nav-item">
                <a class="nav-link" href="#keychain" target="_blank">Keychain</a>
              </li>   
			  <li class="nav-item">
                <a class="nav-link" href="./blocchain" target="_blank">Bloc Explorer(alpha)</a>
              </li>
            </ul>
          </div>
        </div> 
        <button class="close-button" id="close-button"><i class="lni-close"></i></button>
      </div>  
      <!-- Navbar Start -->
      <nav class="navbar navbar-expand-lg fixed-top scrolling-navbar menu-bg">
        <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <div class="navbar-header">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-navbar" aria-controls="main-navbar" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
              <span class="lni-menu"></span>
              <span class="lni-menu"></span>
              <span class="lni-menu"></span>
            </button>
            <a href="index.html" class="navbar-brand"><img src="assets/img/logo.png" alt=""></a>
          </div>
          <div class="collapse navbar-collapse" id="main-navbar">
            <ul class="navbar-nav mr-auto w-100 justify-content-end clearfix">
              <li class="nav-item" id="open-button">
                <a class="nav-link">
                  <i class="lni-menu"></i>
                </a>
              </li>
            </ul>
          </div>
        </div>

        <!-- Mobile Menu Start -->
        <ul class="mobile-menu">
          <li>
            <a href="#header-wrap">Home</a>
          </li>
          <li>
            <a href="#services">Bloc what?</a>
          </li>
		  		  <li>
            <a href="#team">Connect</a>
          </li>
          <li>
            <a href="#keychain" target="_blank">Keychain</a>
          </li>
		   <li>
            <a href="./blocchain" target="_blank">Bloc Explorer (Alpha)</a>
          </li>
        </ul>
        <!-- Mobile Menu End -->
      </nav>
      <!-- Navbar End -->
    </header>
    
    <!-- Intro Section Start -->
    <section class="intro">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="heading-count">
              <h2>Mainnet</h2>
              <p>Launches in ...</p>
            </div>
          </div>
          <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="row countdown justify-content-center">
              <div id="clock" class="time-count"></div>
            </div>
            <a href="#keychain" target="_blank" class="btn btn-common">Keychain</a>
            <a href="#services" class="btn btn-border">Bloc What?</a>
            <div class="social mt-4">
              <a class="telegram" href="https://t.me/blocbit" target="_blank"><i class="lni-telegram"></i></a>
              <a class="instagram" href="https://github.com/blocbit target="_blank"" target="_blank"><i class="lni-github-original"></i></a>
			  <a class="twitter" href="https://twitter.com/blocbit" target="_blank"><i class="lni-twitter"></i></a>
              <a class="youtube" href="https://www.youtube.com/channel/UCHmSb5KTzrLzzMjP8w6GKRg" target="_blank"><i class="lni-youtube"></i></a>
            </div>
            <a href="#services" class="target-scroll page-scroll"><i class="lni-chevron-down"></i></a>
          </div>
        </div>
      </div>
    </section>
    <!-- Intro Section End -->

    <section id="services" class="section-padding" >
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-8 col-xs-12 text-center">
            <h6 class="subtitle" style="padding-top:10%;">
              Whats a bloc.bit
            </h6>
            <h2 class="section-title">
              <a href="#" class="video-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/dfsRQyYXOsQ" data-target="#modWhat">Bitcoin Concensus</a>
            </h2>
            <div class="section-info">
              Poor bitcoin has been forked to many times. We're here to try and make an honest blockchain of her ..
            </div>
          </div>            
        </div>
        
        <div class="row">
          <div class="col-lg-4 col-md-6 col-xs-12 fadeInUp" data-animation="fadeInUp">
            <div class="services-item">
              <div class="icon">
                <i class="lni-mobile"></i>
              </div>
              <h3><a href="#" class="video-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Jfrjeg26Cwk" data-target="#modWhat">Anonymous</a></h3>
              <p>Nobody knows who you are unless you tell them. You are free to make that choice</p>
            </div>
          </div> 
          <div class="col-lg-4 col-md-6 col-xs-12 fadeInUp" data-animation="fadeInUp" data-delay="300">
            <div class="services-item">
              <div class="icon">
                <i class="lni-star"></i>
              </div>
              <h3><a href="#" class="video-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Jfrjeg26Cwk" data-target="#modWhat">Incentive</a></h3>
              <p>get one today, use it and you get another tomorrow</p>
            </div>
          </div> 
          <div class="col-lg-4 col-md-6 col-xs-12 fadeInUp" data-animation="fadeInUp" data-delay="600">
            <div class="services-item">
              <div class="icon">
                <i class="lni-bullhorn"></i>
              </div>
              <h3><a href="#" class="video-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Jfrjeg26Cwk" data-target="#modWhat">Community</a></h3>
              <p>If you want to go quickly, go alone. If you want to go far, go together.</p>
            </div>
          </div>
         </div>
				
	<!-- Modal -->
<div class="modal fade" id="modWhat" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      
      <div class="modal-body">

       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>        
        <!-- 16:9 aspect ratio -->
<div class="embed-responsive embed-responsive-16by9">
  <iframe class="embed-responsive-item" src="" id="video"  allowscriptaccess="always" allow="autoplay"></iframe>
</div>
        
        
      </div> </div> </div> </div> 
  

		</div>
		 
		  <div class="row justify-content-center">
          <div class="col-md-8 col-lg-8 col-xs-12 text-center fadeInUp" data-animation="fadeInUp" data-delay="800">
		   <div class="services-item" style="padding-top:10%;">
		   <div class="icon">
                <i class="lni-github"></i>
              </div>
              <h3><a href="https://github.com/blocbit" target="_blank">Technicals</a></h3>
			  <p>Show me the nerdy stuff, I love it</p>
            </div>
          </div>            
        </div>
		 
      </div>
    </section>
    <!-- Team Section Start -->
    <section id="team" class="section-padding">
	<div class="tri-down"></div>
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-8 col-xs-12 text-center">
            <h6 class="subtitle">
              Meet 
            </h6>
            <h2 class="section-title">
              Team Bloc.bit
            </h2>
            <div class="section-info">
              The peps listed below have no control over the outcome of the vote, blocchain is immutable and once a vote is hashed it cannot be changed. We work night and day to keep it this way, Fair and out of minority control.
            </div>
          </div>            
        </div>
        <div class="row justify-content-center">
          <div class="col-sm-6 col-md-6 col-lg-3">
            <!-- Team Item Starts -->
            <div class="team-item text-center">
              <div class="team-img">
                <img class="img-fluid" src="assets/img/team/bittwist.png" alt="">
                <div class="team-overlay">
                  <div class="overlay-social-icon text-center">
                    <ul class="social-icons">
					  <li><a href="https://github.com/blocbit" target="_blank"><i class="lni-github" aria-hidden="true"></i></a></li>
                      <li><a href="https://twitter.com/twistsmyth" target="_blank"><i class="lni-twitter-filled" aria-hidden="true"></i></a></li>
                      <li><a href="https://www.youtube.com/channel/UCHmSb5KTzrLzzMjP8w6GKRg" target="_blank"><i class="lni-youtube" aria-hidden="true"></i></a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="info-text">
                <h3><a href="#">Twistsmyth</a></h3>
                <p>Wizard</p>
              </div>
            </div>
            <!-- Team Item Ends -->
          </div>
          <div class="col-sm-6 col-md-6 col-lg-3">
            <!-- Team Item Starts -->
            <div class="team-item text-center">
              <div class="team-img">
                <img class="img-fluid" src="assets/img/team/bitcandi.png" alt="">
                <div class="team-overlay">
                  <div class="overlay-social-icon text-center">
                    <ul class="social-icons">
                      <li><a href="https://instagram.com/bloc.bit" target="_blank"><i class="lni-instagram-filled" aria-hidden="true"></i></a></li>
                      <li><a href="https://twitter.com/consensuscandi" target="_blank"><i class="lni-twitter-filled" aria-hidden="true"></i></a></li>
					  <li><a href="https://www.youtube.com/channel/UCHmSb5KTzrLzzMjP8w6GKRg" target="_blank"><i class="lni-youtube" aria-hidden="true"></i></a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="info-text">
                <h3><a href="#">Candi</a></h3>
                <p>Star</p>
              </div>
            </div>
            <!-- Team Item Ends -->	
          </div>
          <div class="col-sm-6 col-md-6 col-lg-3">
            <!-- Team Item Starts -->
            <div class="team-item text-center">
              <div class="team-img">
                <img class="img-fluid" src="assets/img/team/bitghost.png" alt="">
                <div class="team-overlay">
                  <div class="overlay-social-icon text-center">
                    <ul class="social-icons">
                       <li><a href="https://www.youtube.com/channel/UCHmSb5KTzrLzzMjP8w6GKRg" target="_blank"><i class="lni-youtube" aria-hidden="true"></i></a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="info-text">
                <h3><a href="#">Ghost</a></h3>
                <p>Greyhat</p>
              </div>
            </div>
            <!-- Team Item Ends -->
          </div>
          </div>
        </div>
      </div>
    </section>
  
	<section id="keychain" class="section-padding" >
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-8 col-xs-12 text-center">
            <h6 class="subtitle">
              Keychain
            </h6>
            <h2 class="section-title">
             <a href="#" class="video-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Jfrjeg26Cwk" data-target="#modWhat">Registration</a>
            </h2>
            <div class="section-info">
              Key your crypto security
            </div>
          </div>   
        </div>

	<div class='cbox' id='iregister'>
	  <form id='iregisterform' action='/' method='POST'>
		<label style="min-width:50px;">Bit ID: </label><input type='text' name='registerusername'><br>
		<label style="min-width:50px;">Pin: </label><input type='password' name='registerpin'><br>
		<input type='submit' value='✓'>
	  </form>
	  <div class='cdokey' id='iregisterdokey'>
		Press button on key
	  </div>
	</div>
     </div>
    </section>
  </div>
 <div class='cerror'></div>
  <div class='cdone'></div>
    <!-- Footer Section Start -->
    <footer class="footer-area section-padding">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="footer-text text-center">
              <ul class="social-icon">
                <li>
                  <a class="telegram" href="https://t.me/blocbit" target="_blank"><i class="lni-telegram"></i></a>
                </li>
				  <li>
                  <a class="instagram" href="https://github.com/blocbit" target="_blank"><i class="lni-github-original"></i></a>
                </li>
                <li>
                  <a class="twitter" href="https://twitter.com/blocbit" target="_blank"><i class="lni-twitter"></i></a>
                </li>
                <li>
                  <a class="youtube" href="https://www.youtube.com/channel/UCHmSb5KTzrLzzMjP8w6GKRg" target="_blank"><i class="lni-youtube"></i></a>
                </li>
              </ul>
			  <p>Donate a Slave Free Coffee :</p>
			  <ul class="donate">
                <li>
                </li>
				  <li>
                 <p>btc: 3PQnXLqxVbcVGhFNqdzwwdwZecmnqTav45</p>
                </li>
                <li>
                 <p>ltc: MJjMXwPyWRwemzn69mxkSMUKfByKopUtbE</p>
                </li>
                <li>
                 <p>xmr: 43HecCXQqKKBTzQrkufjx8UHmdCmRHEAKLvfVijrpMiGFw9Lkbn8cRVSNZ3juT8AhfaoWr6LgydJiiJ7w3CGes5E9nKw4jm</p>
                </li>
              </ul>
             <p>First time VPS: <a href=" https://www.vultr.com/?ref=7666504" target="_blank">signup</a></p>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <!-- Footer Section End -->

    <!-- Go to Top Link -->
    <a href="#" class="back-to-top">
      <i class="lni-chevron-up"></i>
    </a>

    <!-- Preloader -->
    <div id="preloader">
      <div class="loader" id="loader-1"></div>
    </div>
    <!-- End Preloader -->

    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="assets/js/jquery-min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/vegas.min.js"></script>
    <script src="assets/js/jquery.countdown.min.js"></script>
    <script src="assets/js/menu.js"></script>
    <script src="assets/js/classie.js"></script>
    <script src="assets/js/scrolling-nav.js"></script>
    <script src="assets/js/jquery.nav.js"></script>
    <script src="assets/js/jquery.easing.min.js"></script>
    <script src="assets/js/wow.js"></script>
    <script src="assets/js/jquery.slicknav.js"></script>
    <script src="assets/js/main.js"></script>
	<script src="assets/js/webauthnauthenticate.js"></script>
	<script src="assets/js/webauthnregister.js"></script>
	<script>
	$(document).ready(function() {
	// Gets the video src from the data-src on each button
	var $videoSrc;  
	$('.video-btn').click(function() {
		$videoSrc = $(this).data( "src" );
	});
	// console.log($videoSrc); 
	// when the modal is opened autoplay it  
	$('#modWhat').on('shown.bs.modal', function (e) {	
	// set the video src to autoplay and not to show related video. Youtube related video is like a box of chocolates... you never know what you're gonna get
	$("#video").attr('src',$videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0" ); 
	})  
	// stop playing the youtube video when I close the modal
	$('#modWhat').on('hide.bs.modal', function (e) {
		// a poor man's stop video
		$("#video").attr('src',$videoSrc); 
	}) 	
	// document ready  
	});
	</script>
<script>
  $(function(){

	$('#iregisterform').submit(function(ev){
		var self = $(this);
		ev.preventDefault();
		$('.cerror').empty().hide();
		
		$.ajax({url: '/',
				method: 'POST',
				data: {registerusername: self.find('[name=registerusername]').val(), registerpin: self.find('[name=registerpin]').val()},
				dataType: 'json',
				success: function(j){
					$('#iregisterform,#iregisterdokey').toggle();
					/* activate the key and get the response */
					webauthnRegister(j.challenge, function(success, info){
						if (success) {
							$.ajax({url: '/',
									method: 'POST',
									data: {register: info},
									dataType: 'json',
									success: function(j){
										$('#iregisterform,#iregisterdokey').toggle();
										$('.cdone').text("registration completed successfully").show();
										setTimeout(function(){ $('.cdone').hide(300); }, 2000);
									},
									error: function(xhr, status, error){
										$('.cerror').text("registration failed: "+error+": "+xhr.responseText).show();
									}
								   });
						} else {
							$('.cerror').text(info).show();
						}
					});
				},

				error: function(xhr, status, error){
					$('#iregisterform').show();
					$('#iregisterdokey').hide();
					$('.cerror').text("couldn't initiate registration: "+error+": "+xhr.responseText).show();
				}
			   });
	});
});
</script>
    
</body>
</html>
