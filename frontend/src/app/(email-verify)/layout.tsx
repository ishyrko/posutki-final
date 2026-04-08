/**
 * Routes that must work while the user is logged in (e.g. confirming a new email from the cabinet).
 * The (auth) layout redirects authenticated users to /kabinet, which would block this flow.
 */
export default function EmailVerifyLayout({ children }: { children: React.ReactNode }) {
    return <>{children}</>;
}
