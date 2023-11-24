#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 55 - Перевозка плит, блоков, ЖБИ

/*
Перевезти 10 блоков
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "блок" | "жби";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_55);
