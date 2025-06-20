<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndOfDayReportResource\Pages;
use App\Filament\Resources\EndOfDayReportResource\RelationManagers;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
use Filament\Forms;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Forms\Components\Tab;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Modal;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class EndOfDayReportResource extends Resource
{
    protected static ?string $model = EndOfDayReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'End of Day Report';
    protected static ?string $navigationGroup = 'Daily Production';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Tabs::make('Enter Operation')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Operation Selection')
                        ->schema([
                            Section::make()
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('operated_date')
    ->label('Operation Date')
    ->required()
    ->reactive()
    ->afterStateUpdated(function (callable $set, $state) {
        $ids = \App\Models\EnterPerformanceRecord::whereDate('operation_date', $state)
            ->pluck('id')
            ->toArray();

        \Log::info('Matching IDs:', $ids);

        $set('matching_record_ids', implode(', ', $ids));
    }),

Textarea::make('matching_record_ids')
    ->label('Matching Record IDs')
    ->disabled()
    ->dehydrated(false)
    ->rows(3)
    ->columnSpan(2),
                                ]),
                        ]),
                ]),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListEndOfDayReports::route('/'),
            'create' => Pages\CreateEndOfDayReport::route('/create'),
            'edit' => Pages\EditEndOfDayReport::route('/{record}/edit'),
        ];
    }
}
