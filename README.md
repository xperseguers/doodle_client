=============
Doodle Client
=============

This library provides a missing feature of Doodle (http://doodle.com): an API to programmatically access and create
polls on the Doodle platform.

I was surprised to find only a basic API to *initiate* polls (not actually creating them, just pre-filling the form) but
not anything else to fetch the list of polls or related participant answers so I contacted them and got this answer on
September 25th, 2015:

> Unfortunately, Doodle won't be offering an API any longer.
>
> -- <cite>Katharina</cite>

As such I wrote this PHP client.


Basic Usage
===========

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
    echo 'Export answers as: ' .
        '<a href="' . htmlspecialchars($poll->getExportExcelUrl()) . '">Excel</a> | ' .
        '<a href="' . htmlspecialchars($poll->getExportPdfUrl()) . '">PDF</a>';
    echo '</li>';
}
echo '</ul>';

// Optional, if you want to prevent actually authenticating over and over again
// with future requests (thus reusing the local authentication cookies)
$client->disconnect();
```

Create a Poll (Text Options)
============================

```
$newPoll = $client->createPoll([
    'type' => 'text',
    'title' => 'Dinner',
    'location' => 'Restaurant Xtra',
    'description' => 'I suggest we meet and have a nice time together',
    'name' => 'John Doo',
    'email' => 'john.doo@example.com',
    'options' => [
        'Lasagna',
        'Pizza',
        'Meat',
    ],
]);
echo 'link to new poll: ' . $newPoll->getPublicUrl();
```


Create a Poll (Dates)
=====================

```
$newPoll = $client->createPoll([
    'type' => 'date',
    'title' => 'Dinner',
    'location' => 'Restaurant Xtra',
    'description' => 'I suggest we meet and have a nice time together',
    'name' => 'John Doo',
    'email' => 'john.doo@example.com',
    'dates' => [
        '20150929' => ['1930', '2000'],
        '20150930' => ['2000'],
        '20151030' => ['1945', '2000'],
    ],
]);
echo 'link to new poll: ' . $newPoll->getPublicUrl();
```


Delete a Poll
=============

```
// Selection of a given poll could be based on any "$poll" from the
// foreach loop in "Basic Usage" example.

$client->deletePoll($poll);
```


Table of Answers
================

Another example of use, would be to fetch answers for a given poll.

```
// Selection of a given poll could be based on any "$poll" from the
// foreach loop in "Basic Usage" example.

echo '<table>';

echo '<thead>';
echo '<tr>';
echo '<th></th>';
$options = $poll->getOptions();
foreach ($options as $option) {
    echo '<th>' . htmlspecialchars($option) . '</th>';
}
echo '</tr>';
echo '</thead>';

echo '<tbody>';
$participants = $poll->getParticipants();
foreach ($participants as $participant) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($participant->getName()) . '</td>';
    foreach ($participant->getPreferences() as $preference) {
        switch ($preference) {
            case 'i':
                $value = 'If needed';
                $color = '#ffeda1';
                break;
            case 'y':
                $value = 'YES';
                $color = '#d1f3d1';
                break;
            case 'n':
                $value = 'NO';
                $color = '#ffccca';
                break;
            case 'q':
            default:
                $value = '?';
                $color = '#eaeaea';
                break;
        }
        echo '<td style="background-color:' . $color . '">' . htmlspecialchars($value) . '</td>';
    }
    echo '</tr>';
}
echo '</tbody>';

echo '</table>';
```