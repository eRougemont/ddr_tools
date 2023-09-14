<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');
use Psr\Log\LogLevel;
use Oeuvres\Kit\{Xt, Log};
use Oeuvres\Kit\Logger\{LoggerCli};
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Util\Variables;

// set global variables here
ZoteroTei::init([
    'style' => 'nouvelles-perspectives-en-sciences-sociales',
    'lang' => 'fr-FR'
]);


// direct call as CLI
if (
    php_sapi_name() == 'cli'
    && isset($argv[0])
    && realpath($argv[0]) == realpath(__FILE__)
) {
    ZoteroTei::cli();
}

class ZoteroTei
{
    static $conf = [];
    static $variables;
    static $additionalMarkup;

    static function init($conf = [])
    {
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        self::$conf = array_merge(self::$conf, $conf);
        // build dictionary of variables to extract from extra
        self::$variables = array_flip(array_merge(Variables::NUMBER_VARIABLES, Variables::STANDARD_VARIABLE));
        unset(self::$variables['note']);
        $tagsoup = function($csl, $html) {
            $html = preg_replace(
                [
                    '@&lt;(/?)(i|sup|sub)&gt;@',
                    '@&lt;(span style="font-variant:small-caps;"|/span)&gt;@u'
                ],
                [
                    '<$1$2>',
                    '<$1>',
                ],
                $html
            );
            return $html;
        };
        self::$additionalMarkup = [
            "bibliography" => [
                "container-title" => $tagsoup,
                "collection-title" => $tagsoup,
                "edition" => $tagsoup,
                "reviewed-title" => $tagsoup,
                "title" => $tagsoup,
                "csl-entry" => function($item, $html) {
                    $key = 'call-number';
                    if (!isset($item->$key)) return $html; 
                    $id = $item->$key;
                    $html = preg_replace(
                        [
                            '@, & @', // a fix for APA <names variable="editor translator" delimiter=",  &amp; ">
                            '@style=“([^”]*)”@u' // a fix for quotes in atts
                        ], 
                        [
                            ', &#38; ',
                            'style="$1"',
                        ],
                        $html);
                    // $html = "<small>[$id]</small> " . $html;
                    $html = "<span id=\"$id\"></span>" . $html;
                    return $html;
                }
            ],
            "citation" => [
            ]
        ];
                
    }

    public static function cli()
    {
        global $argv;
        if (!isset($argv[1])) {
            die("usage: php zotero_tei.php my_zotero_CSL-JSON_export.json (*.xml)*\n");
        }
        array_shift($argv); // shift arg 0
        $zotero_csl_file = array_shift($argv);
        $dst_path = dirname($zotero_csl_file) 
        . '/' 
        . pathinfo($zotero_csl_file, PATHINFO_FILENAME);

        $json = file_get_contents($zotero_csl_file);
        // load records as object, needed by citeproc
        $data = json_decode($json, false, 1024, JSON_THROW_ON_ERROR);
        foreach($data as $item) {
            self::extraVariables($item);
        }
        $zotero_html_file = $dst_path . ".html";
        if (
            !file_exists($zotero_html_file) 
            || filemtime($zotero_csl_file) > filemtime($zotero_html_file)
        ) {
            // styled html from csl data
            $style = StyleSheet::loadStyleSheet(self::$conf['style']);
            $citeProc = new CiteProc($style, self::$conf['lang'], self::$additionalMarkup);
            $html = $citeProc->render($data, "bibliography");
            file_put_contents($zotero_html_file, $html);
        }

        // glob of TEI file to populate
        if (!count($argv)) return;
        // alert if no tei export of the lib
        $zotero_tei_file = $dst_path . ".xml";
        if (!file_exists($zotero_tei_file)) {
            die("$zotero_tei_file not found, expected as a TEI export of your Zotero library\n");
        }
        
        // prepare csl for injection
        $csl_dic = [];
        foreach ($data as $item)
        {
            if (!isset($item->{'call-number'})) continue;
            $id = $item->{'call-number'};
            if (isset($csl_dic[$id])) {
                echo "Zotero CSL json export, doublon : $id\n";
                continue;
            }
            unset($item->id);
            $csl_dic[$id] = json_encode($item, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
        }
        foreach($argv as $glob) {
            foreach (glob($glob) as $tei_file) {
                $id = strtok(pathinfo($tei_file, PATHINFO_FILENAME), '_');
                echo "$id — $tei_file \n";
                $xml = file_get_contents($tei_file);
                $dom = new DOMDocument();
                $dom->substituteEntities = true;
                $dom->preserveWhiteSpace = true;
                $dom->formatOutput = false;
                $dom = Xt::loadXml($xml, $dom);
                $xml = Xt::transformToXml(
                    __DIR__ . "/zotero_tei.xsl",
                    $dom,
                    [
                        'id' => $id,
                        'zotero_tei_file' => 'file:///'. str_replace('\\', '/',realpath($zotero_tei_file)),
                        'zotero_html_file' => 'file:///'. str_replace('\\', '/',realpath($zotero_html_file)),
                    ],
                );
                if (isset($csl_dic[$id])) {
                    $xml = str_replace(
                        '<xenoData type="CSL"/>', 
                        "<xenoData type=\"CSL\"><![CDATA[\n$csl_dic[$id]\n]]></xenoData>", 
                        $xml);
                }
                else {
                    echo "Pas de CSL pour : $tei_file\n";
                }

                file_put_contents($tei_file, $xml);
            }
        }

    }



    /**
     * From a zotero csl json item
     * parse the extra field
     * populate the item with extracted extra variables
     */
    static function extraVariables(&$item)
    {
        if (!isset($item->note)) return;
        $extra = $item->note;
        $note = '';
        // preg_split keep empty lines (not strtok)
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $extra) as $line){
            $pos = strpos($line, ':');
            if (!$pos) {
                $note .= $line . "\n";
                continue;
            }
            $var = trim(substr($line, 0, $pos));
            if (!isset(self::$variables[$var])) {
                $note .= $line . "\n";
                continue;
            }
            $item->{$var} = trim(substr($line, $pos + 1));
        }
        $note = trim($note);
        if (!$note) {
            unset($item->note);
        }
        else {
            $item->note = $note;
        }
    }
}



