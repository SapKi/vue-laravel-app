<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'           => $this->faker->sentence(4),
            'content'         => $this->faker->paragraph(2),
            'status'          => 'pending',
            'risk_score'      => $this->faker->numberBetween(0, 30),
            'flags'           => null,
            'suggested_action' => 'approve',
            'reviewer_note'   => null,
            'reviewed_at'     => null,
        ];
    }
}
