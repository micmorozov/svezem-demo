#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 70 - Перевозка газелью

Fact -> "газель";

S -> Fact interp(Category.Category_70);
