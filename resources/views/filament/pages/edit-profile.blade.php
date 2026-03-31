<x-filament-panels::page>
    <x-filament-panels::form wire:submit="updateProfile">
        {{ $this->profileForm }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('updateProfile')
                    ->label('Save Profile')
                    ->submit('updateProfile'),
            ]"
        />
    </x-filament-panels::form>

    <x-filament-panels::form wire:submit="updatePassword">
        {{ $this->passwordForm }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('updatePassword')
                    ->label('Update Password')
                    ->submit('updatePassword'),
            ]"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
