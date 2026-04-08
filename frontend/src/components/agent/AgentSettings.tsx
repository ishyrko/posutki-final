import { Bell, MessageSquare, Mail, Smartphone } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";

const AgentSettings = () => {
  return (
    <div className="max-w-2xl">
      <h1 className="text-2xl font-display font-bold text-foreground mb-6">Настройки</h1>

      {/* Email notifications */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2"><Mail className="w-4 h-4" />Email-уведомления</h3>
        <div className="space-y-4">
          {[
            { label: "Новые обращения", on: true },
            { label: "Новые сообщения", on: true },
            { label: "Напоминания о показах", on: true },
            { label: "Еженедельная статистика", on: false },
            { label: "Окончание платных услуг", on: true },
          ].map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <span className="text-sm text-foreground">{item.label}</span>
              <Switch defaultChecked={item.on} />
            </div>
          ))}
        </div>
      </div>

      {/* Push */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2"><Bell className="w-4 h-4" />Push-уведомления</h3>
        <div className="space-y-4">
          {[
            { label: "Новые обращения", on: true },
            { label: "Новые сообщения", on: true },
            { label: "Напоминания о показах", on: true },
            { label: "Советы и рекомендации", on: false },
          ].map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <span className="text-sm text-foreground">{item.label}</span>
              <Switch defaultChecked={item.on} />
            </div>
          ))}
        </div>
      </div>

      {/* SMS */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2"><Smartphone className="w-4 h-4" />SMS-уведомления</h3>
        <div className="space-y-4">
          {[
            { label: "Новые обращения (только срочные)", on: true },
            { label: "Напоминания о показах", on: false },
          ].map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <span className="text-sm text-foreground">{item.label}</span>
              <Switch defaultChecked={item.on} />
            </div>
          ))}
        </div>
      </div>

      {/* Auto-reply */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2"><MessageSquare className="w-4 h-4" />Автоответы</h3>
        <div className="flex items-center justify-between mb-4">
          <span className="text-sm text-foreground">Включить автоответы</span>
          <Switch defaultChecked={true} />
        </div>
        <div className="mb-4">
          <label className="text-sm text-muted-foreground mb-1.5 block">Шаблон автоответа</label>
          <Textarea
            defaultValue={"Здравствуйте! Спасибо за интерес к объекту.\nЯ свяжусь с вами в ближайшее время.\n\nС уважением,\n{agent_name}"}
            rows={5}
          />
          <p className="text-xs text-muted-foreground mt-1">Используйте {"{agent_name}"} для подстановки имени</p>
        </div>
        <div>
          <label className="text-sm text-muted-foreground mb-1.5 block">Отправлять автоответ, если нет ответа в течение</label>
          <div className="flex items-center gap-2">
            <Input type="number" defaultValue={30} className="w-20" />
            <span className="text-sm text-muted-foreground">минут</span>
          </div>
        </div>
      </div>

      {/* Integrations */}
      <div className="bg-card rounded-xl border border-border p-6 shadow-card">
        <h3 className="font-semibold text-foreground mb-4">Интеграции</h3>
        <div className="space-y-4">
          {[
            { label: "Синхронизация с Google Calendar", on: false },
            { label: "Экспорт объектов в Onliner.by", on: false },
            { label: "Экспорт объектов в Kufar.by", on: false },
            { label: "Интеграция с CRM-системой", on: false },
          ].map((item) => (
            <div key={item.label} className="flex items-center justify-between">
              <span className="text-sm text-foreground">{item.label}</span>
              <Switch defaultChecked={item.on} />
            </div>
          ))}
        </div>
      </div>

      <div className="flex justify-end mt-6">
        <Button className="bg-gradient-primary text-primary-foreground border-0">Сохранить настройки</Button>
      </div>
    </div>
  );
};

export default AgentSettings;
