#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 93 - Аренда автокрана

Transp -> AnyWord<kwtype="аренда">;
What -> "автокран" | "кран";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_93);