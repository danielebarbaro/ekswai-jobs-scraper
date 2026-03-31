<x-filament-panels::page>
    <form wire:submit="updateProfile">
        <x-filament::section heading="Profile Information">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-filament::input.wrapper label="Name">
                    <x-filament::input type="text" wire:model="name" required />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper label="Email">
                    <x-filament::input type="email" wire:model="email" required />
                </x-filament::input.wrapper>
            </div>

            @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            @error('email') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror

            <div class="mt-4">
                <x-filament::button type="submit">
                    Save Profile
                </x-filament::button>
            </div>
        </x-filament::section>
    </form>

    <form wire:submit="updatePassword" class="mt-6">
        <x-filament::section heading="Update Password">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-filament::input.wrapper label="Current Password">
                    <x-filament::input type="password" wire:model="current_password" required />
                </x-filament::input.wrapper>

                <div></div>

                <x-filament::input.wrapper label="New Password">
                    <x-filament::input type="password" wire:model="password" required />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper label="Confirm Password">
                    <x-filament::input type="password" wire:model="password_confirmation" required />
                </x-filament::input.wrapper>
            </div>

            @error('current_password') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            @error('password') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror

            <div class="mt-4">
                <x-filament::button type="submit">
                    Update Password
                </x-filament::button>
            </div>
        </x-filament::section>
    </form>
</x-filament-panels::page>
