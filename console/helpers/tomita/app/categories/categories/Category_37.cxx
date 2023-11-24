#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 37 - Вывоз пианино

/*
Вывезти 2|два старых пианино
*/

Transp -> AnyWord<kwtype="утилизировать">;
What -> "пианино";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_37);