#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 59 - Перевозка стекла и окон
Fact -> AnyWord<kwtype="стекло">;

S -> Fact interp(Category.Category_59);
