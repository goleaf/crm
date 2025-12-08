<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OCRDocumentResource\Pages;
use App\Models\OCRDocument;
use App\Services\OCR\OCRService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OCRDocumentResource extends Resource
{
    protected static ?string $model = OCRDocument::class;

    protected static ?string $modelLabel = 'OCR Document';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'OCR & Docs';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Document Upload')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Document')
                            ->required()
                            ->disk('local')
                            ->directory('ocr/uploads')
                            ->acceptedFileTypes(config('ocr.upload.accepted_types', ['image/jpeg', 'image/png', 'application/pdf']))
                            ->maxSize(config('ocr.upload.max_size_kb', 10240))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->formatStateUsing(fn($state) => $state ? number_format($state * 100, 1) . '%' : '-')
                    ->color(fn(OCRDocument $record) => $record->confidence_color),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Process OCR')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('primary')
                    ->action(function (OCRDocument $record, OCRService $ocrService) {
                        try {
                            $record->markAsProcessing();

                            // For demo purposes, we process synchronously here. 
                            // In production, dispatch a job.
                            $fullPath = Storage::disk('local')->path($record->file_path);

                            $result = $ocrService->process($fullPath);

                            if ($result->isParsed) {
                                $record->markAsCompleted(
                                    data: ['text' => $result->text, 'raw' => $result->rawResponse],
                                    confidence: $result->confidence,
                                    processingTime: 0.0 // Timer could be added
                                );

                                \Filament\Notifications\Notification::make()
                                    ->title('OCR Completed')
                                    ->success()
                                    ->send();
                            } else {
                                $record->markAsFailed('No text extracted');
                                \Filament\Notifications\Notification::make()
                                    ->title('OCR Failed')
                                    ->body('No text extracted from document')
                                    ->danger()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            $record->markAsFailed($e->getMessage());
                            \Filament\Notifications\Notification::make()
                                ->title('Processing Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn(OCRDocument $record) => $record->status !== 'processing'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListOCRDocuments::route('/'),
            'create' => Pages\CreateOCRDocument::route('/create'),
            'view' => Pages\ViewOCRDocument::route('/{record}'),
            'edit' => Pages\EditOCRDocument::route('/{record}/edit'),
        ];
    }
}
