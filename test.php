<?php 
var_dump($_POST);
echo '<br>';
var_dump(isset($_POST));
echo '<br>';
var_dump(isset($_POST['name']));
$_POST[] = null;
echo '<br>';
var_dump($_POST);
var_dump(isset($_POST));

 ?>