#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 65 - Контейнерная перевозка

Fact -> "контейнер";

S -> Fact interp(Category.Category_65);