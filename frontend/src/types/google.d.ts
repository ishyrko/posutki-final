declare namespace google.accounts {
    interface IdConfiguration {
        client_id: string;
        callback: (response: { credential: string; select_by?: string }) => void;
        auto_select?: boolean;
        cancel_on_tap_outside?: boolean;
        ux_mode?: 'popup' | 'redirect';
        login_uri?: string;
    }

    interface Id {
        initialize(config: IdConfiguration): void;
        prompt(momentListener?: (notification: { isNotDisplayed: () => boolean; isSkippedMoment: () => boolean }) => void): void;
        renderButton(parent: HTMLElement, options: Record<string, unknown>): void;
        disableAutoSelect(): void;
    }

    const id: Id;
}

interface Window {
    google?: {
        accounts: {
            id: google.accounts.Id;
        };
    };
}
