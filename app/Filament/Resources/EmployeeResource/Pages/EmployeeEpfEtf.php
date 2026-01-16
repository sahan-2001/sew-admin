<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\EpfEtfGroup;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class EmployeeEpfEtf extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = EmployeeResource::class;
    protected static string $view = 'filament.resources.employee-resource.pages.employee-epf-etf';
    protected static bool $shouldRegisterNavigation = false;

    /* ---------------- Header Actions ---------------- */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New EPF/ETF Group')
                ->icon('heroicon-o-plus')
                ->form(self::formSchema())
                ->action(function (array $data) {
                    // automatically set site_id
                    $data['site_id'] = session('site_id');
                    EpfEtfGroup::create($data);
                }),
        ];
    }

    /* ---------------- Table ---------------- */
    protected function getTableQuery(): Builder
    {
        return EpfEtfGroup::query()->where('site_id', session('site_id'));
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('epf_employee_percentage')->label('EPF Emp %'),
            Tables\Columns\TextColumn::make('epf_employer_percentage')->label('EPF Employer %'),
            Tables\Columns\TextColumn::make('etf_employer_percentage')->label('ETF %'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->form(self::formSchema()),

            Tables\Actions\DeleteAction::make(),
        ];
    }

    /* ---------------- Form Schema ---------------- */
    public static function formSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('epf_employee_percentage')
                ->numeric()
                ->required()
                ->suffix('%'),

            Forms\Components\TextInput::make('epf_employer_percentage')
                ->numeric()
                ->required()
                ->suffix('%'),

            Forms\Components\TextInput::make('etf_employer_percentage')
                ->numeric()
                ->required()
                ->suffix('%'),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            Forms\Components\Textarea::make('remarks')
                ->columnSpanFull(),
        ];
    }
}
