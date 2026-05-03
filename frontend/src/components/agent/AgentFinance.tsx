import { Plus, Download, Rocket, Star, Palette, ArrowUp, Check } from "lucide-react";
import { Button } from "@/components/ui/button";
import { BynCurrencyMark } from "@/components/BynCurrency";

const activeServices = [
  { icon: Star, label: "VIP", property: "ул. Притыцкого, 34", expires: "22.02.2026", daysLeft: 8, color: "text-yellow-500" },
  { icon: Rocket, label: "Турбо", property: "пр. Независимости, 89", expires: "16.02.2026", daysLeft: 2, color: "text-blue-500" },
];

const paymentHistory = [
  { date: "15.02.2026", desc: "VIP-размещение (7 дней)", amount: -50, status: "paid" },
  { date: "10.02.2026", desc: "Пополнение баланса", amount: 200, status: "paid" },
  { date: "08.02.2026", desc: "Турбо (1 день)", amount: -10, status: "paid" },
  { date: "01.02.2026", desc: "Поднятие в топ (3 дня)", amount: -20, status: "paid" },
  { date: "25.01.2026", desc: "Пополнение баланса", amount: 100, status: "paid" },
];

const plans = [
  { name: "Бесплатно", priceNum: "0", objects: "до 3 объектов", current: false },
  { name: "Базовый", priceNum: "20", objects: "до 10 объектов", current: false },
  { name: "Профи", priceNum: "50", objects: "до 50 объектов", current: true },
  { name: "Агентство", priceNum: "150", objects: "безлимит", current: false },
];

const services = [
  { icon: Star, name: "VIP-размещение", duration: "7 дней", priceNum: "50", color: "text-yellow-500" },
  { icon: ArrowUp, name: "Поднятие в топ", duration: "3 дня", priceNum: "20", color: "text-green-500" },
  { icon: Rocket, name: "Турбо", duration: "1 день", priceNum: "10", color: "text-blue-500" },
  { icon: Palette, name: "Выделение цветом", duration: "7 дней", priceNum: "15", color: "text-purple-500" },
];

const AgentFinance = () => {
  return (
    <div>
      <h1 className="text-2xl font-display font-bold text-foreground mb-6">Финансы и платежи</h1>

      {/* Balance */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-muted-foreground mb-1">Текущий баланс</p>
            <p className="text-3xl font-bold text-foreground inline-flex items-baseline gap-1">
              150.00 <BynCurrencyMark />
            </p>
          </div>
          <Button className="bg-gradient-primary text-primary-foreground border-0"><Plus className="w-4 h-4 mr-2" />Пополнить</Button>
        </div>
      </div>

      {/* Active services */}
      <div className="bg-card rounded-xl border border-border p-5 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4">Активные услуги</h3>
        {activeServices.length > 0 ? (
          <div className="space-y-3">
            {activeServices.map((s, i) => (
              <div key={i} className="flex items-center gap-3 p-3 rounded-lg border border-border">
                <s.icon className={`w-5 h-5 ${s.color}`} />
                <div className="flex-1">
                  <p className="text-sm font-medium text-foreground">{s.label} — {s.property}</p>
                  <p className="text-xs text-muted-foreground">до {s.expires} (осталось {s.daysLeft} дн.)</p>
                </div>
                <Button variant="outline" size="sm">Продлить</Button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-muted-foreground">Нет активных услуг</p>
        )}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Plans */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <h3 className="font-semibold text-foreground mb-4">Тарифные планы</h3>
          <div className="space-y-2">
            {plans.map((p) => (
              <div key={p.name} className={`flex items-center gap-3 p-3 rounded-lg border ${p.current ? "border-primary bg-primary/5" : "border-border"}`}>
                <div className="flex-1">
                  <p className="text-sm font-medium text-foreground">{p.name}</p>
                  <p className="text-xs text-muted-foreground">{p.objects}</p>
                </div>
                <span className="text-sm font-semibold text-foreground inline-flex items-baseline gap-1">
                  {p.priceNum} <BynCurrencyMark />/мес
                </span>
                {p.current ? (
                  <span className="text-xs text-primary font-medium flex items-center gap-1"><Check className="w-3 h-3" />Текущий</span>
                ) : (
                  <Button variant="outline" size="sm" className="h-7 text-xs">Выбрать</Button>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Extra services */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <h3 className="font-semibold text-foreground mb-4">Дополнительные услуги</h3>
          <div className="space-y-2">
            {services.map((s) => (
              <div key={s.name} className="flex items-center gap-3 p-3 rounded-lg border border-border hover:border-primary/30 transition-colors">
                <s.icon className={`w-5 h-5 ${s.color}`} />
                <div className="flex-1">
                  <p className="text-sm font-medium text-foreground">{s.name}</p>
                  <p className="text-xs text-muted-foreground">{s.duration}</p>
                </div>
                <span className="text-sm font-semibold text-foreground inline-flex items-baseline gap-1">
                  {s.priceNum} <BynCurrencyMark />
                </span>
                <Button variant="outline" size="sm" className="h-7 text-xs">Купить</Button>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Payment history */}
      <div className="bg-card rounded-xl border border-border p-5 shadow-card">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-foreground">История платежей</h3>
          <Button variant="ghost" size="sm"><Download className="w-3.5 h-3.5 mr-1" />Скачать отчёт</Button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-xs text-muted-foreground border-b border-border">
                <th className="text-left py-2">Дата</th>
                <th className="text-left py-2">Описание</th>
                <th className="text-right py-2">Сумма</th>
                <th className="text-right py-2">Статус</th>
              </tr>
            </thead>
            <tbody>
              {paymentHistory.map((p, i) => (
                <tr key={i} className="border-b border-border last:border-0">
                  <td className="py-3 text-muted-foreground">{p.date}</td>
                  <td className="py-3 text-foreground">{p.desc}</td>
                  <td className={`py-3 text-right font-medium ${p.amount > 0 ? "text-green-600" : "text-foreground"}`}>
                    <span className="inline-flex items-baseline gap-1 justify-end">
                      {p.amount > 0 ? "+" : ""}{p.amount.toFixed(2)} <BynCurrencyMark />
                    </span>
                  </td>
                  <td className="py-3 text-right"><span className="text-xs text-green-600">✓ Оплачено</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default AgentFinance;
