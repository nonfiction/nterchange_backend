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
      <td>ENVIRONMENT</td>
      <td><?= ENVIRONMENT ?></td>
    </tr>
    <tr>
      <td>DOCUMENT_ROOT</td>
      <td><?= DOCUMENT_ROOT ?></td>
    </tr>
    <tr><td>DEFAULT_PAGE_EXTENSION</td><td><?= DEFAULT_PAGE_EXTENSION ?></td></tr>
    <tr><td>GZIP_COMPRESSION</td><td><?= GZIP_COMPRESSION ?></td></tr>
    <tr><td>CONF_DIR</td><td><?= CONF_DIR ?></td></tr>
    <tr><td>ROOT_DIR</td><td><?= ROOT_DIR ?></td></tr>
    <tr><td>CACHE_DIR</td><td><?= CACHE_DIR ?></td></tr>
    <tr><td>APP_NAME</td><td><?= APP_NAME ?></td></tr>
    <tr><td>APP_DIR</td><td><?= APP_DIR ?></td></tr>
    <tr><td>BASE_DIR</td><td><?= BASE_DIR ?></td></tr>

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

