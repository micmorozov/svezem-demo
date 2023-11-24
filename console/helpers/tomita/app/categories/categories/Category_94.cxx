#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 94 - Аренда автовышки

Transp -> AnyWord<kwtype="аренда">;
What -> "автовышка"|"автовышки";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_94);
