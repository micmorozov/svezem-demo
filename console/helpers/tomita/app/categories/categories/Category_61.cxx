#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 61 - Перевозка пиломатериала

What -> AnyWord<kwtype="дерево">;

Fact -> What;

S -> Fact interp(Category.Category_61);
