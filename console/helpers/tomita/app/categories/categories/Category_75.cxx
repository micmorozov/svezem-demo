#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 75 - Перевозка камазом

Fact -> "камаз";

S -> Fact interp(Category.Category_75);
