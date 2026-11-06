<?php
namespace App\Support;
class AccountPasswords {
    public static function rules(): array {
        return ['bail','required','string','confirmed','min:15','max:128',new \App\Rules\NotBreachedPassword];
    }
}
