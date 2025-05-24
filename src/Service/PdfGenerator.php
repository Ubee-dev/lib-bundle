<?php

namespace Khalil1608\LibBundle\Service;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator 
{
    public function htmlToPdfContent(string $htmlContent): string
    {
        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true)
            ->set('isPhpEnabled', true)
            ->set('isRemoteEnabled', true)
        ;
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait')
            ->render();
        return $dompdf->output();
    }

  
}