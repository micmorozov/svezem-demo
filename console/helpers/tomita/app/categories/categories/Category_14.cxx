#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 14 - Сборная перевозка

Fact -> "сборный";

S -> Fact interp(Category.Category_14);