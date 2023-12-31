<?php

//
// Copyright (c) Sebastian Kucharczyk <kuchen@kekse.biz>
// v0.2.0
//
// Will first fetch your .md markdown document,
// then uses the GitHub API to render it to pure HTML.
//
// But you first have to set the following constants (somewhere in the
// middle of this script) to your needs:
//
// 	TOKEN
// 	USER
// 	MODE (maybe)
//
// And maybe you also want to adapt the 'TIMEOUT' constant (below)?
//

//
namespace kekse;

if(!extension_loaded('curl'))
{
	die('No cURL module loaded!');
}
else
{
	require_once(__DIR__ . '/github-markdown.inc.php');
}

//
function getMarkdownHTML(... $_args)
{
	return \kekse\github\getMarkdownHTML(... $_args);
}

//
if(!defined('TIMEOUT')) define('TIMEOUT', 16);

function httpRequest($_url, $_method = 'GET', $_headers = null, $_data = null)
{
	//
	$_method = strtoupper($_method);

	//
	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, $_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $_method);
	
	if($_method === 'POST')
	{
		if(is_array($_data))
		{
			$_data = json_encode($_data);
		}
		else if(! is_string($_data))
		{
			die('Invalid $_data argument (neither array nor string)');
		}

		curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
	}

	if(is_array($_headers))
	{
		curl_setopt($curl, CURLOPT_HTTPHEADER, $_headers);
		
		if(array_key_exists('User-Agent', $_headers))
		{
			curl_setopt($curl, CURLOPT_USERAGENT, $_headers['User-Agent']);
		}
	}

	curl_setopt($curl, CURLOPT_TIMEOUT, TIMEOUT);
	//curl_setopt($curl, CURLOPT_HEADER, true);

	//
	$response = curl_exec($curl);
	$error = curl_error($curl);

	//
	curl_close($curl);

	//
	if($error)
	{
		die('Error in cURL request: ' . $error);
	}
	else if(curl_errno($curl))
	{
		die('Error in cURL request: (' . curl_errno($curl) . ') ' . curl_error($curl));
	}

	return $response;
}

namespace kekse\github;

//
const API = 'https://api.github.com/markdown';
const API_VERSION = '2022-11-28';
const RAW = 'https://raw.githubusercontent.com/%{user}/%{repository}/master/';
const AGENT = 'https://github.com/kekse1/';

//
function generateDocumentURL($_repository, $_path = 'README.md', $_user = USER)
{
	$result = str_replace('%{repository}', $_repository, RAW);
	$result = str_replace('%{user}', $_user, $result);
	if($result[strlen($result) - 1] !== '/') $result .= '/';
	if(is_string($_path)) $result .= $_path;
	return $result;
}

function renderMarkdown($_document, $_repository, $_user = USER)
{
	$data = array('text' => $_document);
	$headers = array(
		'Accept' => 'application/vnd.github+json',
		'Authorization' => 'Bearer ' . TOKEN,
		'X-GitHub-Api-Version' => API_VERSION,
		'User-Agent' => AGENT
	);

	$data = array(
		'text' => $_document,
		'mode' => MODE
	);

	if(is_string($_repository) && is_string($_user))
	{
		$data['context'] = $_user . '/' . $_repository;
	}

	return \kekse\httpRequest(API, 'POST', $headers, $data);
}

function getMarkdownDocument($_repository, $_path = 'README.md', $_user = USER)
{
	$url = generateDocumentURL($_repository, $_path, $_user);
	return \kekse\httpRequest($url);
}

function getMarkdownHTML($_repository, $_path = 'README.md', $_user = USER)
{
	$document = getMarkdownDocument($_repository, $_path, $_user);
	return renderMarkdown($document, $_repository, $_user);
}

?>
