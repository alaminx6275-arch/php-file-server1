<?php
$url = "RAW_LINK_HERE";
file_put_contents("script.php", file_get_contents($url));
echo "Downloaded!";
?>
