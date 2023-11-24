#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 66 - Перевозка с грузчиками

Fact -> "грузчик";

S -> Fact interp(Category.Category_66);
