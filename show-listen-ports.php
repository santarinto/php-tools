<?php

define('PREPEND_SPACES_COUNT', 2);

function isDebugMode(): bool {
	global $argv;	

	return isset($argv[1]);
}

exec('sudo lsof -iTCP -sTCP:LISTEN -n -P', $out);

$count = count($out);
$data = [];
$cols = [];

for ($i = 0; $i < $count; $i++) {
	$line = $out[$i];

	if (isDebugMode()) {
		echo($line) . PHP_EOL;	
		continue;
	}

	$words = array_values(array_filter(explode(' ', $line)));
	$words = array_filter($words, static function($w, $k): bool {
		if (!in_array($k, [0, 1, 2, 4, 8], true)) {
			return false;
		}
		
		return true;
	}, ARRAY_FILTER_USE_BOTH);

	if ($i === 0) {
		$cols = array_keys($words);
	}

	$words = array_map(function(string $word): string {
		return trim($word);
	}, $words);

	foreach ($cols as $col) {
		$word = $words[$col];

		if ($col === 8) {
			$ws = explode(':', $word);
			$word = $ws[1] . ' : ' . $ws[0];
		}

		$length = strlen($word);		

		$data[$col][$i] = [
			'value'  => $word,
			'length' => $length,
		];

		if (!array_key_exists('data', $data[$col])) {
			$data[$col]['data'] = [
				'maxlength' => 0,
			];
		}

		if ($data[$col]['data']['maxlength'] < $length) {
			$data[$col]['data']['maxlength'] = $length;
		}
	}
}

if (isDebugMode()) {
	exit(0);
}

for ($i = 0; $i <  $count; $i++) {
	echo(str_repeat(' ', PREPEND_SPACES_COUNT));

	foreach ($cols as $col) {
		$word = $data[$col][$i]['value'] ?? '';

		if ($i === 0) {
			$word = str_pad(
				$word,
				$data[$col]['data']['maxlength'],
				' ',
				STR_PAD_BOTH
			);
		} else {
			$pad = STR_PAD_RIGHT;

			if (in_array($col, [0, 8])) {
				$pad = STR_PAD_LEFT;
			}

			$word = str_pad(
					$word, 
					$data[$col]['data']['maxlength'], 
					' ', 
					$pad
			);
		}

		echo($word);

		if ($col < max($cols)) {
			echo("\t");
		}
	}

	echo(PHP_EOL);
}
