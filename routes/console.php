<?php

use App\Services\ShopCatalogImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shop:import-astro-catalog {--dry-run : Показать, что будет перенесено, без записи в БД} {--truncate : Очистить shop_* каталог перед повторным импортом}', function (ShopCatalogImportService $service) {
    $preview = $service->preview();

    $this->info('Источник Astro-x:');
    $this->line("  categories: {$preview['source']['categories']}");
    $this->line("  products: {$preview['source']['products']}");
    $this->line("  images: {$preview['source']['images']}");

    $this->info('Целевой каталог Мёд:');
    $this->line("  categories: {$preview['target']['categories']}");
    $this->line("  products: {$preview['target']['products']}");
    $this->line("  images: {$preview['target']['images']}");

    if ($this->option('dry-run')) {
        $this->comment('Dry run завершен. Изменения в БД не вносились.');

        return Command::SUCCESS;
    }

    try {
        $result = $service->execute((bool) $this->option('truncate'));
    } catch (\RuntimeException $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Импорт завершен.');
    $this->line("  imported categories: {$result['imported']['categories']}");
    $this->line("  imported products: {$result['imported']['products']}");
    $this->line("  imported images: {$result['imported']['images']}");
    $this->line("  target categories: {$result['target_after']['categories']}");
    $this->line("  target products: {$result['target_after']['products']}");
    $this->line("  target images: {$result['target_after']['images']}");

    if ($result['truncate_applied']) {
        $this->comment('Перед импортом каталог Мёд был очищен.');
    }

    return Command::SUCCESS;
})->purpose('Перенести категории и товары из каталога Astro-x в shop_* каталог Мёд');
