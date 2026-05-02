declare module "sanitize-html" {
    export interface IOptions {
        allowedTags?: string[];
        allowedAttributes?: Record<string, string[]>;
    }

    export interface Defaults {
        allowedTags: string[];
        allowedAttributes: Record<string, string[]>;
    }

    interface SanitizeHtml {
        (dirty: string, options?: IOptions): string;
        defaults: Defaults;
    }

    const sanitizeHtml: SanitizeHtml;
    export default sanitizeHtml;
}
