<?php
ini_set ('display_errors', 'on');
error_reporting (E_ERROR);

class SeoPilotClient
{
	static $sp_version           = '2.08';
	static $sp_socket_timeout    = 10;

	static $sp_error             = array();
	private static $domain       = 'www.seopilot.pl';

	var $params = array(
		"__allow_ip__" => array(),
		"__cache_life_time__" => 3600,
		"__cache_reload_time__" => 300,
		"__charset__" => 'DEFAULT',
		"__remote_addr_key__" => 'REMOTE_ADDR',
		"__request_uri_key__" => 'REQUEST_URI',
		"__template__" => '',
		"__demo_box__" => array(),
		"__demo_box_count__" => 3,
		"__max_box_count__" => 3,
	);

	var $sp_request_uri      = '';
	var $sp_links_page       = array();

	var $sp_test             = false;
	var $sp_links_db_file = '';

	function SeoPilotClient($options = array())
	{
		$this->sp_host = preg_replace('{^https?://(www\.)?(.*?)/}i', '$2', strtolower( $_SERVER['HTTP_HOST'] ));
		if (isset($options['is_test'])) {
			$this->sp_test = $options['is_test'];
		}

		if (!defined('SEOPILOT_USER')) {
			self::raise_error("Constant SEOPILOT_USER is not defined.");
			return;
		}

		$this->sp_links_db_file = dirname(__FILE__) . '/'.SEOPILOT_USER.'.links.db';
		if(is_file($this->sp_links_db_file) && !is_writable($this->sp_links_db_file)){
			self::raise_error("Can't write to Link db: permission denied!");
			return;	
		}
		if(!file_exists($this->sp_links_db_file)){
			self::loadDb();
		}
		if(is_file($this->sp_links_db_file)) {
			$this->parse_params($this->sp_links_db_file);
		}
		
		if(
			(time() - filemtime($this->sp_links_db_file)) > $this->params['__cache_life_time__']
			||
			((time() -  filemtime($this->sp_links_db_file)) > $this->params['__cache_reload_time__'] && filesize($this->sp_links_db_file) == 0)
		) {
			self::loadDb();
		}

		if(!isset($_SERVER[$this->params['__request_uri_key__']]) || $_SERVER[$this->params['__request_uri_key__']] == ''){
			$this->params['__request_uri_key__'] = 'REQUEST_URI';
		}
		$request_uri = $_SERVER[$this->params['__request_uri_key__']];
		if (isset($options['request_uri']) && strlen($options['request_uri']) != 0){
			$request_uri = $options['request_uri'];
			$this->params['__request_uri_key__'] = 'MANUAL_URI';
		}
		$this->sp_request_uri = rawurldecode(preg_replace('@^https?://.*?/(.*)@smi', '/$1', $request_uri));

		if (isset($options['charset']) && strlen($options['charset']) != 0 && $this->params['__charset__'] == 'DEFAULT'){
			$this->params['__charset__'] = $options['charset'];
		}

		if(in_array($_SERVER[$this->params['__remote_addr_key__']], $this->params['__allow_ip__']))
		{
			if(strpos($this->sp_request_uri, SEOPILOT_USER) !== false) {
				$this->sp_test = true;
			}
		}
		$this->load_links();
	}
	
	function loadDb()
	{
		$links = self::fetch_remote_file(self::$domain, "/files/api.php?m=links&hash=".SEOPILOT_USER);
		self::lc_write($this->sp_links_db_file, $links);
	}

	function load_links()
	{
		@clearstatcache();
		if($this->sp_test)
		{
			$this->sp_links_page = array_fill(0, $this->params['__demo_box_count__'], $this->params['__demo_box__']);
		} else
		{
			$this->sp_links_page = $this->lc_read($this->sp_links_db_file, $this->sp_request_uri);
		}

		if (!in_array(strtolower($this->params['__charset__']), array('utf-8', 'default')) && !empty($this->sp_links_page))
		{
			foreach ($this->sp_links_page as &$row)
			{
				$test = iconv('UTF-8', $this->params['__charset__'].'//TRANSLIT//IGNORE', $row['text']);
				$row['text'] = $test != '' ? $test: $row['text'];
				
				$anchor = iconv('UTF-8', $this->params['__charset__'].'//TRANSLIT//IGNORE', $row['anchor']);
				$row['anchor'] = $anchor != '' ? $anchor: $row['anchor'];
			}unset($row);
		}
	}

