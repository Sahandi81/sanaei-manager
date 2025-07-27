<?php


use Illuminate\Console\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Translation\Translator;

function tr_helper(string $lang, ?string $key, ?string $attr = null): \Illuminate\Foundation\Application|array|string|Translator|Application|null
{
    $msg = $lang.'.'.$key;
    $str = trans($msg, [], Config::get('app.locale'));
    if (str_contains($str, ($msg))){
        $str = $key ?? 'UNDEFINED';
    }
    if (!is_null($attr)){
        $replacedText = tr_helper('validation', 'attributes.' . $attr);
        if (str_contains($replacedText, $attr)){
            $str = str_replace('?attr',  $attr, $str);
        }else{
            $str = str_replace('?attr', $replacedText, $str);
        }
    }
    return $str;
}
