#!/usr/bin/env php
<?php
  # Simple CLI written by a non-PHP practicioner - please improve!
  # * include path management
  # * use auto-load?
  # * let caller pass username / password via ENV (Heroku) or config file

  set_include_path(get_include_path() . PATH_SEPARATOR . './Domain');
  set_include_path(get_include_path() . PATH_SEPARATOR . './Domain/Repository');
  set_include_path(get_include_path() . PATH_SEPARATOR . './Domain/Model');

  include 'Client.php';
  include 'PollRepository.php';
  include 'Poll.php';

  $doodleUsername = YOUR_USER_NAME;
  $doodlePassword = YOUR_PASSWORD;

  $client = new \Causal\DoodleClient\Client($doodleUsername, $doodlePassword);
  $client->connect();

  $newPoll = $client->createPoll([
    'type' => 'date',
    'title' => 'Test Poll',
    'name' => 'John Doe',
    'email' => $doodleUsername,
    'dates' => [
      '20160126' => ['2000']
    ]
  ]);

  # will show participation link - or raise an error
  echo $newPoll->getPublicUrl();
?>
