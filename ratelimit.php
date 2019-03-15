<?php
/*
"leaky bucket" throttling method: keeps track of the last request ($last_api_request) 
and a ratio of the number of requests/limit for the time frame ($minute_throttle). 
The leaky bucket never resets its counter (unlike the Twitter API's throttle which resets every hour),
but if the bucket becomes full (user reached the limit), they must wait n seconds for the bucket to empty a little
before they can make another request. In other words it's like a rolling limit: 
if there are previous requests within the time frame, they are slowly leaking out of the bucket; 
it only restricts you if you fill the bucket.

This code snippet will calculate a new $minute_throttle value on every request. 
I specified the minute in $minute_throttle because you can add throttles for any time period, 
such as hourly, daily, etc... although more than one will quickly start to make it confusing for the users*/

$minute = 60;
$minute_limit = 100; # users are limited to 100 requests/minute
$last_api_request = $this->get_last_api_request(); # get from the workfile timestamp; in epoch seconds
$last_api_diff = time() - $last_api_request; # in seconds
$minute_throttle = $this->get_throttle_minute(); # get from the DB
if ( is_null( $minute_limit ) ) {
    $new_minute_throttle = 0;
} else {
    $new_minute_throttle = $minute_throttle - $last_api_diff;
    $new_minute_throttle = $new_minute_throttle < 0 ? 0 : $new_minute_throttle;
    $new_minute_throttle += $minute / $minute_limit;
    $minute_hits_remaining = floor( ( $minute - $new_minute_throttle ) * $minute_limit / $minute  );
    # can output this value with the request if desired:
    $minute_hits_remaining = $minute_hits_remaining >= 0 ? $minute_hits_remaining : 0;
}

if ( $new_minute_throttle > $minute ) {
    $wait = ceil( $new_minute_throttle - $minute );
    usleep( 250000 );
    throw new My_Exception ( 'The one-minute API limit of ' . $minute_limit 
        . ' requests has been exceeded. Please wait ' . $wait . ' seconds before attempting again.' );
}
# Save the values back to the database.
#$this->save_last_api_request( time() );
#$this->save_throttle_minute( $new_minute_throttle );

/*
function ratelimiter($rate = 5, $per = 8) {
  $last_check = microtime(True);
  $allowance = $rate;

  return function ($consumed = 1) use (
    &$last_check,
    &$allowance,
    $rate,
    $per
  ) {
    $current = microtime(True);
    $time_passed = $current - $last_check;
    $last_check = $current;

    $allowance += $time_passed * ($rate / $per);
    if ($allowance > $rate)
      $allowance = $rate;

    if ($allowance < $consumed) {
      $duration = ($consumed - $allowance) * ($per / $rate);
      $last_check += $duration;
      usleep($duration * 1000000);
      $allowance = 0;
    }
    else
      $allowance -= $consumed;

    return;
  };
}