import useIsDark from "@/hooks/useIsDark";

const LIGHT_LOGO = "/ronge-aka-golpo-logo.svg";
const DARK_LOGO = "/ronge-aka-golpo-logo-dark.svg";

export default function Logo({ className = "h-9 w-auto" }) {
    const isDark = useIsDark();

    return (
        <img
            src={isDark ? DARK_LOGO : LIGHT_LOGO}
            alt="Ronge Aka Golpo"
            width={582}
            height={200}
            className={className}
        />
    );
}
