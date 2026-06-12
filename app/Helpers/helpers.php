<?php

use App\Helpers\Translator;

if (! function_exists('lang_trans')) {
    /**
     * Helper to translate text using Google Translate if needed.
     */
    function lang_trans(string $text, ?string $to = null): string
    {
        return Translator::translate($text, $to);
    }
}
