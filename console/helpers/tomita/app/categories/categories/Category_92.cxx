#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 92 - Аренда спецтехники

Transp -> AnyWord<kwtype="аренда">;
What -> "спецтехника";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_92);