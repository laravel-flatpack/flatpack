export default function AppLogoIcon(props: { src: string }) {
    return (
        <div className="logo" style={{ maskImage: `url(${props.src})` }}></div>
    );
}
