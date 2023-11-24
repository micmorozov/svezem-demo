#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 68 - Перевозка бытовой техники

What -> AnyWord<kwtype="бытовая_техника">;

Fact -> What;

S -> Fact interp(Category.Category_68);
