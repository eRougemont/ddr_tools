<?php declare(strict_types = 1);

$data_file = __DIR__ . '/ddr_iiif.tsv';
$dic = [];
$handle = fopen($data_file, "r");
while (($row = fgetcsv($handle, null, "\t")) !== FALSE) {
    if ($row[0][0] == '#') continue;
    $dic[$row[0]] = $row[1];
}

$glob = dirname(dirname(__DIR__)) . '/ddr_inedits/ddr*.xml';
echo "$glob\n";
foreach (glob($glob) as $file) {
    echo "$file\n";
    $xml = file_get_contents($file);
    $xml = preg_replace_callback(
        '@ facs="([^"]+)"@',
        function ($matches) {
            global $dic;
            if (isset($dic[$matches[1]])) {
                return ' facs="' . $dic[$matches[1]] . '"';
            }
            return $matches[0];
        },
        $xml
    );
    file_put_contents($file, $xml);
}
