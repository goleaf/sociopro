$str = file_get_contents('upload/update_2.3.1/composer.json');

$myfile = fopen("composer.json", "w") or die("Unable to open file!");
fwrite($myfile, $str);
fclose($myfile);


$strs = file_get_contents('upload/update_2.3.1/composer.lock');

$myfiles = fopen("composer.lock", "w") or die("Unable to open file!");
fwrite($myfiles, $strs);
fclose($myfiles);