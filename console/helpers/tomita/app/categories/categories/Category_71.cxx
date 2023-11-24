#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 71 - Перевозка самосвалами и тонарами

Fact -> "самосвал" | "тонар";

S -> Fact interp(Category.Category_71);
