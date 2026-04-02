import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useAppearance } from '@/hooks/use-appearance';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { type User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { LogOut, Monitor, Moon, Palette, Settings, Sun } from 'lucide-react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();
    const { appearance, updateAppearance } = useAppearance();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    const themes = [
        { value: 'light' as const, icon: Sun },
        { value: 'dark' as const, icon: Moon },
        { value: 'system' as const, icon: Monitor },
    ];

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div className="flex items-center gap-1 px-2 py-1.5">
                <Palette className="mr-1 size-4 text-muted-foreground" />
                <span className="mr-auto text-sm text-muted-foreground">Theme</span>
                {themes.map(({ value, icon: Icon }) => (
                    <button
                        key={value}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            'rounded-md p-1.5 transition-colors',
                            appearance === value
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent/50 hover:text-accent-foreground',
                        )}
                    >
                        <Icon className="size-4" />
                    </button>
                ))}
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full"
                        href={edit()}
                        as="button"
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Settings
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />

            <DropdownMenuItem asChild>
                <Link
                    className="block w-full"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Log out
                </Link>
            </DropdownMenuItem>
        </>
    );
}
