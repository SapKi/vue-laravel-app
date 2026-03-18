<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Clean report — low risk, suggested approve
        Item::create([
            'title'           => 'Bug report: login page freezes on Safari',
            'content'         => 'When I try to log in using Safari 17 on macOS Sonoma, the page becomes unresponsive after submitting credentials. Reproducible 100% of the time. Chrome works fine.',
            'status'          => 'pending',
            'risk_score'      => 0,
            'flags'           => null,
            'suggested_action'=> 'approve',
            'reviewer_note'   => null,
            'reviewed_at'     => null,
        ]);

        // Spam submission — high risk, suggested reject
        Item::create([
            'title'           => 'BUY NOW - LIMITED OFFER - FREE MONEY',
            'content'         => 'Click here to earn money fast! Guaranteed prize winner! Buy now act now no risk 100% free cash bonus!',
            'status'          => 'pending',
            'risk_score'      => 85,
            'flags'           => ['spam', 'caps_heavy'],
            'suggested_action'=> 'reject',
            'reviewer_note'   => null,
            'reviewed_at'     => null,
        ]);

        // Feature request — clean, approved by reviewer
        Item::create([
            'title'           => 'Feature request: export queue to CSV',
            'content'         => 'It would be helpful to export the current review queue to a CSV file so our team can do offline analysis and track decision trends over time.',
            'status'          => 'approved',
            'risk_score'      => 0,
            'flags'           => null,
            'suggested_action'=> 'approve',
            'reviewer_note'   => 'Good idea, added to the backlog.',
            'reviewed_at'     => now()->subDays(2),
        ]);

        // Offensive content — rejected by reviewer
        Item::create([
            'title'           => 'This app is stupid and worthless',
            'content'         => 'Whoever built this is an idiot. Shut up and fix your broken garbage product already.',
            'status'          => 'rejected',
            'risk_score'      => 60,
            'flags'           => ['offensive'],
            'suggested_action'=> 'reject',
            'reviewer_note'   => 'Violates community guidelines — abusive language.',
            'reviewed_at'     => now()->subDay(),
        ]);
    }
}
