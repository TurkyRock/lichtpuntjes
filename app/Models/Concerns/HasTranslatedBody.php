<?php
namespace App\Models\Concerns;
trait HasTranslatedBody {
    public function bodyFor(?string $locale=null): string {
        $locale=$locale??app()->getLocale();
        if ($this->translation_state==='ready' && hash_equals((string)$this->translation_hash,hash('sha256',$this->body))
            && in_array($locale,['nl','en'],true) && is_string($this->translations[$locale]??null)) return $this->translations[$locale];
        return $this->body;
    }
    public function isTranslated(?string $locale=null): bool {
        return $this->bodyFor($locale)!==$this->body;
    }
}
