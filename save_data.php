<?php
// $_POST contains the info passed to the script.
$filename = "./data/".$_POST['filename'];
$data = $_POST['filedata'];

// write to disk
file_put_contents($filename, $data);
?>
