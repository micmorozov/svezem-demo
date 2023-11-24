#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 69 - Перевозка растений

Fact -> AnyWord<kwtype="растение">;

S -> Fact interp(Category.Category_69);
