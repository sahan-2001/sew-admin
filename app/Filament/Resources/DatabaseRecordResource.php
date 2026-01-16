<?php
// app/Filament/Resources/DatabaseRecordResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\DatabaseRecordResource\Pages;
use App\Models\DatabaseRecord;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DynamicModelImport;
use App\Exports\DynamicModelExport;
use Illuminate\Support\Facades\Auth;


class DatabaseRecordResource extends Resource
{
    protected static ?string $model = DatabaseRecord::class;
    protected static ?string $navigationLabel = 'Database Records';
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view database records') ?? false;
    }
    
    public static function table(Table $table): Table
    {
        $models = collect(config('database-records.models'));

        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('created_by')
                    ->label('Created By')
                    ->numeric()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])

            ->actions([

                // IMPORT
                Action::make('import')
                    ->label('Import Excel / CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Select::make('model_key')
                            ->label('Select Table')
                            ->options($models->mapWithKeys(fn ($m, $k) => [$k => $m['label']]))
                            ->reactive()
                            ->required(),

                        CheckboxList::make('fields')
                            ->label('Select Fields To Import')
                            ->options(fn ($get) =>
                                config("database-records.models.{$get('model_key')}.fields") ?? []
                            )
                            ->columns(2)
                            ->required(),

                        FileUpload::make('file')
                            ->label('Upload Excel / CSV')
                            ->disk('local')
                            ->directory('imports')
                            ->storeFiles()     
                            ->preserveFilenames()
                            ->required()
                            ->previewable(false)
                            ->imageEditor(false)
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',

                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'text/comma-separated-values',
                                'application/octet-stream', 
                            ])
                            ->maxSize(10240),

                    ])
                    ->action(function ($data) {
    $config = config("database-records.models.{$data['model_key']}");

    $filePath = storage_path('app/' . $data['file']);

    Excel::import(
        new DynamicModelImport(
            $config['model'],
            $data['fields']
        ),
        $filePath
    );

    Notification::make()
        ->title('Import Successful')
        ->body('Only selected fields were imported.')
        ->success()
        ->send();
}),


                // EXPORT
                Action::make('export')
                    ->label('Export Excel / CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Select::make('model_key')
                            ->label('Select Table')
                            ->options($models->mapWithKeys(fn ($m, $k) => [$k => $m['label']]))
                            ->reactive()
                            ->required(),

                        CheckboxList::make('fields')
                            ->label('Select Fields To Export')
                            ->options(fn ($get) =>
                                config("database-records.models.{$get('model_key')}.fields") ?? []
                            )
                            ->columns(2)
                            ->required(),
                    ])
                    ->action(function ($data) {
                        $config = config("database-records.models.{$data['model_key']}");

                        return Excel::download(
                            new DynamicModelExport(
                                $config['model'],
                                $data['fields']
                            ),
                            $data['model_key'] . '.xlsx'
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatabaseRecords::route('/'),
        ];
    }
}
