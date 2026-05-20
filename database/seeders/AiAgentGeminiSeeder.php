<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\Activity;
use App\Enums\InputType;
use App\Models\AiAgent;
use App\Models\GatewayOption;
use Illuminate\Database\Seeder;

class AiAgentGeminiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $exists = AiAgent::where('slug', 'gemini')->exists();
        if ($exists) {
            return;
        }

        $ai = AiAgent::create([
            'name'   => 'Gemini',
            'slug'   => 'gemini',
            'misc'   => null,
            'status' => Status::INACTIVE
        ]);

        GatewayOption::create([
            'model_id'   => $ai->id,
            'model_type' => 'App\Models\AiAgent',
            'option'     => 'gemini_api_key',
            'value'      => '',
            'type'       => InputType::TEXT,
            'activities' => json_encode('')
        ]);

        GatewayOption::create([
            'model_id'   => $ai->id,
            'model_type' => 'App\Models\AiAgent',
            'option'     => 'gemini_status',
            'value'      => (string)Activity::DISABLE,
            'type'       => InputType::SELECT,
            'activities' => json_encode([
                Activity::ENABLE => "enable",
                Activity::DISABLE => "disable",
            ])
        ]);
    }
}
