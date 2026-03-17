<?php

namespace Tests\Unit;

use App\Services\ModerationService;
use PHPUnit\Framework\TestCase;

class ModerationServiceTest extends TestCase
{
    private ModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ModerationService();
    }

    public function test_clean_content_gets_low_score_and_approve_suggestion(): void
    {
        $result = $this->service->analyze(
            'Meeting notes from today',
            'We discussed the quarterly roadmap and agreed on next steps for the team.'
        );

        $this->assertEquals(0, $result['risk_score']);
        $this->assertEmpty($result['flags']);
        $this->assertEquals('approve', $result['suggested_action']);
    }

    public function test_spam_keywords_increase_score_and_set_spam_flag(): void
    {
        $result = $this->service->analyze(
            'Buy now limited offer',
            'Click here to earn money fast and win a prize!'
        );

        $this->assertGreaterThan(20, $result['risk_score']);
        $this->assertContains('spam', $result['flags']);
    }

    public function test_offensive_keywords_increase_score_and_set_offensive_flag(): void
    {
        $result = $this->service->analyze(
            'Complaint',
            'This is stupid and the person who made it is an idiot.'
        );

        $this->assertContains('offensive', $result['flags']);
        $this->assertGreaterThan(20, $result['risk_score']);
    }

    public function test_caps_heavy_text_gets_flagged(): void
    {
        $result = $this->service->analyze(
            'IMPORTANT NOTICE',
            'PLEASE READ THIS VERY CAREFULLY BECAUSE IT IS EXTREMELY URGENT.'
        );

        $this->assertContains('caps_heavy', $result['flags']);
    }

    public function test_url_in_content_adds_has_urls_flag(): void
    {
        $result = $this->service->analyze(
            'Check this out',
            'Visit https://example.com for more details.'
        );

        $this->assertContains('has_urls', $result['flags']);
    }

    public function test_very_short_content_gets_flagged(): void
    {
        $result = $this->service->analyze('Hi', 'ok');

        $this->assertContains('very_short', $result['flags']);
    }

    public function test_high_risk_score_suggests_reject(): void
    {
        $result = $this->service->analyze(
            'BUY NOW WIN PRIZE',
            'CLICK HERE free money guaranteed no risk act now buy now earn money fast!'
        );

        $this->assertEquals('reject', $result['suggested_action']);
        $this->assertGreaterThanOrEqual(35, $result['risk_score']);
    }

    public function test_score_is_capped_at_100(): void
    {
        $result = $this->service->analyze(
            'BUY NOW WIN FREE MONEY CLICK HERE',
            'STUPID IDIOT HATE KILL DIE. Buy now click here free money winner prize guaranteed act now earn money!'
        );

        $this->assertLessThanOrEqual(100, $result['risk_score']);
    }
}
