<?php
ini_set('memory_limit', -1);
$path = "/data";

$exists = [];
$list = [];
scanner($path, $list, $exists);

$surfix = 'json';
switch (getenv('OUTPUT_FORMAT')) {
    case 'sql':
        $surfix = 'sql';
        $tableName = getenv('OUTPUT_TABLE');
        if (empty($tableName)) {
            $tableName = 'synology_file_duplicates';
        }
        $output = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
 `id` int NOT NULL AUTO_INCREMENT,
 `path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
 `size` bigint NOT NULL,
 `md5` char(32) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `duplicateID` (`size`,`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;\n";
        if (!empty($list)) {
            foreach ($list as $i => $r) {
                if ($i % 1000 == 0) {
                    if ($i != 0) {
                        $output .= ";\n";
                    }
                    $output .= "INSERT INTO `{$tableName}`(`path`, `size`, `md5`) VALUES\n";
                } else {
                    $output .= ",\n";
                }
                $sqlPath = addslashes($r['path']);
                $output .= "('{$sqlPath}',{$r['size']},'{$r['md5']}')";
            }
            $output .= ';';
        }
        break;
    case 'csv':
        $surfix = 'csv';
        $output = "path,size,md5\n" . implode("\n", array_map(function($r) {
                $csvPath = '"' . str_replace('"', '""', $r['path']) . '"';
                return "{$csvPath},{$r['size']},{$r['md5']}";
            }, $list));
        break;
    case 'json':
    default:
        $surfix = 'json';
        $output = json_encode($list);
}

$outputFile = getenv('OUTPUT_FILE');
if (empty($outputFile)) {
    $outputFile = 'duplicates.' . date('YmdHis') . '.' . $surfix;
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
        if (!isset($exists[$size])) {
            $exists[$size] = $path;
        } else {
            if (is_string($exists[$size])) {
                $lastPath = $exists[$size];
                $lastMd5 = md5_file($lastPath);
                $exists[$size] = [$lastMd5 => $lastPath];
            }
            $md5 = md5_file($path);
            if (!isset($exists[$size][$md5])) {
                $exists[$size][$md5] = $path;
            } else {
                $lastPath = $exists[$size][$md5];
                if (true !== $lastPath) {
                    $exists[$size][$md5] = true;
                    $list[] = [
                        'path' => substr($lastPath, 5),
                        'size' => $size,
                        'md5' => $md5,
                    ];
                }
                $list[] = [
                    'path' => substr($path, 5),
                    'size' => $size,
                    'md5' => $md5,
                ];
            }
        }
    }
}
