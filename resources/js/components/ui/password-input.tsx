import { Input } from '@/components/ui/input';
import { Eye, EyeOff } from 'lucide-react';
import { InputHTMLAttributes, useState } from 'react';

export function PasswordInput(props: InputHTMLAttributes<HTMLInputElement>) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <Input
                {...props}
                type={visible ? 'text' : 'password'}
                className={`pr-10 ${props.className ?? ''}`}
            />
            <button
                type="button"
                onClick={() => setVisible(!visible)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                tabIndex={-1}
                aria-label={visible ? 'Hide password' : 'Show password'}
            >
                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
}
