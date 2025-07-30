<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Filament RichEditor component generates S3 url with X-Amz-* parameters used
        // for authentication despite the bucket is public.
        // The authentication expires after 5 minutes resulting in a 403 error when accessing
        // to the url (to the media).
        // The 2 lines below remove the parameters from the url to avoid this.
        $data['body'] = preg_replace('/(X-Amz-[^=]*=[^&"]*)(&amp;)/', '', $data['body']);
        $data['body'] = preg_replace('/(X-Amz-[^=]*=[^&"]*)/', '', $data['body']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
