import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Phone, MessageSquare, CalendarDays, X, Send, StickyNote, ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

const inquiryTabs = [
  { key: "all", label: "Все", count: 38 },
  { key: "new", label: "Новые", count: 12 },
  { key: "in_progress", label: "В работе", count: 18 },
  { key: "done", label: "Завершённые", count: 8 },
];

const inquiries = [
  { id: 1, name: "Александр Иванов", email: "alexander@mail.ru", phone: "+375 29 123-45-67", property: "2-комн. кв., ул. Притыцкого, 34", propertyImage: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=100&h=70&fit=crop", message: "Здравствуйте, интересует эта квартира. Можно посмотреть в эти выходные?", time: "Сегодня, 14:35", status: "new", messages: [
    { from: "client", text: "Здравствуйте, интересует эта квартира. Можно посмотреть в эти выходные?", time: "14:35" },
  ] },
  { id: 2, name: "Мария Петрова", email: "maria.p@gmail.com", phone: "+375 29 987-65-43", property: "3-комн. кв., пр. Независимости, 89", propertyImage: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=100&h=70&fit=crop", message: "Добрый день! Скажите, какой этаж? И есть ли парковка?", time: "Сегодня, 12:10", status: "new", messages: [
    { from: "client", text: "Добрый день! Скажите, какой этаж? И есть ли парковка?", time: "12:10" },
    { from: "agent", text: "Здравствуйте! 7 этаж из 16, есть подземный паркинг. Стоимость машиноместа обсуждается отдельно.", time: "12:25" },
    { from: "client", text: "Отлично, спасибо! Можно организовать показ?", time: "12:30" },
  ] },
  { id: 3, name: "Дмитрий Сидоров", email: "d.sidorov@yandex.by", phone: "+375 29 555-44-33", property: "1-комн. кв., ул. Ленина, 12", propertyImage: "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=100&h=70&fit=crop", message: "Можно посмотреть в субботу?", time: "12.02", status: "in_progress", messages: [
    { from: "client", text: "Можно посмотреть в субботу?", time: "10:00" },
    { from: "agent", text: "Да, конечно! Предлагаю в 14:00. Подходит?", time: "10:15" },
    { from: "client", text: "Идеально, буду!", time: "10:20" },
  ] },
  { id: 4, name: "Елена Козлова", email: "elena.k@tut.by", phone: "+375 33 222-11-00", property: "Дом, д. Боровляны", propertyImage: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=100&h=70&fit=crop", message: "Спасибо за показ! Мы с мужем решили — берём!", time: "10.02", status: "done", messages: [] },
];

const statusOptions = [
  { value: "new", label: "Новое" },
  { value: "in_progress", label: "В работе" },
  { value: "showing", label: "Показ назначен" },
  { value: "not_interested", label: "Не интересует" },
  { value: "deal", label: "Сделка" },
  { value: "done", label: "Завершено" },
];

