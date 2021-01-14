<?php

define('PREPEND_SPACES_COUNT', 2);

function isDebugMode(): bool
{
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
        echo ($line) . PHP_EOL;
        continue;
    }

    $words = array_values(array_filter(explode(' ', $line)));
    $words = array_filter($words, static function ($w, $k): bool {
        if (!in_array($k, [0, 1, 2, 4, 8], true)) {
            return false;
        }

        return true;
    }, ARRAY_FILTER_USE_BOTH);

    if ($i === 0) {
        $cols = array_keys($words);
    }

    $words = array_map(function (string $word): string {
        return trim($word);
    }, $words);

    foreach ($cols as $col) {
        $word = $words[$col];

        if ($col === 8 && $i > 0) {
            $word = explode(':', $word);
            array_walk($word, 'trim');
            $length = [
                strlen($word[0]),
                strlen($word[1]),
            ];
        } else {
            $length = strlen($word);
        }

        $data[$col][$i] = [
            'value' => $word,
            'length' => $length,
        ];

        if (!array_key_exists('data', $data[$col])) {
            $data[$col]['data'] = [
                'maxlength' => 0,
            ];
        }

        if ($col === 8 && $i > 0) {
            if (!is_array($data[$col]['data']['maxlength'])) {
                $data[$col]['data']['maxlength'] = [0 => 0, 1 => 1];
            }

            if ($data[$col]['data']['maxlength'][0] < $length[0]) {
                $data[$col]['data']['maxlength'][0] = $length[0];
            }

            if ($data[$col]['data']['maxlength'][1] < $length[1]) {
                $data[$col]['data']['maxlength'][1] = $length[1];
            }
        } else {
            if ($data[$col]['data']['maxlength'] < $length) {
                $data[$col]['data']['maxlength'] = $length;
            }
        }
    }
}

if (isDebugMode()) {
    exit(0);
}

for ($i = 0; $i < $count; $i++) {
    echo(str_repeat(' ', PREPEND_SPACES_COUNT));

    foreach ($cols as $col) {
        $word = $data[$col][$i]['value'] ?? '';
        $maxLength = $data[$col]['data']['maxlength'];

        if ($i === 0) {
            if ($col === 8) {
                $maxLength = 3 + $data[$col]['data']['maxlength'][0] + $data[$col]['data']['maxlength'][1];
            }

            $word = str_pad(
                $word,
                $maxLength,
                ' ',
                STR_PAD_BOTH
            );
        }


        if ($col === 8 && $i > 0) {
            [$wIp, $wPort] = $word;
            [$wPortMaxLength, $wIpMaxLength] = $data[$col]['data']['maxlength'];

            $w = str_pad($wPort, $wPortMaxLength, ' ', STR_PAD_LEFT);
            $w .= ' : ';
            $w .= str_pad($wIp, $wIpMaxLength, ' ');

            $word = $w;
            $maxLength = $wPortMaxLength + $wIpMaxLength + 3;
        } else {
            $pad = STR_PAD_RIGHT;

            if ($col === 0) {
                $pad = STR_PAD_LEFT;
            }

            $word = str_pad($word, $maxLength, ' ', $pad);
        }

        echo($word);

        if ($col < max($cols)) {
            echo("\t");
        }
    }

    echo(PHP_EOL);
}
