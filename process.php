<?php
# Telegram Bot Token 
define('BOT_TOKEN', 'Enter Your Bot token here');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/'); // Это трогать не нужно

# Адрес вебхука, куда будет стучаться Telegram
define('WEBHOOK', 'https://test.ru/bot-mamoeb/process.php'); // 


# Общая функция для запроса к API Телеграмма
function apiRequest($toSend = array(), $json = true)
	{
		$ch = curl_init(API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json ? json_encode($toSend) : $toSend);
		if($json)
			{
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
			}
		$a = curl_exec($ch);
		return json_decode($a, true);
	}

# webhook
function setWebhook($url, $delete = false)
	{
		$webhook = $delete == true ? 'delete' : $url;
		$toSend = array('url' => $webhook, 'method' => 'setWebhook');
		
		return apiRequest($toSend);
	}

# Функция выдачи inline-результата
function answerInlineQuery($query_id, $results)
	{
		$toSend = array('method' => 'answerInlineQuery', 'cache_time' => 0, 'inline_query_id' => $query_id, 'results' => array($results));
		
		return apiRequest($toSend);
	}


# Задаем webhook, если скрипт вызван из cli, либо есть get-параметр webhook
if(php_sapi_name() == 'cli' OR isset($_GET['webhook']))
	{
		var_dump(setWebhook(WEBHOOK));
		die;
	}


# Начинаем работу, обрабатываем входящий запрос
$content = file_get_contents("php://input");

# Парсим JSON
$update = @json_decode($content, true);

if(!$update)
	{
		die('Error: invalid JSON');
	}
else
	{
		if(isset($update['inline_query']))
			{
				# Бот вызван через inline-режим
				
				$_INLINE = $update['inline_query']; // Массив inline-query
				$_QUERY = $_INLINE['query'];		// Строка запроса. Может быть пустой.
				$_QUERY_ID = $_INLINE['id'];		// ID inline-query
				
				## Начинаем подбор грязного ругательства
				# Открываем плохой файл
				$f = file_get_contents('vocabulary.json');
				$json = json_decode($f, true) or die('Invalid vocabulary');
				
				# Выбираем случайное
				$string = trim($json['curses'][rand(0, count($json['curses']) - 1)]);
				
				# Формируем результат отправки
				$results = array('type' => 'article', 'id' => md5(microtime()), 'title' => $string, 'input_message_content' => array('message_text' => $string, 'parse_mode' => 'HTML'));
				
				answerInlineQuery($_QUERY_ID, $results); // Отвечаем
			}
	}