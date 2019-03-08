<?
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