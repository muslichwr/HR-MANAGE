<?php

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class PdfHelper
{
    /**
     * Create a new PDF document
     *
     * @param string $view
     * @param array $data
     * @param string $paperSize
     * @return Dompdf
     */
    public static function loadView($view, $data = [], $paperSize = 'a4')
    {
        $html = View::make($view, $data)->render();
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paperSize);
        
        return $dompdf;
    }
    
    /**
     * Generate and return the PDF as a string
     *
     * @param string $view
     * @param array $data
     * @param string $paperSize
     * @return string
     */
    public static function generatePdf($view, $data = [], $paperSize = 'a4')
    {
        $dompdf = self::loadView($view, $data, $paperSize);
        $dompdf->render();
        
        return $dompdf->output();
    }
} 