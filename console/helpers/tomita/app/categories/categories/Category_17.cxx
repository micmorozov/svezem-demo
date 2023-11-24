#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 17 - Перевозка спецтехники

/*
перевезти спец технику
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "техника" | "спецтехника";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_17);