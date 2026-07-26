<?php

namespace App\Filament\Resources\AppSubmissions\Pages;

use App\Enums\AppSubmissionStatus;
use App\Filament\Resources\AppSubmissions\AppSubmissionResource;
use App\Models\AppSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

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
                ->modalHeading('Approve submission')
                ->modalDescription('Mark this submission ready for manual setup in WooCommerce.')
                ->requiresConfirmation()
                ->action(function (AppSubmission $record): void {
                    $record->update([
                        'status' => AppSubmissionStatus::Approved,
                        'reviewed_by' => auth()->id(),
                    ]);

                    Notification::make()->title('Submission approved')->success()->send();
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
