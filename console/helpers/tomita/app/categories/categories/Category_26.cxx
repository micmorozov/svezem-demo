#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 26 - Перевозка тракторов

/*
перевозти 2 трактора
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "трактор";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_26);