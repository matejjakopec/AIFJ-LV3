<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class LexicalAnalysisController extends AbstractController
{
    const CONSTANTS = [
        'jedan',
        'dva',
        'tri',
        'cetiri',
        'pet',
        'sest',
        'sedam',
        'osam',
        'devet',
    ];

    const OPERATORS = [
        'SUM',
        'SUB',
        'EQUAL',
        'MULTI',
        'DIV',
        'HIGHER',
        'LOVER',
        '=',
    ];

    const KEYWORDS = [
        'AKO',
        'FUNKCIJA',
        'REZULTAT',
        'INACE',
    ];

    const SEPARATORS = [
        ',',
        '.',
    ];

    const COMMENT = '-';

    public array $results = ['comments' => 0];

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    #[Route('/lexical', name: 'lexical')]
    public function entry()
    {
        $projectRoot = $this->appKernel->getProjectDir();
        $file = file($projectRoot . '/input.txt');

        $output = '';

        foreach ($file as $lineNumber => $line) {
            $line = rtrim($line, PHP_EOL);
            $lineCount = ['comments' => 0];
            preg_match_all('/\$[a-zA-Z\d]+/', $line, $matches);

            if (array_key_exists(0, $matches)) {
                foreach ($matches[0] as $match) {
                    $this->results['identifiers'][$match] = ($this->results['identifiers'][$match] ?? 0) + 1;
                    $lineCount['identifiers'][$match] = ($lineCount['identifiers'][$match] ?? 0) + 1;
                    $line = str_replace($match, '', $line);
                }
            }

            if (str_starts_with($line, self::COMMENT) && str_ends_with($line, self::COMMENT)){
                $this->results['comments'] = ($this->results['comments'] ?? 0) + 1;
                $lineCount['comments']++;
                $output = $this->addToOutput($output, $lineCount, ++$lineNumber);

                continue;
            }

            foreach (self::CONSTANTS as $constant) {
                $count = substr_count($line, $constant);
                $this->results['constants'][$constant] = ($this->results['constants'][$constant] ?? 0) + $count;
                $lineCount['constants'][$constant] = ($lineCount['constants'][$constant] ?? 0) + $count;
            }

            foreach (self::OPERATORS as $operator) {
                $count = substr_count($line, $operator);
                $this->results['operators'][$operator] = ($this->results['operators'][$operator] ?? 0) + $count;
                $lineCount['operators'][$operator] = ($lineCount['operators'][$operator] ?? 0) + $count;
            }

            foreach (self::KEYWORDS as $keyword) {
                $count = substr_count($line, $keyword);
                $this->results['keyword'][$keyword] = ($this->results['keyword'][$keyword] ?? 0) + $count;
                $lineCount['keyword'][$keyword] = ($lineCount['keyword'][$keyword] ?? 0) + $count;
            }

            foreach (self::SEPARATORS as $separator) {
                $count = substr_count($line, $separator);
                $this->results['separator'][$separator] = ($this->results['separator'][$separator] ?? 0) + $count;
                $lineCount['separator'][$separator] = ($lineCount['separator'][$separator] ?? 0) + $count;
            }
            $lineCount = $this->sanitize($lineCount);
            $output = $this->addToOutput($output, $lineCount, ++$lineNumber);
        }

        $output = $this->summarize($output);

        return new Response($output);
    }

    private function addToOutput(string $output, array $lineCount, int $lineNumber): string
    {
        $output .= "<h3>LINE: $lineNumber</h3>";
        foreach ($lineCount as $type => $items) {
            $output .= "<h4>$type</h4>";
            $output .= '<ol>';
            if ('comments' === $type) {
                if (0 !== $items) {
                    $output .= "<li>[$type] &#x2192; $items</li>";
                }
            } else {
                foreach ($items as $name => $item) {
                    if (0 !== $item) {
                        $output .= "<li>[$name] &#x2192; $item</li>";
                    }
                }
            }
            $output .= '</ol>';
        }

        $output .= '</br><hr></br>';

        return $output;
    }

    private function summarize(string $output): string
    {
        $output .= "<h3>SUMMARIZE</h3>";
        foreach ($this->results as $type => $items) {
            $output .= "<h4>$type</h4>";
            $output .= '<ol>';
            if ('comments' === $type) {
                if (0 !== $items) {
                    $output .= "<li>[$type] &#x2192; $items</li>";
                }
            } else {
                foreach ($items as $name => $item) {
                    if (0 !== $item) {
                        $output .= "<li>[$name] &#x2192; $item</li>";
                    }
                }
            }
            $output .= '</ol>';
        }

        $output .= '</br><hr></br>';

        return $output;
    }

    private function sanitize(array $line): array
    {
        foreach ($line as $type => $items) {
            if ('comments' === $type && 0 === $items) {
                unset($line[$type]);
            } else {
                foreach ($items as $name => $item) {
                    if (0 === $item) {
                        unset($line[$type][$name]);
                    }
                }
            }

            if (empty($line[$type])) {
                unset($line[$type]);
            }
        }

        return $line;
    }
}