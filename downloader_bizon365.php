<?
for ($i = 0; $i < 114; $i++) {
	$file = file_get_contents("https://static.bizon365.ru/userfiles/80647/presentation/kak_vyiti_na_novyi_uroven_3D.pdf/$i.png");
	$fp = fopen("img/$i.png", "w+" );
	fwrite($fp, $file);
	fclose($fp);
}
?>
