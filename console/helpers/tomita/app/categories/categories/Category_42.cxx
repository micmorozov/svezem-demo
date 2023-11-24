#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 42 - Вывоз снега

Transp -> AnyWord<kwtype="утилизировать">;
What -> "снег";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_42);