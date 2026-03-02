<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->rule('confirmed')
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn(?string $state): bool => filled($state)),
                TextInput::make('password_confirmation')
                    ->label('Подтвердите пароль')
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->required(fn(string $context): bool => $context === 'create'),

            ]);
    }
}
