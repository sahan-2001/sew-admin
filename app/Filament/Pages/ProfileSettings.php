<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};
use Illuminate\Support\Facades\Hash;

class ProfileSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.profile-settings';
    protected static ?string $title = 'Profile Settings';

    public $name;
    public $email;
    public $phone_1;
    public $phone_2;
    public $nic;
    public $address_line_1;
    public $address_loine_2;
    public $city;
    public $zip_code;
    public $password;
    public $password_confirmation;

    public function mount(): void
    {
        $user = auth()->user();
        $this->form->fill($user->only([
            'name', 'email', 'phone_1', 'phone_2', 'nic',
            'address_line_1', 'address_loine_2', 'city', 'zip_code',
        ]));
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Personal Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('nic')->label('NIC'),
                ]),

                Section::make('Contact Details')
                ->columns(3)
                ->schema([
                    TextInput::make('email')->email()->required(),
                    TextInput::make('phone_1')->label('Phone 1'),
                    TextInput::make('phone_2')->label('Phone 2'),
                ]),
                
                Section::make('Address Details')
                ->columns(2)
                ->schema([
                    TextInput::make('address_line_1')->label('Address Line 1'),
                    TextInput::make('address_loine_2')->label('Address Line 2'),
                    TextInput::make('city')->label('City'),
                    TextInput::make('zip_code')->label('Zip Code'),
                ]),

                Section::make('Update Password')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->minLength(8)
                        ->nullable()
                        ->dehydrated(fn($state) => filled($state)),
                    TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->same('password')
                        ->dehydrated(false),
                ]),
            ]),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_1' => $data['phone_1'],
            'phone_2' => $data['phone_2'],
            'nic' => $data['nic'],
            'address_line_1' => $data['address_line_1'],
            'address_loine_2' => $data['address_loine_2'],
            'city' => $data['city'],
            'zip_code' => $data['zip_code'],
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $this->notify('success', 'Profile updated successfully!');
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\ButtonAction::make('save')
                ->label('Update Profile')
                ->submit('submit'),
        ];
    }
}
