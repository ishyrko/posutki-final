import Image from 'next/image';
import Link from 'next/link';

export function AuthBrandLogo() {
    return (
        <Link href="/" className="inline-block">
            <Image
                src="/brand/logo.png"
                alt="posutki.by"
                width={180}
                height={40}
                sizes="160px"
                priority
                className="h-10 w-auto object-contain mx-auto"
            />
            <span className="sr-only">posutki.by</span>
        </Link>
    );
}
