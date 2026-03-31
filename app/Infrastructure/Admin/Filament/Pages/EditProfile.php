<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $title = 'Profile';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.edit-profile';

    public ?string $name = '';

    public ?string $email = '';

    public ?string $current_password = '';

    public ?string $password = '';

    public ?string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::default(), 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        Notification::make()
            ->title('Password updated')
            ->success()
            ->send();
    }
}
