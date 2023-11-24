#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 52 - Перевозка домов

/*
Перевезти 2|два дома
*/

Transp -> AnyWord<kwtype="перевезти">;
What -> "дом" | "сруб";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_52);