	function parse_params($db_file)
	{
		$size_index = 40;
		$item_delim = chr(1);
		$element_delim = chr(2);

		$header = fopen($db_file, "r");
		$this->readFAT = array();
		for($i =0; $i < 3; $i++){
			$tmp = array();
			$readed = trim(fread($header, $size_index));
			if($readed == '' || strpos($readed, ':') === false || strpos($readed, '|') === false){
				break;
			}
			list($opt, $vals) = explode(':', $readed, 2);
			list($tmp['start'], $tmp['length']) = explode('|', $vals, 2);

			$this->readFAT[$opt] = $tmp;
		}
		if(empty($this->readFAT)){
			return;
		}

		$opt = &$this->readFAT['params'];
		fseek($header, $opt['start']);

		$opt['content'] = '';
		if($opt['length'] > 0){
			$opt['content'] = fread($header, $opt['length']);
		}
		$opt['content'] = explode($item_delim, $opt['content']);

		$tmp = array();
		foreach($opt['content'] as $k => $line)
		{
			list($key, $value) = explode(':', $line, 2);
			if(strpos($value, $element_delim) !== false)
			{
				$value = explode($element_delim, $value);
				$tmp2 = array();
				foreach($value as $val)
				{
					if(strpos($val, ':') !== false)
					{
						list($kk, $vv) = explode(':', $val, 2);
						$tmp2[$kk] = $vv;
					} else
					{
						$tmp2[] = $val;
					}
				}
				$value = $tmp2;
			}
			$tmp[$key] = $value;
		}
		$opt = $tmp;
		unset($opt);
		fclose($header);

		$this->params = $this->readFAT['params'];

		unset($this->readFAT['params']);
	}

	function parse_data($data)
	{
		$path = preg_quote($this->sp_request_uri, '@');
		if(substr($this->sp_request_uri, -1) == '/'){
			$path .= '?';
		}
		$regexp = "@\|~".$path."~(.*?)~(.*?)~\|@si";

		preg_match_all($regexp, $data, $match);

		$upd_data = array();
		for ($i=0; $i < count($match[0]); $i++)
		{
			$tmp = array();
			preg_match_all('/^(.*?)__#h#__(.*?)$/si', $match[2][$i], $tmp);

			$upd_data[$i]['url'] 	= $match[1][$i];
			$upd_data[$i]['text'] 	= $tmp[2][0];
			$upd_data[$i]['anchor'] 	= $tmp[1][0];
		}
		return $upd_data;
	}

	function show_result($res='')
	{
		if($this->sp_test || in_array($_SERVER[$this->params['__remote_addr_key__']], $this->params['__allow_ip__']))
		{
			$res .= "
<!--[
	//test_".SEOPILOT_USER."//
	//version_PHP_".self::$sp_version."//
	//{$this->params['__request_uri_key__']}:".$this->sp_request_uri."//
	//client_path:".dirname(__FILE__)."//
	//charset:{$this->params['__charset__']}//
	//{$this->params['__remote_addr_key__']}:{$_SERVER[$this->params['__remote_addr_key__']]}//
	//links_file_size:".filesize($this->sp_links_db_file)."//
	//cache_lifetime:{$this->params['__cache_life_time__']}//
	//cache_reloadtime:{$this->params['__cache_reload_time__']}//
	//time:".time()."//
	//ver:".phpversion()." - ".(class_exists('Phar')? '1': '0')."//".(class_exists('Phar')?"\n\t//SupportedSignatures:".base64_encode(var_export(Phar::getSupportedSignatures(), 1))."//":"")."
	//server:".base64_encode(var_export($_SERVER, 1))."//
	//cache_ftime:".(time() - filemtime($this->sp_links_db_file))."//".(!empty(self::$sp_error) ? "\n\t//errors:".implode("\n", self::$sp_error)."//" : '')."
]-->";
		}
		return $res;
	}

	/**
	 * build links for the page
	 */
	function build_links()
	{
		if ( count($this->sp_links_page) == 0)
			return $this->show_result();

		$tpl = base64_decode($this->params['__template__']);
		if (!$tpl) {
			self::raise_error("Template is empty");
			return $this->show_result();
		}

		if (!preg_match("/<{block}>(.*?)<{\/block}>/si", $tpl, $block)) {
			self::raise_error("Wrong template format: no <{block}><{/block}> tags");
			return $this->show_result();
		}

		$tpl = str_replace('%', "%%", $tpl);
		$tpl = str_replace($block[0], "%s", $tpl);
		$block = $block[0];
		$block = trim(str_replace(array("<{block}>", "<{/block}>"), "", $block));
		
		if (strpos($block, '<{link}>')===false)
			self::raise_error("Wrong template format: no <{link}> tag.");
		if (strpos($block, '<{text}>')===false)
			self::raise_error("Wrong template format: no <{text}> tag.");
		if (strpos($block, '<{host}>')===false)
			self::raise_error("Wrong template format: no <{host}> tag.");

		$text = '';
		foreach ($this->sp_links_page as $i => $link)
		{
			if ($i >= $this->params['__max_box_count__'])
				continue;

			if (!is_array($link))
			{
				self::raise_error("link must be an array");
				continue;
			} elseif (!isset($link['text']) || !isset($link['url']))
			{
				self::raise_error("format of link must be an array('anchor'=>\$anchor,'url'=>\$url,'text'=>\$text");
				continue;
			} elseif (!($parsed=@parse_url($link['url'])) || !isset($parsed['host']))
			{
				self::raise_error("wrong format of url: ".$link['url']);
				continue;
			}
			if (($level=count(explode(".",$parsed['host'])))<2)
			{
				self::raise_error("wrong host: ".$parsed['host']." in url ".$link['url']);
				continue;
			}
			$host = strtolower(($level > 2 && strpos(strtolower($parsed['host']),'www.')===0) ? substr($parsed['host'],4) : $parsed['host']);
			$href = empty($link['punicode_url']) ? $link['url'] : $link['punicode_url'];
			$link['anchor'] = str_replace(array("#a#", "#/a#"),array('<a href="'.$href.'">', '</a>'), $link['anchor']);
			$link['text'] = str_replace(array("#a#", "#/a#"),array('<a href="'.$href.'">', '</a>'), $link['text']);
			$text .= str_replace(array('<{link}>','<{text}>','<{host}>'), array($link['anchor'], $link['text'], $host), $block);
		}

		$tpl = sprintf($tpl, $text);

		return $this->show_result($tpl);
	}

