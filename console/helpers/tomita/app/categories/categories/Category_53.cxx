#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 53 - Перевозка бытовок

/*
Перевезти бытовку
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "бытовка";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_53);
