<?php
/* Configurations */
$url = 'https://santehnika-online.ru/';

$allow_extentions = array('css', 'js', 'jpg', 'png', 'gif', 'svg', 'ttf', 'woff', 'woff2', 'eot');
set_time_limit(180); // 3 minutes

// web: run.php?page-address, cli: php run.php?page-address
if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] && $_SERVER['QUERY_STRING'])
	$url = $_SERVER['QUERY_STRING'];
elseif((!isset($_SERVER['HTTP_USER_AGENT']) || !$_SERVER['HTTP_USER_AGENT']) && isset($argv[1]))
	$url = $argv[1];

$urlparse = parse_url($url);
$site = get_file($url);
$htmlfile = ext($url, '/');

if (!$htmlfile)
	$htmlfile = ext(substr($url, 0, -1), '/');

if ($htmlfile == $urlparse['host'])
	$htmlfile = 'index.html';

if (!strpos($htmlfile, '.'))
	$htmlfile = $htmlfile.'.html';


/*
Maybe I was wrong and it better way to naming file ;)
if (!strpos($htmlfile, '.'))
	$htmlfile = 'index.html';
*/


$site = remove_stats($site);
file_put_contents($htmlfile, $site);
echo_log('The page "'. $htmlfile.'" was created');

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

function update_file($filename, $find, $replace)
{
	$content = get_file($filename);
	$content = str_replace($find, $replace, $content);
	file_put_contents($filename, $content);
}


function create_folders($folders)
{
	$path = '';
	foreach ($folders as $folder)
	{
		$path .= $folder.'/';
		if(!is_dir($path))
			if(!mkdir($path, 0777))
				echo_log('Can`t create the folder "'.$path.'"');
	}
}

function create_folder_path($file_path)
{
	$folders = explode('/', $file_path);
	unset($folders[count($folders)-1]);
	$dir = join('/',$folders);
	if(!is_dir($dir))
	{
		create_folders($folders);
		echo_log('The folder "'. $dir.'" was created');
	}
}

function ext($filename, $delimter = '.')
{
	// if (strpos($filename, $delimter) === false)
		// return null;
	$parts = explode($delimter, $filename);
	return array_pop($parts);
}


function search_files_in_css($matches)
{
	global $url, $entity;
	$file_url = $matches[1];


	if (substr($file_url, 0, 8) != 'https://' && substr($file_url, 0, 7) != 'http://')
	{
		if (substr($file_url, 0, 1) == '/')
		{
			$filepath = substr($file_url, 1);
			if (strpos($filepath, '#'))
				$filepath = substr($filepath, 0, strrpos($filepath,'#'));
			if (strpos($filepath, '?'))
				$filepath = substr($filepath, 0, strrpos($filepath,'?'));

			// Changes in css file
			$css_file = $entity;
			if (substr($css_file, 0, 1) == '/')
			{
				$css_file = substr($css_file, 1);
			}

			$root_way = substr($css_file, 0, strrpos($css_file,'/'));
			$dots_way = '';
			while(strpos($file_url, $root_way) === false)
			{
				$root_way = substr($root_way, 0, strrpos($root_way,'/'));
				$dots_way .= '../';
				if (!$root_way)
					break;
			}
			$css_path = str_replace($root_way, $dots_way, $filepath);

			$css_text = file_get_contents($css_file);
			$css_text = str_replace($file_url, $css_path, $css_text);

			file_put_contents($css_file, $css_text);
			// !Changes in css file


			$urlparse = parse_url($url);
			$filelink = $urlparse['scheme'] . '://' . $urlparse['host'] . $file_url;
		}
		else
		{
			$current_folder = substr($entity, 0, strrpos($entity,'/') + 1);

			while(substr($file_url, 0, 3) == '../')
			{

				$file_url = substr($file_url, 3);
				if (substr($current_folder, -1) == '/')
					$current_folder = substr($current_folder, 0, -1);

				$current_folder = substr($current_folder, 0, strrpos($current_folder,'/'));
			}


			$filepath = $current_folder . '/' . $file_url;
			if (strpos($filepath, '#'))
				$filepath = substr($filepath, 0, strrpos($filepath,'#'));
			if (strpos($filepath, '?'))
				$filepath = substr($filepath, 0, strrpos($filepath,'?'));
			if (substr($filepath, 0, 1) == '/')
				$filepath = substr($filepath, 1);

			if (substr($entity, 0, 1) == '/')
			{
				$urlparse = parse_url($url);
				$filelink = $urlparse['scheme'] . '://' . $urlparse['host'] . '/' . $filepath;
			}
			else
			{
				$filelink = substr($url, 0, strrpos($url,'/') + 1) . $filepath;
			}
		}

		echo_log('[from] '.$filelink);
		echo_log('	[to] '.$filepath);

		create_folder_path($filepath);
		$filelink = get_file($filelink);
		file_put_contents($filepath, $filelink);

	}
}


function search_files($matches)
{
	global $allow_extentions, $url, $entity, $htmlfile;

	$entity = $matches[2];
	$extention = ext($entity);

	if (in_array($extention, $allow_extentions))
	{
		if (substr($entity, 0, 8) == 'https://' || substr($entity, 0, 7) == 'http://')
		{
			$filename = ext($entity, '/');
			$filepart = explode('.', $filename);
			$filepath = 'vendor/'.array_shift($filepart).'/'.$filename;
			$filelink = $entity;
		}
		else if(substr($entity, 0, 2) == '//')
		{
			$entity = 'http:'.$entity;
			$filename = ext($entity, '/');
			$filepart = explode('.', $filename);
			$filepath = 'vendor/'.array_shift($filepart).'/'.$filename;
			$filelink = $entity;
		}
		else if(substr($entity, 0, 1) == '/')
		{
			$filepath = substr($entity, 1);
			$urlparse = parse_url($url);
			$filelink = $urlparse['scheme'] . '://' . $urlparse['host'] . $entity;

			update_file($htmlfile, $entity, $filepath);
		}
		else
		{
			if (substr($entity, 0, 2) == './')
			{
				$entity = substr($entity, 2);
			}

			$filepath = $entity;
			$filelink = substr($url, 0, strrpos($url,'/') + 1) . $entity;
		}

		echo_log('[from] '.$filelink);
		echo_log('	[to] '.$filepath);

		create_folder_path($filepath);
		$file = get_file($filelink);
		file_put_contents($filepath, $file);

		if (ext($entity) == 'css')
		{
			preg_replace_callback('/url\(["\']?([\/\w\:\.-]+\??\#?[\&\w=\.\-]+)["\']?\)/im', 'search_files_in_css', $file);
		}

	}
}

function remove_stats($site)
{
	// Yandex.Metrika
	$stats_begin = '<!-- Yandex.Metrika counter -->';
	$stats_end = '<!-- /Yandex.Metrika counter -->';
	$stats_begin_pos = strpos($site, $stats_begin);
	$stats_end_pos = strpos($site, $stats_end);
	$stats_len = $stats_end_pos - $stats_begin_pos;
	$site = substr_replace($site, '', $stats_begin_pos, $stats_len);
	return $site;
}

preg_replace_callback('/(href|src)=["\']?([\/\w\:\.-]+)["\']?/im', 'search_files', $site);

?>
