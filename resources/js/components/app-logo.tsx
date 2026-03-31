import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square items-center justify-center rounded-md">
                <AppLogoIcon className="w-12" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold text-3xl" style={{ fontFamily: "'Leckerli One', cursive" }}>
                    ekswai
                </span>
            </div>
        </>
    );
}
