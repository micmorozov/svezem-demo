// Категория 105 - Аренда траншеекопателя

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "траншеекопатель" | "бары" | "грунторез";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_105);