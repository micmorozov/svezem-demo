#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 97 - Аренда трактора

Transp -> AnyWord<kwtype="аренда">;
What -> "трактор";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_97);