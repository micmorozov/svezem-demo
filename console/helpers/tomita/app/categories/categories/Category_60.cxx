#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 60 - Перевозка металопроката

What -> AnyWord<kwtype="металлопрокат">;

Fact -> What;

S -> Fact interp(Category.Category_60);
