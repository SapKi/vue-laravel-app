<?php

namespace App\Services;

class ModerationService
{
    private const SPAM_KEYWORDS = [
        'buy now', 'click here', 'free money', 'winner', 'prize',
        'limited offer', 'earn money', 'make money fast', 'act now',
        'guaranteed', 'no risk', '100% free', 'cash bonus',
    ];

    private const OFFENSIVE_KEYWORDS = [
        'hate', 'kill', 'die', 'stupid', 'idiot', 'moron',
        'dumb', 'shut up', 'loser', 'worthless',
    ];

    public function analyze(string $title, string $content): array
    {
        $fullText = strtolower($title . ' ' . $content);
        $flags = [];
        $score = 0;

        // Spam keyword check (up to 35 points)
        $spamHits = $this->countKeywordHits($fullText, self::SPAM_KEYWORDS);
        if ($spamHits > 0) {
            $flags[] = 'spam';
            $score += min(35, $spamHits * 12);
        }

        // Offensive keyword check (up to 30 points)
        $offensiveHits = $this->countKeywordHits($fullText, self::OFFENSIVE_KEYWORDS);
        if ($offensiveHits > 0) {
            $flags[] = 'offensive';
            $score += min(30, $offensiveHits * 15);
        }

        // Excessive caps check (>40% uppercase letters → 20 points)
        $letters = preg_replace('/[^a-zA-Z]/', '', $title . ' ' . $content);
        if (strlen($letters) > 5) {
            $upperCount = strlen(preg_replace('/[^A-Z]/', '', $letters));
            $capsRatio = $upperCount / strlen($letters);
            if ($capsRatio > 0.4) {
                $flags[] = 'caps_heavy';
                $score += 20;
            }
        }

        // URL presence (10 points)
        if (preg_match('/https?:\/\/\S+/i', $content)) {
            $flags[] = 'has_urls';
            $score += 10;
        }

        // Very short content (<15 chars, suspicious) (5 points)
        if (strlen(trim($content)) < 15) {
            $flags[] = 'very_short';
            $score += 5;
        }

        $score = min(100, $score);

        $suggestedAction = null;
        if ($score >= 35) {
            $suggestedAction = 'reject';
        } elseif ($score === 0) {
            $suggestedAction = 'approve';
        }

        return [
            'risk_score' => $score,
            'flags' => $flags ?: null,
            'suggested_action' => $suggestedAction,
        ];
    }

    private function countKeywordHits(string $text, array $keywords): int
    {
        $hits = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $hits++;
            }
        }
        return $hits;
    }
}
