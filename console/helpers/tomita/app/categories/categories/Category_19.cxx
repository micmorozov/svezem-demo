#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 19 - Бортовые перевозки
What -> "бортовой"|"коник";

Fact -> What;

S -> Fact interp(Category.Category_19);