const AgentInquiries = () => {
  const [tab, setTab] = useState("all");
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [replyText, setReplyText] = useState("");
  const [noteText, setNoteText] = useState("Клиент ищет квартиру для семьи, важна близость к школе. Бюджет до 160k.");

  const filtered = tab === "all" ? inquiries : inquiries.filter((i) => i.status === tab);
  const selected = inquiries.find((i) => i.id === selectedId);

  return (
    <div>
      <h1 className="text-2xl font-display font-bold text-foreground mb-6">Обращения и клиенты</h1>

      {/* Tabs */}
      <div className="flex gap-1 mb-4 overflow-x-auto">
        {inquiryTabs.map((t) => (
          <button
            key={t.key}
            onClick={() => { setTab(t.key); setSelectedId(null); }}
            className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
              tab === t.key ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground hover:text-foreground"
            }`}
          >
            {t.label} ({t.count})
          </button>
        ))}
      </div>

      {/* List */}
      <div className="space-y-3">
        {filtered.map((inq) => (
          <div
            key={inq.id}
            onClick={() => setSelectedId(inq.id)}
            className={`bg-card rounded-xl border border-border p-4 shadow-card cursor-pointer transition-colors hover:border-primary/30 ${selectedId === inq.id ? "border-primary/50 ring-1 ring-primary/20" : ""}`}
          >
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold flex-shrink-0">
                {inq.name.charAt(0)}
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between gap-2">
                  <h4 className="font-medium text-foreground">{inq.name}</h4>
                  <span className="text-xs text-muted-foreground whitespace-nowrap">{inq.time}</span>
                </div>
                <p className="text-xs text-muted-foreground">{inq.phone}</p>
                <div className="flex items-center gap-2 mt-2">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={inq.propertyImage} alt="" className="w-10 h-7 rounded object-cover" />
                  <span className="text-xs text-muted-foreground truncate">{inq.property}</span>
                </div>
                <p className="text-sm text-foreground/80 mt-2 line-clamp-1">💬 &quot;{inq.message}&quot;</p>
                <div className="flex items-center gap-2 mt-3">
                  <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
                    inq.status === "new" ? "bg-green-500/15 text-green-600" :
                    inq.status === "in_progress" ? "bg-blue-500/15 text-blue-600" :
                    "bg-gray-500/15 text-gray-600"
                  }`}>
                    {statusOptions.find((s) => s.value === inq.status)?.label ?? inq.status}
                  </span>
                  <div className="ml-auto flex gap-1">
                    <Button variant="ghost" size="sm" className="h-7 text-xs"><Phone className="w-3 h-3 mr-1" />Позвонить</Button>
                    <Button variant="ghost" size="sm" className="h-7 text-xs"><MessageSquare className="w-3 h-3 mr-1" />Ответить</Button>
                    <Button variant="ghost" size="sm" className="h-7 text-xs"><CalendarDays className="w-3 h-3 mr-1" />Показ</Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Detail modal */}
      <AnimatePresence>
        {selected && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 bg-foreground/40 flex items-center justify-center p-4"
            onClick={() => setSelectedId(null)}
          >
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              onClick={(e) => e.stopPropagation()}
              className="bg-card rounded-2xl border border-border shadow-card-hover w-full max-w-2xl max-h-[85vh] overflow-y-auto"
            >
              {/* Header */}
              <div className="flex items-center justify-between p-5 border-b border-border">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                    {selected.name.charAt(0)}
                  </div>
                  <div>
                    <h3 className="font-semibold text-foreground">{selected.name}</h3>
                    <p className="text-xs text-muted-foreground">{selected.phone} · {selected.email}</p>
                  </div>
                </div>
                <button onClick={() => setSelectedId(null)} className="p-2 rounded-lg hover:bg-muted text-muted-foreground"><X className="w-4 h-4" /></button>
              </div>

              {/* Property */}
              <div className="p-5 border-b border-border">
                <p className="text-xs text-muted-foreground mb-2">Объект:</p>
                <div className="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={selected.propertyImage} alt="" className="w-16 h-12 rounded-lg object-cover" />
                  <div className="flex-1">
                    <p className="text-sm font-medium text-foreground">{selected.property}</p>
                  </div>
                  <Button variant="ghost" size="sm" className="text-xs"><ExternalLink className="w-3 h-3 mr-1" />Открыть</Button>
                </div>
              </div>

              {/* Chat */}
              <div className="p-5 border-b border-border">
                <p className="text-xs text-muted-foreground mb-3">История общения:</p>
                <div className="space-y-3 mb-4">
                  {selected.messages.map((msg, i) => (
                    <div key={i} className={`flex ${msg.from === "agent" ? "justify-end" : "justify-start"}`}>
                      <div className={`max-w-[80%] px-4 py-2.5 rounded-2xl text-sm ${
                        msg.from === "agent"
                          ? "bg-primary text-primary-foreground rounded-br-md"
                          : "bg-muted text-foreground rounded-bl-md"
                      }`}>
                        <p>{msg.text}</p>
                        <p className={`text-xs mt-1 ${msg.from === "agent" ? "text-primary-foreground/60" : "text-muted-foreground"}`}>{msg.time}</p>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="flex gap-2">
                  <Input placeholder="Ваш ответ..." value={replyText} onChange={(e) => setReplyText(e.target.value)} className="flex-1" />
                  <Button size="icon" className="bg-gradient-primary text-primary-foreground border-0 flex-shrink-0"><Send className="w-4 h-4" /></Button>
                </div>
              </div>

              {/* Actions & Notes */}
              <div className="p-5">
                <div className="flex flex-wrap gap-2 mb-4">
                  <Button variant="outline" size="sm"><CalendarDays className="w-3.5 h-3.5 mr-1" />Назначить показ</Button>
                  <Button variant="outline" size="sm"><StickyNote className="w-3.5 h-3.5 mr-1" />Добавить заметку</Button>
                  <Select defaultValue={selected.status}>
                    <SelectTrigger className="w-[160px] h-9"><SelectValue /></SelectTrigger>
                    <SelectContent className="bg-card border-border z-[60]">
                      {statusOptions.map((s) => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-1.5 flex items-center gap-1"><StickyNote className="w-3 h-3" />Заметки (только для вас):</p>
                  <Textarea value={noteText} onChange={(e) => setNoteText(e.target.value)} rows={2} className="text-sm" />
                </div>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default AgentInquiries;
