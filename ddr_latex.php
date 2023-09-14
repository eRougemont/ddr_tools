<?php

declare(strict_types=1);

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
    /** Diectory where LaTeX hould work */
    static $work_dir;
    static $skeltex;

    public static function cli()
    {
        global $argv;
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        if (!isset($argv[1])) {
            die("usage: php docx_tei.php examples/*.docx\n");
        }
        // drop $argv[0], $argv[1…] should be file
        array_shift($argv);
        self::$work_dir = __DIR__ . "/work/latex/";
        self::$tei = new Tei();
        // loop on arguments to get files of globs
        foreach ($argv as $glob) {
            foreach (glob($glob) as $tei_file) {
                self::pdf($tei_file);
            }
        }
    }

    public static function bookurl($tei_file)
    {
        $tei_name = pathinfo($tei_file, PATHINFO_FILENAME);
        list($tei_name) = explode("_", $tei_name);
        $path = realpath($tei_file);
        
        $bookurl = "https://unige.ch/rougemont/";
        $folder = basename(dirname($path));
        if (strpos($folder, 'livres') !== false) {
          $bookurl .= "livres/".$tei_name.'/';
        }
        else if (strpos($folder, 'articles') !== false) {
          $journal = explode('ddr-', $tei_name, 2)[1];
          $bookurl .= "articles/".$journal.'/';
        }
        else if (strpos($folder, 'corr') !== false) {
          $bookurl .= "correspondances/".$tei_name.'/';
        }
    }

    public static function pdf($tei_file)
    {
        $bookurl = self::bookurl($tei_file);
        // load TEI
        self::$tei->load($tei_file);
        $filename = pathinfo($tei_file, PATHINFO_FILENAME);
        // Tex will need a lot of files, generate latex in a tmp dir
        $dir = self::$work_dir . strtok($filename, '_');
        Filesys::mkdir($dir);
        $oldPath = getcwd();
        chdir($dir); // change working directory
        // A4 2 cols
        $latex_file = "$dir/$filename.tex";
        self::$tei->toUri(
            'latex', 
            $latex_file, 
            [
                'template' => __DIR__ . "/rougemont_a4col2.tex",
                'latex.xsl' => __DIR__ . "/rougemont_latex.xsl",
                'bookurl' => $bookurl,
            ]
        );
        exec("latexmk -lualatex -quiet -f " . $latex_file);
        
        /*
        // booklet
        $latex_file =  "$dir/{$filename}_105x297.tex";
        self::$tei->toUri(
            'latex', 
            $latex_file, 
            [
                'template' => __DIR__ . "/piaget_booklet.tex",
                'latex.xsl' => __DIR__ . "/piaget_latex.xsl",
            ]
        );
        exec("latexmk -lualatex -quiet -f " . $latex_file);
        // to build the booklet, apply a tex script to pdf
        $tex = file_get_contents(__DIR__ .'/vendor/oeuvres/xsl/tei_latex/booklet_bind.tex');        
        // set the pdf file to transform (relative path)
        $tex = str_replace('{thepdf}', "{{$filename}_105x297}", $tex);
        // write the tex to process
        $dst_booklet = "$dir/{$filename}_booklet";
        file_put_contents("$dst_booklet.tex", $tex);
        // works with -pdf only
        exec("latexmk -pdf -quiet -f $dst_booklet.tex");

        // A5 for screen
        $latex_file = "$dir/{$filename}_a5.tex";
        self::$tei->toUri(
            'latex', 
            $latex_file, 
            [
                'template' => __DIR__ . "/piaget_a5.tex",
                'latex.xsl' => __DIR__ . "/piaget_latex.xsl",
            ]
        );
        // exec("latexmk -xelatex -quiet -f " . $latex_file);
        exec("latexmk -lualatex -quiet -f " . $latex_file);
        */
        chdir($oldPath);
    }
}
