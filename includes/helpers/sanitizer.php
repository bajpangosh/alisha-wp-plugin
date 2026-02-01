<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alisha_Sanitizer
{
    public static function clean_text($text)
    {
        return sanitize_text_field($text);
    }
}
