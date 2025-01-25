<?php

namespace TweakPHP\Client\Psy;

use Psy\VarDumper\Cloner;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use TweakPHP\Client\Tinker;

class Presenter extends \Psy\VarDumper\Presenter
{
    private Cloner $cloner;

    public function __construct(OutputFormatter $formatter, $forceArrayIndexes = false)
    {
        parent::__construct($formatter, $forceArrayIndexes);

        $this->cloner = new Cloner;
    }

    public function addCasters(array $casters)
    {
        parent::addCasters($casters);

        $this->cloner->addCasters($casters);
    }

    public function present($value, ?int $depth = null, int $options = 0): string
    {
        $dumper = new HtmlDumper;
        $dumper->setDumpHeader('');
        $data = $this->cloner->cloneVar($value, ! ($options & self::VERBOSE) ? Caster::EXCLUDE_VERBOSE : 0);
        if ($depth !== null) {
            $data = $data->withMaxDepth($depth);
        }

        $output = '';
        $dumper->dump($data, function ($line, $depth) use (&$output) {
            if ($depth >= 0) {
                if ($output !== '') {
                    $output .= \PHP_EOL;
                }
                $output .= \str_repeat('  ', $depth).$line;
            }
        });

        if (isset(Tinker::$statements[Tinker::$current])) {
            Tinker::$statements[Tinker::$current]['html'] = $output;
        }

        return parent::present($value, $depth, $options);
    }
}
