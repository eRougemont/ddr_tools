<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log};
use Oeuvres\Kit\Logger\{LoggerCli};
use Oeuvres\Teinte\Format\{Tei};

// direct call as CLI
if (
    php_sapi_name() == 'cli'
    && isset($argv[0])
    && realpath($argv[0]) == realpath(__FILE__)
) {
    Rougemont::cli();
}

class Rougemont
{
    /** Object to load Tei for exports */
    static $tei;
    /** Where project docx */
    static $dst_dir;

    public static function cli()
    {
        global $argv;
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        self::$tei = new Tei();
        self::$tei->template('docx', __DIR__ . '/ddr_tmpl.docx');
        self::$dst_dir = dirname(__DIR__) . '/ddr_docx/';

        if (!isset($argv[1])) {
            die("usage: php ddr_docx.php livres/*.xml\n");
        }
        // drop $argv[0], $argv[1…] should be file
        array_shift($argv);
        // loop on arguments to get files of globs
        foreach ($argv as $glob) {
            foreach (glob($glob) as $tei_file) {
                self::docx($tei_file);
            }
        }
    }


    public static function docx($tei_file)
    {
        if (!file_exists($tei_file)) {
            Log::warning($tei_file . " 404 File not found");
            return;
        }
        if (!is_file($tei_file)) {
            Log::warning($tei_file . " is not a file");
            return;
        }    
        $dst_file = self::$dst_dir . pathinfo($tei_file, PATHINFO_FILENAME) . '.docx';
        // Log::info($tei_file . " -> " . $dst_file);
        self::$tei->load($tei_file);
        self::$tei->toUri('docx', $dst_file);    
    }
}