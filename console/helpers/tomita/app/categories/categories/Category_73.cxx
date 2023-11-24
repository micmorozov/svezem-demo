#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 73 - Перевозка тралом

Fact -> "трал";

S -> Fact interp(Category.Category_73);
