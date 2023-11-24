#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 4 - Вывоз отходов

Transp -> AnyWord<kwtype="утилизировать">;
What -> "тбо"|"тко"|"жбо"|"отход";

Fact -> (Transp) (AnyWord+) What;

S -> Fact interp(Category.Category_4);