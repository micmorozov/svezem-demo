#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 44 - Вывоз септика

Transp -> AnyWord<kwtype="утилизировать">;
What -> "септик";

Fact -> (Transp) (AnyWord+) What;

S -> Fact interp(Category.Category_44);
