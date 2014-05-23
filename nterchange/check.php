<!DOCTYPE html>
<?php
include $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';
$title = 'nterchange status';
?>

<html>
<head>
  <title><?= $title ?></title>
  <style type="text/css">
    body { max-width: 600px; margin: 0 auto; font-family: sans-serif; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #DDD; padding: 0.25em; }
  </style>
</head>
<body>
  <h1><?= $title ?></h1>
  <table>
    <tr>
      <?php
        $db = NDB::connect();
      ?>
      <th colspan="2">Database</th>
    </tr>
    <tr>
      <?php
        $m = NModel::factory('cms_auth');
        $user_count = $m->find();
      ?>
      <td># of users</td><td><?= $user_count ?></td>
    </tr>
  </table>
</body>
</html>
