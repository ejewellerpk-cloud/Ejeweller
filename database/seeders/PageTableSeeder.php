<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Services\DefaultPagesService;
use Dipokhalder\EnvEditor\EnvEditor;
use Illuminate\Database\Seeder;

class PageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(DefaultPagesService::class)->ensure();

        $envService = new EnvEditor();
        if ($envService->getValue('DEMO') && $envService->getValue('DISPLAY_TYPE') == 'fashion') {
            $page = Page::where('slug', 'size-charts')->first();
            if (!$page) {
                Page::create([
                    'title'            => 'Size Charts',
                    'slug'             => 'size-charts',
                    'description'      => 'Note: please check it before you purchase any product.',
                    'menu_section_id'  => 1,
                    'menu_template_id' => null,
                    'status'           => \App\Enums\Status::ACTIVE,
                ]);
                $page = Page::where('slug', 'size-charts')->first();
            }

            if ($page && file_exists(public_path('/images/seeder/size-chart/size-chart.png'))) {
                $page->clearMediaCollection('page-image');
                $page->addMedia(public_path('/images/seeder/size-chart/size-chart.png'))
                    ->preservingOriginal()
                    ->toMediaCollection('page-image');
            }
        }
    }
}
