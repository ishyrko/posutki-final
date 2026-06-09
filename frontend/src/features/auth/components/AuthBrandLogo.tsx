import Image from 'next/image';
import Link from 'next/link';

type AuthBrandLogoProps = {
    /** На тёмном фоне — прозрачный вариант логотипа */
    variant?: 'default' | 'transparent';
    className?: string;
};

export function AuthBrandLogo({ variant = 'default', className }: AuthBrandLogoProps) {
    const src = variant === 'transparent' ? '/brand/logo-transparent.png' : '/brand/logo.png';

    return (
        <Link href="/" className={className ?? 'inline-block'}>
            <Image
                src={src}
                alt="posutki.by"
                width={0}
                height={0}
                sizes="160px"
                priority
                className="mx-auto h-10 w-auto object-contain"
            />
            <span className="sr-only">posutki.by</span>
        </Link>
    );
}
