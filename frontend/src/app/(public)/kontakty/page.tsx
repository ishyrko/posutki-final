import type { Metadata } from 'next';
import { ContactForm } from '@/features/contact/components/ContactForm';
import { ContactInfo } from '@/features/contact/components/ContactInfo';

export const metadata: Metadata = {
    title: 'Контакты — Posutki.by',
    description: 'Контакты и режим работы поддержки Posutki.by: телефон, email и форма обратной связи.',
};

export default function ContactPage() {
    return (
        <section className="relative overflow-hidden bg-background py-20">
            <div className="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
            <div className="absolute bottom-0 right-1/4 h-64 w-64 rounded-full bg-accent blur-3xl" />
            <div className="container relative mx-auto px-4">
                <div className="mx-auto max-w-3xl text-center">
                    <h1 className="mb-3 font-display text-3xl font-bold text-foreground">Контакты</h1>
                    <p className="mb-10 text-muted-foreground">
                        Свяжитесь с нами по телефону или email — или напишите через форму ниже.
                    </p>
                </div>
                <ContactInfo />
                <ContactForm />
            </div>
        </section>
    );
}
