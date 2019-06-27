<?php

$url = 'https://site-url.com/';

$files = '
upload/iblock/2cd/3.jpg
upload/iblock/957/04.jpg
';

function get_file($url)
{
	$context = stream_context_create(array("http" => array("header" => "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36")));
	@$contents = file_get_contents($url, false, $context);
	if (!$contents)
		echo_log('File "'.$url.'" not found');
	return $contents;
}

function echo_log($text)
{
	if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'])
	{
		echo $text.'<br>';
	}
	else
	{
		echo $text."\n\n";
	}
}

$counter = 0;
$files = explode("\n", $files);
foreach($files as $file)
{
  $file = trim($file);
  if(!$file)
    continue;
  $path = explode("/", $file);
  $name = array_pop($path);
  $path = join("/", $path);

  echo_log("Start getting: ".$file);
  $content = get_file($url.$file);
  if(!is_dir($path))
    mkdir($path, 0777, true);
  if(file_put_contents($file, $content))
    $counter++;
}

echo_log("Done! ".$counter." file(s) successfully downloaded");
