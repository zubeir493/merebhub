<?php

namespace App\Filament\Resources\AppSubmissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('submitter_name'),
                TextEntry::make('submitter_email'),
                TextEntry::make('app_name'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('suggested_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('platform'),
                TextEntry::make('category')->placeholder('-'),
                TextEntry::make('fulfillment_type')->badge(),
                TextEntry::make('payment_model')->badge(),
                TextEntry::make('billing_interval')->badge()->placeholder('-'),
                TextEntry::make('trial_days')->suffix(' days')->placeholder('-'),
                TextEntry::make('demo_url')->url(fn ($state): ?string => $state)->openUrlInNewTab()->placeholder('-'),
                TextEntry::make('attachments.original_name')
                    ->label('Attachments')
                    ->listWithLineBreaks()
                    ->placeholder('No attachments')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('-'),
                TextEntry::make('linkedAuthor.name')
                    ->label('Linked author')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
