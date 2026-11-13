<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Throwable;

class InvoiceTextExtractor
{
    public function extract(string $absolutePath): ?string
    {
        try {
            $text = trim((new Parser())->parseFile($absolutePath)->getText());

            return $text === '' ? null : mb_substr($text, 0, 100000);
        } catch (Throwable) {
            return null;
        }
    }
}