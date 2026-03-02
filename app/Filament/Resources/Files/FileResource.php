<?php

namespace App\Filament\Resources\Files;

use App\Filament\Resources\Files\Pages\CreateFile;
use App\Filament\Resources\Files\Pages\EditFile;
use App\Filament\Resources\Files\Pages\ListFiles;
use App\Filament\Resources\Files\Schemas\FileForm;
use App\Filament\Resources\Files\Tables\FilesTable;
use App\Models\File;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Film;
    protected static ?string $recordTitleAttribute = 'original_name';
    protected static UnitEnum|string|null $navigationGroup = 'Системное';
    protected static ?string $pluralModelLabel = 'Загруженные файлы';
    protected static bool $hasTitleCaseModelLabel = false;
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return FileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiles::route('/'),
            'create' => CreateFile::route('/create'),
            'edit' => EditFile::route('/{record}/edit'),
        ];
    }
}
