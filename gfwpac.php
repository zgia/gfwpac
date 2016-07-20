<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

define('ABSPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// ShadowsocksX 配置文件
define('TARGETDIR', '/Users/liyuntian/.ShadowsocksX/');
// 线上的base64 encode的GFW列表
define('GFWLIST', 'https://raw.githubusercontent.com/gfwlist/gfwlist/master/gfwlist.txt');
// 写到gfwlist.js中的__PROXY__
define('PROXY_STR', '"SOCKS5 127.0.0.1:1080; SOCKS 127.0.0.1:1080; DIRECT;"');

// 备份
backupOldGFW();

// 更新
$rules = getGFWLIST();
writeGFWLIST($rules);

println('Success.');

/**
 * 下载文件
 *
 * @param string $url
 * @param array  $httpinfo
 *
 * @return string
 */
function download($url, &$httpinfo = [])
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$resource = curl_exec($ch);
	$httpinfo = curl_getinfo($ch);
	curl_close($ch);

	if (intval($httpinfo["http_code"]) == 200)
	{
		return $resource;
	}
	else
	{
		$httpinfo = [];

		return null;
	}
}

/**
 * 从github获取 GFWLIST
 *
 * @return array
 */
function getGFWLIST()
{
	println('Downloading gfwlist...');

	$file = download(GFWLIST);
	if ($file)
	{
		println('Downloaded.');
		$file = base64_decode($file);
	}
	else
	{
		println('Cannot download gfwlist.');
		exit();
	};

	$rules = parseRuleFile(explode("\n", $file));

	// 用户自定义
	$userrules = getUserRules();
	if ($userrules)
	{
		$rules = array_merge($rules, $userrules);
	}

	return $rules;
}

/**
 * 备份
 */
function backupOldGFW()
{
	println('Backuping old gfwlist.js.');

	rename(TARGETDIR . 'gfwlist.js', TARGETDIR . date('YmdHis') . '.gfwlist.js');

	println('Backuped.');
}

/**
 * 把下载的内容写到本地文件
 *
 * @param array $rules
 */
function writeGFWLIST($rules)
{
	println('Parsing...');

	/**
	 * 只是为了格式化,每行前有2个空格缩进
	 *
	 * var rules = [
	 *   "line1",
	 *   "...",
	 *   "linen"
	 * ];
	 */

	$indent = '  ';
	$rules  = '[' . PHP_EOL . $indent . '"' . implode('",' . PHP_EOL . $indent . '"', $rules) . '"' . PHP_EOL . ']';

	$gfw = str_replace(['__PROXY__', '__RULES__'],
	                   [PROXY_STR, $rules],
	                   file_get_contents(ABSPATH . 'abp.js'));

	println('Parsed.', 'Writing to gfwlist.js.');

	file_put_contents(TARGETDIR . 'gfwlist.js', $gfw);
}

/**
 * 获取用户自定义规则
 *
 * @return array
 */
function getUserRules()
{
	$userrule = ABSPATH . 'user-rule.txt';
	$file     = file($userrule);

	file_put_contents($userrule,
	                  '! Put user rules line by line in this file.' . PHP_EOL . '! See https://adblockplus.org/en/filter-cheatsheet' . PHP_EOL);

	return parseRuleFile($file);
}

/**
 * 解析下载的文件,过滤掉"!"和"["开头的行,空行
 *
 * @param array $rulesInFile
 *
 * @return array
 */
function parseRuleFile($rulesInFile)
{
	$rules = [];
	foreach ($rulesInFile as $line)
	{
		$line = trim($line);
		if (!$line || preg_match('/^\!|^\[/', $line))
		{
			continue;
		}

		$line    = addcslashes($line, '/\\');
		$rules[] = $line;
	}

	return $rules;
}

/**
 * 每行输出加一个回车
 */
function println()
{
	foreach (func_get_args() as $str)
	{
		echo $str, PHP_EOL;
	}
}


