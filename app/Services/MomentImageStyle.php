<?php
namespace App\Services;

class MomentImageStyle
{
    /** Newest first. Include reserved images so parallel jobs also count. */
    public function choose(string $preferred, array $recent): string
    {
        $recent = array_values(array_filter(array_slice($recent, 0, 12), fn ($s) => in_array($s, ['sketch', 'photo'], true)));
        $sketches = count(array_filter($recent, fn ($s) => $s === 'sketch'));
        $photos = count($recent) - $sketches;
        if (abs($sketches - $photos) >= 2) return $sketches > $photos ? 'photo' : 'sketch';
        if (count($recent) >= 2 && $recent[0] === $recent[1]) return $recent[0] === 'sketch' ? 'photo' : 'sketch';
        return in_array($preferred, ['sketch', 'photo'], true) ? $preferred : 'sketch';
    }
}
