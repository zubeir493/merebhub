<?php

namespace App\Filament\Resources\AppSubmissions\Pages;

use App\Actions\ApproveSubmission;
use App\Enums\AppSubmissionStatus;
use App\Filament\Resources\AppSubmissions\AppSubmissionResource;
use App\Models\AppSubmission;
use App\Models\Author;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewAppSubmission extends ViewRecord
{
    protected static string $resource = AppSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->color('success')
                ->icon(Heroicon::CheckCircle)
                ->visible(fn (AppSubmission $record): bool => $record->status === AppSubmissionStatus::Pending)
                ->modalHeading('Approve and publish submission')
                ->modalDescription('This creates the product, initial build, and matching WooCommerce listing.')
                ->schema([
                    Select::make('author_id')
                        ->label('Existing author')
                        ->options(Author::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('new_author_name')
                        ->label('Or create author')
                        ->required(fn ($get): bool => blank($get('author_id')))
                        ->maxLength(255),
                    Textarea::make('new_author_bio')
                        ->label('New author bio')
                        ->rows(3),
                    TextInput::make('tagline')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('category')
                        ->required()
                        ->datalist(['Developer tools', 'Productivity', 'Business', 'Design', 'Marketing', 'Data & analytics', 'Security', 'Utilities', 'Games']),
                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ETB'),
                    TextInput::make('compare_at_price')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('ETB'),
                    TextInput::make('version_number')
                        ->default('1.0.0')
                        ->required(),
                    FileUpload::make('cover_path')
                        ->image()
                        ->disk('public')
                        ->directory('product-covers')
                        ->imageEditor()
                        ->required(),
                    TextInput::make('keygen_policy_id')
                        ->label('Keygen policy ID')
                        ->helperText('Leave empty to use KEYGEN_POLICY_ID from the environment.'),
                    Toggle::make('is_featured')
                        ->label('Feature on homepage'),
                ])
                ->action(function (array $data, AppSubmission $record, ApproveSubmission $approve): void {
                    try {
                        $product = $approve->handle($record, auth()->user(), $data);

                        Notification::make()
                            ->title("{$product->name} is live")
                            ->body("WooCommerce product #{$product->wc_product_id} was created.")
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->title('Approval failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('reject')
                ->color('danger')
                ->icon(Heroicon::XCircle)
                ->visible(fn (AppSubmission $record): bool => $record->status === AppSubmissionStatus::Pending)
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')
                        ->label('Reason for rejection')
                        ->required()
                        ->minLength(10),
                ])
                ->action(function (array $data, AppSubmission $record): void {
                    $record->update([
                        'status' => AppSubmissionStatus::Rejected,
                        'reviewed_by' => auth()->id(),
                        'rejection_reason' => $data['reason'],
                    ]);

                    Notification::make()->title('Submission rejected')->success()->send();
                }),
        ];
    }
}
