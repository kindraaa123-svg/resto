<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class Translator
{
    /**
     * Translate text to current application locale.
     */
    public static function translate(string $text, ?string $to = null): string
    {
        return $text;
        /*
        $to = $to ?? App::getLocale();
        ...
        */
    }
}
