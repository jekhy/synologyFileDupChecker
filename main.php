<?php
$path = "/data";

$exists = [];
$list = [];
scanner($path, $list, $exists);

$surfix = 'json';
switch (getenv('OUTPUT_FORMAT')) {
    case 'csv':
        $surfix = 'csv';
        $output = "path,size,md5\n" . implode("\n", array_map(function($r) {
                return "{$r['path']},{$r['size']},{$r['md5']}";
            }, $list));
        break;
    case 'json':
    default:
        $surfix = 'json';
        $output = json_encode($list);
}

$outputFile = getenv('OUTPUT_FILE');
if (empty($outputFile)) {
    $outputFile = date("YmdHis") . "." . $surfix;
}

$outputPath = "/output/{$outputFile}";
file_put_contents($outputPath, $output);
echo "The result has been written to the path:{$outputPath}";

function scanner($path, &$list, &$exists) {
    if (is_dir($path)) {
        $dir = dir($path);
        while (false !== ($file = $dir->read())) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            scanner($path . '/' . $file, $list, $exists);
        }
    } else {
        $size = filesize($path);
        if (isset($exists[$size])) {
            if (true !== $exists[$size]) {
                $lastPath = $exists[$size];
                $exists[$size] = true;
                $list[] = [
                    'path' => substr($lastPath, 5),
                    'size' => $size,
                    'md5' => md5_file($lastPath),
                ];
            }
            $list[] = [
                'path' => substr($path, 5),
                'size' => $size,
                'md5' => md5_file($path),
            ];
        } else {
            $exists[$size] = $path;
        }
    }
}
