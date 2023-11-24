#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 38 - Вывоз окно

/*
Вывезти 2|два старых окна
*/

Transp -> AnyWord<kwtype="утилизировать">;
What -> "окно";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_38);