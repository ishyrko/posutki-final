import { TrendingUp, TrendingDown, Eye, MessageSquare, Home, Handshake, CheckSquare, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";
export type AgentTab = "overview" | "properties" | "inquiries" | "calendar" | "analytics" | "finance" | "profile" | "settings";

const stats = [
  { label: "Активных объектов", value: "24", change: "+3", up: true, icon: Home },
  { label: "Просмотров за неделю", value: "1 847", change: "+12%", up: true, icon: Eye },
  { label: "Обращений за неделю", value: "38", change: "+8", up: true, icon: MessageSquare },
  { label: "Сделок в месяц", value: "5", change: "-1", up: false, icon: Handshake },
];

const chartData = [
  { day: "Пн", views: 245, inquiries: 5 },
  { day: "Вт", views: 312, inquiries: 8 },
  { day: "Ср", views: 287, inquiries: 4 },
  { day: "Чт", views: 356, inquiries: 7 },
  { day: "Пт", views: 298, inquiries: 6 },
  { day: "Сб", views: 189, inquiries: 3 },
  { day: "Вс", views: 160, inquiries: 5 },
];

const recentInquiries = [
  { id: 1, name: "Александр Иванов", phone: "+375 29 123-45-67", property: "2-комн., ул. Притыцкого 34", time: "14:35", status: "new" },
  { id: 2, name: "Мария Петрова", phone: "+375 29 987-65-43", property: "3-комн., пр. Независимости 89", time: "12:10", status: "new" },
  { id: 3, name: "Дмитрий Сидоров", phone: "+375 29 555-44-33", property: "1-комн., ул. Ленина 12", time: "Вчера", status: "in_progress" },
];

const todayTasks = [
  { id: 1, time: "09:00", title: "Показ квартиры", address: "ул. Притыцкого 34", client: "А. Иванов", done: false },
  { id: 2, time: "11:00", title: "Показ квартиры", address: "пр. Независимости 89", client: "М. Петрова", done: false },
  { id: 3, time: "14:00", title: "Встреча с клиентом", address: "Офис", client: "Д. Сидоров", done: false },
  { id: 4, time: "16:00", title: "Фотосъёмка объекта", address: "ул. Немига 5", client: "—", done: true },
];

const topProperties = [
  { title: "2-комн., ул. Притыцкого 34", price: "150 000 BYN", views: 847, inquiries: 12, image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=100&h=70&fit=crop" },
  { title: "3-комн., пр. Независимости 89", price: "210 000 BYN", views: 623, inquiries: 8, image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=100&h=70&fit=crop" },
  { title: "1-комн., ул. Ленина 12", price: "85 000 BYN", views: 512, inquiries: 15, image: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=100&h=70&fit=crop" },
];

const statusColors: Record<string, string> = {
  new: "bg-green-500",
  in_progress: "bg-blue-500",
};

const AgentOverview = ({ onNavigate }: { onNavigate: (tab: AgentTab) => void }) => {
  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-display font-bold text-foreground">Добро пожаловать, Иван!</h1>
          <p className="text-sm text-muted-foreground mt-1">Пятница, 14 февраля 2026</p>
        </div>
        <Button className="bg-gradient-primary text-primary-foreground border-0">
          <Plus className="w-4 h-4 mr-2" />Добавить объект
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {stats.map((s) => (
          <div key={s.label} className="bg-card rounded-xl border border-border p-4 shadow-card">
            <div className="flex items-center justify-between mb-3">
              <div className="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
                <s.icon className="w-4 h-4 text-primary" />
              </div>
              <span className={`text-xs font-medium flex items-center gap-0.5 ${s.up ? "text-green-600" : "text-red-500"}`}>
                {s.up ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
                {s.change}
              </span>
            </div>
            <p className="text-2xl font-bold text-foreground">{s.value}</p>
            <p className="text-xs text-muted-foreground mt-0.5">{s.label}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Chart */}
        <div className="lg:col-span-2 bg-card rounded-xl border border-border p-5 shadow-card">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-foreground">Активность за неделю</h3>
            <div className="flex gap-4 text-xs text-muted-foreground">
              <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-primary" />Просмотры</span>
              <span className="flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-full bg-green-500" />Обращения</span>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={220}>
            <LineChart data={chartData}>
              <XAxis dataKey="day" axisLine={false} tickLine={false} tick={{ fontSize: 12 }} />
              <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12 }} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid hsl(var(--border))", fontSize: 13 }} />
              <Line type="monotone" dataKey="views" stroke="hsl(var(--primary))" strokeWidth={2} dot={false} />
              <Line type="monotone" dataKey="inquiries" stroke="#22c55e" strokeWidth={2} dot={false} />
            </LineChart>
          </ResponsiveContainer>
        </div>

        {/* Recent inquiries */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-foreground">Новые обращения</h3>
            <button onClick={() => onNavigate("inquiries")} className="text-xs text-primary hover:underline">Все →</button>
          </div>
          <div className="space-y-3">
            {recentInquiries.map((inq) => (
              <div key={inq.id} className="flex items-start gap-3 p-2.5 rounded-lg hover:bg-muted/50 transition-colors">
                <div className="relative">
                  <div className="w-9 h-9 rounded-full bg-muted flex items-center justify-center text-foreground font-semibold text-sm">
                    {inq.name.charAt(0)}
                  </div>
                  <span className={`absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-card ${statusColors[inq.status]}`} />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-foreground">{inq.name}</p>
                  <p className="text-xs text-muted-foreground truncate">{inq.property}</p>
                  <p className="text-xs text-muted-foreground">{inq.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {/* Today tasks */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-foreground flex items-center gap-2">
              <CheckSquare className="w-4 h-4 text-primary" />Задачи на сегодня
            </h3>
            <Button variant="ghost" size="sm" className="text-primary"><Plus className="w-3.5 h-3.5 mr-1" />Добавить</Button>
          </div>
          <div className="space-y-2">
            {todayTasks.map((task) => (
              <div key={task.id} className={`flex items-center gap-3 p-3 rounded-lg border border-border ${task.done ? "opacity-50" : ""}`}>
                <input type="checkbox" defaultChecked={task.done} className="w-4 h-4 rounded border-border accent-primary" />
                <span className="text-xs font-mono text-muted-foreground w-10">{task.time}</span>
                <div className="flex-1 min-w-0">
                  <p className={`text-sm font-medium text-foreground ${task.done ? "line-through" : ""}`}>{task.title}</p>
                  <p className="text-xs text-muted-foreground truncate">{task.address} · {task.client}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Top properties */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-foreground">Топ объектов</h3>
            <button onClick={() => onNavigate("properties")} className="text-xs text-primary hover:underline">Все объекты →</button>
          </div>
          <div className="space-y-3">
            {topProperties.map((prop, i) => (
              <div key={i} className="flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition-colors">
                <span className="text-lg font-bold text-muted-foreground/40 w-5">{i + 1}</span>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={prop.image} alt={prop.title} className="w-14 h-10 rounded-lg object-cover flex-shrink-0" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-foreground truncate">{prop.title}</p>
                  <p className="text-xs text-primary font-medium">{prop.price}</p>
                </div>
                <div className="text-right text-xs text-muted-foreground">
                  <p>👁 {prop.views}</p>
                  <p>💬 {prop.inquiries}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default AgentOverview;
