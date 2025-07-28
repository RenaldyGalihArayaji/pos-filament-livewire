<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;


class Pos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pos';

    protected static ?string $navigationLabel = 'Halaman Kasir';

    protected static ?string $title = 'Halaman Kasir';

}
