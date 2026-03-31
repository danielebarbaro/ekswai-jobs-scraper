import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/logo.png"
            alt="ekswai"
            {...props}
        />
    );
}
