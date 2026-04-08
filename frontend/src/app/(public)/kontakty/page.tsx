import type { Metadata } from 'next';
import { ContactForm } from '@/features/contact/components/ContactForm';

export const metadata: Metadata = {
    title: 'Обратная связь — RNB.by',
    description: 'Свяжитесь с командой RNB.by: вопросы, предложения и помощь по сервису.',
};

export default function ContactPage() {
    return (
        <section className="relative overflow-hidden bg-background py-20">
            <div className="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
            <div className="absolute bottom-0 right-1/4 h-64 w-64 rounded-full bg-accent blur-3xl" />
            <div className="container relative mx-auto px-4">
                <ContactForm />
            </div>
        </section>
    );
}
