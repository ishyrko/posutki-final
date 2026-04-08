"use client";

import { ShieldCheck, Clock, CreditCard, Headphones } from "lucide-react";

const features = [
  { icon: ShieldCheck, title: "Проверенные объявления", desc: "Каждое объявление проходит модерацию" },
  { icon: Clock, title: "Быстрый отклик", desc: "Свяжитесь с владельцем в пару кликов" },
  { icon: CreditCard, title: "Понятные цены", desc: "Стоимость за сутки указана в объявлении" },
  { icon: Headphones, title: "Поддержка", desc: "Помощь при вопросах по сервису" },
];

const FeaturesSection = () => {
  return (
    <section className="py-16 md:py-24 bg-background">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          {features.map((f) => {
            const Icon = f.icon;
            return (
              <div key={f.title} className="text-center">
                <div className="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                  <Icon className="h-7 w-7 text-primary" />
                </div>
                <h3 className="font-display font-semibold text-foreground mb-2">{f.title}</h3>
                <p className="text-sm text-muted-foreground leading-relaxed">{f.desc}</p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default FeaturesSection;
