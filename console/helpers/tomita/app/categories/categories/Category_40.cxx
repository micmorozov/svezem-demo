#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 40 - Вывоз мусора

Transp -> AnyWord<kwtype="утилизировать">;
What -> AnyWord<kwtype="мусор">;

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_40);
