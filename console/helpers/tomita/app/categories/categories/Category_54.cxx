#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 54 - Перевозка оборудования

/*
Перевезти оборудование
*/

What -> AnyWord<kwtype="оборудование">;

Fact -> What;

S -> Fact interp(Category.Category_54);
