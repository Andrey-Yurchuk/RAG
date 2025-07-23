<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class TextProcessingService
{
    private int $chunkSize;
    private int $chunkOverlap;

    public function __construct(array $config)
    {
        $this->chunkSize = $config['embedding']['chunk_size'];
        $this->chunkOverlap = $config['embedding']['chunk_overlap'];
    }
}
