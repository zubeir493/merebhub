<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreSupportTicketAttachmentsAction
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<SupportTicketAttachment>
     */
    public function handle(SupportTicketMessage $message, array $files): array
    {
        if ($files === []) {
            return [];
        }

        $message->loadMissing('ticket');
        $disk = (string) config('support.attachments_disk', 'private');
        $directory = 'support/'.$message->ticket->public_id.'/'.$message->public_id;
        $storedPaths = [];
        $attachments = [];

        try {
            foreach ($files as $file) {
                $path = $file->store($directory, $disk);

                if (! is_string($path)) {
                    throw new RuntimeException('The support attachment could not be stored.');
                }

                $storedPaths[] = $path;
                $checksum = hash_file('sha256', $file->getRealPath() ?: $file->getPathname());

                if (! is_string($checksum)) {
                    throw new RuntimeException('The support attachment checksum could not be calculated.');
                }

                $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $originalName = Str::limit(str_replace(["\r", "\n"], '', $originalName), 180, '');

                $attachments[] = $message->attachments()->create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $originalName,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => (int) $file->getSize(),
                    'checksum' => $checksum,
                    'scan_status' => SupportAttachmentScanStatus::Pending,
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPaths);

            throw $exception;
        }

        return $attachments;
    }
}
