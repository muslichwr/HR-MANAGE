<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Dompdf\Dompdf loadView(string $view, array $data = [], string $paperSize = 'a4')
 * @method static string generatePdf(string $view, array $data = [], string $paperSize = 'a4')
 * 
 * @see \App\Helpers\PdfHelper
 */
class PDF extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pdf-helper';
    }
} 