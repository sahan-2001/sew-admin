<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;


class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $label = 'Employee';
    protected static ?string $pluralLabel = 'Employees';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        TextInput::make('employee_code')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Employee Code (Auto)'),

                        TextInput::make('first_name')->required(),
                        TextInput::make('last_name')->required(),

                        DatePicker::make('date_of_birth')->maxDate(now()),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                    ])->columns(3),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        TextInput::make('phone'),
                        TextInput::make('email')->email(),
                        Textarea::make('address')->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('HR Information')
                    ->schema([
                        DatePicker::make('joined_date')->required()->maxDate(now()),
                        Select::make('employment_type')
                            ->required()
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'temporary' => 'Temporary',
                                'intern' => 'Intern',
                            ]),

                        TextInput::make('designation')->required(),
                        TextInput::make('department')->required(),

                        TextInput::make('basic_salary')
                            ->numeric()
                            ->prefix('Rs.'),

                        Toggle::make('is_active')->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('full_name')->searchable(),
                Tables\Columns\TextColumn::make('designation')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employment_type')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('joined_date')->date(),

                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        Tables\Columns\TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_type')
                    ->options([
                        'permanent' => 'Permanent',
                        'contract' => 'Contract',
                        'temporary' => 'Temporary',
                        'intern' => 'Intern',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('handle')
                    ->label('View / Manage')
                    ->icon('heroicon-o-cog')  
                    ->url(fn (Employee $record): string => static::getUrl('handle', ['record' => $record])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'handle' => Pages\HandleEmployee::route('/{record}/handle'),
        ];
    }
}
