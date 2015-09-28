=============
Doodle Client
=============

This library provides a missing feature of Doodle (http://doodle.com): an API to programmatically access polls on the
Doodle platform.

I was surprised to find only a basic API to *create* polls but not anything else to fetch the list of polls or related
participant answers so I contacted them and got this answer on September 25th, 2015:

> Unfortunately, Doodle won't be offering an API any longer.
>
> -- <cite>Katharina</cite>

As such I wrote this PHP client.


Example of use
==============

```
$doodleUsername = 'me@example.com';
$doodlePassword = 'my-very-secret-password';

$client = new \Causal\DoodleClient\Client($doodleUsername, $doodlePassword);
$client->connect();

$myPolls = $client->getPersonalPolls();

echo '<h1>My polls</h1>';
echo '<ul>';
foreach ($myPolls as $poll) {
    echo '<li>';
    echo '<a href="' . htmlspecialchars($poll->getPublicUrl()) . '">' . htmlspecialchars($poll->getTitle()) . '</a>';
    echo '<blockquote>' . nl2br($poll->getDescription()) . '</blockquote>';
    echo '</li>';
}
echo '</ul>';

// Optional, if you want to prevent actually authenticating over and over again
// with future requests (thus reusing the local authentication cookies)
$client->disconnect();
```
