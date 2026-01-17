<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\User;
use App\Models\SiteUser;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Notifications\Notification;

class SiteUsers extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    // ✅ THIS LINE FIXES THE ERROR
    protected static string $resource = SiteResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Sites for Users';
    protected static ?string $navigationGroup = 'System Settings';

    // ⚠️ resource pages MUST use resource view path
    protected static string $view = 'filament.resources.site-resource.pages.site-users';

    public function table(Table $table): Table
    {
        return $table
            ->query(Site::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users'),
            ])
            ->actions([
                Tables\Actions\Action::make('setUsers')
                    ->label('Set Users')
                    ->icon('heroicon-o-users')
                    ->form([
                        Forms\Components\CheckboxList::make('users')
                            ->label('Assign Users')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->columns(2)
                            ->required(),
                    ])
                    ->mountUsing(function ($form, Site $record) {
                        $form->fill([
                            'users' => SiteUser::where('site_id', $record->id)
                                ->pluck('user_id')
                                ->toArray(),
                        ]);
                    })
                    ->action(function ($data, Site $record) {
                        // Delete old assignments
                        SiteUser::where('site_id', $record->id)->delete();

                        // Save new assignments
                        foreach ($data['users'] as $userId) {
                            SiteUser::create([
                                'site_id' => $record->id,
                                'user_id' => $userId,
                            ]);
                        }

                        Notification::make()
                            ->title('Users assigned successfully')
                            ->success()
                            ->send();
                    })
                    ->after(function () {
                        // Reload the page so the table updates
                        redirect(request()->header('Referer') ?? url()->current());
                    }),
            ]);
    }
}
