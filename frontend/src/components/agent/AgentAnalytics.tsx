import { useState } from "react";
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";
import { Eye, MessageSquare, Heart, Share2, Phone } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

const viewsData = Array.from({ length: 30 }, (_, i) => ({
  day: i + 1,
  views: Math.floor(120 + Math.random() * 200),
  inquiries: Math.floor(2 + Math.random() * 8),
}));

const sourceData = [
  { name: "Поиск по сайту", value: 45, color: "hsl(var(--primary))" },
  { name: "Прямые переходы", value: 30, color: "#22c55e" },
  { name: "Соц. сети", value: 15, color: "#3b82f6" },
  { name: "Google/Yandex", value: 10, color: "#a855f7" },
];

const topProperties = [
  { title: "2-комн. кв., ул. Притыцкого, 34", views: 847, inquiries: 12, favorites: 34, shares: 8, phoneShows: 45 },
  { title: "3-комн. кв., пр. Независимости, 89", views: 623, inquiries: 8, favorites: 22, shares: 5, phoneShows: 31 },
  { title: "1-комн. кв., ул. Ленина, 12", views: 512, inquiries: 15, favorites: 41, shares: 12, phoneShows: 38 },
  { title: "Дом, д. Боровляны", views: 445, inquiries: 6, favorites: 18, shares: 3, phoneShows: 22 },
  { title: "Офис, ул. Немига, 5", views: 398, inquiries: 4, favorites: 9, shares: 2, phoneShows: 15 },
];

const geoData = [
  { city: "Минск", percent: 78 },
  { city: "Гомель", percent: 8 },
  { city: "Витебск", percent: 5 },
  { city: "Брест", percent: 4 },
  { city: "Другие", percent: 5 },
];

const AgentAnalytics = () => {
  const [period, setPeriod] = useState("30");

  const totalViews = viewsData.reduce((a, b) => a + b.views, 0);
  const totalInquiries = viewsData.reduce((a, b) => a + b.inquiries, 0);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-display font-bold text-foreground">Статистика и аналитика</h1>
        <Select value={period} onValueChange={setPeriod}>
          <SelectTrigger className="w-[200px] h-10"><SelectValue /></SelectTrigger>
          <SelectContent className="bg-card border-border z-50">
            <SelectItem value="7">Последние 7 дней</SelectItem>
            <SelectItem value="30">Последние 30 дней</SelectItem>
            <SelectItem value="90">Последние 3 месяца</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Views chart */}
      <div className="bg-card rounded-xl border border-border p-5 shadow-card mb-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold text-foreground">Просмотры объектов</h3>
          <div className="text-sm text-muted-foreground">
            Всего: <span className="font-semibold text-foreground">{totalViews.toLocaleString()}</span> · Среднее: <span className="font-semibold text-foreground">{Math.round(totalViews / 30)}/день</span>
          </div>
        </div>
        <ResponsiveContainer width="100%" height={220}>
          <LineChart data={viewsData}>
            <XAxis dataKey="day" axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
            <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
            <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid hsl(var(--border))", fontSize: 13 }} />
            <Line type="monotone" dataKey="views" stroke="hsl(var(--primary))" strokeWidth={2} dot={false} name="Просмотры" />
          </LineChart>
        </ResponsiveContainer>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Inquiries chart */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <h3 className="font-semibold text-foreground mb-1">Обращения</h3>
          <p className="text-sm text-muted-foreground mb-4">Всего: {totalInquiries} · Конверсия: {(totalInquiries / totalViews * 100).toFixed(1)}%</p>
          <ResponsiveContainer width="100%" height={180}>
            <BarChart data={viewsData.slice(-14)}>
              <XAxis dataKey="day" axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
              <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid hsl(var(--border))", fontSize: 13 }} />
              <Bar dataKey="inquiries" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} name="Обращения" />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Sources */}
        <div className="bg-card rounded-xl border border-border p-5 shadow-card">
          <h3 className="font-semibold text-foreground mb-4">Источники трафика</h3>
          <div className="flex items-center gap-6">
            <ResponsiveContainer width={160} height={160}>
              <PieChart>
                <Pie data={sourceData} dataKey="value" cx="50%" cy="50%" innerRadius={40} outerRadius={70} paddingAngle={3}>
                  {sourceData.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
            <div className="space-y-2">
              {sourceData.map((s) => (
                <div key={s.name} className="flex items-center gap-2">
                  <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: s.color }} />
                  <span className="text-sm text-foreground">{s.name}</span>
                  <span className="text-sm font-semibold text-foreground ml-auto">{s.value}%</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Top properties table */}
      <div className="bg-card rounded-xl border border-border p-5 shadow-card mb-6">
        <h3 className="font-semibold text-foreground mb-4">Топ объектов по просмотрам</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-xs text-muted-foreground border-b border-border">
                <th className="text-left py-2 pr-4">#</th>
                <th className="text-left py-2 pr-4">Объект</th>
                <th className="text-right py-2 px-3"><Eye className="w-3.5 h-3.5 inline" /></th>
                <th className="text-right py-2 px-3"><MessageSquare className="w-3.5 h-3.5 inline" /></th>
                <th className="text-right py-2 px-3"><Phone className="w-3.5 h-3.5 inline" /></th>
                <th className="text-right py-2 px-3"><Heart className="w-3.5 h-3.5 inline" /></th>
                <th className="text-right py-2 px-3"><Share2 className="w-3.5 h-3.5 inline" /></th>
              </tr>
            </thead>
            <tbody>
              {topProperties.map((p, i) => (
                <tr key={i} className="border-b border-border last:border-0 hover:bg-muted/30">
                  <td className="py-3 pr-4 text-muted-foreground font-bold">{i + 1}</td>
                  <td className="py-3 pr-4 font-medium text-foreground">{p.title}</td>
                  <td className="py-3 px-3 text-right">{p.views}</td>
                  <td className="py-3 px-3 text-right">{p.inquiries}</td>
                  <td className="py-3 px-3 text-right">{p.phoneShows}</td>
                  <td className="py-3 px-3 text-right">{p.favorites}</td>
                  <td className="py-3 px-3 text-right">{p.shares}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Geo */}
      <div className="bg-card rounded-xl border border-border p-5 shadow-card">
        <h3 className="font-semibold text-foreground mb-4">География просмотров</h3>
        <div className="space-y-3">
          {geoData.map((g) => (
            <div key={g.city} className="flex items-center gap-3">
              <span className="text-sm text-foreground w-20">{g.city}</span>
              <div className="flex-1 h-2 rounded-full bg-muted overflow-hidden">
                <div className="h-full rounded-full bg-primary" style={{ width: `${g.percent}%` }} />
              </div>
              <span className="text-sm font-medium text-foreground w-10 text-right">{g.percent}%</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default AgentAnalytics;
