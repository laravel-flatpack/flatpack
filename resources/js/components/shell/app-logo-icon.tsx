export function AppLogoIcon(props: { src: string; className?: string }) {
    return (
        <div
            className={`logo ${props.className ?? ''}`}
            style={{ maskImage: `url(${props.src})` }}
        ></div>
    );
}