	function lc_read($filename, $uri)
	{
		if(empty($this->readFAT))
			return array();

		$fp = @fopen($filename, 'r');

		@flock($fp, LOCK_SH);
		if ($fp)
		{
			fseek($fp, $this->readFAT['flinks']['start']);
			$flinks = @fread($fp, $this->readFAT['flinks']['length']);

			preg_match_all('%\[~'.preg_quote($uri, '%').':(\d+):(\d+)~\]%smi', $flinks, $result);

			$delta = $this->readFAT['clinks']['start'];
			$links = array();
			foreach($result[1] as $k => $val)
			{
				fseek($fp, $delta + $val);
				$line = @fread($fp, $result[2][$k]);
				
				preg_match_all('/^(.*?)~(.*?)__#h#__(.*?)$/si', $line, $tmp);
				$upd_data['url'] 	= $tmp[1][0];
				$upd_data['text'] 	= $tmp[3][0];
				$upd_data['anchor'] 	= $tmp[2][0];
				$links[] = $upd_data;
			}

			@flock($fp, LOCK_UN);
			@fclose($fp);
			return $links;
		}
		return self::raise_error("Can't get data from the file: " . $filename);
	}
	
	static function lc_write($filename, $data)
	{
		$fp = @fopen($filename, 'wb');
		if ($fp)
		{
			@flock($fp, LOCK_EX);
			$length = strlen($data);
			@fwrite($fp, $data, $length);
			@flock($fp, LOCK_UN);
			@fclose($fp);

			if (md5(file_get_contents($filename)) != md5($data)) {
				return self::raise_error("Integrity was violated while writing to file: " . $filename);
			}
			return true;
		}
		return self::raise_error("Can't write to file: " . $filename);
	}
	
	static function raise_error($e)
	{
		self::$sp_error[] = $e;
		return false;
	}

	function fetch_remote_file($host, $path)
	{
		@ini_set('allow_url_fopen', 1);
		@ini_set('default_socket_timeout', self::$sp_socket_timeout);

		if (($data = @file_get_contents('http://' . $host . $path)) !== false)
		{
			return $data;
		}
		if ($ch = @curl_init())
		{
			@curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
			@curl_setopt($ch, CURLOPT_HEADER, false);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$sp_socket_timeout);

			$data = @curl_exec($ch);
			@curl_close($ch);
			if($data !== false){
				return $data;
			}
		}

		$buff = '';
		$fp = @fsockopen($host, 80, $errno, $errstr, self::$sp_socket_timeout);
		if ($fp)
		{
			@fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
			while (!@feof($fp))
			{
				$buff .= @fgets($fp, 128);
			}
			@fclose($fp);

			$page = explode("\r\n\r\n", $buff);
			return $page[1];
		}

		self::raise_error("Can't connect to server: " . $host . $path.' ['.$errstr.']');
		return '';
	}

	function getCountLinks()
	{
		return sizeof($this->sp_links_page);
	}
	
	function onCommand($param)
	{
		if(!in_array($_SERVER[$this->params['__remote_addr_key__']], $this->params['__allow_ip__']) || !isset($param['method']))
			die('ERROR_ACCESS');

		$result = array();
		switch($param['method'])
		{
			case 'forceUpdateCache':
				@unlink($this->sp_links_db_file);
				if(file_exists($this->sp_links_db_file))
				{
					die('ERROR');
				} else 
				{
					die('OK');
				}
			break;
		}
		die('ERROR');
	}
}

if(realpath($_SERVER['SCRIPT_FILENAME']) == realpath(__FILE__))
{
	$method = $_SERVER['REQUEST_METHOD'];
	if($method == 'GET')
	{
		if(!defined('SEOPILOT_USER')){
			define('SEOPILOT_USER', array_pop(explode('/', dirname(__FILE__))));
		}

		$seo = new SeoPilotClient();
		if(isset($_GET['method']))
		{
			$seo->onCommand($_GET);
		} else
		{
			print $seo->show_result("<h1>Work!</h1>");
		}
	}
}
?>