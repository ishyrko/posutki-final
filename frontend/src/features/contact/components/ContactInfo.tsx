import { Clock, Mail, Phone } from "lucide-react";
import { COMPANY } from "@/lib/company";

const contactItems = [
  {
    icon: Phone,
    label: "Телефон",
    value: COMPANY.phoneDisplay,
    href: `tel:${COMPANY.phone}`,
  },
  {
    icon: Mail,
    label: "Email",
    value: COMPANY.email,
    href: `mailto:${COMPANY.email}`,
  },
] as const;

export function ContactInfo() {
  return (
    <div className="mx-auto mb-10 max-w-3xl space-y-6">
      <div className="grid gap-4 sm:grid-cols-2">
        {contactItems.map((item) => (
          <div
            key={item.label}
            className="rounded-2xl border border-border bg-card/80 p-5 text-left shadow-card backdrop-blur"
          >
            <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
              <item.icon className="h-5 w-5 text-primary" />
            </div>
            <p className="mb-1 text-sm font-medium text-muted-foreground">{item.label}</p>
            {"href" in item && item.href ? (
              <a
                href={item.href}
                className="text-sm font-semibold text-foreground transition-colors hover:text-primary"
              >
                {item.value}
              </a>
            ) : (
              <p className="text-sm font-semibold text-foreground">{item.value}</p>
            )}
          </div>
        ))}
      </div>

      <div className="rounded-2xl border border-border bg-card/80 p-5 text-left shadow-card backdrop-blur sm:p-6">
        <div className="mb-4 flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
            <Clock className="h-5 w-5 text-primary" />
          </div>
          <h2 className="font-display text-lg font-semibold text-foreground">
            Режим работы поддержки
          </h2>
        </div>
        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-muted-foreground">Обращения через сайт и email</dt>
            <dd className="mt-0.5 font-medium text-foreground">{COMPANY.supportOnline}</dd>
          </div>
          <div>
            <dt className="text-muted-foreground">Ответ менеджера</dt>
            <dd className="mt-0.5 font-medium text-foreground">{COMPANY.workingHours}</dd>
          </div>
          <div>
            <dt className="text-muted-foreground">Выходные</dt>
            <dd className="mt-0.5 font-medium text-foreground">{COMPANY.supportDaysOff}</dd>
          </div>
        </dl>
        <p className="mt-4 text-sm text-muted-foreground">
          Сообщения, отправленные в нерабочее время, обрабатываются в ближайший рабочий день.
        </p>
      </div>
    </div>
  );
}
