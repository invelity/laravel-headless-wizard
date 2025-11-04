<?php

declare(strict_types=1);

namespace Invelity\WizardPackage\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Invelity\WizardPackage\Models\WizardProgress;

class WizardProgressFactory extends Factory
{
    protected $model = WizardProgress::class;

    public function definition(): array
    {
        return [
            'wizard_id' => $this->faker->slug,
            'user_id' => null,
            'session_id' => $this->faker->uuid,
            'current_step_id' => 'step-1',
            'completed_steps' => [],
            'step_data' => [],
            'status' => 'in_progress',
            'started_at' => now(),
            'last_activity_at' => now(),
            'metadata' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
            'completed_steps' => ['step-1', 'step-2', 'step-3'],
        ]);
    }

    public function abandoned(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'abandoned',
            'last_activity_at' => now()->subDays(31),
        ]);
    }

    public function forUser(int $userId): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
            'session_id' => null,
        ]);
    }
}
